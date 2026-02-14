<?php
require_once 'config/database.php';

$webhookSecret = "Mrercedes@001";

/* Read payload */
$payload = file_get_contents("php://input");
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

/* Verify signature */
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(400);
    exit("Invalid signature");
}

$data = json_decode($payload, true);
$event = $data['event'] ?? '';

/* PAYMENT CAPTURED */
if ($event === 'payment.captured') {

    $orderId = $data['payload']['payment']['entity']['order_id'] ?? null;

    if ($orderId) {

        // fetch payment link data
        $stmt = $pdo->prepare("
            SELECT * FROM payment_links
            WHERE razorpay_order_id = ?
            LIMIT 1
        ");
        $stmt->execute([$orderId]);
        $link = $stmt->fetch();

        if ($link) {

            // insert into payments ledger
            $insert = $pdo->prepare("
                INSERT INTO payments
                (client_id, project_id, amount, currency, razorpay_order_id, paid_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $insert->execute([
                $link['client_id'],
                $link['project_id'],
                $link['amount'],
                $link['currency'],
                $orderId
            ]);

            // mark link paid
            $pdo->prepare("
                UPDATE payment_links
                SET status='paid', paid_at=NOW()
                WHERE id=?
            ")->execute([$link['id']]);
        }
    }
}

http_response_code(200);
echo "Webhook OK";