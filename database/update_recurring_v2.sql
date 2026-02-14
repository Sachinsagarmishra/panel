-- Add source_invoice_id to recurring_invoices to track base invoices
ALTER TABLE recurring_invoices ADD COLUMN source_invoice_id INT NULL;
ALTER TABLE recurring_invoices ADD CONSTRAINT fk_source_invoice FOREIGN KEY (source_invoice_id) REFERENCES invoices(id) ON DELETE SET NULL;
