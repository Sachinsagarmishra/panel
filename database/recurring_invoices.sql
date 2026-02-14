-- Create Recurring Invoices Table
CREATE TABLE IF NOT EXISTS recurring_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    project_id INT NULL,
    frequency VARCHAR(50) DEFAULT 'monthly', -- monthly, weekly, yearly
    next_date DATE NOT NULL,
    last_generated_date DATE NULL,
    amount DECIMAL(15, 2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USD',
    payment_mode VARCHAR(50),
    bank_account INT NULL,
    paypal_account INT NULL,
    upi_account INT NULL,
    notes TEXT,
    status ENUM('active', 'paused', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- Create Recurring Invoice Items Table
CREATE TABLE IF NOT EXISTS recurring_invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recurring_id INT NOT NULL,
    description TEXT NOT NULL,
    quantity DECIMAL(10, 2) DEFAULT 1,
    rate DECIMAL(15, 2) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (recurring_id) REFERENCES recurring_invoices(id) ON DELETE CASCADE
);
