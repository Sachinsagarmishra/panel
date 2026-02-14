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
    $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE id = ?");
    $stmt->execute([$statusMap[$status], $invoiceId]);
    
    header("Location: invoices.php?success=Invoice status updated successfully");
    exit;
    
} catch(PDOException $e) {
    header("Location: invoices.php?error=" . urlencode("Error updating status: " . $e->getMessage()));
    exit;
}
?>