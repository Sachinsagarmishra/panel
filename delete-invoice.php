<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header("Location: invoices.php");
    exit;
}

$invoiceId = $_GET['id'];

try {
    // Start transaction for safety
    $pdo->beginTransaction();
    
    // First, delete all invoice items
    $deleteItemsStmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
    $deleteItemsStmt->execute([$invoiceId]);
    
    // Then delete the invoice
    $deleteInvoiceStmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
    $deleteInvoiceStmt->execute([$invoiceId]);
    
    // Check if invoice was actually deleted
    if ($deleteInvoiceStmt->rowCount() > 0) {
        $pdo->commit();
        header("Location: invoices.php?success=Invoice deleted successfully");
    } else {
        $pdo->rollback();
        header("Location: invoices.php?error=Invoice not found or already deleted");
    }
    
} catch(PDOException $e) {
    $pdo->rollback();
    header("Location: invoices.php?error=" . urlencode("Error deleting invoice: " . $e->getMessage()));
}

exit;
?>
