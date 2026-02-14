<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

header('Content-Type: application/json');

/*
 * Supported filter types:
 * - last_7_days
 * - last_28_days
 * - last_90_days
 * - last_365_days
 * - lifetime
 * - year (val = 2026, 2025 etc)
 * - month (val = 2025-02, 2025-01 etc)
 * - custom (start & end)
 */

$period = $_GET['period'] ?? 'last_28_days';
$val = $_GET['val'] ?? null; // For year/month specific value
$startDate = $_GET['start'] ?? null;
$endDate = $_GET['end'] ?? null;

$response = [
    'labels' => [],
    'datasets' => [],
    'totals' => [] // Returns totals per currency for "Estimated Revenue" display
];

try {
    // Check if payment_date column exists
    $colCheck = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'payment_date'");
    $hasPaymentDate = $colCheck->rowCount() > 0;
    $dateCol = $hasPaymentDate ? "COALESCE(payment_date, created_at)" : "created_at";

    // Default config
    $dateFormat = '';
    $groupBy = '';
    $whereClause = "WHERE status = 'Paid'";
    $params = [];
    $allDates = []; // For pre-filling labels to ensure continuous axis

    // Logic Switch
    switch ($period) {
        case 'last_7_days':
            $whereClause .= " AND $dateCol >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
            $dateFormat = '%d %b'; // 01 Jan
            $groupBy = "DATE($dateCol)";
            // Fill last 7 days including today
            for ($i = 6; $i >= 0; $i--) {
                $allDates[date('d M', strtotime("-$i days"))] = 0;
            }
            break;

        case 'last_28_days':
            $whereClause .= " AND $dateCol >= DATE_SUB(CURDATE(), INTERVAL 27 DAY)";
            $dateFormat = '%d %b';
            $groupBy = "DATE($dateCol)";
            for ($i = 27; $i >= 0; $i--) {
                $allDates[date('d M', strtotime("-$i days"))] = 0;
            }
            break;

        case 'last_90_days':
            $whereClause .= " AND $dateCol >= DATE_SUB(CURDATE(), INTERVAL 89 DAY)";
            $dateFormat = '%d %b';
            $groupBy = "DATE($dateCol)";
            // Filling 90 days might be too much for PHP loop, let's rely on DB data mostly or fill gaps in JS? 
            // Let's fill it, 90 iterations is fast.
            for ($i = 89; $i >= 0; $i--) {
                $allDates[date('d M', strtotime("-$i days"))] = 0;
            }
            break;

        case 'last_365_days':
            $whereClause .= " AND $dateCol >= DATE_SUB(CURDATE(), INTERVAL 364 DAY)";
            $dateFormat = '%b %Y'; // Jan 2024
            $groupBy = "DATE_FORMAT($dateCol, '%Y-%m')";
            // Monthly grouping for 365 days
            for ($i = 11; $i >= 0; $i--) {
                $allDates[date('M Y', strtotime("-$i months"))] = 0;
            }
            break;

        case 'year':
            // Specific year e.g. 2025
            $year = $val ? intval($val) : date('Y');
            $whereClause .= " AND YEAR($dateCol) = ?";
            $params[] = $year;
            $dateFormat = '%b'; // Jan
            $groupBy = "MONTH($dateCol)";
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            foreach ($months as $m)
                $allDates[$m] = 0;
            break;

        case 'month':
            // Specific month e.g. 2025-02
            // Expected val format: YYYY-MM
            if (!$val)
                $val = date('Y-m');
            list($y, $m) = explode('-', $val);
            $whereClause .= " AND YEAR($dateCol) = ? AND MONTH($dateCol) = ?";
            $params[] = $y;
            $params[] = $m;
            $dateFormat = '%d %b';
            $groupBy = "DATE($dateCol)";
            // Fill days in that month
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int) $m, (int) $y);
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $allDates[date('d M', mktime(0, 0, 0, (int) $m, $i, (int) $y))] = 0;
            }
            break;

        case 'custom':
            if ($startDate && $endDate) {
                $whereClause .= " AND DATE($dateCol) BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;

                // Determine granularity based on range duration
                $start = new DateTime($startDate);
                $end = new DateTime($endDate);
                $diff = $start->diff($end)->days;

                if ($diff > 60) {
                    // Monthly
                    $dateFormat = '%b %Y';
                    $groupBy = "DATE_FORMAT($dateCol, '%Y-%m')";
                } else {
                    // Daily
                    $dateFormat = '%d %b';
                    $groupBy = "DATE($dateCol)";
                }
            }
            break;

        case 'lifetime':
        default:
            // Group by Month-Year for long term
            $dateFormat = '%b %Y';
            $groupBy = "DATE_FORMAT($dateCol, '%Y-%m')";
            break;
    }

    // --- Query Data ---
    $sql = "
        SELECT 
            DATE_FORMAT($dateCol, '$dateFormat') as time_label,
            currency,
            SUM(amount) as total
        FROM invoices
        $whereClause
        GROUP BY $groupBy, currency
        ORDER BY $dateCol ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Process Totals ---
    // Calculate total revenue for this period per currency
    $periodTotals = [];
    $totalCurrencies = []; // Track unique currencies

    foreach ($rawData as $row) {
        $c = $row['currency'];
        $v = (float) $row['total'];
        if (!isset($periodTotals[$c]))
            $periodTotals[$c] = 0;
        $periodTotals[$c] += $v;

        if (!in_array($c, $totalCurrencies))
            $totalCurrencies[] = $c;
    }

    // Get Symbols for currencies
    $symbols = [];
    if (!empty($totalCurrencies)) {
        $ph = implode(',', array_fill(0, count($totalCurrencies), '?'));
        $cStmt = $pdo->prepare("SELECT code, symbol FROM currencies WHERE code IN ($ph)");
        $cStmt->execute($totalCurrencies);
        $currRows = $cStmt->fetchAll(PDO::FETCH_KEY_PAIR); // code => symbol
        $symbols = $currRows;
    }

    // Format totals for response
    foreach ($periodTotals as $code => $amt) {
        $sym = $symbols[$code] ?? $code;
        $response['totals'][] = [
            'currency' => $code,
            'symbol' => $sym,
            'amount' => $amt,
            'formatted' => $sym . number_format($amt, 2)
        ];
    }

    // --- Process Datasets ---
    $currencies = $totalCurrencies;
    $organized = [];
    $labels = !empty($allDates) ? array_keys($allDates) : [];

    // If no pre-filled dates (lifetime or custom), build labels from data
    if (empty($labels)) {
        foreach ($rawData as $row) {
            $lbl = $row['time_label'];
            if (!in_array($lbl, $labels))
                $labels[] = $lbl;
        }
    }

    // Initialize datasets
    foreach ($currencies as $curr) {
        $organized[$curr] = array_fill_keys($labels, 0);
    }

    // Fill data
    foreach ($rawData as $row) {
        $lbl = $row['time_label'];
        $curr = $row['currency'];
        $val = (float) $row['total'];

        // Only set if label exists or add?
        // If we pre-filled, we only set existing. If logic gap, ignore? 
        // Best to set if exists.
        if (isset($organized[$curr][$lbl])) {
            $organized[$curr][$lbl] = $val;
        } else if (empty($allDates)) {
            // For lifetime/custom, we built labels from data, so it should exist or we missed sort order
            // Just incase
            $organized[$curr][$lbl] = $val;
        }
    }

    // Colors
    $colors = [
        'USD' => ['border' => '#22c55e', 'bg' => 'rgba(34, 197, 94, 0.1)'],
        'INR' => ['border' => '#f59e0b', 'bg' => 'rgba(245, 158, 11, 0.1)'],
        'EUR' => ['border' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.1)'],
        'GBP' => ['border' => '#8b5cf6', 'bg' => 'rgba(139, 92, 246, 0.1)'],
    ];

    foreach ($currencies as $curr) {
        $color = $colors[$curr] ?? ['border' => '#64748b', 'bg' => 'rgba(100, 116, 139, 0.1)'];
        $response['datasets'][] = [
            'label' => $curr,
            'data' => array_values($organized[$curr]),
            'borderColor' => $color['border'],
            'backgroundColor' => $color['bg'],
            'borderWidth' => 2,
            'tension' => 0.4,
            'fill' => true,
            'pointRadius' => count($labels) > 60 ? 0 : 3, // Hide points if too many
            'pointHoverRadius' => 6
        ];
    }

    $response['labels'] = $labels;

} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>