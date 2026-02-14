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
    // 1. Determine Date Range and Grouping
    $dateFormat = '';
    $groupBy = '';
    $whereClause = "WHERE status = 'Paid'";
    $params = [];

    // Helper to get all dates in range
    $allDates = [];

    switch ($period) {
        case 'monthly':
            // Show daily breakdown for this month
            $whereClause .= " AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND MONTH(created_at) = MONTH(CURRENT_DATE())";
            $dateFormat = '%d %b'; // 01 Jan
            $groupBy = "DATE(created_at)";

            // Generate all days for this month
            $numDays = date('t');
            for ($i = 1; $i <= $numDays; $i++) {
                $allDates[date('d M', mktime(0, 0, 0, date('m'), $i))] = 0; // Key format matches SQL output
            }
            break;

        case 'yearly':
            // Show monthly breakdown for this year
            $whereClause .= " AND YEAR(created_at) = YEAR(CURRENT_DATE())";
            $dateFormat = '%b'; // Jan
            $groupBy = "MONTH(created_at)";

            // Generate all months
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            foreach ($months as $m)
                $allDates[$m] = 0;
            break;

        case 'lifetime':
        default:
            // Show monthly breakdown for all time? Or Yearly? 
            // YouTube lifetime usually shows a long trend. Let's do Year-Month.
            $dateFormat = '%b %Y'; // Jan 2023
            $groupBy = "DATE_FORMAT(created_at, '%Y-%m')";
            // For lifetime, we don't pre-fill zeros because the range is dynamic
            break;
    }

    if ($startDate && $endDate) {
        // Custom range logic could be added here
    }

    // 2. Query Data Grouped by Currency and Date
    // We need to fetch data for all currencies separately or grouped
    $sql = "
        SELECT 
            DATE_FORMAT(created_at, '$dateFormat') as time_label,
            currency,
            SUM(amount) as total
        FROM invoices
        $whereClause
        GROUP BY $groupBy, currency
        ORDER BY created_at ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_GROUP); // Group by first column (time_label) invalid here as we have mulitple rows per label.
    // fetchAll returns list.

    $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Process Data for Chart.js

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

    // If lifetime, we build labels dynamically
    if ($period == 'lifetime') {
        $labels = [];
        foreach ($rawData as $row) {
            if (!in_array($row['time_label'], $labels)) {
                $labels[] = $row['time_label'];
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

        if (isset($organized[$curr][$lbl])) {
            $organized[$curr][$lbl] = $val;
        }
    }

    // Colors for graph
    $colors = [
        'USD' => ['border' => '#22c55e', 'bg' => 'rgba(34, 197, 94, 0.1)'],
        'INR' => ['border' => '#f59e0b', 'bg' => 'rgba(245, 158, 11, 0.1)'],
        'EUR' => ['border' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.1)'],
    ];

    $datasets = [];
    foreach ($currencies as $curr) {
        $color = $colors[$curr] ?? ['border' => '#64748b', 'bg' => 'rgba(100, 116, 139, 0.1)'];

        $datasets[] = [
            'label' => $curr,
            'data' => array_values($organized[$curr]), // Ensure matched by index with labels
            'borderColor' => $color['border'],
            'backgroundColor' => $color['bg'],
            'borderWidth' => 2,
            'tension' => 0.4,
            'fill' => true,
            'pointRadius' => 0,
            'pointHoverRadius' => 4
        ];
    }

    $response['labels'] = $labels;
    $response['datasets'] = $datasets;

} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>