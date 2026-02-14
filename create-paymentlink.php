<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

/* Razorpay keys */
$keyId = "rzp_live_Rv8zU9pdP96QJN";
$keySecret = "TfgkHtUBSR5EkC4jpJL763VD";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $client_id = (int) $_POST['client_id'];
    $project_id = (int) $_POST['project_id'];
    $amount = (float) $_POST['amount'];
    $currency = $_POST['currency'];

    if ($amount <= 0) {
        $error = "Invalid amount";
    } else {

        /* unique slug */
        $slug = bin2hex(random_bytes(6));

        /* CREATE RAZORPAY ORDER (CURL) */
        $orderPayload = json_encode([
            "amount" => (int) ($amount * 100),
            "currency" => $currency,
            "receipt" => "pay_" . $slug
        ]);

        $ch = curl_init("https://api.razorpay.com/v1/orders");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $keyId . ":" . $keySecret,
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $orderPayload
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $error = "Curl Error: " . $err;
        } else {

            $order = json_decode($response, true);

            if (!empty($order['id'])) {

                $stmt = $pdo->prepare("
                    INSERT INTO payment_links
                    (client_id, project_id, amount, currency, slug, razorpay_order_id, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'created')
                ");
                $stmt->execute([
                    $client_id,
                    $project_id,
                    $amount,
                    $currency,
                    $slug,
                    $order['id']
                ]);

                header("Location: paymentlink.php?success=1");
                exit;

            } else {
                $error = $order['error']['description'] ?? "Razorpay order failed";
            }
        }
    }
}

/* Load dropdowns */
$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll();
$projects = $pdo->query("SELECT id, title FROM projects ORDER BY title")->fetchAll();
$currencies = $pdo->query("SELECT * FROM currencies WHERE is_active=1")->fetchAll();
?>


<?php
$page_title = 'Create Payment Link';
include 'includes/header.php';
?>

<style>
    .form-card {
        max-width: 760px;
        background: #fff;
        border-radius: 16px;
        padding: 30px;
        margin-top: 20px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-group label {
        font-weight: 600;
        font-size: 13px;
        margin-bottom: 6px;
        display: block;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 30px;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
<?php
// Note: header.php handles opening body, container, sidebar and main-content
?>

            <header class="header">
                <div>
                    <h1>Create Payment Link</h1>
                    <p>Generate a temporary payment page on your website</p>
                </div>
            </header>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="form-card">

                <form method="POST">

                    <div class="form-grid">

                        <div class="form-group">
                            <label>Client *</label>
                            <select name="client_id" required class="form-select">
                                <option value="">Select Client</option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= $c['id'] ?>">
                                        <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Project *</label>
                            <select name="project_id" required class="form-select">
                                <option value="">Select Project</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?= $p['id'] ?>">
                                        <?= htmlspecialchars($p['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Amount *</label>
                            <input type="number" step="0.01" min="1" name="amount" placeholder="Enter amount" required
                                class="form-input">
                        </div>

                        <div class="form-group">
                            <label>Currency *</label>
                            <select name="currency" class="form-select">
                                <?php foreach ($currencies as $c): ?>
                                    <option value="<?= $c['code'] ?>">
                                        <?= $c['symbol'] ?>     <?= $c['code'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>

                    <div class="form-actions">
                        <a href="paymentlink.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            Generate Website Payment Page
                        </button>
                    </div>

                </form>

            </div>

        </main>
    </div>

<?php include "includes/footer.php"; ?>