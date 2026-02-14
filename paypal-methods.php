<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

// Handle form submission
if ($_POST) {
    $account_name = trim($_POST['account_name']);
    $email = trim($_POST['email']);
    $link = trim($_POST['link']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    try {
        if (isset($_POST['method_id']) && $_POST['method_id']) {
            // Update existing method
            if ($is_default) {
                $pdo->exec("UPDATE paypal_methods SET is_default = FALSE");
            }

            $stmt = $pdo->prepare("
                UPDATE paypal_methods SET 
                account_name = ?, email = ?, link = ?, is_default = ?
                WHERE id = ?
            ");
            $stmt->execute([$account_name, $email, $link, $is_default, $_POST['method_id']]);
            $success = "PayPal method updated successfully!";
        } else {
            // Add new method
            if ($is_default) {
                $pdo->exec("UPDATE paypal_methods SET is_default = FALSE");
            }

            $stmt = $pdo->prepare("
                INSERT INTO paypal_methods (account_name, email, link, is_default) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$account_name, $email, $link, $is_default]);
            $success = "PayPal method added successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all PayPal methods
try {
    $methodsStmt = $pdo->query("SELECT * FROM paypal_methods ORDER BY is_default DESC, account_name ASC");
    $paypalMethods = $methodsStmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
    $paypalMethods = [];
}
?>

<?php
$page_title = 'PayPal Methods';
include 'includes/header.php';
?>
<header class="header fade-in">
    <div>
        <h1>PayPal Methods</h1>
        <p>Manage your PayPal payment details</p>
    </div>
    <button onclick="toggleMethodForm()" class="btn btn-primary">
        <span>Add PayPal Method</span>
    </button>
</header>

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

<!-- PayPal Method Form -->
<div id="methodForm" class="form-modal" style="display: none;">
    <div class="form-content">
        <div class="form-header">
            <h2 id="formTitle">Add PayPal Method</h2>
            <button onclick="toggleMethodForm()" class="close-btn">✕</button>
        </div>

        <form method="POST">
            <input type="hidden" name="method_id" id="method_id">

            <div class="form-group">
                <label class="form-label">Account Name (Internal name for identification) *</label>
                <input type="text" name="account_name" id="account_name" class="form-input" required
                    placeholder="e.g. My Business PayPal">
            </div>

            <div class="form-group">
                <label class="form-label">PayPal Email *</label>
                <input type="email" name="email" id="email" class="form-input" required placeholder="email@example.com">
            </div>

            <div class="form-group">
                <label class="form-label">PayPal Link (Optional)</label>
                <input type="url" name="link" id="link" class="form-input" placeholder="https://paypal.me/yourname">
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;">
                <input type="checkbox" name="is_default" id="is_default" style="width: auto;">
                <label for="is_default" style="margin-bottom: 0;">Set as default method</label>
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary">Save PayPal Method</button>
                <button type="button" onclick="toggleMethodForm()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- PayPal Methods Grid -->
<div class="payment-grid-container fade-in">
    <?php if (empty($paypalMethods)): ?>
        <div class="empty-state">
            <div class="empty-text">No PayPal methods added yet!</div>
        </div>
    <?php else: ?>
        <?php foreach ($paypalMethods as $method): ?>
            <div class="payment-card">
                <div class="payment-card-header">
                    <div class="payment-card-title">
                        <div class="payment-card-icon">
                            <i class="fab fa-paypal"></i>
                        </div>
                        <div>
                            <?php echo htmlspecialchars($method['account_name']); ?>
                            <?php if ($method['is_default']): ?>
                                <span class="status-badge default">DEFAULT</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="payment-card-actions">
                        <button onclick='editMethod(<?php echo json_encode($method); ?>)' class="btn btn-secondary"
                            style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Edit</button>
                        <a href="delete-paypal-method.php?id=<?php echo $method['id']; ?>"
                            onclick="return confirm('Are you sure you want to delete this PayPal method?')"
                            class="btn btn-error"
                            style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: #fee2e2; color: #dc2626;">Delete</a>
                    </div>
                </div>

                <div class="payment-card-body" style="flex-direction: column; gap: 0;">
                    <div class="payment-detail-group">
                        <div class="payment-detail-label">PayPal Email</div>
                        <div class="payment-detail-value"><?php echo htmlspecialchars($method['email']); ?></div>
                    </div>
                    <?php if ($method['link']): ?>
                        <div class="payment-detail-group">
                            <div class="payment-detail-label">PayPal Link</div>
                            <div class="payment-detail-value">
                                <a href="<?php echo htmlspecialchars($method['link']); ?>" target="_blank"
                                    style="color: #3b82f6; text-decoration: none;">
                                    <?php echo htmlspecialchars($method['link']); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    function toggleMethodForm() {
        const form = document.getElementById('methodForm');
        if (form.style.display === 'none') {
            form.style.display = 'flex';
            document.getElementById('formTitle').innerText = 'Add PayPal Method';
            document.getElementById('method_id').value = '';
            document.getElementById('account_name').value = '';
            document.getElementById('email').value = '';
            document.getElementById('link').value = '';
            document.getElementById('is_default').checked = false;
        } else {
            form.style.display = 'none';
        }
    }

    function editMethod(method) {
        document.getElementById('methodForm').style.display = 'flex';
        document.getElementById('formTitle').innerText = 'Edit PayPal Method';
        document.getElementById('method_id').value = method.id;
        document.getElementById('account_name').value = method.account_name;
        document.getElementById('email').value = method.email;
        document.getElementById('link').value = method.link;
        document.getElementById('is_default').checked = method.is_default == 1;
    }
</script>

<?php include 'includes/footer.php'; ?>