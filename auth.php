<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function checkAuth() {
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        header("Location: login.php");
        exit;
    }
    
    // Optional: Check session timeout (24 hours)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 86400)) {
        session_destroy();
        header("Location: login.php?error=" . urlencode("Session expired. Please login again."));
        exit;
    }
}

// Function to get current user info
function getCurrentUser() {
    return [
        'username' => $_SESSION['username'] ?? 'User',
        'login_time' => $_SESSION['login_time'] ?? time()
    ];
}
?>