<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

try {
    // Create paypal_methods table
    $pdo->exec("CREATE TABLE IF NOT EXISTS paypal_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        account_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        link VARCHAR(255),
        is_default TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create upi_methods table
    $pdo->exec("CREATE TABLE IF NOT EXISTS upi_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        account_name VARCHAR(255) NOT NULL,
        upi_id VARCHAR(255) NOT NULL,
        qr_code VARCHAR(255),
        bank_details TEXT,
        is_default TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Add columns to invoices table
    $pdo->exec("ALTER TABLE invoices ADD COLUMN IF NOT EXISTS paypal_account INT NULL");
    $pdo->exec("ALTER TABLE invoices ADD COLUMN IF NOT EXISTS upi_account INT NULL");

    echo "Migration successful!";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>