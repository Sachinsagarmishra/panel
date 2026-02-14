<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header("Location: passwords.php?error=" . urlencode("Password ID not provided"));
    exit;
}

$passwordId = $_GET['id'];

try {
    // Get password details before deletion
    $passwordStmt = $pdo->prepare("SELECT website_link FROM passwords WHERE id = ?");
    $passwordStmt->execute([$passwordId]);
    $password = $passwordStmt->fetch();
    
    if (!$password) {
        header("Location: passwords.php?error=" . urlencode("Password not found"));
        exit;
    }
    
    // Delete the password
    $deleteStmt = $pdo->prepare("DELETE FROM passwords WHERE id = ?");
    $deleteStmt->execute([$passwordId]);
    
    if ($deleteStmt->rowCount() > 0) {
        $domain = parse_url($password['website_link'], PHP_URL_HOST);
        $successMessage = "Password for '{$domain}' deleted successfully";
        header("Location: passwords.php?success=" . urlencode($successMessage));
    } else {
        header("Location: passwords.php?error=" . urlencode("Password not found or already deleted"));
    }
    
} catch(PDOException $e) {
    header("Location: passwords.php?error=" . urlencode("Error deleting password: " . $e->getMessage()));
}

exit;
?>