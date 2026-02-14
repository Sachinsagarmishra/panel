-- Add payment_date column to invoices table
ALTER TABLE invoices ADD COLUMN payment_date DATETIME NULL AFTER status;

-- Update existing Paid invoices to have payment_date same as created_at (or updated_at if available) as a fallback
UPDATE invoices SET payment_date = created_at WHERE status = 'Paid' AND payment_date IS NULL;
