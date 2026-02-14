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
    // Check if this account is being used in any invoices
    $checkUsageStmt = $pdo->prepare("SELECT COUNT(*) as count FROM invoices WHERE bank_account = ?");
    $checkUsageStmt->execute([$accountId]);
    $usageCount = $checkUsageStmt->fetch()['count'];
    
    if ($usageCount > 0) {
        // If account is used in invoices, show warning but still allow deletion
        $warningMessage = "This account is linked to $usageCount invoice(s). Deleting it will remove the bank details from those invoices.";
        
        // You can choose to prevent deletion or allow with warning
        // For now, we'll allow deletion but clear the references
        $updateInvoicesStmt = $pdo->prepare("UPDATE invoices SET bank_account = NULL WHERE bank_account = ?");
        $updateInvoicesStmt->execute([$accountId]);
    }
    
    // Get account details before deletion for confirmation
    $accountStmt = $pdo->prepare("SELECT account_name FROM bank_accounts WHERE id = ?");
    $accountStmt->execute([$accountId]);
    $account = $accountStmt->fetch();
    
    if (!$account) {
        header("Location: bank-accounts.php?error=Account not found");
        exit;
    }
    
    // Delete the bank account
    $deleteStmt = $pdo->prepare("DELETE FROM bank_accounts WHERE id = ?");
    $deleteStmt->execute([$accountId]);
    
    if ($deleteStmt->rowCount() > 0) {
        $successMessage = "Bank account '{$account['account_name']}' deleted successfully";
        if (isset($warningMessage)) {
            $successMessage .= ". Note: $warningMessage";
        }
        header("Location: bank-accounts.php?success=" . urlencode($successMessage));
    } else {
        header("Location: bank-accounts.php?error=Account not found or already deleted");
    }
    
} catch(PDOException $e) {
    header("Location: bank-accounts.php?error=" . urlencode("Error deleting account: " . $e->getMessage()));
}

exit;
?>