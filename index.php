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
        return '<div class="amount-wrapper masked">' .
               '<div class="amount-actual"><div>$0.00</div></div>' .
               '<div class="amount-masked">****</div>' .
               '</div>';
    }

    $output = '<div class="amount-wrapper masked">';
    $visible_part = '';
    
    $count = 0;
    foreach ($revenues as $revenue) {
        if ($count >= $limit)
            break;
        $symbol = $revenue['symbol'] ?? '$';
        $amount = number_format($revenue['total_amount'] ?? $revenue['monthly_amount'], 2);
        $visible_part .= '<div>' . $symbol . $amount . '</div>';
        $count++;
    }

    if (count($revenues) > $limit) {
        $remaining = count($revenues) - $limit;
        $visible_part .= '<div style="font-size: 0.8rem; color: #64748b;">+' . $remaining . ' more</div>';
    }
    
    $output .= '<div class="amount-actual">' . $visible_part . '</div>';
    $output .= '<div class="amount-masked">****</div>';
    $output .= '</div>';

    return $output;
}
?>

<?php
$page_title = 'Sachindesign Dashboard';
include 'includes/header.php';
?>

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
            <button class="toggle-visibility" onclick="togglePrivacy(this)">
                <i class="fa-regular fa-eye-slash"></i>
            </button>
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
            <button class="toggle-visibility" onclick="togglePrivacy(this)">
                <i class="fa-regular fa-eye-slash"></i>
            </button>
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

<!-- Dashboard Layout -->
<div class="dashboard-grid fade-in">
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

    /* Dashboard Layout */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr;
        max-width: 600px;
        margin-top: 2rem;
    }

    .dashboard-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .dashboard-card-header {
        padding: 1.5rem 1.5rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        background: white;
    }

    .dashboard-card-header h3 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }

    .card-subtitle {
        font-size: 0.875rem;
        color: #64748b;
    }

    .dashboard-card-content {
        max-height: 500px;
        overflow-y: auto;
    }

    /* Client Item Styling - Custom and Premium */
    .client-item {
        display: flex;
        align-items: center;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.2s ease;
    }

    .client-item:last-child {
        border-bottom: none;
    }

    .client-item:hover {
        background: #f8fafc;
    }

    .client-avatar {
        width: 48px;
        height: 48px;
        background: #475569;
        /* Dark grey like the screenshot */
        color: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.875rem;
        margin-right: 1.25rem;
        flex-shrink: 0;
        border: 2px solid #e2e8f0;
    }

    .client-info {
        flex: 1;
        min-width: 0;
    }

    .client-name {
        font-weight: 700;
        color: #1e293b;
        font-size: 1rem;
        margin-bottom: 0.25rem;
    }

    .client-details {
        color: #64748b;
        font-size: 0.875rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .client-country {
        font-size: 1.5rem;
        margin-left: 1rem;
    }

    /* 6-Card Grid */
    .stats-grid-6 {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    /* Mobile Responsive Fixes */
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
            max-width: 100%;
        }
    }

    @media (max-width: 768px) {
        .stats-grid-6 {
            grid-template-columns: 1fr;
        }
    }

    /* Privacy Toggle Styles */
    .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .toggle-visibility {
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 4px;
        font-size: 0.9rem;
        transition: all 0.2s;
        margin-top: -2px;
        margin-right: -4px;
    }

    .toggle-visibility:hover {
        color: #1e293b;
        transform: scale(1.1);
    }

    .amount-wrapper.masked .amount-actual {
        display: none;
    }

    .amount-wrapper.masked .amount-masked {
        display: block;
        font-family: 'Inter', sans-serif;
        font-weight: 700;
        letter-spacing: 2px;
        color: #1e293b;
        margin-top: 5px;
    }

    .amount-wrapper:not(.masked) .amount-masked {
        display: none;
    }

    .amount-wrapper:not(.masked) .amount-actual {
        display: block;
    }
</style>

<script>
    function togglePrivacy(btn) {
        const card = btn.closest('.stat-card');
        const wrapper = card.querySelector('.amount-wrapper');
        const icon = btn.querySelector('i');
        
        if (wrapper.classList.contains('masked')) {
            wrapper.classList.remove('masked');
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            btn.style.color = '#1e293b';
        } else {
            wrapper.classList.add('masked');
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            btn.style.color = '#94a3b8';
        }
    }
</script>

<?php include "includes/footer.php"; ?>