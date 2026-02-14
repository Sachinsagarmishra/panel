<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

$isEdit = isset($_GET['edit']);
$projectId = $_GET['edit'] ?? null;
$project = null;

// Get all clients for dropdown
try {
    $clientsStmt = $pdo->query("SELECT id, name, brand_name FROM clients ORDER BY name");
    $clients = $clientsStmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error fetching clients: " . $e->getMessage();
}

// If editing, fetch existing project data
if ($isEdit && $projectId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        $project = $stmt->fetch();
        
        if (!$project) {
            header("Location: projects.php");
            exit;
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle form submission
if ($_POST) {
    $title = trim($_POST['title']);
    $client_id = $_POST['client_id'];
    $services = $_POST['services'] ?? [];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    $deliverables = trim($_POST['deliverables']);
    $notes = trim($_POST['notes']);
    
    // Convert services array to JSON
    $services_json = json_encode($services);
    
    try {
        if ($isEdit && $projectId) {
            // Update existing project
            $stmt = $pdo->prepare("
                UPDATE projects SET 
                title = ?, client_id = ?, services = ?, start_date = ?, end_date = ?, 
                status = ?, deliverables = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $client_id, $services_json, $start_date, $end_date, $status, $deliverables, $notes, $projectId]);
            $success = "Project updated successfully!";
        } else {
            // Insert new project
            $stmt = $pdo->prepare("
                INSERT INTO projects (title, client_id, services, start_date, end_date, status, deliverables, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $client_id, $services_json, $start_date, $end_date, $status, $deliverables, $notes]);
            $success = "Project added successfully!";
        }
        
        // Redirect after successful submission
        header("Location: projects.php");
        exit;
        
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Available services
$availableServices = [
    'Web Design',
    'Web Development',
    'SEO',
    'Graphics Design',
    'Social Media Management',
    'Content Writing',
    'Branding',
    'E-commerce',
    'WordPress',
    'Mobile App'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Add New'; ?> Add Project</title>
    <link href="assets/style.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div>
                    <h1><?php echo $isEdit ? ' Edit Project' : 'Add New Project'; ?></h1>
                    <p style="color: #888;">Create and manage your project details</p>
                </div>
                <a href="projects.php" class="btn btn-secondary">← Back to Projects</a>
            </header>

            <?php if (isset($error)): ?>
                <div style="background: #ef4444; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div style="background: #10b981; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Project Form -->
            <form method="POST" class="form">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <!-- Left Column -->
                    <div>
                        <div class="form-group">
                            <label class="form-label" for="title">Project Title *</label>
                            <input type="text" id="title" name="title" class="form-input" 
                                   value="<?php echo htmlspecialchars($project['title'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="client_id">Client *</label>
                            <select id="client_id" name="client_id" class="form-select" required>
                                <option value="">Select a client...</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>" 
                                            <?php echo ($project['client_id'] ?? '') == $client['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($client['name']); ?>
                                        <?php if ($client['brand_name']): ?>
                                            (<?php echo htmlspecialchars($client['brand_name']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Services *</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-top: 0.5rem;">
                                <?php 
                                $selectedServices = [];
                                if ($project && $project['services']) {
                                    $selectedServices = json_decode($project['services'], true) ?? [];
                                }
                                foreach ($availableServices as $service): 
                                ?>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; color: #ccc;">
                                        <input type="checkbox" name="services[]" value="<?php echo $service; ?>"
                                               <?php echo in_array($service, $selectedServices) ? 'checked' : ''; ?>
                                               style="margin: 0;">
                                        <?php echo $service; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="Idea" <?php echo ($project['status'] ?? 'Idea') == 'Idea' ? 'selected' : ''; ?>>💡 Idea</option>
                                <option value="In Progress" <?php echo ($project['status'] ?? '') == 'In Progress' ? 'selected' : ''; ?>>🚀 In Progress</option>
                                <option value="Review" <?php echo ($project['status'] ?? '') == 'Review' ? 'selected' : ''; ?>>👀 Review</option>
                                <option value="Done" <?php echo ($project['status'] ?? '') == 'Done' ? 'selected' : ''; ?>>✅ Done</option>
                            </select>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div>
                        <div class="form-group">
                            <label class="form-label" for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" class="form-input" 
                                   value="<?php echo $project['start_date'] ?? ''; ?>">
                        </div>
                        

                        <div class="form-group">
                            <label class="form-label" for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" class="form-input" 
                                   value="<?php echo $project['end_date'] ?? ''; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="deliverables">Deliverables</label>
                            <textarea id="deliverables" name="deliverables" class="form-textarea" 
                                      placeholder="List the project deliverables..."><?php echo htmlspecialchars($project['deliverables'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Full Width -->
                <div class="form-group">
                    <label class="form-label" for="notes">Project Notes</label>
                    <textarea id="notes" name="notes" class="form-textarea" 
                              placeholder="Any additional notes about this project..."><?php echo htmlspecialchars($project['notes'] ?? ''); ?></textarea>
                </div>

                <!-- Actions -->
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <a href="projects.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <?php echo $isEdit ? 'Update Project' : 'Create Project'; ?>
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
        // Auto-calculate project duration
        document.getElementById('start_date').addEventListener('change', calculateDuration);
        document.getElementById('end_date').addEventListener('change', calculateDuration);
        
        function calculateDuration() {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            
            if (startDate && endDate && endDate > startDate) {
                const timeDiff = endDate.getTime() - startDate.getTime();
                const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
                
                // You can display this duration somewhere if needed
                console.log('Project duration:', dayDiff, 'days');
            }
        }
    </script>
</body>
</html>