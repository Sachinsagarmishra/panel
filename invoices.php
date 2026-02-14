<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

// Handle status filter
$statusFilter = $_GET['status'] ?? '';

// Handle form submission
if ($_POST) {
    $invoice_number = trim($_POST['invoice_number']);
    $client_id = $_POST['client_id'];
    $project_id = $_POST['project_id'] ?? null;
    $issue_date = $_POST['issue_date'];
    $due_date = $_POST['due_date'];
    $total_amount = floatval($_POST['amount']);
    $currency = $_POST['currency'] ?? 'USD';
    $payment_mode = trim($_POST['payment_mode']);
    $bank_account = $_POST['bank_account'] ?? null;
    $paypal_account = $_POST['paypal_account'] ?? null;
    $upi_account = $_POST['upi_account'] ?? null;
    $notes = trim($_POST['notes']);

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Insert invoice
        $stmt = $pdo->prepare("
            INSERT INTO invoices (invoice_number, client_id, project_id, issue_date, due_date, amount, currency, payment_mode, bank_account, paypal_account, upi_account, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$invoice_number, $client_id, $project_id, $issue_date, $due_date, $total_amount, $currency, $payment_mode, $bank_account, $paypal_account, $upi_account, $notes]);

        $invoiceId = $pdo->lastInsertId();

        // Insert invoice items
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            $itemStmt = $pdo->prepare("
                INSERT INTO invoice_items (invoice_id, description, quantity, rate, amount) 
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($_POST['items'] as $item) {
                if (!empty($item['description']) && !empty($item['rate'])) {
                    $quantity = floatval($item['quantity']) ?: 1;
                    $rate = floatval($item['rate']);
                    $amount = $quantity * $rate;

                    $itemStmt->execute([$invoiceId, $item['description'], $quantity, $rate, $amount]);
                }
            }
        }

        $pdo->commit();
        $success = "Invoice created successfully!";

        // Redirect to avoid form resubmission
        header("Location: invoices.php?success=" . urlencode($success));
        exit;

    } catch (PDOException $e) {
        $pdo->rollback();
        $error = "Error: " . $e->getMessage();
    }
}

// Get invoices with filters
$sql = "
    SELECT i.*, c.name as client_name, c.email as client_email, c.brand_name as client_brand,
           p.title as project_title, ba.account_name, ba.bank_name,
           curr.symbol as currency_symbol, curr.name as currency_name
    FROM invoices i 
    JOIN clients c ON i.client_id = c.id 
    LEFT JOIN projects p ON i.project_id = p.id
    LEFT JOIN bank_accounts ba ON i.bank_account = ba.id
    LEFT JOIN currencies curr ON i.currency = curr.code
    WHERE 1=1
";

$params = [];
if ($statusFilter) {
    $sql .= " AND i.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY i.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $invoices = $stmt->fetchAll();

    // Get clients and projects for dropdowns
    $clientsStmt = $pdo->query("SELECT id, name, brand_name FROM clients ORDER BY name");
    $clients = $clientsStmt->fetchAll();

    $projectsStmt = $pdo->query("SELECT id, title, client_id FROM projects ORDER BY title");
    $projects = $projectsStmt->fetchAll();

    $bankAccountsStmt = $pdo->query("SELECT id, account_name, bank_name FROM bank_accounts ORDER BY account_name");
    $bankAccounts = $bankAccountsStmt->fetchAll();

    // Get active currencies
    $currenciesStmt = $pdo->query("SELECT * FROM currencies WHERE is_active = 1 ORDER BY code");
    $activeCurrencies = $currenciesStmt->fetchAll();

    // Calculate statistics by currency
    $totalAmount = [];
    $paidAmount = [];
    $unpaidCount = 0;
    $overdueCount = 0;

    foreach ($invoices as $invoice) {
        $currency = $invoice['currency'] ?? 'USD';
        $symbol = $invoice['currency_symbol'] ?? '$';

        if (!isset($totalAmount[$currency])) {
            $totalAmount[$currency] = ['amount' => 0, 'symbol' => $symbol];
        }
        if (!isset($paidAmount[$currency])) {
            $paidAmount[$currency] = ['amount' => 0, 'symbol' => $symbol];
        }

        $totalAmount[$currency]['amount'] += $invoice['amount'];

        if ($invoice['status'] == 'Paid') {
            $paidAmount[$currency]['amount'] += $invoice['amount'];
        } elseif ($invoice['status'] == 'Unpaid') {
            $unpaidCount++;
        } elseif ($invoice['status'] == 'Overdue') {
            $overdueCount++;
        }
    }

} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Generate next invoice number
try {
    $nextInvoiceStmt = $pdo->query("SELECT COUNT(*) + 1 as next_number FROM invoices");
    $nextNumber = $nextInvoiceStmt->fetch()['next_number'];
    $nextInvoiceNumber = 'INV-' . date('Y') . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
} catch (PDOException $e) {
    $nextInvoiceNumber = 'INV-' . date('Y') . '-001';
}
?>

