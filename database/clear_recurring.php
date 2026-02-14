<?php
require_once '../config/database.php';

try {
    $pdo->exec("DELETE FROM recurring_invoice_items");
    $pdo->exec("DELETE FROM recurring_invoices");
    echo "Recurring tables cleared successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>