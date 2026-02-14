<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

header('Content-Type: application/json');

$period = $_GET['period'] ?? 'lifetime';
$startDate = $_GET['start'] ?? null;
$endDate = $_GET['end'] ?? null;

$response = [
    'labels' => [],
    'datasets' => []
];

try {
    // Check if payment_date column exists
    $colCheck = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'payment_date'");
    $hasPaymentDate = $colCheck->rowCount() > 0;

    // Use payment_date if available (for precise revenue timing), otherwise created_at
    $dateCol = $hasPaymentDate ? "COALESCE(payment_date, created_at)" : "created_at";

    // Determine Date Range and Grouping
    $dateFormat = '';
    $groupBy = '';
    $whereClause = "WHERE status = 'Paid'";
    $params = [];

    // Helper to get all dates in range
    $allDates = [];

    switch ($period) {
        case 'monthly':
            // Show daily breakdown for this month
            // Use CURRENT_DATE() from SQL
            $whereClause .= " AND YEAR($dateCol) = YEAR(CURRENT_DATE()) AND MONTH($dateCol) = MONTH(CURRENT_DATE())";
            $dateFormat = '%d %b'; // 01 Jan
            $groupBy = "DATE($dateCol)";

            // Generate all days for this month
            $numDays = date('t');
            for ($i = 1; $i <= $numDays; $i++) {
                $allDates[date('d M', mktime(0, 0, 0, date('m'), $i))] = 0; // Key format matches SQL output
            }
            break;

        case 'yearly':
            // Show monthly breakdown for this year
            $whereClause .= " AND YEAR($dateCol) = YEAR(CURRENT_DATE())";
            $dateFormat = '%b'; // Jan
            $groupBy = "MONTH($dateCol)";

            // Generate all months
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            foreach ($months as $m)
                $allDates[$m] = 0;
            break;

        case 'lifetime':
        default:
            $dateFormat = '%b %Y'; // Jan 2023
            $groupBy = "DATE_FORMAT($dateCol, '%Y-%m')";
            break;
    }

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

    // Identify all unique currencies involved
    $currencies = [];
    foreach ($rawData as $row) {
        if (!in_array($row['currency'], $currencies)) {
            $currencies[] = $row['currency'];
        }
    }

    // Organize data by Currency -> Label -> Value
    $organized = [];
    $labels = array_keys($allDates); // Start with pre-filled dates if any

    if ($period == 'lifetime') {
        $labels = [];
        foreach ($rawData as $row) {
            $lbl = $row['time_label'];
            if (!in_array($lbl, $labels)) {
                $labels[] = $lbl;
            }
        }
    }

    // Initialize datasets structure
    foreach ($currencies as $curr) {
        $organized[$curr] = array_fill_keys($labels, 0); // Fill with 0
    }

    // Fill real values
    foreach ($rawData as $row) {
        $lbl = $row['time_label'];
        $curr = $row['currency'];
        $val = (float) $row['total'];

        // Handle case where label might not exist in pre-filled array (rare but possible with format mismatch)
        if (isset($organized[$curr][$lbl])) {
            $organized[$curr][$lbl] = $val;
        } else if ($period == 'lifetime') {
            // Should be handled by label building above
            $organized[$curr][$lbl] = $val;
        }
    }

    // Colors for graph
    $colors = [
        'USD' => ['border' => '#22c55e', 'bg' => 'rgba(34, 197, 94, 0.1)'],
        'INR' => ['border' => '#f59e0b', 'bg' => 'rgba(245, 158, 11, 0.1)'],
        'EUR' => ['border' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.1)'],
        'GBP' => ['border' => '#8b5cf6', 'bg' => 'rgba(139, 92, 246, 0.1)'],
    ];

    $datasets = [];
    foreach ($currencies as $curr) {
        $color = $colors[$curr] ?? ['border' => '#64748b', 'bg' => 'rgba(100, 116, 139, 0.1)'];

        $datasets[] = [
            'label' => $curr,
            'data' => array_values($organized[$curr]),
            'borderColor' => $color['border'],
            'backgroundColor' => $color['bg'],
            'borderWidth' => 2,
            'tension' => 0.4,
            'fill' => true,
            'pointRadius' => 3,
            'pointHoverRadius' => 6
        ];
    }

    $response['labels'] = $labels;
    $response['datasets'] = $datasets;

} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>