<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    header("Location: currencies.php");
    exit;
}

$currencyId = $_GET['id'];
$status = $_GET['status'] === 'true' ? 1 : 0;

try {
    $stmt = $pdo->prepare("UPDATE currencies SET is_active = ? WHERE id = ?");
    $stmt->execute([$status, $currencyId]);
    
    header("Location: currencies.php?success=Currency status updated successfully");
    exit;
    
} catch(PDOException $e) {
    header("Location: currencies.php?error=" . urlencode("Error updating status: " . $e->getMessage()));
    exit;
}
?>