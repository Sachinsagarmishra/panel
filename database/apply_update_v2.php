<?php
require_once '../config/database.php';
try {
    $pdo->exec("ALTER TABLE recurring_invoices ADD COLUMN source_invoice_id INT NULL");
    $pdo->exec("ALTER TABLE recurring_invoices ADD CONSTRAINT fk_source_invoice FOREIGN KEY (source_invoice_id) REFERENCES invoices(id) ON DELETE SET NULL");
    echo "Recurring table updated!";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>