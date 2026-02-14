<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

/* Razorpay keys */
$keyId = "rzp_live_Rv8zU9pdP96QJN";

/* Get slug */
$slug = $_GET['slug'] ?? '';

if (!$slug) {
    die("Invalid payment link");
}

/* Fetch payment link */
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
    die("Payment link expired or not found");
}

if ($payment['status'] === 'paid') {
    die("Payment already completed");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Complete Payment</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<style>
body {
    margin: 0;
    font-family: 'Inter', sans-serif;
      /* Background Image */
  background-image: url("https://panel.sachindesign.com/assets/macosscreen.webp");
  background-size: cover;  
  background-position: center;   
  background-repeat: no-repeat;  
  
    min-height: 100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
}
.card {
    background:#fff;
    border-radius:18px;
    max-width:420px;
    width:100%;
    padding:28px;
    box-shadow:0 30px 60px rgba(0,0,0,0.25);
    text-align:center;
}
.logo {
    font-size:22px;
    font-weight:800;
    margin-bottom:10px;
}
.badge {
    display:inline-block;
    background:#eef2ff;
    color:#4f46e5;
    padding:6px 14px;
    border-radius:20px;
    font-size:13px;
    margin-bottom:14px;
}
.amount {
    font-size:34px;
    font-weight:800;
    margin:20px 0;
}
.details {
    background:#f8fafc;
    border-radius:12px;
    padding:14px;
    margin-bottom:20px;
    text-align:left;
    font-size:14px;
}
.details div {
    margin-bottom:6px;
}
.pay-btn {
    width:100%;
    padding:14px;
    font-size:16px;
    font-weight:700;
    border:none;
    border-radius:12px;
    background:linear-gradient(135deg,#047aff,#047aff);
    color:white;
    cursor:pointer;
}
.pay-btn:hover {
    opacity:.95;
}
.footer {
    margin-top:14px;
    font-size:12px;
    color:#64748b;
}
@media(max-width:480px){
    .amount{font-size:28px;}
}
</style>
</head>

<body>

<div class="card">

<div class="logo">SachinDesign</div>
<div class="badge">Secure Payment</div>

<h2><?= htmlspecialchars($payment['project_title']) ?></h2>

<div class="amount">
<?= number_format($payment['amount'],2) ?> <?= htmlspecialchars($payment['currency']) ?>
</div>

<div class="details">
    <div><strong>Client:</strong> <?= htmlspecialchars($payment['client_name']) ?></div>
    <div><strong>Project:</strong> <?= htmlspecialchars($payment['project_title']) ?></div>
    <div><strong>Status:</strong> Pending</div>
</div>

<button id="payBtn" class="pay-btn">
Pay Now
</button>

<div class="footer">
🔒 Powered by Razorpay • Secure Checkout
</div>

</div>

<script>
var options = {
    "key": "<?= $keyId ?>",
    "amount": "<?= (int)($payment['amount'] * 100) ?>",
    "currency": "<?= $payment['currency'] ?>",
    "name": "SachinDesign",
    "description": "<?= addslashes($payment['project_title']) ?>",
    "order_id": "<?= $payment['razorpay_order_id'] ?>",
    "handler": function (response){
        window.location = "payment-success.php?slug=<?= $payment['slug'] ?>";
    },
    "theme": {
        "color": "#4f46e5"
    }
};

var rzp = new Razorpay(options);

document.getElementById('payBtn').onclick = function(e){
    rzp.open();
    e.preventDefault();
}
</script>

</body>
</html>