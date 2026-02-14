<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header("Location: tasks.php?error=" . urlencode("Task ID not provided"));
    exit;
}

$taskId = $_GET['id'];

try {
    // Get task details before deletion for confirmation
    $taskStmt = $pdo->prepare("SELECT task_name FROM tasks WHERE id = ?");
    $taskStmt->execute([$taskId]);
    $task = $taskStmt->fetch();
    
    if (!$task) {
        header("Location: tasks.php?error=" . urlencode("Task not found"));
        exit;
    }
    
    // Delete the task
    $deleteStmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $deleteStmt->execute([$taskId]);
    
    if ($deleteStmt->rowCount() > 0) {
        $successMessage = "Task '{$task['task_name']}' deleted successfully";
        header("Location: tasks.php?success=" . urlencode($successMessage));
    } else {
        header("Location: tasks.php?error=" . urlencode("Task not found or already deleted"));
    }
    
} catch(PDOException $e) {
    header("Location: tasks.php?error=" . urlencode("Error deleting task: " . $e->getMessage()));
}

exit;
?>
