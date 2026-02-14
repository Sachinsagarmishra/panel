<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

// Handle form submission
if ($_POST) {
    $website_link = trim($_POST['website_link']);
    $username_email = trim($_POST['username_email']);
    $password = trim($_POST['password']);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO passwords (website_link, username_email, password) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$website_link, $username_email, $password]);
        $success = "Password saved successfully!";

        // Redirect to avoid form resubmission
        header("Location: passwords.php?success=" . urlencode($success));
        exit;

    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all passwords
try {
    $passwordsStmt = $pdo->query("SELECT * FROM passwords ORDER BY created_at DESC");
    $passwords = $passwordsStmt->fetchAll();

    // Calculate statistics
    $totalPasswords = count($passwords);
    $thisMonthPasswords = count(array_filter($passwords, function ($pass) {
        return strpos($pass['created_at'], date('Y-m')) === 0;
    }));

} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<?php
$page_title = 'Password Manager';
include 'includes/header.php';
?>
<header class="header fade-in">
    <div>
        <h1>🔐 Password Manager</h1>
        <p>Securely store and manage your passwords</p>
    </div>
    <button onclick="togglePasswordForm()" class="btn btn-primary">
        <span>➕</span>
        <span>Add Password</span>
    </button>
</header>

<!-- Statistics Cards -->
<div class="stats-grid fade-in">
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-title">Total Passwords</div>
                <div class="stat-value"><?php echo $totalPasswords; ?></div>
            </div>
            <div class="stat-icon">🔐</div>
        </div>
        <div class="stat-change neutral">
            <span>🔒</span>
            <span>Stored securely</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-title">Added This Month</div>
                <div class="stat-value"><?php echo $thisMonthPasswords; ?></div>
            </div>
            <div class="stat-icon">📅</div>
        </div>
        <div class="stat-change positive">
            <span>🆕</span>
            <span>New entries</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-title">Security Status</div>
                <div class="stat-value" style="font-size: 1.5rem; color: #059669;">Safe</div>
            </div>
            <div class="stat-icon">🛡️</div>
        </div>
        <div class="stat-change positive">
            <span>🔒</span>
            <span>Encrypted</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-title">Quick Access</div>
                <div class="stat-value" style="font-size: 1.5rem;">Ready</div>
            </div>
            <div class="stat-icon">⚡</div>
        </div>
        <div class="stat-change positive">
            <span>🚀</span>
            <span>Available</span>
        </div>
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

<!-- Password Form Modal -->
<div id="passwordForm" class="form-modal" style="display: none;">
    <div class="form-content">
        <div class="form-header">
            <h2>🔐 Add New Password</h2>
            <button type="button" onclick="togglePasswordForm()" class="close-btn">✕</button>
        </div>

        <form method="POST" id="passwordFormElement">
            <div class="form-grid" style="grid-template-columns: 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label" for="website_link">Website Link *</label>
                    <input type="url" id="website_link" name="website_link" class="form-input"
                        placeholder="https://example.com" required>
                    <small style="color: #64748b; font-size: 0.75rem;">
                        💡 Include https:// for proper validation
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="username_email">Username / Email *</label>
                    <input type="text" id="username_email" name="username_email" class="form-input"
                        placeholder="username or email@example.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password *</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" class="form-input"
                            placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    <div style="margin-top: 0.5rem;">
                        <button type="button" onclick="generatePassword()" class="btn btn-secondary"
                            style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                            🎲 Generate Strong Password
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" onclick="togglePasswordForm()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">🔐 Save Password</button>
            </div>
        </form>
    </div>
</div>

