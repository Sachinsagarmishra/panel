<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

/* ======================
   DELETE PAYMENT LINK (SAFE)
====================== */
if (isset($_GET['delete_id'])) {
    $deleteId = (int) $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM payment_links WHERE id = ?");
    $stmt->execute([$deleteId]);
    header("Location: paymentlink.php?deleted=1");
    exit;
}

/* ======================
   MONTH FILTER
====================== */
$selectedMonth = $_GET['month'] ?? date('Y-m');
[$year, $month] = explode('-', $selectedMonth);

/* ======================
   PAYMENT LINKS (TEMPORARY)
====================== */
$paymentsLinks = $pdo->query("
    SELECT pl.*, c.name AS client_name, p.title AS project_title
    FROM payment_links pl
    JOIN clients c ON c.id = pl.client_id
    JOIN projects p ON p.id = pl.project_id
    ORDER BY pl.id DESC
")->fetchAll();

/* ======================
   METRICS (PAID ONLY – LEDGER)
====================== */

/* Total Payments Amount */
$totalPaymentsTillDate = $pdo->query("
    SELECT IFNULL(SUM(amount),0)
    FROM payments
")->fetchColumn();

/* Total Transactions */
$totalTxn = $pdo->query("
    SELECT COUNT(*)
    FROM payments
")->fetchColumn();

/* This Month Payments */
$thisMonthStmt = $pdo->prepare("
    SELECT IFNULL(SUM(amount),0)
    FROM payments
    WHERE MONTH(paid_at)=?
    AND YEAR(paid_at)=?
");
$thisMonthStmt->execute([$month, $year]);
$thisMonthAmount = $thisMonthStmt->fetchColumn();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Payment Links</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="assets/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .table td {
            vertical-align: middle;
        }

        .status.created {
            background: #fde3e3;
            color: #ef4444;
            border: 1px solid #ef4444;
            padding: 2px 12px;
            border-radius: 20px;
        }

        .status.paid {
            background: #e8ffe1;
            color: #15803d;
            border: 1px solid #22c55e;
            padding: 2px 12px;
            border-radius: 20px;
        }

        .copy-btn {
            background: #f3f4f6;
            border: none;
            padding: 8px 10px;
            border-radius: 6px;
            cursor: pointer;
        }

        .copy-btn:hover {
            background: #e5e7eb;
        }

        .delete-btn {
            color: #ef4444;
            margin-left: 10px;
        }

        .delete-btn:hover {
            color: #dc2626;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
        }

        .stat-value {
            font-size: 26px;
            font-weight: 700;
        }

        .stat-sub {
            font-size: 13px;
            color: #64748b;
        }

        .form-input {
            width: 180px;
        }
    </style>
</head>

<body>

    <div class="container">

        <!-- ================= SIDEBAR ================= -->
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">

            <header class="header">
                <div>
                    <h1>Payment Links</h1>
                    <p>Website based Razorpay payments</p>
                </div>
                <a href="create-paymentlink.php" class="btn btn-primary">
                    Generate Link
                </a>
            </header>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">✅ Payment link created</div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">🗑️ Payment link deleted</div>
            <?php endif; ?>

            <!-- ================= FILTER ================= -->
            <div style="margin-top:25px;display:flex;justify-content:space-between;align-items:center">
                <h3>Filter by Month</h3>
                <input type="month" class="form-input" value="<?= $selectedMonth ?>"
                    onchange="window.location='paymentlink.php?month='+this.value">
            </div>

            <!-- ================= METRICS ================= -->
            <div class="stats-grid">

                <div class="card">
                    <h4>Total Payments</h4>
                    <div class="stat-value"><?= number_format($totalPaymentsTillDate, 2) ?></div>
                    <div class="stat-sub">Paid payments (all time)</div>
                </div>

                <div class="card">
                    <h4>Total Transactions</h4>
                    <div class="stat-value"><?= number_format($totalTxn) ?></div>
                    <div class="stat-sub">Successful payments</div>
                </div>

                <div class="card">
                    <h4>This Month</h4>
                    <div class="stat-value"><?= number_format($thisMonthAmount, 2) ?></div>
                    <div class="stat-sub"><?= date('F Y', strtotime($selectedMonth)) ?></div>
                </div>

            </div>

            <!-- ================= TABLE ================= -->
            <div class="table-container" style="margin-top:25px">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Project</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php foreach ($paymentsLinks as $p):
                            $paymentUrl = "https://panel.sachindesign.com/pay.php?slug=" . $p['slug'];
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($p['client_name']) ?></strong></td>
                                <td><?= htmlspecialchars($p['project_title']) ?></td>
                                <td><strong><?= number_format($p['amount'], 2) ?>     <?= $p['currency'] ?></strong></td>
                                <td>
                                    <span class="status <?= strtolower($p['status']) ?>">
                                        <?= ucfirst($p['status']) ?>
                                    </span>
                                </td>
                                <td>

                                    <button class="copy-btn" onclick="copyPayment(
'<?= addslashes($p['client_name']) ?>',
'<?= addslashes($p['project_title']) ?>',
'<?= number_format($p['amount'], 2) . ' ' . $p['currency'] ?>',
'<?= $paymentUrl ?>',
this)">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>

                                    <a class="delete-btn" href="paymentlink.php?delete_id=<?= $p['id'] ?>"
                                        onclick="return confirm('Delete this payment page permanently?')">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </a>

                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <script>
        function copyPayment(client, project, amount, link, btn) {

            const msg =
                `Hi ${client},

This is the payment link for:
Project: ${project}
Amount: ${amount}

Please complete the payment using the link below:
${link}

Thank you!`;

            navigator.clipboard.writeText(msg).then(() => {
                const old = btn.innerHTML;
                btn.innerHTML = '✓';
                btn.style.background = '#dcfce7';
                setTimeout(() => {
                    btn.innerHTML = old;
                    btn.style.background = '#f3f4f6';
                }, 1200);
            });
        }
    </script>

</body>

</html>