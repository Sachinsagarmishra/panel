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
                    <?php
                    // Ensure USD and INR are always shown if active or at least once
                    $displayEarnings = [];
                    $foundCurrencies = array_column($monthlyEarnings, 'currency');

                    // Add existing earnings
                    foreach ($monthlyEarnings as $earning) {
                        $displayEarnings[$earning['currency']] = $earning;
                    }

                    // Force add USD and INR if missing (with 0 values)
                    if (!isset($displayEarnings['USD'])) {
                        $displayEarnings['USD'] = ['currency' => 'USD', 'symbol' => '$', 'monthly_amount' => 0];
                    }
                    if (!isset($displayEarnings['INR'])) {
                        $displayEarnings['INR'] = ['currency' => 'INR', 'symbol' => '₹', 'monthly_amount' => 0];
                    }

                    // Re-index for display function
                    $finalEarnings = array_values($displayEarnings);

                    // Custom display logic to ensure USD and INR show up
                    echo formatCurrencyDisplay($finalEarnings, 5);
                    ?>
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

<!-- Revenue Analytics Chart -->
<div class="chart-container fade-in" style="margin-bottom: 2rem;">
    <div class="chart-header">
        <div class="header-content">
            <h3>Revenue Analytics</h3>
            <div class="estimated-revenue">
                <div class="revenue-label">Estimated Revenue</div>
                <div class="revenue-value" id="estimatedRevenue">$0.00</div>
            </div>
        </div>

        <div class="chart-controls">
            <div class="date-filter-dropdown">
                <button class="filter-toggle" onclick="toggleFilterMenu()">
                    <span id="currentFilterLabel">Lifetime</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="filter-menu" id="filterMenu">
                    <div class="filter-group">
                        <div class="filter-item" onclick="selectFilter('last_7_days', 'Last 7 days')">Last 7 days</div>
                        <div class="filter-item" onclick="selectFilter('last_28_days', 'Last 28 days')">Last 28 days
                        </div>
                        <div class="filter-item" onclick="selectFilter('last_90_days', 'Last 90 days')">Last 90 days
                        </div>
                        <div class="filter-item" onclick="selectFilter('last_365_days', 'Last 365 days')">Last 365 days
                        </div>
                        <div class="filter-item active" onclick="selectFilter('lifetime', 'Lifetime')">Lifetime</div>
                    </div>
                    <div class="filter-divider"></div>
                    <div class="filter-group year-group">
                        <?php
                        $currentYear = date('Y');
                        for ($i = 0; $i < 3; $i++):
                            $year = $currentYear - $i;
                            ?>
                            <div class="filter-item"
                                onclick="selectFilter('year', '<?php echo $year; ?>', '<?php echo $year; ?>')">
                                <?php echo $year; ?></div>
                        <?php endfor; ?>
                    </div>
                    <div class="filter-divider"></div>
                    <div class="filter-group month-group">
                        <?php
                        // Show last 3 months
                        for ($i = 0; $i < 3; $i++):
                            $date = date('Y-m', strtotime("-$i months"));
                            $label = date('F Y', strtotime("-$i months"));
                            ?>
                            <div class="filter-item"
                                onclick="selectFilter('month', '<?php echo $label; ?>', '<?php echo $date; ?>')">
                                <?php echo $label; ?></div>
                        <?php endfor; ?>
                    </div>
                    <div class="filter-divider"></div>
                    <div class="filter-item" onclick="showCustomRange()">Custom</div>
                </div>
            </div>
        </div>
    </div>

    <div class="chart-wrapper">
        <canvas id="revenueChart"></canvas>
    </div>
</div>

