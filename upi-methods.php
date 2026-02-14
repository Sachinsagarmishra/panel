<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

// Handle form submission
if ($_POST) {
    $account_name = trim($_POST['account_name']);
    $upi_id = trim($_POST['upi_id']);
    $bank_details = trim($_POST['bank_details']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    $qr_code_path = $_POST['existing_qr_code'] ?? '';

    // Handle QR Code upload
    if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/qr_codes/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = pathinfo($_FILES['qr_code']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('upi_qr_') . '.' . $file_extension;
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['qr_code']['tmp_name'], $target_path)) {
            $qr_code_path = $target_path;
        }
    }

    try {
        if (isset($_POST['method_id']) && $_POST['method_id']) {
            // Update existing method
            if ($is_default) {
                $pdo->exec("UPDATE upi_methods SET is_default = FALSE");
            }

            $stmt = $pdo->prepare("
                UPDATE upi_methods SET 
                account_name = ?, upi_id = ?, qr_code = ?, bank_details = ?, is_default = ?
                WHERE id = ?
            ");
            $stmt->execute([$account_name, $upi_id, $qr_code_path, $bank_details, $is_default, $_POST['method_id']]);
            $success = "UPI method updated successfully!";
        } else {
            // Add new method
            if ($is_default) {
                $pdo->exec("UPDATE upi_methods SET is_default = FALSE");
            }

            $stmt = $pdo->prepare("
                INSERT INTO upi_methods (account_name, upi_id, qr_code, bank_details, is_default) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$account_name, $upi_id, $qr_code_path, $bank_details, $is_default]);
            $success = "UPI method added successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all UPI methods
try {
    $methodsStmt = $pdo->query("SELECT * FROM upi_methods ORDER BY is_default DESC, account_name ASC");
    $upiMethods = $methodsStmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<?php
$page_title = 'UPI Methods';
include 'includes/header.php';
?>
<header class="header fade-in">
    <div>
        <h1>UPI Methods</h1>
        <p>Manage your UPI and QR Code details</p>
    </div>
    <button onclick="toggleMethodForm()" class="btn btn-primary">
        <span>Add UPI Method</span>
    </button>
</header>

<!-- Success/Error Messages -->
<?php if (isset($success)): ?>
    <div class="alert alert-success">
        ✅
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-error">
        ❌
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- UPI Method Form -->
<div id="methodForm" class="form-modal" style="display: none;">
    <div class="form-content">
        <div class="form-header">
            <h2 id="formTitle">Add UPI Method</h2>
            <button onclick="toggleMethodForm()" class="close-btn">✕</button>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="method_id" id="method_id">
            <input type="hidden" name="existing_qr_code" id="existing_qr_code">

            <div class="form-group">
                <label class="form-label">Account Name (Internal) *</label>
                <input type="text" name="account_name" id="account_name" class="form-input" required
                    placeholder="e.g. My Business UPI">
            </div>

            <div class="form-group">
                <label class="form-label">UPI ID *</label>
                <input type="text" name="upi_id" id="upi_id" class="form-input" required placeholder="name@upi">
            </div>

            <div class="form-group">
                <label class="form-label">QR Code Image</label>
                <input type="file" name="qr_code" id="qr_code" class="form-input" accept="image/*">
                <div id="qr_preview_container" style="margin-top: 0.5rem; display: none;">
                    <img id="qr_preview" src="" style="max-width: 100px; border-radius: 8px;">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Bank Details (to show under UPI) *</label>
                <textarea name="bank_details" id="bank_details" class="form-textarea" required
                    placeholder="Bank Name, Account Number, IFSC etc."></textarea>
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;">
                <input type="checkbox" name="is_default" id="is_default" style="width: auto;">
                <label for="is_default" style="margin-bottom: 0;">Set as default method</label>
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary">Save UPI Method</button>
                <button type="button" onclick="toggleMethodForm()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- UPI Methods Grid -->
<div class="dashboard-grid fade-in">
    <?php if (empty($upiMethods)): ?>
        <div class="empty-state">
            <div class="empty-text">No UPI methods added yet!</div>
        </div>
    <?php else: ?>
        <?php foreach ($upiMethods as $method): ?>
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div>
                        <h3 style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-qrcode" style="color: #6366f1;"></i>
                            <?php echo htmlspecialchars($method['account_name']); ?>
                            <?php if ($method['is_default']): ?>
                                <span class="status-mini done" style="font-size: 0.6rem;">DEFAULT</span>
                            <?php endif; ?>
                        </h3>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button onclick='editMethod(<?php echo json_encode($method); ?>)' class="btn btn-secondary"
                            style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Edit</button>
                        <a href="delete-upi-method.php?id=<?php echo $method['id']; ?>"
                            onclick="return confirm('Are you sure you want to delete this UPI method?')" class="btn btn-error"
                            style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: #fee2e2; color: #dc2626;">Delete</a>
                    </div>
                </div>
                <div class="dashboard-card-content" style="padding: 1.5rem; display: flex; gap: 1.5rem;">
                    <?php if ($method['qr_code']): ?>
                        <div style="flex-shrink: 0;">
                            <img src="<?php echo htmlspecialchars($method['qr_code']); ?>"
                                style="width: 100px; height: 100px; object-fit: contain; border: 1px solid #e2e8f0; border-radius: 8px;">
                        </div>
                    <?php endif; ?>
                    <div style="flex: 1;">
                        <div style="margin-bottom: 1rem;">
                            <div
                                style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">
                                UPI ID</div>
                            <div style="font-weight: 500;">
                                <?php echo htmlspecialchars($method['upi_id']); ?>
                            </div>
                        </div>
                        <div>
                            <div
                                style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">
                                Bank Details</div>
                            <div style="font-size: 0.85rem; line-height: 1.4; color: #475569;">
                                <?php echo nl2br(htmlspecialchars($method['bank_details'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
    .form-modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1002;
        padding: 1rem;
    }

    .form-content {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        width: 100%;
        max-width: 550px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .form-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #64748b;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .form-textarea {
        width: 100%;
        min-height: 100px;
        padding: 0.875rem;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-family: inherit;
        resize: vertical;
    }
</style>

<script>
    function toggleMethodForm() {
        const form = document.getElementById('methodForm');
        if (form.style.display === 'none') {
            form.style.display = 'flex';
            document.getElementById('formTitle').innerText = 'Add UPI Method';
            document.getElementById('method_id').value = '';
            document.getElementById('upi_id').value = '';
            document.getElementById('account_name').value = '';
            document.getElementById('bank_details').value = '';
            document.getElementById('existing_qr_code').value = '';
            document.getElementById('qr_preview_container').style.display = 'none';
            document.getElementById('is_default').checked = false;
        } else {
            form.style.display = 'none';
        }
    }

    function editMethod(method) {
        document.getElementById('methodForm').style.display = 'flex';
        document.getElementById('formTitle').innerText = 'Edit UPI Method';
        document.getElementById('method_id').value = method.id;
        document.getElementById('account_name').value = method.account_name;
        document.getElementById('upi_id').value = method.upi_id;
        document.getElementById('bank_details').value = method.bank_details;
        document.getElementById('existing_qr_code').value = method.qr_code;

        if (method.qr_code) {
            document.getElementById('qr_preview').src = method.qr_code;
            document.getElementById('qr_preview_container').style.display = 'block';
        } else {
            document.getElementById('qr_preview_container').style.display = 'none';
        }

        document.getElementById('is_default').checked = method.is_default == 1;
    }
</script>

<?php include 'includes/footer.php'; ?>