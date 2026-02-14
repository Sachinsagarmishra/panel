<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header("Location: bank-accounts.php");
    exit;
}

$accountId = $_GET['id'];

try {
    // Remove default from all accounts
    $pdo->exec("UPDATE bank_accounts SET is_default = FALSE");
    
    // Set new default
    $stmt = $pdo->prepare("UPDATE bank_accounts SET is_default = TRUE WHERE id = ?");
    $stmt->execute([$accountId]);
    
    header("Location: bank-accounts.php?success=Default account updated successfully");
    exit;
    
} catch(PDOException $e) {
    header("Location: bank-accounts.php?error=" . urlencode("Error updating default account: " . $e->getMessage()));
    exit;
}
?>