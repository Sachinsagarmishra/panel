<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

// Handle form submission
if ($_POST) {
    $account_name = trim($_POST['account_name']);
    $bank_name = trim($_POST['bank_name']);
    $account_number = trim($_POST['account_number']);
    $ifsc_code = trim($_POST['ifsc_code']);
    $upi_id = trim($_POST['upi_id']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    // Get custom fields
    $custom_fields = [];
    if (isset($_POST['custom_fields']) && is_array($_POST['custom_fields'])) {
        foreach ($_POST['custom_fields'] as $field) {
            if (!empty($field['label']) && !empty($field['value'])) {
                $custom_fields[] = [
                    'label' => trim($field['label']),
                    'value' => trim($field['value'])
                ];
            }
        }
    }
    $custom_fields_json = json_encode($custom_fields);

    try {
        if (isset($_POST['account_id']) && $_POST['account_id']) {
            // Update existing account
            if ($is_default) {
                $pdo->exec("UPDATE bank_accounts SET is_default = FALSE");
            }

            $stmt = $pdo->prepare("
                UPDATE bank_accounts SET 
                account_name = ?, bank_name = ?, account_number = ?, ifsc_code = ?, upi_id = ?, is_default = ?, custom_fields = ?
                WHERE id = ?
            ");
            $stmt->execute([$account_name, $bank_name, $account_number, $ifsc_code, $upi_id, $is_default, $custom_fields_json, $_POST['account_id']]);
            $success = "Bank account updated successfully!";
        } else {
            // Add new account
            if ($is_default) {
                $pdo->exec("UPDATE bank_accounts SET is_default = FALSE");
            }

            $stmt = $pdo->prepare("
                INSERT INTO bank_accounts (account_name, bank_name, account_number, ifsc_code, upi_id, is_default, custom_fields) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$account_name, $bank_name, $account_number, $ifsc_code, $upi_id, $is_default, $custom_fields_json]);
            $success = "Bank account added successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all bank accounts
try {
    $accountsStmt = $pdo->query("SELECT * FROM bank_accounts ORDER BY is_default DESC, account_name ASC");
    $bankAccounts = $accountsStmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<?php
$page_title = 'Bank Accounts';
include 'includes/header.php';
?>
<header class="header fade-in">
    <div>
        <h1>Bank Accounts</h1>
        <p>Manage your business bank accounts</p>
    </div>
    <button onclick="toggleAccountForm()" class="btn btn-primary">
        <span>Add Account</span>
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

<!-- Account Form Modal -->
<div id="accountForm" class="form-modal" style="display: none;">
    <div class="form-content">
        <div class="form-header">
            <h2 id="formTitle">Add Bank Account</h2>
            <button type="button" onclick="toggleAccountForm()" class="close-btn">✕</button>
        </div>

        <form method="POST" id="accountFormElement">
            <input type="hidden" id="account_id" name="account_id">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <div class="form-group">
                        <label class="form-label" for="account_name">Account Name *</label>
                        <input type="text" id="account_name" name="account_name" class="form-input"
                            placeholder="e.g., Business Savings" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="bank_name">Bank Name *</label>
                        <input type="text" id="bank_name" name="bank_name" class="form-input"
                            placeholder="e.g., HDFC Bank" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="account_number">Account Number *</label>
                        <input type="text" id="account_number" name="account_number" class="form-input" required>
                    </div>
                </div>

                <div>
                    <div class="form-group">
                        <label class="form-label" for="ifsc_code">IFSC Code</label>
                        <input type="text" id="ifsc_code" name="ifsc_code" class="form-input"
                            placeholder="e.g., HDFC0001234">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="upi_id">UPI ID</label>
                        <input type="text" id="upi_id" name="upi_id" class="form-input"
                            placeholder="e.g., business@paytm">
                    </div>

                    <div class="form-group">
                        <label
                            style="display: flex; align-items: center; gap: 0.5rem; color: #374151; font-weight: 600;">
                            <input type="checkbox" id="is_default" name="is_default" style="margin: 0;">
                            Set as default account
                        </label>
                    </div>
                </div>
            </div>

            <!-- Custom Fields Section -->
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <label class="form-label">Additional Details</label>
                    <button type="button" onclick="addCustomField()" class="btn btn-secondary"
                        style="padding: 0.5rem 1rem;">
                        ➕ Add Field
                    </button>
                </div>

                <div id="customFieldsContainer">
                    <!-- Custom fields will be added here dynamically -->
                </div>
            </div>

            <div class="form-actions">
                <button type="button" onclick="toggleAccountForm()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Bank Accounts List -->
<div class="stats-grid fade-in">
    <?php if (empty($bankAccounts)): ?>
        <div class="stat-card" style="grid-column: 1 / -1; text-align: center;">
            <div style="font-weight: 600; margin-bottom: 0.5rem;">No bank accounts yet</div>
            <div style="color: #64748b;">Add your first bank account to start receiving payments!</div>
        </div>
    <?php else: ?>
        <?php foreach ($bankAccounts as $account): ?>
            <div class="stat-card">
                <?php if ($account['is_default']): ?>
                    <div style="position: absolute; top: 0.5rem; right: 0.5rem;">
                        <span
                            style="background: #10b981; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                            Default
                        </span>
                    </div>
                <?php endif; ?>

                <div class="stat-header">
                    <div>
                        <div class="stat-title"><?php echo htmlspecialchars($account['account_name']); ?></div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0.5rem 0;">
                            <?php echo htmlspecialchars($account['bank_name']); ?>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 1rem; color: #64748b; font-size: 0.875rem;">
                    <div style="margin-bottom: 0.5rem;">
                        <strong>Account:</strong> ****<?php echo substr($account['account_number'], -4); ?>
                    </div>

                    <?php if ($account['ifsc_code']): ?>
                        <div style="margin-bottom: 0.5rem;">
                            <strong>IFSC:</strong> <?php echo htmlspecialchars($account['ifsc_code']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($account['upi_id']): ?>
                        <div style="margin-bottom: 0.5rem;">
                            <strong>UPI:</strong> <?php echo htmlspecialchars($account['upi_id']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($account['custom_fields']): ?>
                        <?php
                        $customFields = json_decode($account['custom_fields'], true);
                        if (is_array($customFields)):
                            foreach ($customFields as $field):
                                ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong><?php echo htmlspecialchars($field['label']); ?>:</strong>
                                    <?php echo htmlspecialchars($field['value']); ?>
                                </div>
                                <?php
                            endforeach;
                        endif;
                        ?>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button onclick="editAccount(<?php echo htmlspecialchars(json_encode($account)); ?>)"
                        class="btn btn-secondary action-btn" title="Edit Account">
                        <i class="fa-regular fa-pen-to-square"></i>
                    </button>

                    <?php if (!$account['is_default']): ?>
                        <button onclick="setDefault(<?php echo $account['id']; ?>)" class="btn btn-success action-btn"
                            title="Set as Default">
                            <i class="fa-regular fa-sun"></i>
                        </button>
                    <?php endif; ?>

                    <button onclick="viewAccountDetails(<?php echo htmlspecialchars(json_encode($account)); ?>)"
                        class="btn btn-secondary action-btn" title="View Details">
                        <i class="fa-regular fa-eye"></i>
                    </button>

                    <button
                        onclick="deleteAccount(<?php echo $account['id']; ?>, '<?php echo htmlspecialchars($account['account_name']); ?>')"
                        class="btn btn-danger action-btn" title="Delete Account">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</main>
</div>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: linear-gradient(135deg, #9a9a9a00 0%, #00000008 100%);
        padding: 20px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: none;
    }

    .stat-card::before {
        background: linear-gradient(135deg, #9a9a9a 0%, #000000 100%);
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
        width: 90%;
        max-width: 800px;
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

    /* Custom Fields Styles */
    .custom-field {
        display: grid;
        grid-template-columns: 1fr 1fr 50px;
        gap: 1rem;
        align-items: center;
        margin-bottom: 1rem;
        padding: 1rem;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }

    .custom-field:hover {
        background: #f1f5f9;
    }

    .remove-field-btn {
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        cursor: pointer;
        font-size: 0.875rem;
        transition: all 0.2s;
    }

    .remove-field-btn:hover {
        background: #dc2626;
        transform: scale(1.1);
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

    @media (max-width: 768px) {
        .custom-field {
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.4rem !important;
            min-width: 28px;
            font-size: 0.75rem;
        }
    }
</style>

<script>
    let customFieldIndex = 0;

    function toggleAccountForm(account = null) {
        const form = document.getElementById('accountForm');
        const formTitle = document.getElementById('formTitle');
        const formElement = document.getElementById('accountFormElement');

        const isVisible = form.style.display !== 'none';

        if (isVisible) {
            form.style.display = 'none';
            document.body.style.overflow = 'auto';
            resetForm();
        } else {
            form.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            if (account) {
                // Edit mode
                populateForm(account);
                formTitle.textContent = '✏️ Edit Bank Account';
            } else {
                // Add mode
                resetForm();
                formTitle.textContent = '🏦 Add Bank Account';
            }
        }
    }

    function resetForm() {
        document.getElementById('accountFormElement').reset();
        document.getElementById('account_id').value = '';
        document.getElementById('customFieldsContainer').innerHTML = '';
        customFieldIndex = 0;
    }

    function populateForm(account) {
        document.getElementById('account_id').value = account.id;
        document.getElementById('account_name').value = account.account_name;
        document.getElementById('bank_name').value = account.bank_name;
        document.getElementById('account_number').value = account.account_number;
        document.getElementById('ifsc_code').value = account.ifsc_code || '';
        document.getElementById('upi_id').value = account.upi_id || '';
        document.getElementById('is_default').checked = account.is_default == 1;

        // Load custom fields
        const customFieldsContainer = document.getElementById('customFieldsContainer');
        customFieldsContainer.innerHTML = '';
        customFieldIndex = 0;

        if (account.custom_fields) {
            try {
                const customFields = JSON.parse(account.custom_fields);
                if (Array.isArray(customFields)) {
                    customFields.forEach(field => {
                        addCustomField(field.label, field.value);
                    });
                }
            } catch (e) {
                console.error('Error parsing custom fields:', e);
            }
        }
    }

    function addCustomField(label = '', value = '') {
        const container = document.getElementById('customFieldsContainer');
        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'custom-field';
        fieldDiv.innerHTML = `
                <div>
                    <input type="text" name="custom_fields[${customFieldIndex}][label]" 
                           class="form-input" placeholder="Field name (e.g., Swift Code)" 
                           value="${label}" required>
                </div>
                <div>
                    <input type="text" name="custom_fields[${customFieldIndex}][value]" 
                           class="form-input" placeholder="Field value" 
                           value="${value}" required>
                </div>
                <div>
                    <button type="button" class="remove-field-btn" onclick="removeCustomField(this)" title="Remove Field">✕</button>
                </div>
            `;

        container.appendChild(fieldDiv);
        customFieldIndex++;
    }

    function removeCustomField(button) {
        button.closest('.custom-field').remove();
    }

    function editAccount(account) {
        toggleAccountForm(account);
    }

    function setDefault(accountId) {
        if (confirm('Set this as your default bank account?')) {
            window.location.href = `set-default-account.php?id=${accountId}`;
        }
    }

    function viewAccountDetails(account) {
        let details = `Account Details:\n\n`;
        details += `Name: ${account.account_name}\n`;
        details += `Bank: ${account.bank_name}\n`;
        details += `Account Number: ${account.account_number}\n`;

        if (account.ifsc_code) {
            details += `IFSC: ${account.ifsc_code}\n`;
        }

        if (account.upi_id) {
            details += `UPI: ${account.upi_id}\n`;
        }

        if (account.custom_fields) {
            try {
                const customFields = JSON.parse(account.custom_fields);
                if (Array.isArray(customFields) && customFields.length > 0) {
                    details += `\nAdditional Details:\n`;
                    customFields.forEach(field => {
                        details += `${field.label}: ${field.value}\n`;
                    });
                }
            } catch (e) {
                console.error('Error parsing custom fields:', e);
            }
        }

        if (account.is_default == 1) {
            details += `\n⭐ This is your default account`;
        }

        alert(details);
    }

    function deleteAccount(accountId, accountName) {
        createDeleteModal(accountId, accountName);
    }

    function createDeleteModal(accountId, accountName) {
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
                            <div class="delete-title">Delete Bank Account</div>
                        </div>
                        
                        <div class="delete-message">
                            <p><strong>Account:</strong> ${accountName}</p>
                            <br>
                            <p>⚠️ <strong>Warning:</strong> This will permanently delete:</p>
                            <ul style="margin: 1rem 0; padding-left: 1.5rem; color: #dc2626;">
                                <li>Bank account details</li>
                                <li>Associated payment information</li>
                                <li>All custom fields and data</li>
                            </ul>
                            <p><strong>This action cannot be undone!</strong></p>
                        </div>
                        
                        <div class="delete-actions">
                            <button class="delete-cancel-btn" onclick="closeDeleteModal()">
                                ❌ Cancel
                            </button>
                            <button class="delete-confirm-btn" onclick="confirmDelete(${accountId})">
                                🗑️ Delete Forever
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

    function confirmDelete(accountId) {
        // Show loading state
        const confirmBtn = document.querySelector('.delete-confirm-btn');
        const cancelBtn = document.querySelector('.delete-cancel-btn');

        confirmBtn.innerHTML = '⏳ Deleting...';
        confirmBtn.disabled = true;
        cancelBtn.disabled = true;

        // Redirect to delete script
        setTimeout(() => {
            window.location.href = `delete-bank-account.php?id=${accountId}`;
        }, 500);
    }

    // Initialize modal event listeners
    document.addEventListener('DOMContentLoaded', function () {
        // Close modal when clicking outside
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('form-modal')) {
                toggleAccountForm();
            }
            if (e.target.classList.contains('delete-confirmation')) {
                closeDeleteModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const accountModal = document.getElementById('accountForm');
                if (accountModal && accountModal.style.display !== 'none') {
                    toggleAccountForm();
                }
                closeDeleteModal();
            }
        });
    });
</script>
<?php include 'includes/footer.php'; ?>