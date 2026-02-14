-- 1. Create PayPal Methods Table
CREATE TABLE IF NOT EXISTS paypal_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    link VARCHAR(255),
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Create UPI Methods Table
CREATE TABLE IF NOT EXISTS upi_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_name VARCHAR(255) NOT NULL,
    upi_id VARCHAR(255) NOT NULL,
    qr_code VARCHAR(255),
    bank_details TEXT,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Add columns to Invoices Table (Run only if columns don't exist)
-- Note: Running this on an existing table with these columns might cause an error, but ignore if columns exist.
ALTER TABLE invoices ADD COLUMN paypal_account INT NULL AFTER bank_account;
ALTER TABLE invoices ADD COLUMN upi_account INT NULL AFTER paypal_account;

-- 4. Add Constraints (Optional but good for integrity)
-- ALTER TABLE invoices ADD FOREIGN KEY (paypal_account) REFERENCES paypal_methods(id) ON DELETE SET NULL;
-- ALTER TABLE invoices ADD FOREIGN KEY (upi_account) REFERENCES upi_methods(id) ON DELETE SET NULL;
