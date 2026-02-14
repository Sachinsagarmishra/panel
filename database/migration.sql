-- Create PayPal Methods Table
CREATE TABLE IF NOT EXISTS paypal_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    link VARCHAR(255),
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create UPI Methods Table
CREATE TABLE IF NOT EXISTS upi_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_name VARCHAR(255) NOT NULL,
    upi_id VARCHAR(255) NOT NULL,
    qr_code VARCHAR(255),
    bank_details TEXT,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add columns to Invoices Table
ALTER TABLE invoices ADD COLUMN paypal_account INT NULL AFTER bank_account;
ALTER TABLE invoices ADD COLUMN upi_account INT NULL AFTER paypal_account;
