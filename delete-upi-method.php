<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

if (isset($_GET['id'])) {
    try {
        // Get QR code path to delete file
        $stmt = $pdo->prepare("SELECT qr_code FROM upi_methods WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $method = $stmt->fetch();

        if ($method && $method['qr_code'] && file_exists($method['qr_code'])) {
            unlink($method['qr_code']);
        }

        $stmt = $pdo->prepare("DELETE FROM upi_methods WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        header("Location: upi-methods.php?success=UPI method deleted successfully");
    } catch (PDOException $e) {
        header("Location: upi-methods.php?error=" . urlencode($e->getMessage()));
    }
}
?>