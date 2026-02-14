<?php
// This script can be called via Cron or manually
require_once __DIR__ . '/config/database.php';

function generateRecurringInvoices($pdo)
{
    echo "Checking for recurring invoices to generate...<br>";
    $today = date('Y-m-d');

    // 1. Get all active recurring templates due today or earlier
    $stmt = $pdo->prepare("SELECT * FROM recurring_invoices WHERE next_date <= ? AND status = 'active'");
    $stmt->execute([$today]);
    $overdue_recurring = $stmt->fetchAll();

    foreach ($overdue_recurring as $r) {
        try {
            $pdo->beginTransaction();

            // 2. Generate Invoice Number (e.g., INV-RECUR-ID-DATE)
            $inv_no = "INV-R-" . $r['id'] . "-" . date('ymd', strtotime($r['next_date']));

            // 3. Insert into invoices table
            $ins = $pdo->prepare("
                INSERT INTO invoices (
                    invoice_number, client_id, project_id, issue_date, due_date, 
                    amount, currency, payment_mode, bank_account, paypal_account, upi_account, 
                    notes, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Unpaid')
            ");

            $due_date = date('Y-m-d', strtotime($r['next_date'] . ' + 7 days')); // Default 7 days due date

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
            $next_date = $r['next_date'];
            switch ($r['frequency']) {
                case 'weekly':
                    $next_date = date('Y-m-d', strtotime($next_date . ' + 1 week'));
                    break;
                case 'monthly':
                    $next_date = date('Y-m-d', strtotime($next_date . ' + 1 month'));
                    break;
                case 'yearly':
                    $next_date = date('Y-m-d', strtotime($next_date . ' + 1 year'));
                    break;
            }

            $upd = $pdo->prepare("UPDATE recurring_invoices SET next_date = ?, last_generated_date = ? WHERE id = ?");
            $upd->execute([$next_date, $today, $r['id']]);

            $pdo->commit();
            echo "SUCCESS: Generated invoice $inv_no for Client ID {$r['client_id']}<br>";

        } catch (Exception $e) {
            $pdo->rollBack();
            echo "ERROR: Failed to generate invoice for recurring ID {$r['id']}: " . $e->getMessage() . "<br>";
        }
    }
    echo "Done.";
}

// If accessed directly, run the function
if (php_sapi_name() !== 'cli' || basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    generateRecurringInvoices($pdo);
}
?>