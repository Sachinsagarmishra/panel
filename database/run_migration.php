<?php
require_once '../config/database.php';

try {
    $sql = file_get_contents('recurring_invoices.sql');
    $pdo->exec($sql);
    echo "Recurring Invoices tables created successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>