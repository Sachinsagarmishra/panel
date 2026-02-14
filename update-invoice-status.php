<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    header("Location: invoices.php");
    exit;
}

$invoiceId = $_GET['id'];
$status = $_GET['status'];

// Map status values
$statusMap = [
    'paid' => 'Paid',
    'unpaid' => 'Unpaid',
    'overdue' => 'Overdue'
];

if (!isset($statusMap[$status])) {
    header("Location: invoices.php?error=invalid_status");
    exit;
}

try {
    $newStatus = $statusMap[$status];

    // Check if payment_date column exists
    try {
        $colCheck = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'payment_date'");
        $hasPaymentDate = $colCheck->rowCount() > 0;
    } catch (PDOException $e) {
        $hasPaymentDate = false;
    }

    if ($newStatus === 'Paid' && $hasPaymentDate) {
        // Set payment_date to NOW() when marking as paid
        $stmt = $pdo->prepare("UPDATE invoices SET status = ?, payment_date = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $invoiceId]);
    } else {
        // Just update status
        $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $invoiceId]);
    }

    header("Location: invoices.php?success=Invoice status updated successfully");
    exit;

} catch (PDOException $e) {
    header("Location: invoices.php?error=" . urlencode("Error updating status: " . $e->getMessage()));
    exit;
}
?>