<!-- Custom Range Modal -->
<div id="customRangeModal" class="form-modal" style="display: none;">
    <div class="form-content" style="max-width: 400px;">
        <h3>Select Date Range</h3>
        <div class="form-group">
            <label>Start Date</label>
            <input type="date" id="customStart" class="form-input">
        </div>
        <div class="form-group">
            <label>End Date</label>
            <input type="date" id="customEnd" class="form-input">
        </div>
        <div style="display: flex; gap: 10px; margin-top: 15px;">
            <button class="btn btn-primary" onclick="applyCustomRange()">Apply</button>
            <button class="btn btn-secondary"
                onclick="document.getElementById('customRangeModal').style.display='none'">Cancel</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let revenueChart = null;
    let currentPeriod = 'lifetime';
    let currentVal = null; // For year/month

    function toggleFilterMenu() {
        const menu = document.getElementById('filterMenu');
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }

    // Close menu when clicking outside
    document.addEventListener('click', function (event) {
        const dropdown = document.querySelector('.date-filter-dropdown');
        const menu = document.getElementById('filterMenu');
        if (dropdown && !dropdown.contains(event.target)) {
            menu.style.display = 'none';
        }
    });

    function selectFilter(period, label, val = null) {
        document.getElementById('currentFilterLabel').innerText = label;
        document.getElementById('filterMenu').style.display = 'none';

        // Update active class
        document.querySelectorAll('.filter-item').forEach(el => el.classList.remove('active'));
        event.target.classList.add('active');

        currentPeriod = period;
        currentVal = val;

        if (period === 'custom') return; // Handled by showCustomRange() separately if clicked directly

        updateChart(period, val);
    }

    function showCustomRange() {
        document.getElementById('filterMenu').style.display = 'none';
        document.getElementById('customRangeModal').style.display = 'flex';
    }

    function applyCustomRange() {
        const start = document.getElementById('customStart').value;
        const end = document.getElementById('customEnd').value;

        if (!start || !end) {
            alert('Please select both dates');
            return;
        }

        document.getElementById('customRangeModal').style.display = 'none';
        document.getElementById('currentFilterLabel').innerText = 'Custom Range';
        updateChart('custom', null, start, end);
    }

    async function updateChart(period, val = null, start = null, end = null) {
        // Construct query
        let url = `get-revenue-data.php?period=${period}`;
        if (val) url += `&val=${val}`;
        if (start && end) url += `&start=${start}&end=${end}`;

        try {
            const response = await fetch(url);
            const data = await response.json();

            // Update Estimated Revenue Display
            const revEl = document.getElementById('estimatedRevenue');
            if (data.totals && data.totals.length > 0) {
                // Combine formatted totals e.g. "$100.00 + ₹2000.00"
                revEl.innerText = data.totals.map(t => t.formatted).join(' + ');
            } else {
                revEl.innerText = '$0.00';
            }

            if (revenueChart) {
                revenueChart.destroy();
            }

            const ctx = document.getElementById('revenueChart').getContext('2d');
            revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: data.datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false // Hide legend to match screenshot clean look? Or keep it? Screenshot doesn't show multiple lines.
                            // Keeping it for multi-currency support
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'white',
                            titleColor: '#1e293b',
                            bodyColor: '#1e293b',
                            borderColor: '#e2e8f0',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: true,
                            callbacks: {
                                label: function (context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('en-US', { style: 'currency', currency: context.dataset.label }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f1f5f9',
                                borderDash: [5, 5]
                            },
                            ticks: {
                                callback: function (value) {
                                    return value.toLocaleString(); // Format Y axis
                                },
                                color: '#94a3b8',
                                font: { size: 11 }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#94a3b8',
                                font: { size: 11 },
                                maxTicksLimit: 9 // Limit X axis labels
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    },
                    elements: {
                        line: {
                            tension: 0
                        },
                        point: {
                            radius: 0,
                            hoverRadius: 6
                        }
                    }
                }
            });

        } catch (error) {
            console.error('Error fetching chart data:', error);
        }
    }

    // Initialize with Lifetime data
    document.addEventListener('DOMContentLoaded', () => {
        updateChart('lifetime');
    });
</script>

<style>
    /* Chart Container Styling */
    .chart-container {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .chart-header h3 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    .chart-filters {
        display: flex;
        gap: 0.5rem;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 8px;
    }

    .filter-btn {
        background: none;
        border: none;
        padding: 6px 12px;
        font-size: 0.85rem;
        font-weight: 500;
        color: #64748b;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .filter-btn:hover {
        color: #1e293b;
        background: rgba(255, 255, 255, 0.5);
    }

    .filter-btn.active {
        background: white;
        color: #0f172a;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        font-weight: 600;
    }

    .chart-wrapper {
        position: relative;
        height: 300px;
        width: 100%;
    }

    @media (max-width: 768px) {
        .chart-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .chart-filters {
            width: 100%;
            justify-content: space-between;
        }

        .filter-btn {
            flex: 1;
            text-align: center;
        }
    }
</style>

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