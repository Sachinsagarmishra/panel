<?php
// This script can be called via Cron or manually
date_default_timezone_set('Asia/Kolkata');
require_once __DIR__ . '/config/database.php';

function generateRecurringInvoices($pdo)
{
    $today = date('Y-m-d');

    // 1. Get all active recurring templates due today or earlier
    $stmt = $pdo->prepare("SELECT * FROM recurring_invoices WHERE next_date <= ? AND status = 'active'");
    $stmt->execute([$today]);
    $overdue_recurring = $stmt->fetchAll();

    foreach ($overdue_recurring as $r) {
        try {
            $pdo->beginTransaction();

            // 2. Generate Sequential Invoice Number (Standard format)
            $nextInvoiceStmt = $pdo->query("SELECT COUNT(*) + 1 as next_number FROM invoices");
            $nextNumber = $nextInvoiceStmt->fetch()['next_number'];
            $inv_no = 'INV-' . date('Y') . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            // Append -R to identify it as recurring without breaking the sequence if you want, 
            // OR just keep it standard. Let's keep it standard but add a prefix if desired.
            // Actually, to make the filter work, it should have the 'INV-R-' prefix or a specific flag.
            // Let's use 'INV-R-YYYY-XXX' for automated ones.
            $inv_no = 'INV-R-' . date('Y') . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // 3. Insert into invoices table
            $ins = $pdo->prepare("
                INSERT INTO invoices (
                    invoice_number, client_id, project_id, issue_date, due_date, 
                    amount, currency, payment_mode, bank_account, paypal_account, upi_account, 
                    notes, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Unpaid')
            ");

            $due_date = date('Y-m-d', strtotime($r['next_date'] . ' + 5 days'));

            $ins->execute([
                $inv_no,
                $r['client_id'],
                $r['project_id'],
                $r['next_date'],
                $due_date,
                $r['amount'],
                $r['currency'],
                $r['payment_mode'],
                $r['bank_account'],
                $r['paypal_account'],
                $r['upi_account'],
                $r['notes']
            ]);

            $new_invoice_id = $pdo->lastInsertId();

            // 4. Copy Items
            $item_stmt = $pdo->prepare("SELECT * FROM recurring_invoice_items WHERE recurring_id = ?");
            $item_stmt->execute([$r['id']]);
            $items = $item_stmt->fetchAll();

            $ins_item = $pdo->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, rate, amount) VALUES (?, ?, ?, ?, ?)");
            foreach ($items as $item) {
                $ins_item->execute([$new_invoice_id, $item['description'], $item['quantity'], $item['rate'], $item['amount']]);
            }

            // 5. Update next_date in recurring table
            $current_next = is_string($r['next_date']) ? $r['next_date'] : date('Y-m-d');
            $next_date_obj = new DateTime($current_next);

            if ($r['frequency'] == 'weekly') {
                $next_date_obj->modify('+1 week');
            } elseif ($r['frequency'] == 'monthly') {
                $next_date_obj->modify('+1 month');
            } elseif ($r['frequency'] == 'yearly') {
                $next_date_obj->modify('+1 year');
            }
            $final_next_date = $next_date_obj->format('Y-m-d');

            $upd = $pdo->prepare("UPDATE recurring_invoices SET next_date = ?, last_generated_date = ? WHERE id = ?");
            $upd->execute([$final_next_date, $today, $r['id']]);

            $pdo->commit();

        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
}


// If accessed directly, run the function
if (php_sapi_name() !== 'cli' || basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    generateRecurringInvoices($pdo);
}
?>