<?php
$page_title = 'Invoices';
include 'includes/header.php';
?>
<header class="header fade-in">
    <div>
        <h1>Invoices</h1>
        <p>Manage your billing and payments</p>
    </div>
    <button id="newInvoiceBtn" onclick="toggleInvoiceForm()" class="btn btn-primary">
        <span>New Invoice</span>
    </button>
</header>



<!-- Filters -->
<div class="filters fade-in">
    <div class="filter-group">
        <label class="form-label" style="margin-bottom: 0;">Filter by Status:</label>
        <select id="statusFilter" class="form-select" style="width: auto;" onchange="filterInvoices()">
            <option value="">All Status</option>
            <option value="Paid" <?php echo $statusFilter == 'Paid' ? 'selected' : ''; ?>>✅ Paid</option>
            <option value="Unpaid" <?php echo $statusFilter == 'Unpaid' ? 'selected' : ''; ?>>⏳ Unpaid</option>
            <option value="Overdue" <?php echo $statusFilter == 'Overdue' ? 'selected' : ''; ?>>⚠️ Overdue</option>
        </select>
    </div>

    <div class="filter-group">
        <a href="?status=Unpaid" class="btn btn-secondary">
            <span>📋</span>
            <span>Pending Invoices</span>
        </a>
        <a href="?status=Overdue" class="btn btn-secondary">
            <span>⚠️</span>
            <span>Overdue Invoices</span>
        </a>
        <a href="currencies.php" class="btn btn-secondary">
            <span>💱</span>
            <span>Manage Currencies</span>
        </a>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($success)): ?>
    <div class="alert alert-success">
        ✅ <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-error">
        ❌ <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        ✅ <?php echo htmlspecialchars($_GET['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        ❌ <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
<?php endif; ?>

<!-- Invoice Form -->
<div id="invoiceForm" class="form-modal" style="display: none;">
    <div class="form-content">
        <div class="form-header">
            <h2>Create New Invoice</h2>
            <button type="button" onclick="toggleInvoiceForm()" class="close-btn">✕</button>
        </div>

        <form method="POST" id="invoiceFormElement">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <div class="form-group">
                        <label class="form-label" for="invoice_number">Invoice Number *</label>
                        <input type="text" id="invoice_number" name="invoice_number" class="form-input"
                            value="<?php echo $nextInvoiceNumber; ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="client_id">Client *</label>
                        <select id="client_id" name="client_id" class="form-select" required
                            onchange="loadClientProjects()">
                            <option value="">Select Client...</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>">
                                    <?php echo htmlspecialchars($client['name']); ?>
                                    <?php if ($client['brand_name']): ?>
                                        (<?php echo htmlspecialchars($client['brand_name']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="project_id">Linked Project</label>
                        <select id="project_id" name="project_id" class="form-select">
                            <option value="">Select Project...</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="currency">Currency *</label>
                        <select id="currency" name="currency" class="form-select" required
                            onchange="updateCurrencySymbol()">
                            <?php foreach ($activeCurrencies as $curr): ?>
                                <option value="<?php echo $curr['code']; ?>" data-symbol="<?php echo $curr['symbol']; ?>"
                                    <?php echo $curr['code'] == 'USD' ? 'selected' : ''; ?>>
                                    <?php echo $curr['symbol']; ?>     <?php echo $curr['code']; ?> -
                                    <?php echo $curr['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="form-group">
                        <label class="form-label" for="issue_date">Issue Date *</label>
                        <input type="date" id="issue_date" name="issue_date" class="form-input"
                            value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="due_date">Due Date *</label>
                        <input type="date" id="due_date" name="due_date" class="form-input"
                            value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="payment_mode">Payment Mode</label>
                        <select id="payment_mode" name="payment_mode" class="form-select" onchange="togglePaymentFields()">
                            <option value="">Select Mode...</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="PayPal">PayPal</option>
                            <option value="UPI">UPI</option>
                        </select>
                    </div>

                    <div class="form-group payment-field bank-field" style="display: none;">
                        <label class="form-label" for="bank_account">Bank Account *</label>
                        <select id="bank_account" name="bank_account" class="form-select">
                            <option value="">Select Bank Account...</option>
                            <?php foreach ($bankAccounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>">
                                    <?php echo htmlspecialchars($account['account_name']); ?> -
                                    <?php echo htmlspecialchars($account['bank_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group payment-field paypal-field" style="display: none;">
                        <label class="form-label" for="paypal_account">PayPal Account *</label>
                        <select id="paypal_account" name="paypal_account" class="form-select">
                            <option value="">Select PayPal...</option>
                            <?php foreach ($paypalMethods as $method): ?>
                                <option value="<?php echo $method['id']; ?>">
                                    <?php echo htmlspecialchars($method['account_name']); ?> (<?php echo htmlspecialchars($method['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group payment-field upi-field" style="display: none;">
                        <label class="form-label" for="upi_account">UPI Account *</label>
                        <select id="upi_account" name="upi_account" class="form-select">
                            <option value="">Select UPI...</option>
                            <?php foreach ($upiMethods as $method): ?>
                                <option value="<?php echo $method['id']; ?>">
                                    <?php echo htmlspecialchars($method['account_name']); ?> (<?php echo htmlspecialchars($method['upi_id']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Invoice Items Section -->
            <div class="form-group">
                <label class="form-label">Invoice Items</label>
                <div class="items-header"
                    style="display: grid; grid-template-columns: 2fr 100px 120px 120px 50px; gap: 1rem; margin-bottom: 0.5rem; font-weight: 600; color: #64748b; font-size: 0.875rem;">
                    <div>Description</div>
                    <div>Qty</div>
                    <div>Rate (<span id="currency-display">$</span>)</div>
                    <div>Amount (<span id="currency-display-2">$</span>)</div>
                    <div></div>
                </div>

                <div id="invoiceItems">
                    <div class="invoice-item" data-index="0">
                        <div class="item-row"
                            style="display: grid; grid-template-columns: 2fr 100px 120px 120px 50px; gap: 1rem; align-items: center; margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                            <div>
                                <textarea name="items[0][description]" class="form-input" rows="2"
                                    placeholder="Enter description..." required></textarea>
                            </div>
                            <div>
                                <input type="number" name="items[0][quantity]" class="form-input" value="1" min="0.01"
                                    step="0.01" onchange="calculateItemAmount(0)" required>
                            </div>
                            <div>
                                <input type="number" name="items[0][rate]" class="form-input" step="0.01" min="0"
                                    placeholder="0.00" onchange="calculateItemAmount(0)" required>
                            </div>
                            <div>
                                <input type="number" name="items[0][amount]" class="form-input" step="0.01" readonly
                                    style="background: #e2e8f0; font-weight: 600;">
                            </div>
                            <div>
                                <button type="button" onclick="removeItem(0)" class="btn btn-secondary"
                                    style="padding: 0.5rem; background: #ef4444; color: white;" title="Remove Item"><i
                                        class="fa-regular fa-trash-can"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin: 1rem 0;">
                    <button type="button" onclick="addInvoiceItem()" class="btn btn-secondary">
                        ➕ Add Another Item
                    </button>
                </div>

                <div class="total-section"
                    style="text-align: right; padding: 1rem; background: #f8fafc; border-radius: 8px; margin-top: 1rem;">
                    <div style="font-size: 1.2rem; font-weight: 600; color: #059669;">
                        Total Amount: <span id="currency-symbol">$</span><span id="totalAmount">0.00</span>
                    </div>
                    <input type="hidden" id="amount" name="amount" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="notes">Additional Notes</label>
                <textarea id="notes" name="notes" class="form-textarea" rows="3"
                    placeholder="Any additional notes for this invoice..."></textarea>
            </div>

            <div class="form-actions">
                <button type="button" onclick="toggleInvoiceForm()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Invoice</button>
            </div>
        </form>
    </div>
</div>

<!-- Invoices Table -->
<div class="table-container fade-in">
    <div class="table-header">
        <div class="table-title">
            <span>All Invoices (<?php echo count($invoices); ?>)</span>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Client</th>
                <th>Project</th>
                <th>Amount</th>
                <th>Dates</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($invoices)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b; padding: 3rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;"></div>
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">No invoices yet</div>
                        <div>Create your first invoice to start billing!</div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #1e293b;">
                                <?php echo htmlspecialchars($invoice['invoice_number']); ?>
                            </div>
                            <div style="color: #64748b; font-size: 0.75rem;">
                                <?php echo ($invoice['currency'] ?? 'USD'); ?> - Created:
                                <?php echo date('M j, Y', strtotime($invoice['created_at'])); ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 600;">
                                <?php echo htmlspecialchars($invoice['client_name']); ?>
                            </div>
                            <?php if ($invoice['client_brand']): ?>
                                <div style="color: #64748b; font-size: 0.875rem;">
                                    <?php echo htmlspecialchars($invoice['client_brand']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($invoice['project_title']): ?>
                                <div style="color: #1e293b; font-weight: 500;">
                                    <?php echo htmlspecialchars($invoice['project_title']); ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #64748b;">No project linked</span>
                            <?php endif; ?>

                            <?php if ($invoice['payment_mode']): ?>
                                <div style="color: #64748b; font-size: 0.875rem;">
                                    <?php echo htmlspecialchars($invoice['payment_mode']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight: 700; font-size: 1.25rem; color: #059669;">
                                <?php echo ($invoice['currency_symbol'] ?? '$'); ?>
                                <?php echo number_format($invoice['amount'], 2); ?>
                            </div>
                            <div style="color: #64748b; font-size: 0.75rem;">
                                <?php echo ($invoice['currency'] ?? 'USD'); ?>
                                <?php if ($invoice['account_name']): ?>
                                    - <?php echo htmlspecialchars($invoice['account_name']); ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 0.875rem;">
                                <div><strong>Issue:</strong> <?php echo date('M j, Y', strtotime($invoice['issue_date'])); ?>
                                </div>
                                <div><strong>Due:</strong> <?php echo date('M j, Y', strtotime($invoice['due_date'])); ?></div>
                                <?php
                                $daysLeft = ceil((strtotime($invoice['due_date']) - time()) / (60 * 60 * 24));
                                if ($invoice['status'] != 'Paid'):
                                    if ($daysLeft < 0): ?>
                                        <div style="color: #ef4444; font-weight: 600;">
                                            ⚠️ <?php echo abs($daysLeft); ?> days overdue
                                        </div>
                                    <?php elseif ($daysLeft <= 7): ?>
                                        <div style="color: #f59e0b; font-weight: 600;">
                                            ⏰ <?php echo $daysLeft; ?> days left
                                        </div>
                                    <?php endif;
                                endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="status <?php echo strtolower($invoice['status']); ?>">
                                <?php
                                switch ($invoice['status']) {
                                    case 'Paid':
                                        echo 'Paid';
                                        break;
                                    case 'Unpaid':
                                        echo 'Unpaid';
                                        break;
                                    case 'Overdue':
                                        echo 'Overdue';
                                        break;
                                    default:
                                        echo $invoice['status'];
                                }
                                ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                <!-- View/Export Actions -->
                                <div style="display: flex; gap: 0.25rem;">
                                    <button onclick="exportInvoiceToPDF(<?php echo $invoice['id']; ?>)"
                                        class="action-btn btn-primary" title="Export PDF">
                                        <i class="fa-regular fa-circle-down"></i>
                                    </button>
                                    <button onclick="viewInvoiceDetails(<?php echo $invoice['id']; ?>)"
                                        class="action-btn btn-secondary" title="View Details">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>

                                <!-- Status Actions -->
                                <div style="display: flex; gap: 0.25rem;">
                                    <?php if ($invoice['status'] != 'Paid'): ?>
                                        <button onclick="markAsPaid(<?php echo $invoice['id']; ?>)" class="action-btn btn-success"
                                            title="Mark as Paid">
                                            <i class="fa-regular fa-thumbs-up"></i>
                                        </button>
                                    <?php else: ?>
                                        <button onclick="markAsUnpaid(<?php echo $invoice['id']; ?>)" class="action-btn btn-warning"
                                            title="Mark as Unpaid">
                                            <i class="fa-regular fa-circle-xmark"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <!-- Delete Action -->
                                <button
                                    onclick="deleteInvoice(<?php echo $invoice['id']; ?>, '<?php echo htmlspecialchars($invoice['invoice_number']); ?>')"
                                    class="action-btn btn-danger" title="Delete Invoice">
                                    <i class="fa-regular fa-trash-can"></i>️
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</main>
</div>

<style>
    .table th,
    .table td {
        padding: 17px;
    }

    .sidebar {
        border-radius: 0px 20px 20px 0px;
        width: 250px;
        background: #ffffff;
        padding: 1.5rem;
        overflow-y: auto;
        box-shadow: none;
    }


    .logo-icon img {
        width: 40px;
        /* ya jo bhi size chahiye */
        height: auto;
    }

    .main-content {
        background: #fafafa !important;
    }

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
        width: 95%;
        max-width: 1000px;
        max-height: 95vh;
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

    .status {
        border: 1px solid #6a6464;
        padding: 2px 13px;
    }

    .status.paid {
        background: #e8ffe1 !important;
        color: #59ad04 !important;
        border: 1px solid #59ad06 !important;
        padding: 2px 13px;
    }


    .status.unpaid {
        color: #ef4444;
        border: 1px solid #ef4444;
        background: #fde3e3;
    }

    /* Alert Styles */
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

    /* Action Button Styles */
    .action-btn {
        padding: 0.5rem !important;
        min-width: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        font-size: 0.875rem;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
        text-decoration: none;
    }

    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .btn-danger {
        background: #ef4444;
        color: white;
    }

    .btn-danger:hover {
        background: #dc2626;
    }

    .btn-success {
        background: #10b981;
        color: white;
    }

    .btn-success:hover {
        background: #059669;
    }

    .btn-warning {
        background: #f59e0b;
        color: white;
    }

    .btn-warning:hover {
        background: #d97706;
    }

    /* Delete confirmation modal styles */
    .delete-confirmation {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        z-index: 1001;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .delete-modal {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
    }

    .delete-header {
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .delete-icon {
        font-size: 3rem;
        color: #ef4444;
        margin-bottom: 1rem;
    }

    .delete-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .delete-message {
        color: #64748b;
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }

    .delete-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
    }

    .delete-confirm-btn {
        background: #ef4444;
        color: white;
        padding: 0.75rem 2rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .delete-confirm-btn:hover {
        background: #dc2626;
    }

    .delete-cancel-btn {
        background: #6b7280;
        color: white;
        padding: 0.75rem 2rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .delete-cancel-btn:hover {
        background: #4b5563;
    }

    /* Invoice Items Styling */
    .item-row {
        transition: all 0.2s;
    }

    .item-row:hover {
        background: #f1f5f9 !important;
    }

    @media (max-width: 768px) {
        .form-content {
            width: 100%;
            height: 100vh;
            border-radius: 0;
        }

        .items-header,
        .item-row {
            grid-template-columns: 1fr !important;
            gap: 0.5rem !important;
        }

        .items-header>div {
            display: none;
        }

        .item-row>div {
            margin-bottom: 0.5rem;
        }

        .action-btn {
            padding: 0.4rem !important;
            min-width: 28px;
            font-size: 0.75rem;
        }
    }
</style>

<script>
    const projects = <?php echo json_encode($projects); ?>;
    let itemIndex = 0;

    function togglePaymentFields() {
        const mode = document.getElementById('payment_mode').value;
        const allFields = document.querySelectorAll('.payment-field');
        allFields.forEach(f => f.style.display = 'none');

        if (mode === 'Bank Transfer') {
            document.querySelector('.bank-field').style.display = 'block';
        } else if (mode === 'PayPal') {
            document.querySelector('.paypal-field').style.display = 'block';
        } else if (mode === 'UPI') {
            document.querySelector('.upi-field').style.display = 'block';
        }
    }

    function toggleInvoiceForm() {
        const form = document.getElementById('invoiceForm');
        const isVisible = form.style.display !== 'none';

        if (isVisible) {
            form.style.display = 'none';
            document.body.style.overflow = 'auto';
            resetForm();
        } else {
            form.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            generateNewInvoiceNumber();
            updateCurrencySymbol();
            calculateTotal();
            togglePaymentFields();
        }
    }

    function resetForm() {
        document.getElementById('invoiceFormElement').reset();

        // Reset items to just one
        const itemsContainer = document.getElementById('invoiceItems');
        itemsContainer.innerHTML = `
                <div class="invoice-item" data-index="0">
                    <div class="item-row" style="display: grid; grid-template-columns: 2fr 100px 120px 120px 50px; gap: 1rem; align-items: center; margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                        <div>
                            <textarea name="items[0][description]" class="form-input" rows="2" placeholder="Enter description..." required></textarea>
                        </div>
                        <div>
                            <input type="number" name="items[0][quantity]" class="form-input" value="1" min="0.01" step="0.01" onchange="calculateItemAmount(0)" required>
                        </div>
                        <div>
                            <input type="number" name="items[0][rate]" class="form-input" step="0.01" min="0" placeholder="0.00" onchange="calculateItemAmount(0)" required>
                        </div>
                        <div>
                            <input type="number" name="items[0][amount]" class="form-input" step="0.01" readonly style="background: #e2e8f0; font-weight: 600;">
                        </div>
                        <div>
                            <button type="button" onclick="removeItem(0)" class="btn btn-secondary" style="padding: 0.5rem; background: #ef4444; color: white;" title="Remove Item">✕</button>
                        </div>
                    </div>
                </div>
            `;

        itemIndex = 0;
        calculateTotal();
    }

    function generateNewInvoiceNumber() {
        const now = new Date();
        const year = now.getFullYear();
        const existingInvoices = <?php echo count($invoices); ?>;
        const count = existingInvoices + 1;
        const paddedCount = count.toString().padStart(3, '0');

        document.getElementById('invoice_number').value = `INV-${year}-${paddedCount}`;
    }

    function updateCurrencySymbol() {
        const currencySelect = document.getElementById('currency');
        const selectedOption = currencySelect.options[currencySelect.selectedIndex];
        const symbol = selectedOption.getAttribute('data-symbol');

        // Update all currency displays
        document.getElementById('currency-display').textContent = symbol;
        document.getElementById('currency-display-2').textContent = symbol;
        document.getElementById('currency-symbol').textContent = symbol;

        calculateTotal(); // Recalculate to update display
    }

    function addInvoiceItem() {
        itemIndex++;
        const itemsContainer = document.getElementById('invoiceItems');

        const itemDiv = document.createElement('div');
        itemDiv.className = 'invoice-item';
        itemDiv.setAttribute('data-index', itemIndex);

        itemDiv.innerHTML = `
                <div class="item-row" style="display: grid; grid-template-columns: 2fr 100px 120px 120px 50px; gap: 1rem; align-items: center; margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                    <div>
                        <textarea name="items[${itemIndex}][description]" class="form-input" rows="2" placeholder="Enter description..." required></textarea>
                    </div>
                    <div>
                        <input type="number" name="items[${itemIndex}][quantity]" class="form-input" value="1" min="0.01" step="0.01" onchange="calculateItemAmount(${itemIndex})" required>
                    </div>
                    <div>
                        <input type="number" name="items[${itemIndex}][rate]" class="form-input" step="0.01" min="0" placeholder="0.00" onchange="calculateItemAmount(${itemIndex})" required>
                    </div>
                    <div>
                        <input type="number" name="items[${itemIndex}][amount]" class="form-input" step="0.01" readonly style="background: #e2e8f0; font-weight: 600;">
                    </div>
                    <div>
                        <button type="button" onclick="removeItem(${itemIndex})" class="btn btn-secondary" style="padding: 0.5rem; background: #ef4444; color: white;" title="Remove Item">✕</button>
                    </div>
                </div>
            `;

        itemsContainer.appendChild(itemDiv);
    }

    function removeItem(index) {
        const item = document.querySelector(`[data-index="${index}"]`);
        if (item && document.querySelectorAll('.invoice-item').length > 1) {
            item.remove();
            calculateTotal();
        } else if (document.querySelectorAll('.invoice-item').length === 1) {
            // Clear the only item instead of removing
            const textarea = item.querySelector('textarea');
            const inputs = item.querySelectorAll('input');
            textarea.value = '';
            inputs.forEach((input, idx) => {
                if (idx === 0) input.value = '1'; // quantity
                else input.value = '';
            });
            calculateTotal();
        }
    }

    function calculateItemAmount(index) {
        const quantity = parseFloat(document.querySelector(`input[name="items[${index}][quantity]"]`).value) || 0;
        const rate = parseFloat(document.querySelector(`input[name="items[${index}][rate]"]`).value) || 0;
        const amount = (quantity * rate).toFixed(2);

        document.querySelector(`input[name="items[${index}][amount]"]`).value = amount;
        calculateTotal();
    }

    function calculateTotal() {
        const amountInputs = document.querySelectorAll('input[name*="[amount]"]');
        let total = 0;

        amountInputs.forEach(input => {
            total += parseFloat(input.value) || 0;
        });

        document.getElementById('totalAmount').textContent = total.toFixed(2);
        document.getElementById('amount').value = total.toFixed(2);
    }

    function loadClientProjects() {
        const clientId = document.getElementById('client_id').value;
        const projectSelect = document.getElementById('project_id');

        // Clear existing options
        projectSelect.innerHTML = '<option value="">Select Project...</option>';

        if (!clientId) return;

        // Filter projects by client
        const clientProjects = projects.filter(p => p.client_id == clientId);

        clientProjects.forEach(project => {
            const option = document.createElement('option');
            option.value = project.id;
            option.textContent = project.title;
            projectSelect.appendChild(option);
        });
    }

    function filterInvoices() {
        const status = document.getElementById('statusFilter').value;
        const url = new URL(window.location);
        if (status) {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }
        window.location = url;
    }

    function exportInvoiceToPDF(invoiceId) {
        window.open(`export-invoice.php?id=${invoiceId}&type=pdf`, '_blank');
    }

    function viewInvoiceDetails(invoiceId) {
        window.open(`export-invoice.php?id=${invoiceId}&type=view`, '_blank');
    }

    function markAsPaid(invoiceId) {
        if (confirm('Mark this invoice as paid?')) {
            window.location.href = `update-invoice-status.php?id=${invoiceId}&status=paid`;
        }
    }

    function markAsUnpaid(invoiceId) {
        if (confirm('Mark this invoice as unpaid?')) {
            window.location.href = `update-invoice-status.php?id=${invoiceId}&status=unpaid`;
        }
    }

    function deleteInvoice(invoiceId, invoiceNumber) {
        createDeleteModal(invoiceId, invoiceNumber);
    }

    function createDeleteModal(invoiceId, invoiceNumber) {
        // Remove existing modal if any
        const existingModal = document.getElementById('deleteModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Create modal HTML
        const modalHTML = `
                <div id="deleteModal" class="delete-confirmation">
                    <div class="delete-modal">
                        <div class="delete-header">
                            <div class="delete-icon">⚠️</div>
                            <div class="delete-title">Delete Invoice</div>
                        </div>
                        
                        <div class="delete-message">
                            <p><strong>Invoice:</strong> ${invoiceNumber}</p>
                            <br>
                            <p>⚠️ <strong>Warning:</strong> This action will permanently delete:</p>
                            <ul style="margin: 1rem 0; padding-left: 1.5rem; color: #dc2626;">
                                <li>Invoice data and details</li>
                                <li>All invoice items and descriptions</li>
                                <li>Payment and status information</li>
                            </ul>
                            <p><strong>This action cannot be undone!</strong></p>
                        </div>
                        
                        <div class="delete-actions">
                            <button class="delete-cancel-btn" onclick="closeDeleteModal()">
                                Cancel
                            </button>
                            <button class="delete-confirm-btn" onclick="confirmDelete(${invoiceId})">
                                 Delete Forever
                            </button>
                        </div>
                    </div>
                </div>
            `;

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        document.body.style.overflow = 'hidden';
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        if (modal) {
            modal.remove();
            document.body.style.overflow = 'auto';
        }
    }

    function confirmDelete(invoiceId) {
        // Show loading state
        const confirmBtn = document.querySelector('.delete-confirm-btn');
        const cancelBtn = document.querySelector('.delete-cancel-btn');

        confirmBtn.innerHTML = '⏳ Deleting...';
        confirmBtn.disabled = true;
        cancelBtn.disabled = true;

        // Redirect to delete script
        setTimeout(() => {
            window.location.href = `delete-invoice.php?id=${invoiceId}`;
        }, 500);
    }

    // Auto-hide alert messages
    document.addEventListener('DOMContentLoaded', function () {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });

        // Close modal when clicking outside
        const modal = document.getElementById('invoiceForm');
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    toggleInvoiceForm();
                }
            });
        }

        // Close delete modal when clicking outside
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('delete-confirmation')) {
                closeDeleteModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
                const invoiceModal = document.getElementById('invoiceForm');
                if (invoiceModal && invoiceModal.style.display !== 'none') {
                    toggleInvoiceForm();
                }
            }
        });

        // Update due date when issue date changes
        const issueDateInput = document.getElementById('issue_date');
        if (issueDateInput) {
            issueDateInput.addEventListener('change', function () {
                const issueDate = new Date(this.value);
                if (issueDate) {
                    const dueDate = new Date(issueDate);
                    dueDate.setDate(dueDate.getDate() + 30);

                    const dueDateInput = document.getElementById('due_date');
                    dueDateInput.value = dueDate.toISOString().split('T')[0];
                }
            });
        }

        // Form validation before submit
        const form = document.getElementById('invoiceFormElement');
        if (form) {
            form.addEventListener('submit', function (e) {
                const totalAmount = parseFloat(document.getElementById('amount').value);
                if (totalAmount <= 0) {
                    e.preventDefault();
                    alert('Please add at least one item with a valid rate.');
                    return false;
                }
            });
        }
    });
</script>
<?php include "includes/footer.php"; ?>