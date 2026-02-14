<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    header("Location: projects.php?error=" . urlencode("Missing parameters"));
    exit;
}

$projectId = $_GET['id'];
$newStatus = $_GET['status'];

// Validate status
$validStatuses = ['Idea', 'In Progress', 'Review', 'Done'];
if (!in_array($newStatus, $validStatuses)) {
    header("Location: projects.php?error=" . urlencode("Invalid status"));
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE projects SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $projectId]);
    
    if ($stmt->rowCount() > 0) {
        header("Location: projects.php?success=" . urlencode("Project status updated to: $newStatus"));
    } else {
        header("Location: projects.php?error=" . urlencode("Project not found"));
    }
    
} catch(PDOException $e) {
    header("Location: projects.php?error=" . urlencode("Error updating status: " . $e->getMessage()));
}

exit;
?>
