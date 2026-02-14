<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM paypal_methods WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        header("Location: paypal-methods.php?success=PayPal method deleted successfully");
    } catch (PDOException $e) {
        header("Location: paypal-methods.php?error=" . urlencode($e->getMessage()));
    }
}
?>