<nav class="sidebar">
    <div class="logo">
        <div class="logo-icon">F</div>
        <div class="logo-text">FreelancePro</div>
    </div>
    
    <div class="nav-section">
        <div class="nav-title">Overview</div>
        <a href="index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <span class="nav-item-icon">📊</span>
            <span>Dashboard</span>
        </a>
    </div>
    
    <div class="nav-section">
        <div class="nav-title">Client Management</div>
        <a href="clients.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'active' : ''; ?>">
            <span class="nav-item-icon">👥</span>
            <span>Clients</span>
        </a>
        <a href="projects.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'active' : ''; ?>">
            <span class="nav-item-icon">📁</span>
            <span>Projects</span>
        </a>
        <a href="tasks.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : ''; ?>">
            <span class="nav-item-icon">✅</span>
            <span>Tasks</span>
        </a>
    </div>
    
    <div class="nav-section">
        <div class="nav-title">Business</div>
        <a href="proposals.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'proposals.php' ? 'active' : ''; ?>">
            <span class="nav-item-icon">📄</span>
            <span>Proposals</span>
        </a>
        <a href="invoices.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'invoices.php' ? 'active' : ''; ?>">
            <span class="nav-item-icon">💰</span>
            <span>Invoices</span>
        </a>
    </div>
    
    <div class="nav-section">
        <div class="nav-title">Settings</div>
        <a href="bank-accounts.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'bank-accounts.php' ? 'active' : ''; ?>">
            <span class="nav-item-icon">🏦</span>
            <span>Bank Accounts</span>
        </a>
    </div>
</nav>