<?php
require_once '../config/database.php';

try {
    $sql = file_get_contents('bucket_list.sql');
    $pdo->exec($sql);
    echo "Bucket List tables created successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>