<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header("Location: clients.php?error=" . urlencode("Client ID not provided"));
    exit;
}

$clientId = $_GET['id'];

try {
    // Start transaction for safety
    $pdo->beginTransaction();

    // Get client details before deletion
    $clientStmt = $pdo->prepare("SELECT name FROM clients WHERE id = ?");
    $clientStmt->execute([$clientId]);
    $client = $clientStmt->fetch();

    if (!$client) {
        $pdo->rollback();
        header("Location: clients.php?error=" . urlencode("Client not found"));
        exit;
    }


    // Update invoices to remove project references (keep invoices but unlink projects)
    $updateInvoicesStmt = $pdo->prepare("
        UPDATE invoices i 
        INNER JOIN projects p ON i.project_id = p.id 
        SET i.project_id = NULL 
        WHERE p.client_id = ?
    ");
    $updateInvoicesStmt->execute([$clientId]);

    // Delete projects of this client
    $deleteProjectsStmt = $pdo->prepare("DELETE FROM projects WHERE client_id = ?");
    $deleteProjectsStmt->execute([$clientId]);

    // Update invoices to remove client references (keep invoices for record)
    $updateClientInvoicesStmt = $pdo->prepare("UPDATE invoices SET client_id = NULL WHERE client_id = ?");
    $updateClientInvoicesStmt->execute([$clientId]);

    // Delete proposals of this client
    $deleteProposalsStmt = $pdo->prepare("DELETE FROM proposals WHERE client_id = ?");
    $deleteProposalsStmt->execute([$clientId]);

    // Finally, delete the client
    $deleteClientStmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $deleteClientStmt->execute([$clientId]);

    if ($deleteClientStmt->rowCount() > 0) {
        $pdo->commit();
        $successMessage = "Client '{$client['name']}' and all associated data deleted successfully";
        header("Location: clients.php?success=" . urlencode($successMessage));
    } else {
        $pdo->rollback();
        header("Location: clients.php?error=" . urlencode("Client not found or already deleted"));
    }

} catch (PDOException $e) {
    $pdo->rollback();
    header("Location: clients.php?error=" . urlencode("Error deleting client: " . $e->getMessage()));
}

exit;
?>