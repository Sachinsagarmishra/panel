<?php
require_once __DIR__ . '/auth.php';

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    // Store current page for redirect after login
    $currentPage = $_SERVER['REQUEST_URI'];
    $loginUrl = 'login.php';
    
    // Add redirect parameter if not login page itself
    if (!str_contains($currentPage, 'login.php')) {
        $loginUrl .= '?redirect=' . urlencode($currentPage);
    }
    
    header('Location: ' . $loginUrl);
    exit;
}

// Get current user data
$currentUser = $auth->getCurrentUser();

// Function to check user role
function hasRole($role) {
    global $currentUser;
    return $currentUser && $currentUser['role'] === $role;
}

// Function to check if user is admin
function isAdmin() {
    return hasRole('admin');
}

// Function to require admin access
function requireAdmin() {
    if (!isAdmin()) {
        header('HTTP/1.0 403 Forbidden');
        die('Access denied. Admin privileges required.');
    }
}
?>