<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("DESCRIBE invoices");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo implode(", ", $columns);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>