<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

// Get dashboard statistics
try {
    // Total clients
    $totalClientsStmt = $pdo->query("SELECT COUNT(*) as count FROM clients");
    $totalClients = $totalClientsStmt->fetch()['count'];

    // Active projects
    $activeProjectsStmt = $pdo->query("SELECT COUNT(*) as count FROM projects WHERE status IN ('Idea', 'In Progress', 'Review')");
    $activeProjects = $activeProjectsStmt->fetch()['count'];

    // Total projects
    $totalProjectsStmt = $pdo->query("SELECT COUNT(*) as count FROM projects");
    $totalProjects = $totalProjectsStmt->fetch()['count'];

    // Completed projects
    $completedStmt = $pdo->query("SELECT COUNT(*) as count FROM projects WHERE status = 'Done'");
    $completedProjects = $completedStmt->fetch()['count'];

    // New clients this month
    $newClientsStmt = $pdo->query("
        SELECT COUNT(*) as count FROM clients 
        WHERE YEAR(created_at) = YEAR(CURRENT_DATE()) 
        AND MONTH(created_at) = MONTH(CURRENT_DATE())
    ");
    $newClientsThisMonth = $newClientsStmt->fetch()['count'];

    // Total revenue by currency
    $totalRevenueStmt = $pdo->query("
        SELECT i.currency, curr.symbol, SUM(i.amount) as total_amount
        FROM invoices i
        LEFT JOIN currencies curr ON i.currency = curr.code
        WHERE i.status = 'Paid'
        GROUP BY i.currency, curr.symbol
        ORDER BY total_amount DESC
    ");
    $totalRevenue = $totalRevenueStmt->fetchAll();

    // Earnings this month by currency
    $monthlyEarningsStmt = $pdo->query("
        SELECT i.currency, curr.symbol, SUM(i.amount) as monthly_amount
        FROM invoices i
        LEFT JOIN currencies curr ON i.currency = curr.code
        WHERE i.status = 'Paid' 
        AND YEAR(i.created_at) = YEAR(CURRENT_DATE()) 
        AND MONTH(i.created_at) = MONTH(CURRENT_DATE())
        GROUP BY i.currency, curr.symbol
        ORDER BY monthly_amount DESC
    ");
    $monthlyEarnings = $monthlyEarningsStmt->fetchAll();

    // Upcoming deadlines (next 7 days)
    $upcomingStmt = $pdo->query("
        SELECT p.title, p.end_date, c.name as client_name, p.status
        FROM projects p 
        JOIN clients c ON p.client_id = c.id 
        WHERE p.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND p.status != 'Done'
        ORDER BY p.end_date ASC
        LIMIT 5
    ");
    $upcomingDeadlines = $upcomingStmt->fetchAll();

    // Recent clients (last 10)
    $recentClientsStmt = $pdo->query("
        SELECT name, brand_name, email, country, created_at
        FROM clients
        ORDER BY created_at DESC
        LIMIT 8
    ");
    $recentClients = $recentClientsStmt->fetchAll();

} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Helper function to format currency display
function formatCurrencyDisplay($revenues, $limit = 2)
{
    if (empty($revenues)) {
        return '$0.00';
    }

    $output = '';
    $count = 0;
    foreach ($revenues as $revenue) {
        if ($count >= $limit)
            break;
        $symbol = $revenue['symbol'] ?? '$';
        $amount = number_format($revenue['total_amount'] ?? $revenue['monthly_amount'], 2);
        $output .= '<div>' . $symbol . $amount . '</div>';
        $count++;
    }

    if (count($revenues) > $limit) {
        $remaining = count($revenues) - $limit;
        $output .= '<div style="font-size: 0.8rem; color: #64748b;">+' . $remaining . ' more</div>';
    }

    return $output;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sachindesign Dashboard</title>
    <link rel="icon" type="image/png" href="https://sachindesign.com/assets/img/Sachin's%20photo.png">
    <link href="assets/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <div class="container">

        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Header Section -->
            <header class="header fade-in">
                <div>
                    <h1>Hi, Sachin 👋</h1>
                    <p>Here's what's happening with your business today</p>
                </div>
                <div class="user-info">
                    <div>
                        <div style="font-weight: 600; color: #1e293b;">Sachin Mishra</div>
                        <div style="color: #64748b; font-size: 0.875rem;">hi@sachindesign.com</div>
                    </div>
                </div>
            </header>

            <!-- Statistics Cards - Single Row -->
            <div class="stats-grid-6 fade-in">
                <!-- Total Clients -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Clients</div>
                            <div class="stat-value"><?php echo $totalClients; ?></div>
                        </div>
                    </div>
                    <div class="stat-change positive">
                        <span>All clients</span>
                    </div>
                </div>

                <!-- Active Projects -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Active Projects</div>
                            <div class="stat-value"><?php echo $activeProjects; ?></div>
                        </div>
                    </div>
                    <div class="stat-change positive">
                        <span>In progress</span>
                    </div>
                </div>

                <!-- Total Revenue -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Revenue</div>
                            <div class="stat-value" style="font-size: 1.3rem;">
                                <?php echo formatCurrencyDisplay($totalRevenue, 2); ?>
                            </div>
                        </div>
                    </div>
                    <div class="stat-change positive">
                        <span>All time</span>
                    </div>
                </div>

                <!-- Completed Projects -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Completed</div>
                            <div class="stat-value"><?php echo $completedProjects; ?></div>
                        </div>
                    </div>
                    <div class="stat-change positive">
                        <span>Projects done</span>
                    </div>
                </div>

                <!-- New Clients This Month -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">New Clients</div>
                            <div class="stat-value"><?php echo $newClientsThisMonth; ?></div>
                        </div>
                    </div>
                    <div class="stat-change positive">
                        <span>This month</span>
                    </div>
                </div>

                <!-- Monthly Earnings -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Monthly Earnings</div>
                            <div class="stat-value" style="font-size: 1.3rem;">
                                <?php echo formatCurrencyDisplay($monthlyEarnings, 2); ?>
                            </div>
                        </div>
                    </div>
                    <div class="stat-change positive">
                        <span>This month</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions fade-in">
                <a href="add-client.php" class="btn btn-primary">
                    <span>New Client</span>
                </a>
                <a href="add-project.php" class="btn btn-primary">
                    <span>New Project</span>
                </a>
                <a href="invoices.php" class="btn btn-secondary">
                    <span>Create Invoice</span>
                </a>
            </div>

            <!-- Three Column Layout for Tables -->
            <div class="dashboard-grid fade-in">
                <!-- Upcoming Deadlines -->
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h3>Upcoming Deadlines</h3>
                        <span class="card-subtitle">Next 7 Days</span>
                    </div>
                    <div class="dashboard-card-content">
                        <?php if (empty($upcomingDeadlines)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">🎉</div>
                                <div class="empty-text">No upcoming deadlines!</div>
                                <div class="empty-subtext">You're all caught up</div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($upcomingDeadlines as $deadline): ?>
                                <div class="deadline-item">
                                    <div class="deadline-info">
                                        <div class="deadline-title"><?php echo htmlspecialchars($deadline['title']); ?></div>
                                        <div class="deadline-client"><?php echo htmlspecialchars($deadline['client_name']); ?>
                                        </div>
                                    </div>
                                    <div class="deadline-date">
                                        <div class="date-text"><?php echo date('M j', strtotime($deadline['end_date'])); ?>
                                        </div>
                                        <span
                                            class="status-mini <?php echo strtolower(str_replace(' ', '-', $deadline['status'])); ?>">
                                            <?php echo $deadline['status']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>


                <!-- Recent Clients -->
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h3>Recent Clients</h3>
                        <span class="card-subtitle">New additions</span>
                    </div>
                    <div class="dashboard-card-content">
                        <?php if (empty($recentClients)): ?>
                            <div class="empty-state">
                                <div class="empty-text">No clients yet!</div>
                                <div class="empty-subtext">Add your first client</div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentClients as $client): ?>
                                <div class="client-item">
                                    <div class="client-avatar">
                                        <?php echo strtoupper(substr($client['name'], 0, 2)); ?>
                                    </div>
                                    <div class="client-info">
                                        <div class="client-name"><?php echo htmlspecialchars($client['name']); ?></div>
                                        <div class="client-details">
                                            <?php if ($client['brand_name']): ?>
                                                <?php echo htmlspecialchars($client['brand_name']); ?>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($client['email']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="client-country">
                                        <?php
                                        $countryFlags = [
                                            'India' => '🇮🇳',
                                            'United States' => '🇺🇸',
                                            'United Kingdom' => '🇬🇧',
                                            'Canada' => '🇨🇦',
                                            'Australia' => '🇦🇺',
                                            'Germany' => '🇩🇪',
                                            'France' => '🇫🇷',
                                            'Singapore' => '🇸🇬',
                                            'UAE' => '🇦🇪'
                                        ];
                                        echo $countryFlags[$client['country']] ?? '🌍';
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
        .stat-card {
            background: linear-gradient(135deg, #9a9a9a00 0%, #00000008 100%);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: none;
        }

        .stat-title {
            color: #000000;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }

        .stat-change.positive {
            color: #a1a1a1;
            font-size: 12px;
        }

        .stat-card::before {
            background: linear-gradient(135deg, #9a9a9a 0%, #000000 100%);
        }

        .user-avatar {
            background: linear-gradient(135deg, #9a9a9a 0%, #000000 100%);
        }

        .table th,
        .table td {
            padding: 17px;
        }

        .main-content {
            background: #fafafa !important;
        }

        /* 6-Card Grid */
        .stats-grid-6 {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        /* Dashboard Grid for 3 columns */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-top: 2rem;
        }

        /* Dashboard Cards */
        .dashboard-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .dashboard-card-header {
            padding: 1.5rem 1.5rem 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .dashboard-card-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .card-subtitle {
            font-size: 0.875rem;
            color: #64748b;
        }

        .dashboard-card-content {
            padding: 0;
            max-height: 400px;
            overflow-y: auto;
        }

        /* Extra Responsive Fixes */
        @media (max-width: 1400px) {
            .stats-grid-6 {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 1024px) {
            .stats-grid-6 {
                grid-template-columns: repeat(2, 1fr);
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-grid-6 {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        // Add fade-in animation on page load
        document.addEventListener('DOMContentLoaded', function () {
            const elements = document.querySelectorAll('.fade-in');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>

</html>