<!-- Passwords Table -->
<div class="table-container fade-in">
    <div class="table-header">
        <div class="table-title">
            <span>🔐</span>
            <span>Stored Passwords (<?php echo count($passwords); ?>)</span>
        </div>
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Search passwords..." class="search-input">
            <i class="fas fa-search search-icon"></i>
        </div>
    </div>

    <table class="table" id="passwordsTable">
        <thead>
            <tr>
                <th>Website</th>
                <th>Username/Email</th>
                <th>Password</th>
                <th>Added Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($passwords)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #64748b; padding: 3rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🔐</div>
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">No passwords stored yet</div>
                        <div>Add your first password to get started!</div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($passwords as $pass): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div class="website-favicon">
                                    🌐
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #1e293b;">
                                        <a href="<?php echo htmlspecialchars($pass['website_link']); ?>" target="_blank"
                                            style="color: inherit; text-decoration: none;">
                                            <?php
                                            $domain = parse_url($pass['website_link'], PHP_URL_HOST);
                                            echo htmlspecialchars($domain ?: $pass['website_link']);
                                            ?>
                                        </a>
                                    </div>
                                    <div style="color: #64748b; font-size: 0.75rem;">
                                        Click to visit
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500; color: #1e293b;">
                                <?php echo htmlspecialchars($pass['username_email']); ?>
                            </div>
                            <button
                                onclick="copyToClipboard('<?php echo htmlspecialchars($pass['username_email']); ?>', 'username')"
                                class="copy-btn" title="Copy Username">
                                📋 Copy
                            </button>
                        </td>
                        <td>
                            <div class="password-field">
                                <span class="password-hidden" id="password-<?php echo $pass['id']; ?>">
                                    ••••••••••
                                </span>
                                <span class="password-visible" id="password-full-<?php echo $pass['id']; ?>"
                                    style="display: none;">
                                    <?php echo htmlspecialchars($pass['password']); ?>
                                </span>
                                <div style="margin-top: 0.5rem;">
                                    <button onclick="togglePassword(<?php echo $pass['id']; ?>)" class="copy-btn">
                                        👁️ Show
                                    </button>
                                    <button
                                        onclick="copyToClipboard('<?php echo htmlspecialchars($pass['password']); ?>', 'password')"
                                        class="copy-btn" title="Copy Password">
                                        📋 Copy
                                    </button>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 0.875rem;">
                                <div style="font-weight: 600; color: #1e293b;">
                                    <?php echo date('M j, Y', strtotime($pass['created_at'])); ?>
                                </div>
                                <div style="color: #64748b;">
                                    <?php echo date('g:i A', strtotime($pass['created_at'])); ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <button onclick="editPassword(<?php echo $pass['id']; ?>)" class="action-btn btn-secondary"
                                    title="Edit Password">
                                    ✏️
                                </button>
                                <button
                                    onclick="deletePassword(<?php echo $pass['id']; ?>, '<?php echo htmlspecialchars($domain ?: $pass['website_link']); ?>')"
                                    class="action-btn btn-danger" title="Delete Password">
                                    🗑️
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

    /* Password Toggle */
    .password-toggle {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #64748b;
        cursor: pointer;
        padding: 0.5rem;
    }

    .password-toggle:hover {
        color: #1e293b;
    }

    /* Website Favicon */
    .website-favicon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    /* Copy Button */
    .copy-btn {
        background: #e2e8f0;
        border: none;
        border-radius: 6px;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
        margin-right: 0.5rem;
    }

    .copy-btn:hover {
        background: #cbd5e1;
    }

    /* Search Container */
    .search-container {
        position: relative;
        width: 300px;
    }

    .search-input {
        width: 100%;
        padding: 0.5rem 1rem 0.5rem 2.5rem;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.875rem;
    }

    .search-input:focus {
        outline: none;
        border-color: #6366f1;
    }

    .search-icon {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 0.875rem;
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

    /* Logout Item */
    .logout-item {
        color: #ef4444 !important;
        transition: all 0.2s;
    }

    .logout-item:hover {
        background: #fee2e2 !important;
        color: #dc2626 !important;
    }

    @media (max-width: 768px) {
        .search-container {
            width: 100%;
            margin-top: 1rem;
        }

        .table-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<script>
    function togglePasswordForm() {
        const form = document.getElementById('passwordForm');
        const isVisible = form.style.display !== 'none';

        if (isVisible) {
            form.style.display = 'none';
            document.body.style.overflow = 'auto';
            resetForm();
        } else {
            form.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    function resetForm() {
        document.getElementById('passwordFormElement').reset();
        const toggleIcon = document.getElementById('toggleIcon');
        const passwordInput = document.getElementById('password');
        passwordInput.type = 'password';
        toggleIcon.className = 'fas fa-eye';
    }

    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.className = 'fas fa-eye-slash';
        } else {
            passwordInput.type = 'password';
            toggleIcon.className = 'fas fa-eye';
        }
    }

    function generatePassword() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        let password = '';
        for (let i = 0; i < 16; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('password').value = password;
    }

    function togglePassword(id) {
        const hiddenSpan = document.getElementById('password-' + id);
        const visibleSpan = document.getElementById('password-full-' + id);
        const button = event.target;

        if (hiddenSpan.style.display === 'none') {
            hiddenSpan.style.display = 'inline';
            visibleSpan.style.display = 'none';
            button.innerHTML = '👁️ Show';
        } else {
            hiddenSpan.style.display = 'none';
            visibleSpan.style.display = 'inline';
            button.innerHTML = '🙈 Hide';
        }
    }

    function copyToClipboard(text, type) {
        navigator.clipboard.writeText(text).then(function () {
            // Show temporary success message
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '✅ Copied!';
            btn.style.background = '#10b981';
            btn.style.color = 'white';

            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.background = '#e2e8f0';
                btn.style.color = 'inherit';
            }, 2000);
        });
    }

    function editPassword(id) {
        alert('Edit functionality - to be implemented for ID: ' + id);
    }

    function deletePassword(passwordId, website) {
        createDeleteModal(passwordId, website);
    }

    function createDeleteModal(passwordId, website) {
        const existingModal = document.getElementById('deleteModal');
        if (existingModal) {
            existingModal.remove();
        }

        const modalHTML = `
                <div id="deleteModal" class="form-modal">
                    <div class="form-content" style="max-width: 500px;">
                        <div class="form-header">
                            <h2>⚠️ Delete Password</h2>
                            <button type="button" onclick="closeDeleteModal()" class="close-btn">✕</button>
                        </div>
                        
                        <div style="padding: 2rem;">
                            <p><strong>Website:</strong> ${website}</p>
                            <br>
                            <p>⚠️ <strong>Warning:</strong> This will permanently delete:</p>
                            <ul style="margin: 1rem 0; padding-left: 1.5rem; color: #dc2626;">
                                <li>Login credentials</li>
                                <li>Password information</li>
                                <li>Access details</li>
                            </ul>
                            <p><strong>This action cannot be undone!</strong></p>
                            
                            <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
                                <button onclick="closeDeleteModal()" class="btn btn-secondary">❌ Cancel</button>
                                <button onclick="confirmDelete(${passwordId})" class="btn btn-danger">🗑️ Delete Forever</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

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

    function confirmDelete(passwordId) {
        window.location.href = `delete-password.php?id=${passwordId}`;
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase();
        const table = document.getElementById('passwordsTable');
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

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
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('form-modal')) {
                togglePasswordForm();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                togglePasswordForm();
                closeDeleteModal();
            }
        });
    });
</script>
</body>

</html>