<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

// Check if invoice ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Invoice ID not provided');
}

$invoiceId = $_GET['id'];
$type = $_GET['type'] ?? 'html';

try {
    // Get invoice details with client, project, and currency info
    $stmt = $pdo->prepare("
        SELECT i.*, c.name as client_name, c.email as client_email, c.phone as client_phone,
               c.brand_name, p.title as project_title, 
               ba.account_name, ba.bank_name, ba.account_number, ba.ifsc_code, ba.upi_id as bank_upi_id, ba.custom_fields as bank_custom_fields,
               pm.email as paypal_email, pm.link as paypal_link,
               um.upi_id as upi_account_id, um.qr_code as upi_qr_code, um.bank_details as upi_bank_details,
               curr.symbol as currency_symbol, curr.name as currency_name, curr.code as currency_code
        FROM invoices i 
        JOIN clients c ON i.client_id = c.id 
        LEFT JOIN projects p ON i.project_id = p.id
        LEFT JOIN bank_accounts ba ON i.bank_account = ba.id
        LEFT JOIN paypal_methods pm ON i.paypal_account = pm.id
        LEFT JOIN upi_methods um ON i.upi_account = um.id
        LEFT JOIN currencies curr ON i.currency = curr.code
        WHERE i.id = ?
    ");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();
    
    if (!$invoice) {
        die('Invoice not found');
    }
    
    // Get invoice items
    $itemsStmt = $pdo->prepare("
        SELECT * FROM invoice_items 
        WHERE invoice_id = ? 
        ORDER BY id ASC
    ");
    $itemsStmt->execute([$invoiceId]);
    $invoiceItems = $itemsStmt->fetchAll();
    
    // Set default currency if not found
    if (!$invoice['currency_symbol']) {
        $invoice['currency_symbol'] = '$';
        $invoice['currency_name'] = 'US Dollar';
        $invoice['currency_code'] = 'USD';
    }
    
    // Parse bank custom fields
    $bankCustomFields = [];
    if ($invoice['bank_custom_fields']) {
        $bankCustomFields = json_decode($invoice['bank_custom_fields'], true);
        if (!is_array($bankCustomFields)) {
            $bankCustomFields = [];
        }
    }
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

if ($type === 'pdf') {
    header('Content-Type: text/html; charset=UTF-8');
    echo "<script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
    <link rel="icon" type="image/png" href="assets/Sachin.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'inter', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
            padding: 15px;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border: 1px solid #ddd;
            min-height: calc(100vh - 30px);
            display: flex;
            flex-direction: column;
        }
        
        /* Header with gradient */
        .invoice-header {
            background: #182f59;
            color: white;
            padding: 20px 25px 15px;
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        .header-left h1 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .header-right {
            text-align: right;
        }
        
        .company-logo {
            display: inline-block;
        }
        
         .company-logo img {
    width: 40px;
    height: auto;
    }
        
        
        .company-name {
            font-size: 18px;
            font-weight: 600;
        }
        
        .currency-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-top: 5px;
            display: inline-block;
        }
        
        /* Body Content */
        .invoice-body {
            padding: 20px 25px;
            flex-grow: 1;
        }
        
        /* Bill To Section */
        .billing-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .bill-to, .invoice-details {
            width: 48%;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 8px;
            color: #333;
        }
        
        .client-info {
            font-size: 12px;
            line-height: 1.5;
        }
        
        .client-name {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 3px;
        }
        
        .invoice-meta {
            text-align: right;
        }
        
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            min-width: 220px;
        }
        
        .meta-label {
            font-weight: bold;
        }
        
        /* Service Table */
        .service-section {
            margin-bottom: 20px;
        }
        
        .service-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 12px;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .currency-info {
            font-size: 11px;
            color: #666;
            font-weight: normal;
        }
        
        .service-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        
        .service-table thead th {
            background: #f8b500;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }
        
        .service-table tbody td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        
        .service-table .text-center {
            text-align: center;
        }
        
        .service-table .text-right {
            text-align: right;
        }
        
        /* Item Description Styling */
        .item-description {
            font-size: 11px;
            line-height: 1.3;
        }
        
        .item-description-text {
            font-weight: 500;
            margin-bottom: 2px;
        }
        
        /* Bottom Section */
        .bottom-section {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
        
        .terms-section {
            width: 48%;
        }
        
        .terms-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 8px;
        }
        
        .terms-list {
            font-size: 10px;
            line-height: 1.4;
        }
        
        .terms-list li {
            margin-bottom: 3px;
            list-style-position: inside;
        }
        
        .totals-section {
            width: 48%;
        }
        
        .totals-table {
            width: 100%;
            font-size: 12px;
        }
        
        .totals-table td {
            padding: 4px 8px;
            border-bottom: 1px solid #eee;
        }
        
        .totals-table .total-final {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #333;
            border-bottom: none;
            background: #f9f9f9;
        }
        
        .text-right {
            text-align: right;
        }
        
        /* Footer */
        .footer-section {
            background: #182f59;
            color: white;
            padding: 15px 25px;
            margin-top: auto;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .payment-info {
            font-size: 11px;
            flex: 1;
        }
        
        .payment-title {
            font-weight: bold;
            margin-bottom: 6px;
        }
        
        .payment-details {
            line-height: 1.4;
        }
        
        .payment-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 8px;
        }
        
        .payment-column {
            font-size: 10px;
        }
        
        .custom-fields-section {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .custom-fields-title {
            font-weight: bold;
            margin-bottom: 4px;
            font-size: 10px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .questions-section {
            text-align: right;
            flex: 0 0 auto;
            margin-left: 20px;
        }
        
        .questions-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 6px;
        }
        
        .contact-info {
            font-size: 10px;
            margin-bottom: 8px;
        }
        
        .signature-area {
            border-top: 1px solid white;
            padding-top: 5px;
            margin-top: 10px;
            text-align: right;
            font-size: 11px;
        }
        
        /* Print Styles */
        @media print {
            body {
                padding: 0 !important;
                font-size: 10px !important;
            }
            
            .invoice-container {
                border: none !important;
                min-height: auto !important;
                max-width: none !important;
                height: 100vh !important;
                display: flex !important;
                flex-direction: column !important;
            }
            
            .invoice-header {
                background: #182f59 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color: white !important;
            }
            
            .company-logo {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color: white !important;
            }
             .company-logo img {
    width: 40px;
    height: auto;
}
        
            
            .currency-badge {
                background: rgba(255, 255, 255, 0.2) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .service-table thead th {
                background: #f8b500 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color: white !important;
            }
            
            .footer-section {
                background: #182f59 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color: white !important;
                margin-top: auto !important;
            }
            
            .totals-table .total-final {
                background: #f9f9f9 !important;
                -webkit-print-color-adjust: exact !important;
            }
            
            .no-print {
                display: none !important;
            }
            
            .invoice-body {
                flex-grow: 1 !important;
            }
        }
        
        .action-buttons {
            text-align: center;
            padding: 15px;
        }
        
        .btn {
            padding: 8px 16px;
            margin: 0 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-print {
            background: #4a90a4;
            color: white;
        }
        
        .btn-close {
            background: #666;
            color: white;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="header-left">
                <h1>INVOICE</h1>
            </div>
            <div class="header-right">
                <div class="company-logo">
                    <img src="https://sachindesign.com/assets/img/Sachin's%20photo.png" alt="Sachin Logo" />
                </div>
                <div class="company-name">Sachindesign.in</div>
                <div class="currency-badge">
                    <?php echo htmlspecialchars($invoice['currency_code']); ?> - <?php echo htmlspecialchars($invoice['currency_name']); ?>
                </div>
            </div>
        </div>
        
        <div class="invoice-body">
            <!-- Billing Section -->
            <div class="billing-section">
                <div class="bill-to">
                    <div class="section-title">Bill To:</div>
                    <div class="client-info">
                        <div class="client-name"><?php echo htmlspecialchars($invoice['client_name']); ?></div>
                        <?php if ($invoice['brand_name']): ?>
                            <div><strong><?php echo htmlspecialchars($invoice['brand_name']); ?></strong></div>
                        <?php endif; ?>
                        <div><?php echo htmlspecialchars($invoice['client_email']); ?></div>
                        <?php if ($invoice['client_phone']): ?>
                            <div><?php echo htmlspecialchars($invoice['client_phone']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="invoice-details">
                    <div class="invoice-meta">
                        <div class="meta-row">
                            <span class="meta-label">Invoice Number:</span>
                            <span><?php echo htmlspecialchars($invoice['invoice_number']); ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Invoice Date:</span>
                            <span><?php echo date('M j, Y', strtotime($invoice['issue_date'])); ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Due Date:</span>
                            <span><?php echo date('M j, Y', strtotime($invoice['due_date'])); ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Currency:</span>
                            <span><?php echo htmlspecialchars($invoice['currency_code']); ?></span>
                        </div>
                        <?php if ($invoice['project_title']): ?>
                        <div class="meta-row">
                            <span class="meta-label">Project:</span>
                            <span><?php echo htmlspecialchars($invoice['project_title']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Service Details -->
            <div class="service-section">
                <div class="service-title">
                    <span>Service Details:</span>
                    <span class="currency-info">All amounts in <?php echo htmlspecialchars($invoice['currency_code']); ?> (<?php echo htmlspecialchars($invoice['currency_symbol']); ?>)</span>
                </div>
                <table class="service-table">
                    <thead>
                        <tr>
                            <th width="40">No</th>
                            <th>Description of Service</th>
                            <th width="70" class="text-center">Qty</th>
                            <th width="90" class="text-right">Rate (<?php echo htmlspecialchars($invoice['currency_symbol']); ?>)</th>
                            <th width="90" class="text-right">Total (<?php echo htmlspecialchars($invoice['currency_symbol']); ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($invoiceItems)): ?>
                            <?php $itemNumber = 1; ?>
                            <?php foreach ($invoiceItems as $item): ?>
                                <tr>
                                    <td class="text-center"><?php echo $itemNumber; ?></td>
                                    <td>
                                        <div class="item-description">
                                            <div class="item-description-text">
                                                <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center"><?php echo number_format($item['quantity'], 2); ?></td>
                                    <td class="text-right"><?php echo htmlspecialchars($invoice['currency_symbol']); ?><?php echo number_format($item['rate'], 2); ?></td>
                                    <td class="text-right"><?php echo htmlspecialchars($invoice['currency_symbol']); ?><?php echo number_format($item['amount'], 2); ?></td>
                                </tr>
                                <?php $itemNumber++; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback for older invoices without items -->
                            <tr>
                                <td class="text-center">1</td>
                                <td>
                                    <div class="item-description">
                                        <div class="item-description-text">
                                            <?php if ($invoice['project_title']): ?>
                                                <?php echo htmlspecialchars($invoice['project_title']); ?>
                                            <?php else: ?>
                                                Professional Services
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($invoice['notes']): ?>
                                            <div style="margin-top: 4px; color: #666; font-size: 10px;">
                                                <?php echo nl2br(htmlspecialchars($invoice['notes'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">1</td>
                                <td class="text-right"><?php echo htmlspecialchars($invoice['currency_symbol']); ?><?php echo number_format($invoice['amount'], 2); ?></td>
                                <td class="text-right"><?php echo htmlspecialchars($invoice['currency_symbol']); ?><?php echo number_format($invoice['amount'], 2); ?></td>
                            </tr>
                        <?php endif; ?>
                        
                        <!-- Add empty rows if less than 3 items (for consistent spacing) -->
                        <?php 
                        $totalItems = !empty($invoiceItems) ? count($invoiceItems) : 1;
                        $minRows = 3;
                        for ($i = $totalItems; $i < $minRows; $i++): 
                        ?>
                            <tr>
                                <td class="text-center">&nbsp;</td>
                                <td>&nbsp;</td>
                                <td class="text-center">&nbsp;</td>
                                <td class="text-right">&nbsp;</td>
                                <td class="text-right">&nbsp;</td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Bottom Section -->
            <div class="bottom-section">
                <div class="terms-section">
                    <div class="terms-title">Terms and Conditions:</div>
                    <ul class="terms-list">
                        <li>Payment is due upon receipt of this invoice.</li>
                        <li>Please make payments in <?php echo htmlspecialchars($invoice['currency_code']); ?> currency.</li>
                        <li>Work completed is based on the agreed scope.</li>
                        <li>Any disputes must be raised within 7 days.</li>
                    </ul>
                </div>
                
                <div class="totals-section">
                    <table class="totals-table">
                        <tr>
                            <td>Subtotal</td>
                            <td class="text-right"><?php echo htmlspecialchars($invoice['currency_symbol']); ?><?php echo number_format($invoice['amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>Tax (0%)</td>
                            <td class="text-right"><?php echo htmlspecialchars($invoice['currency_symbol']); ?>0.00</td>
                        </tr>
                        <tr>
                            <td>Discount</td>
                            <td class="text-right"><?php echo htmlspecialchars($invoice['currency_symbol']); ?>0.00</td>
                        </tr>
                        <tr class="total-final">
                            <td><strong>Total Amount Due</strong></td>
                            <td class="text-right"><strong><?php echo htmlspecialchars($invoice['currency_symbol']); ?><?php echo number_format($invoice['amount'], 2); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <?php if ($invoice['notes'] && !empty($invoiceItems)): ?>
            <!-- Additional Notes Section (only if there are items and separate notes) -->
            <div style="margin-top: 15px; padding: 10px; background: #f9f9f9; border-left: 4px solid #4a90a4;">
                <div style="font-weight: bold; font-size: 12px; margin-bottom: 5px;">Additional Notes:</div>
                <div style="font-size: 11px; line-height: 1.4;">
                    <?php echo nl2br(htmlspecialchars($invoice['notes'])); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="footer-section">
            <div class="footer-content">
                <div class="payment-info">
                    <div class="payment-title">Payment Information:</div>
                    <div class="payment-details">
                        <!--<strong>Currency:</strong> <?php echo htmlspecialchars($invoice['currency_code']); ?> (<?php echo htmlspecialchars($invoice['currency_name']); ?>)<br>-->
                        <!--<strong>Amount Due:</strong> <?php echo htmlspecialchars($invoice['currency_symbol']); ?><?php echo number_format($invoice['amount'], 2); ?><br>-->
                        
                        <?php if ($invoice['payment_mode'] == 'Bank Transfer' && $invoice['account_name']): ?>
                            <div class="payment-grid">
                                <div class="payment-column">
                                    <strong>Payment Method:</strong> Bank Transfer<br>
                                    <strong>Due Date:</strong> <?php echo date('M j, Y', strtotime($invoice['due_date'])); ?><br>
                                    <strong>Bank:</strong> <?php echo htmlspecialchars($invoice['bank_name']); ?><br>
                                    <strong>Account:</strong> <?php echo htmlspecialchars($invoice['account_name']); ?><br>
                                    
                                    <?php if ($invoice['account_number']): ?>
                                        <strong>Account Number:</strong> <?php echo htmlspecialchars($invoice['account_number']); ?><br>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="payment-column">
                                    <?php if ($invoice['ifsc_code']): ?>
                                        <strong>IFSC Code:</strong> <?php echo htmlspecialchars($invoice['ifsc_code']); ?><br>
                                    <?php endif; ?>
                                    
                                    <?php if ($invoice['bank_upi_id']): ?>
                                        <strong>UPI ID:</strong> <?php echo htmlspecialchars($invoice['bank_upi_id']); ?><br>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($bankCustomFields)): ?>
                                <div class="custom-fields-section">
                                    <div class="custom-fields-title">Additional Bank Details:</div>
                                    <div class="payment-grid">
                                        <?php 
                                        $fieldCount = count($bankCustomFields);
                                        $halfCount = ceil($fieldCount / 2);
                                        ?>
                                        <div class="payment-column">
                                            <?php for ($i = 0; $i < $halfCount; $i++): ?>
                                                <?php if (isset($bankCustomFields[$i])): ?>
                                                    <strong><?php echo htmlspecialchars($bankCustomFields[$i]['label']); ?>:</strong> 
                                                    <?php echo htmlspecialchars($bankCustomFields[$i]['value']); ?><br>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="payment-column">
                                            <?php for ($i = $halfCount; $i < $fieldCount; $i++): ?>
                                                <?php if (isset($bankCustomFields[$i])): ?>
                                                    <strong><?php echo htmlspecialchars($bankCustomFields[$i]['label']); ?>:</strong> 
                                                    <?php echo htmlspecialchars($bankCustomFields[$i]['value']); ?><br>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php elseif ($invoice['payment_mode'] == 'PayPal' && $invoice['paypal_email']): ?>
                            <div class="payment-grid">
                                <div class="payment-column">
                                    <strong>Payment Method:</strong> PayPal<br>
                                    <strong>Due Date:</strong> <?php echo date('M j, Y', strtotime($invoice['due_date'])); ?><br>
                                    <strong>PayPal Email:</strong> <?php echo htmlspecialchars($invoice['paypal_email']); ?><br>
                                    <?php if ($invoice['paypal_link']): ?>
                                        <strong>PayPal Link:</strong> <a href="<?php echo htmlspecialchars($invoice['paypal_link']); ?>" style="color: #f8b500; text-decoration: none;"><?php echo htmlspecialchars($invoice['paypal_link']); ?></a><br>
                                    <?php endif; ?>
                                </div>
                            </div>

                        <?php elseif ($invoice['payment_mode'] == 'UPI' && $invoice['upi_account_id']): ?>
                            <div class="payment-grid" style="display: flex; gap: 20px; align-items: flex-start;">
                                <?php if ($invoice['upi_qr_code']): ?>
                                <div style="flex-shrink: 0;">
                                    <img src="<?php echo htmlspecialchars($invoice['upi_qr_code']); ?>" style="width: 100px; height: 100px; border: 1px solid #eee; border-radius: 4px;">
                                </div>
                                <?php endif; ?>
                                <div class="payment-column" style="flex: 1;">
                                    <strong>Payment Method:</strong> UPI Transfer<br>
                                    <strong>Due Date:</strong> <?php echo date('M j, Y', strtotime($invoice['due_date'])); ?><br>
                                    <strong>UPI ID:</strong> <?php echo htmlspecialchars($invoice['upi_account_id']); ?><br>
                                    <div style="margin-top: 5px;">
                                        <strong>Bank Details:</strong><br>
                                        <div style="font-size: 10px; color: #f8b500; font-weight: 500;"><?php echo nl2br(htmlspecialchars($invoice['upi_bank_details'])); ?></div>
                                    </div>
                                </div>
                            </div>

                        <?php else: ?>
                            <strong>Payment Method:</strong> <?php echo $invoice['payment_mode'] ? htmlspecialchars($invoice['payment_mode']) : 'Bank Transfer'; ?><br>
                            <strong>Due Date:</strong> <?php echo date('M j, Y', strtotime($invoice['due_date'])); ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="questions-section">
                    <div class="questions-title">Questions?</div>
                    <div class="contact-info">
                        <strong>Email Me:</strong> hi@sachindesign.com<br>
                        <strong>Whatsapp:</strong> +91-8800-341-992<br>
                        <strong>Website:</strong> www.sachindesign.com
                    </div>
                    <div class="signature-area">
                        <div style="margin-bottom: 5px;">Date: <?php echo date('M j, Y'); ?></div>
                        <div style="font-style: italic;">We look forward to working with you again.</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons no-print">
            <button onclick="window.print()" class="btn btn-print">📄 Print Invoice</button>
            <button onclick="window.close()" class="btn btn-close">✕ Close</button>
        </div>
    </div>
</body>
</html>