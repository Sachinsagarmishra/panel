<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

// Handle form submission for adding/updating currency
if ($_POST) {
    $code = strtoupper(trim($_POST['code']));
    $name = trim($_POST['name']);
    $symbol = trim($_POST['symbol']);
    $exchange_rate = floatval($_POST['exchange_rate']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        if (isset($_POST['update_id']) && $_POST['update_id']) {
            // Update existing currency
            $stmt = $pdo->prepare("UPDATE currencies SET name = ?, symbol = ?, exchange_rate = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $symbol, $exchange_rate, $is_active, $_POST['update_id']]);
            $success = "Currency updated successfully!";
        } else {
            // Add new currency
            $stmt = $pdo->prepare("INSERT INTO currencies (code, name, symbol, exchange_rate, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$code, $name, $symbol, $exchange_rate, $is_active]);
            $success = "Currency added successfully!";
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all currencies
try {
    $currenciesStmt = $pdo->query("SELECT * FROM currencies ORDER BY is_active DESC, code ASC");
    $currencies = $currenciesStmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Currency Management - FreelancePro</title>
    <link href="assets/style.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
       <!-- Sidebar -->
        <nav class="sidebar">
           <div class="logo">
    <div class="logo-icon">
        <img src="https://sachindesign.com/assets/img/Sachin's%20photo.png" alt="Logo Icon" />
    </div>
    <div class="logo-text">Sachindesign</div>
</div>

            <div class="nav-section">
                <div class="nav-title">Overview</div>
                <a href="index.php" class="nav-item">
                    <span class="nav-item-icon"><i class="fas fa-chart-bar"></i></span>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-title">Client Management</div>
                <a href="clients.php" class="nav-item">
                    <span class="nav-item-icon"><i class="fa-regular fa-circle-user"></i></span>
                    <span>Clients</span>
                </a>
                <a href="projects.php" class="nav-item">
                    <span class="nav-item-icon"><i class="fa-regular fa-copy"></i></span>
                    <span>Projects</span>
                </a>
                <a href="tasks.php" class="nav-item">
                    <span class="nav-item-icon"><i class="fa-regular fa-pen-to-square"></i></span>
                    <span>Tasks</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-title">Business</div>
                <a href="invoices.php" class="nav-item">
                    <span class="nav-item-icon"><i class="fa-regular fa-chess-king"></i></span>
                    <span>Invoices</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-title">Settings</div>
                <a href="bank-accounts.php" class="nav-item">
                    <span class="nav-item-icon"><i class="fa-regular fa-gem"></i></span>
                    <span>Bank Accounts</span>
                </a>
                <a href="currencies.php" class="nav-item">
                    <span class="nav-item-icon">💱</span>
                    <span>Currencies</span>
                </a>
            </div>
            
                <div class="nav-section" style="margin-top: auto; border-top: 1px solid #e2e8f0; padding-top: 1rem;">
        <a href="logout.php" class="nav-item logout-item" onclick="return confirm('Are you sure you want to logout?')">
                    <span class="nav-item-icon"><i class="fa-regular fa-share-from-square"></i></span>
            <span>Logout</span>
        </a>
    </div>
    
        </nav>


        <main class="main-content">
            <header class="header fade-in">
                <div>
                    <h1>💱 Currency Management</h1>
                    <p>Manage exchange rates and currencies</p>
                </div>
                <button onclick="toggleCurrencyForm()" class="btn btn-primary">
                    <span>Add Currency</span>
                </button>
            </header>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Currency Form -->
            <div id="currencyForm" class="form-modal" style="display: none;">
                <div class="form-content">
                    <div class="form-header">
                        <h2 id="formTitle">💱 Add New Currency</h2>
                        <button type="button" onclick="toggleCurrencyForm()" class="close-btn">✕</button>
                    </div>
                    
                    <form method="POST" id="currencyFormElement">
                        <input type="hidden" id="update_id" name="update_id">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                            <div>
                                <div class="form-group">
                                    <label class="form-label" for="code">Currency Code *</label>
                                    <input type="text" id="code" name="code" class="form-input" 
                                           placeholder="USD, INR, AED..." maxlength="10" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="name">Currency Name *</label>
                                    <input type="text" id="name" name="name" class="form-input" 
                                           placeholder="US Dollar" required>
                                </div>
                            </div>

                            <div>
                                <div class="form-group">
                                    <label class="form-label" for="symbol">Symbol *</label>
                                    <input type="text" id="symbol" name="symbol" class="form-input" 
                                           placeholder="$, ₹, د.إ..." maxlength="10" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="exchange_rate">Exchange Rate to USD *</label>
                                    <input type="number" id="exchange_rate" name="exchange_rate" class="form-input" 
                                           step="0.0001" min="0" placeholder="1.0000" required>
                                    <small style="color: #64748b;">1 USD = ? of this currency</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="is_active" id="is_active" checked>
                                <span>Active Currency</span>
                            </label>
                        </div>

                        <div class="form-actions">
                            <button type="button" onclick="toggleCurrencyForm()" class="btn btn-secondary">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Currency</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Currencies List -->
            <div class="table-container fade-in">
                <div class="table-header">
                    <div class="table-title">
                        <span>💱</span>
                        <span>All Currencies (<?php echo count($currencies); ?>)</span>
                    </div>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Currency</th>
                            <th>Symbol</th>
                            <th>Exchange Rate</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($currencies as $currency): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; font-size: 1.1rem;">
                                        <?php echo htmlspecialchars($currency['code']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 500;">
                                        <?php echo htmlspecialchars($currency['name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 1.2rem; font-weight: 600;">
                                        <?php echo htmlspecialchars($currency['symbol']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        1 USD = <?php echo number_format($currency['exchange_rate'], 4); ?> <?php echo $currency['code']; ?>
                                    </div>
                                    <?php if ($currency['code'] != 'USD'): ?>
                                        <div style="font-size: 0.875rem; color: #64748b;">
                                            1 <?php echo $currency['code']; ?> = $<?php echo number_format(1 / $currency['exchange_rate'], 4); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status <?php echo $currency['is_active'] ? 'active' : 'paused'; ?>">
                                        <?php echo $currency['is_active'] ? '<i class="fa-regular fa-circle-check"></i>Active' : '<i class="fa-regular fa-circle-xmark"></i> Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button onclick="editCurrency(<?php echo htmlspecialchars(json_encode($currency)); ?>)" 
                                                class="btn btn-secondary" style="padding: 0.5rem;" title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button onclick="toggleCurrencyStatus(<?php echo $currency['id']; ?>, <?php echo $currency['is_active'] ? 'false' : 'true'; ?>)" 
                                                class="btn btn-secondary" style="padding: 0.5rem;" 
                                                title="<?php echo $currency['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                            <?php echo $currency['is_active'] ? '<i class="fa-regular fa-circle-pause"></i>' : '<i class="fa-regular fa-circle-play"></i>'; ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <style>
        /* Modal Styles */
        .form-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-header h2 {
            color: #1e293b;
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #64748b;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .close-btn:hover {
            background: #f1f5f9;
            color: #1e293b;
        }

        .form-content form {
            padding: 2rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left-color: #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border-left-color: #ef4444;
        }
    </style>

    <script>
        function toggleCurrencyForm(currency = null) {
            const form = document.getElementById('currencyForm');
            const formTitle = document.getElementById('formTitle');
            const formElement = document.getElementById('currencyFormElement');
            
            const isVisible = form.style.display !== 'none';
            
            if (isVisible) {
                form.style.display = 'none';
                document.body.style.overflow = 'auto';
                formElement.reset();
                document.getElementById('update_id').value = '';
                formTitle.textContent = '💱 Add New Currency';
            } else {
                form.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                
                if (currency) {
                    // Edit mode
                    document.getElementById('update_id').value = currency.id;
                    document.getElementById('code').value = currency.code;
                    document.getElementById('name').value = currency.name;
                    document.getElementById('symbol').value = currency.symbol;
                    document.getElementById('exchange_rate').value = currency.exchange_rate;
                    document.getElementById('is_active').checked = currency.is_active == 1;
                    formTitle.textContent = '✏️ Edit Currency';
                    
                    // Disable code field for editing
                    document.getElementById('code').disabled = true;
                } else {
                    // Add mode
                    document.getElementById('code').disabled = false;
                }
            }
        }

        function editCurrency(currency) {
            toggleCurrencyForm(currency);
        }

        function toggleCurrencyStatus(id, newStatus) {
            if (confirm('Are you sure you want to change the currency status?')) {
                window.location.href = `toggle-currency-status.php?id=${id}&status=${newStatus}`;
            }
        }

        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });

            // Close modal when clicking outside
            const modal = document.getElementById('currencyForm');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        toggleCurrencyForm();
                    }
                });
            }
        });
    </script>
</body>
</html>