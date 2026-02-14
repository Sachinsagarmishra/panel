<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

// Silent check for recurring invoices
try {
    ob_start();
    include 'cron-recurring.php';
    ob_end_clean();
} catch (Exception $e) {
    // Ignore
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM recurring_invoices WHERE id = ?")->execute([$id]);
        header("Location: recurring-invoices.php?success=Recurring invoice deleted successfully!");
        exit;
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle toggle status
if (isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $current_status = $_GET['current'];
    $new_status = ($current_status == 'active') ? 'paused' : 'active';
    try {
        $pdo->prepare("UPDATE recurring_invoices SET status = ? WHERE id = ?")->execute([$new_status, $id]);
        header("Location: recurring-invoices.php?success=Status updated successfully!");
        exit;
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle form submission
if ($_POST) {
    $recurring_id = $_POST['recurring_id'] ?? null;
    $client_id = $_POST['client_id'];
    $frequency = $_POST['frequency'];
    $next_date = $_POST['next_date'];
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];
    $notes = $_POST['notes'];
    $items = $_POST['items'] ?? [];

    try {
        $pdo->beginTransaction();

        if ($recurring_id) {
            $stmt = $pdo->prepare("UPDATE recurring_invoices SET client_id = ?, frequency = ?, next_date = ?, amount = ?, currency = ?, notes = ? WHERE id = ?");
            $stmt->execute([$client_id, $frequency, $next_date, $amount, $currency, $notes, $recurring_id]);
            $pdo->prepare("DELETE FROM recurring_invoice_items WHERE recurring_id = ?")->execute([$recurring_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO recurring_invoices (client_id, frequency, next_date, amount, currency, notes, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$client_id, $frequency, $next_date, $amount, $currency, $notes]);
            $recurring_id = $pdo->lastInsertId();
        }

        if (!empty($items)) {
            $itemStmt = $pdo->prepare("INSERT INTO recurring_invoice_items (recurring_id, description, quantity, rate, amount) VALUES (?, ?, ?, ?, ?)");
            foreach ($items as $item) {
                if (!empty($item['description'])) {
                    $qty = floatval($item['quantity'] ?: 1);
                    $rate = floatval($item['rate']);
                    $itemStmt->execute([$recurring_id, $item['description'], $qty, $rate, $qty * $rate]);
                }
            }
        }

        $pdo->commit();

        // RUN GENERATOR IMMEDIATELY
        if (function_exists('generateRecurringInvoices')) {
            generateRecurringInvoices($pdo);
        }

        $msg = (isset($_POST['recurring_id']) && $_POST['recurring_id']) ? "updated" : "set up";
        header("Location: recurring-invoices.php?success=Recurring invoice $msg successfully!");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Get recurring invoices with items
try {
    $stmt = $pdo->query("SELECT r.*, c.name as client_name FROM recurring_invoices r JOIN clients c ON r.client_id = c.id ORDER BY r.created_at DESC");
    $recurring_list = $stmt->fetchAll();

    foreach ($recurring_list as &$r) {
        $itemStmt = $pdo->prepare("SELECT * FROM recurring_invoice_items WHERE recurring_id = ?");
        $itemStmt->execute([$r['id']]);
        $r['items'] = $itemStmt->fetchAll();
    }

    $clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll();
    $currencies = $pdo->query("SELECT * FROM currencies WHERE is_active = 1")->fetchAll();
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

$page_title = 'Recurring Invoices';
include 'includes/header.php';
?>

<header class="header fade-in">
    <div>
        <h1>🔄 Recurring Invoices</h1>
        <p>Manage monthly retainer and subscription invoices</p>
    </div>
    <button onclick="toggleRecurringForm()" class="btn btn-primary">
        <span>Setup New Recurring</span>
    </button>
</header>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">✅
        <?php echo htmlspecialchars($_GET['success']); ?>
    </div>
<?php endif; ?>

<div class="table-container fade-in">
    <div class="table-header">
        <div class="table-title">Active Recurring Schedules</div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Client</th>
                <th>Frequency</th>
                <th>Next Date</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recurring_list)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding: 2rem;">No recurring invoices found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($recurring_list as $r): ?>
                    <tr>
                        <td><strong>
                                <?php echo htmlspecialchars($r['client_name']); ?>
                            </strong></td>
                        <td>
                            <?php echo ucfirst($r['frequency']); ?>
                        </td>
                        <td>
                            <?php echo date('M j, Y', strtotime($r['next_date'])); ?>
                        </td>
                        <td>
                            <?php echo $r['amount'] . ' ' . $r['currency']; ?>
                        </td>
                        <td>
                            <span class="status <?php echo $r['status'] == 'active' ? 'active' : 'paused'; ?>">
                                <?php echo ucfirst($r['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <button onclick='editRecurring(<?php echo json_encode($r); ?>)' class="action-btn btn-secondary"
                                    title="Edit">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <a href="?toggle_status=<?php echo $r['id']; ?>&current=<?php echo $r['status']; ?>"
                                    class="action-btn btn-secondary" title="Pause/Resume">
                                    <i class="fas fa-<?php echo $r['status'] == 'active' ? 'pause' : 'play'; ?>"></i>
                                </a>
                                <a href="?delete=<?php echo $r['id']; ?>" class="action-btn btn-danger"
                                    onclick="return confirm('Are you sure?')" title="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Simple Setup Modal (Hidden by default) -->
<div id="recurringForm" class="form-modal" style="display: none;">
    <div class="form-content">
        <div class="form-header">
            <h2 id="modalTitle">Setup Recurring Invoice</h2>
            <button onclick="toggleRecurringForm()" class="close-btn">×</button>
        </div>
        <form method="POST" id="mainForm" style="padding: 2rem;">
            <input type="hidden" name="recurring_id" id="recurring_id">
            <div class="form-group">
                <label class="form-label">Client</label>
                <select name="client_id" class="form-select" required>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?php echo $c['id']; ?>">
                            <?php echo htmlspecialchars($c['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Frequency</label>
                    <select name="frequency" class="form-select">
                        <option value="weekly">Weekly</option>
                        <option value="monthly" selected>Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">First Invoice Date</label>
                    <input type="date" name="next_date" class="form-input" required
                        value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-input" required id="totalAmount">
                </div>
                <div class="form-group">
                    <label class="form-label">Currency</label>
                    <select name="currency" class="form-select">
                        <?php foreach ($currencies as $curr): ?>
                            <option value="<?php echo $curr['code']; ?>">
                                <?php echo $curr['code']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Items Section -->
            <div style="margin-top: 1.5rem;">
                <label class="form-label">Items (Shown on all generated invoices)</label>
                <div id="itemsList">
                    <div class="item-row"
                        style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <input type="text" name="items[0][description]" class="form-input"
                            placeholder="Service description" required>
                        <input type="number" name="items[0][quantity]" class="form-input" placeholder="Qty" value="1"
                            onchange="calcTotal()">
                        <input type="number" name="items[0][rate]" class="form-input" placeholder="Rate"
                            onchange="calcTotal()">
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" style="margin-top: 0.5rem; font-size: 0.75rem;"
                    onclick="addItem()">+ Add Item</button>
            </div>

            <div class="form-actions" style="margin-top: 2rem;">
                <button type="button" onclick="toggleRecurringForm()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Start Recurring</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleRecurringForm() {
        const f = document.getElementById('recurringForm');
        const isOpening = f.style.display === 'none';

        if (isOpening) {
            // Reset form if opening for "New"
            if (!f.dataset.isEditing) {
                document.getElementById('mainForm').reset();
                document.getElementById('recurring_id').value = '';
                document.getElementById('modalTitle').innerText = 'Setup Recurring Invoice';
                document.getElementById('itemsList').innerHTML = `
                    <div class="item-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <input type="text" name="items[0][description]" class="form-input" placeholder="Service description" required>
                        <input type="number" name="items[0][quantity]" class="form-input" placeholder="Qty" value="1" onchange="calcTotal()">
                        <input type="number" name="items[0][rate]" class="form-input" placeholder="Rate" onchange="calcTotal()">
                    </div>
                `;
            }
        } else {
            delete f.dataset.isEditing;
        }

        f.style.display = isOpening ? 'flex' : 'none';
        document.body.style.overflow = isOpening ? 'hidden' : 'auto';
    }

    function editRecurring(data) {
        const f = document.getElementById('recurringForm');
        f.dataset.isEditing = "true";

        document.getElementById('modalTitle').innerText = 'Edit Recurring Invoice';
        document.getElementById('recurring_id').value = data.id;
        document.querySelector('select[name="client_id"]').value = data.client_id;
        document.querySelector('select[name="frequency"]').value = data.frequency;
        document.querySelector('input[name="next_date"]').value = data.next_date;
        document.querySelector('input[name="amount"]').value = data.amount;
        document.querySelector('select[name="currency"]').value = data.currency;

        // Populate Items
        const list = document.getElementById('itemsList');
        list.innerHTML = '';
        if (data.items && data.items.length > 0) {
            data.items.forEach((item, index) => {
                const div = document.createElement('div');
                div.className = 'item-row';
                div.style = 'display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 0.5rem; margin-bottom: 0.5rem;';
                div.innerHTML = `
                    <input type="text" name="items[${index}][description]" class="form-input" placeholder="Service description" value="${item.description}" required>
                    <input type="number" name="items[${index}][quantity]" class="form-input" placeholder="Qty" value="${item.quantity}" onchange="calcTotal()">
                    <input type="number" name="items[${index}][rate]" class="form-input" placeholder="Rate" value="${item.rate}" onchange="calcTotal()">
                `;
                list.appendChild(div);
            });
            itemIndex = data.items.length;
        } else {
            addItem();
        }

        toggleRecurringForm();
    }

    let itemIndex = 1;
    function addItem() {
        const div = document.createElement('div');
        div.className = 'item-row';
        div.style = 'display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 0.5rem; margin-bottom: 0.5rem;';
        div.innerHTML = `
        <input type="text" name="items[${itemIndex}][description]" class="form-input" placeholder="Service description" required>
        <input type="number" name="items[${itemIndex}][quantity]" class="form-input" placeholder="Qty" value="1" onchange="calcTotal()">
        <input type="number" name="items[${itemIndex}][rate]" class="form-input" placeholder="Rate" onchange="calcTotal()">
    `;
        document.getElementById('itemsList').appendChild(div);
        itemIndex++;
    }

    function calcTotal() {
        let total = 0;
        const rows = document.querySelectorAll('.item-row');
        rows.forEach(row => {
            const qty = row.querySelector('input[name*="[quantity]"]').value || 0;
            const rate = row.querySelector('input[name*="[rate]"]').value || 0;
            total += qty * rate;
        });
        document.getElementById('totalAmount').value = total.toFixed(2);
    }
</script>

<style>
    .form-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 2000;
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
    }

    .form-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
    }

    .status {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status.active {
        background: #dcfce7;
        color: #166534;
    }

    .status.paused {
        background: #fee2e2;
        color: #991b1b;
    }
</style>

<?php include 'includes/footer.php'; ?>