<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Mobile Header -->
<div class="mobile-header">
    <div class="logo">
        <div class="logo-icon">
            <img src="assets/Sachin.png" alt="Logo Icon" />
        </div>
        <div class="logo-text">Sachindesign</div>
    </div>
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
</div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="logo d-none-mobile">
        <div class="logo-icon">
            <img src="assets/Sachin.png" alt="Logo Icon" />
        </div>
        <div class="logo-text">Sachindesign</div>
    </div>

    <div class="nav-section">
        <div class="nav-title">Overview</div>
        <a href="index.php" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
            <span class="nav-item-icon"><i class="fas fa-chart-bar"></i></span>
            <span>Dashboard</span>
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-title">Client Management</div>
        <a href="clients.php" class="nav-item <?php echo $current_page == 'clients.php' ? 'active' : ''; ?>">
            <span class="nav-item-icon"><i class="fa-regular fa-circle-user"></i></span>
            <span>Clients</span>
        </a>
        <a href="projects.php" class="nav-item <?php echo $current_page == 'projects.php' ? 'active' : ''; ?>">
            <span class="nav-item-icon"><i class="fa-regular fa-copy"></i></span>
            <span>Projects</span>
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-title">Business</div>
        <a href="invoices.php"
            class="nav-item <?php echo $current_page == 'invoices.php' || $current_page == 'create-paymentlink.php' ? 'active' : ''; ?>">
            <span class="nav-item-icon"><i class="fa-regular fa-chess-king"></i></span>
            <span>Invoices</span>
        </a>
        <a href="paymentlink.php" class="nav-item <?php echo $current_page == 'paymentlink.php' ? 'active' : ''; ?>">
            <span class="nav-item-icon"><i class="fa-solid fa-link"></i></span>
            <span>Payment Link</span>
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-title">Security</div>
        <a href="passwords.php" class="nav-item <?php echo $current_page == 'passwords.php' ? 'active' : ''; ?>">
            <span class="nav-item-icon"><i class="fa-regular fa-face-grin"></i></span>
            <span>Passwords</span>
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-title">Settings</div>
        <a href="bank-accounts.php"
            class="nav-item <?php echo $current_page == 'bank-accounts.php' ? 'active' : ''; ?>">
            <span class="nav-item-icon"><i class="fa-solid fa-bank"></i></span>
            <span>Bank Accounts</span>
        </a>
        <a href="paypal-methods.php"
            class="nav-item <?php echo $current_page == 'paypal-methods.php' ? 'active' : ''; ?>">
            <span class="nav-item-icon"><i class="fab fa-paypal"></i></span>
            <span>PayPal</span>
        </a>
        <a href="upi-methods.php" class="nav-item <?php echo $current_page == 'upi-methods.php' ? 'active' : ''; ?>">
            <span class="nav-item-icon"><i class="fas fa-qrcode"></i></span>
            <span>UPI Methods</span>
        </a>
        <a href="currencies.php" class="nav-item <?php echo $current_page == 'currencies.php' ? 'active' : ''; ?>">
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (menuToggle && sidebar && overlay) {
            menuToggle.addEventListener('click', function () {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });

            overlay.addEventListener('click', function () {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }
    });
</script>