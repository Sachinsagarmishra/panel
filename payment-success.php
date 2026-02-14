<?php
require_once 'config/database.php';

$slug = $_GET['slug'] ?? '';

if (!$slug) {
    die("Invalid payment");
}

$stmt = $pdo->prepare("
    SELECT pl.*, c.name AS client_name, p.title AS project_title
    FROM payment_links pl
    JOIN clients c ON c.id = pl.client_id
    JOIN projects p ON p.id = pl.project_id
    WHERE pl.slug = ?
    LIMIT 1
");
$stmt->execute([$slug]);
$payment = $stmt->fetch();

if (!$payment) {
    die("Payment not found");
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Payment Successful</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{
    margin:0;
    font-family:Inter, sans-serif;
    background:linear-gradient(135deg,#22c55e,#16a34a);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
}
.card{
    background:#fff;
    max-width:420px;
    width:100%;
    padding:30px;
    border-radius:18px;
    text-align:center;
    box-shadow:0 25px 60px rgba(0,0,0,.25);
}
.icon{
    font-size:60px;
}
.amount{
    font-size:32px;
    font-weight:800;
    margin:12px 0;
}
.small{
    color:#64748b;
    font-size:14px;
}
.status{
    margin-top:16px;
    font-weight:700;
    color:<?= $payment['status']==='paid' ? '#16a34a' : '#f59e0b' ?>;
}
</style>
</head>

<body>

<div class="card">
    <div class="icon">✅</div>
    <h2>Thank You!</h2>

    <div class="amount">
        <?= number_format($payment['amount'],2) ?> <?= htmlspecialchars($payment['currency']) ?>
    </div>

    <div class="small">
        Project: <strong><?= htmlspecialchars($payment['project_title']) ?></strong><br>
        Client: <?= htmlspecialchars($payment['client_name']) ?>
    </div>

    <div class="status">
        Status: <?= strtoupper($payment['status']) ?>
    </div>

    <p class="small" style="margin-top:20px">
        Your payment has been securely processed.
    </p>
</div>

</body>
</html>