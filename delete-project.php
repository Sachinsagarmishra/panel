<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header("Location: projects.php?error=" . urlencode("Project ID not provided"));
    exit;
}

$projectId = $_GET['id'];

try {
    // Start transaction for safety
    $pdo->beginTransaction();

    // Get project details before deletion
    $projectStmt = $pdo->prepare("SELECT title FROM projects WHERE id = ?");
    $projectStmt->execute([$projectId]);
    $project = $projectStmt->fetch();

    if (!$project) {
        $pdo->rollback();
        header("Location: projects.php?error=" . urlencode("Project not found"));
        exit;
    }


    // Update invoices to remove project reference (instead of deleting)
    $updateInvoicesStmt = $pdo->prepare("UPDATE invoices SET project_id = NULL WHERE project_id = ?");
    $updateInvoicesStmt->execute([$projectId]);

    // Delete the project
    $deleteProjectStmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $deleteProjectStmt->execute([$projectId]);

    if ($deleteProjectStmt->rowCount() > 0) {
        $pdo->commit();
        $successMessage = "Project '{$project['title']}' and all associated data deleted successfully";
        header("Location: projects.php?success=" . urlencode($successMessage));
    } else {
        $pdo->rollback();
        header("Location: projects.php?error=" . urlencode("Project not found or already deleted"));
    }

} catch (PDOException $e) {
    $pdo->rollback();
    header("Location: projects.php?error=" . urlencode("Error deleting project: " . $e->getMessage()));
}

exit;
?>