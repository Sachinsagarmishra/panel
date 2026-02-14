<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

// Handle client filter
$clientFilter = $_GET['client_id'] ?? '';

// Get projects with client info
$sql = "
    SELECT p.*, c.name as client_name, c.brand_name, c.email as client_email
    FROM projects p 
    JOIN clients c ON p.client_id = c.id
";

$params = [];
if ($clientFilter) {
    $sql .= " WHERE p.client_id = ?";
    $params[] = $clientFilter;
}

$sql .= " ORDER BY p.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();
    
    // Get clients for filter dropdown
    $clientsStmt = $pdo->query("SELECT id, name, brand_name FROM clients ORDER BY name");
    $allClients = $clientsStmt->fetchAll();
    
    // Calculate statistics
    $totalProjects = count($projects);
    $activeProjects = count(array_filter($projects, function($p) { return $p['status'] == 'In Progress'; }));
    $completedProjects = count(array_filter($projects, function($p) { return $p['status'] == 'Done'; }));
    $ideaProjects = count(array_filter($projects, function($p) { return $p['status'] == 'Idea'; }));
    
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects</title>
    <link href="assets/style.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <nav class="sidebar">
           <div class="logo">
    <div class="logo-icon">
        <img src="https://sachindesign.com/assets/img/Sachin's%20photo.png" alt="Logo Icon" />
    </div>
    <div class="logo-text">Sachindesign</div>
</div>

            <div class="nav-section">
                <div class="nav-title">Overview</div>
                <a href="index.php" class="nav-item">
                    <span class="nav-item-icon"><i class="fas fa-chart-bar"></i></span>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-title">Client Management</div>
                <a href="clients.php" class="nav-item">
                    <span class="nav-item-icon"><i class="fa-regular fa-circle-user"></i></span>
                    <span>Clients</span>
                </a>
                <a href="projects.php" class="nav-item">
                    <span class="nav-item-icon"><i class="fa-regular fa-copy"></i></span>
                    <span>Projects</span>
                </a>
                <a href="tasks.php" class="nav-item">
                    <span class="nav-item-icon"><i class="fa-regular fa-pen-to-square"></i></span>
                    <span>Tasks</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-title">Business</div>
                <a href="invoices.php" class="nav-item">
                    <span class="nav-item-icon"><i class="fa-regular fa-chess-king"></i></span>
                    <span>Invoices</span>
                </a>
                <a href="paymentlink.php" class="nav-item">
                    <span class="nav-item-icon"><i class="fa-solid fa-link"></i></span>
                    <span>Payment Link</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-title">Settings</div>
                <a href="bank-accounts.php" class="nav-item">
                    <span class="nav-item-icon"><i class="fa-regular fa-gem"></i></span>
                    <span>Bank Accounts</span>
                </a>
                <a href="currencies.php" class="nav-item">
                    <span class="nav-item-icon">💱</span>
                    <span>Currencies</span>
                </a>
            </div>
            
                <div class="nav-section" style="margin-top: auto; border-top: 1px solid #e2e8f0; padding-top: 1rem;">
        <a href="logout.php" class="nav-item logout-item" onclick="return confirm('Are you sure you want to logout?')">
                    <span class="nav-item-icon"><i class="fa-regular fa-share-from-square"></i></span>
            <span>Logout</span>
        </a>
    </div>
    
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header fade-in">
                <div>
                    <h1>📁 Projects</h1>
                    <p>Manage your project portfolio</p>
                </div>
                <a href="add-project.php" class="btn btn-primary">
                    <span>New Project</span>
                </a>
            </header>

            <!-- Statistics Cards -->
            <!--<div class="stats-grid fade-in">-->
            <!--    <div class="stat-card">-->
            <!--        <div class="stat-header">-->
            <!--            <div>-->
            <!--                <div class="stat-title">Total Projects</div>-->
            <!--                <div class="stat-value"><?php echo $totalProjects; ?></div>-->
            <!--            </div>-->
            <!--            <div class="stat-icon">📁</div>-->
            <!--        </div>-->
            <!--        <div class="stat-change neutral">-->
            <!--            <span>All projects</span>-->
            <!--        </div>-->
            <!--    </div>-->

            <!--    <div class="stat-card">-->
            <!--        <div class="stat-header">-->
            <!--            <div>-->
            <!--                <div class="stat-title">In Progress</div>-->
            <!--                <div class="stat-value"><?php echo $activeProjects; ?></div>-->
            <!--            </div>-->
            <!--        </div>-->
            <!--        <div class="stat-change positive">-->
            <!--            <span>⚡</span>-->
            <!--            <span>Active work</span>-->
            <!--        </div>-->
            <!--    </div>-->

            <!--    <div class="stat-card">-->
            <!--        <div class="stat-header">-->
            <!--            <div>-->
            <!--                <div class="stat-title">Completed</div>-->
            <!--                <div class="stat-value"><?php echo $completedProjects; ?></div>-->
            <!--            </div>-->
            <!--        </div>-->
            <!--        <div class="stat-change positive">-->
            <!--            <span>🎉</span>-->
            <!--            <span>Finished</span>-->
            <!--        </div>-->
            <!--    </div>-->

            <!--    <div class="stat-card">-->
            <!--        <div class="stat-header">-->
            <!--            <div>-->
            <!--                <div class="stat-title">Ideas</div>-->
            <!--                <div class="stat-value"><?php echo $ideaProjects; ?></div>-->
            <!--            </div>-->
            <!--        </div>-->
            <!--        <div class="stat-change neutral">-->
            <!--            <span>💭</span>-->
            <!--            <span>Planning stage</span>-->
            <!--        </div>-->
            <!--    </div>-->
            <!--</div>-->

            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    ✅ <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    ❌ <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="filters fade-in">
                <div class="filter-group">
                    <label class="form-label" style="margin-bottom: 0;">Filter by Client:</label>
                    <select class="form-select" style="width: auto;" onchange="filterByClient()">
                        <option value="">All Clients</option>
                        <?php foreach ($allClients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" 
                                    <?php echo $clientFilter == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['name']); ?>
                                <?php if ($client['brand_name']): ?>
                                    (<?php echo htmlspecialchars($client['brand_name']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <a href="add-project.php" class="btn btn-secondary">
                        <span>Add Project</span>
                    </a>
                </div>
            </div>

            <!-- Projects Table -->
            <div class="table-container fade-in">
                <div class="table-header">
                    <div class="table-title">
                        <span>All Projects (<?php echo count($projects); ?>)</span>
                    </div>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Project Details</th>
                            <th>Client</th>
                            <th>Services</th>
                            <th>Timeline</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projects)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #64748b; padding: 3rem;">
                                    <div style="font-size: 3rem; margin-bottom: 1rem;">📁</div>
                                    <div style="font-weight: 600; margin-bottom: 0.5rem;">No projects yet</div>
                                    <div>Create your first project to get started!</div>
                                    <a href="add-project.php" class="btn btn-primary" style="margin-top: 1rem;">➕ Add Project</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <div style="font-weight: 600; color: #1e293b; margin-bottom: 0.25rem;">
                                                <?php echo htmlspecialchars($project['title']); ?>
                                            </div>
                                            <?php if ($project['deliverables']): ?>
                                                <div style="color: #64748b; font-size: 0.875rem; line-height: 1.4;">
                                                    📋 <?php echo htmlspecialchars(substr($project['deliverables'], 0, 80)) . (strlen($project['deliverables']) > 80 ? '...' : ''); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div style="color: #64748b; font-size: 0.75rem; margin-top: 0.25rem;">
                                                Created: <?php echo date('M j, Y', strtotime($project['created_at'])); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div style="font-weight: 600; color: #1e293b;">
                                                <?php echo htmlspecialchars($project['client_name']); ?>
                                            </div>
                                            <?php if ($project['brand_name']): ?>
                                                <!--<div style="color: #64748b; font-size: 0.875rem;">-->
                                                <!--    <i class="fa-regular fa-life-ring"></i> <?php echo htmlspecialchars($project['brand_name']); ?>-->
                                                <!--</div>-->
                                            <?php endif; ?>
                                            <!--<div style="color: #64748b; font-size: 0.875rem;">-->
                                            <!--    <i class="fa-regular fa-envelope"></i> <?php echo htmlspecialchars($project['client_email']); ?>-->
                                            <!--</div>-->
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($project['services']): ?>
                                            <div class="services-tags">
                                                <?php 
                                                $services = json_decode($project['services'], true);
                                                if (is_array($services)):
                                                    foreach ($services as $service): 
                                                ?>
                                                    <span class="service-tag"><?php echo htmlspecialchars($service); ?></span>
                                                <?php 
                                                    endforeach;
                                                else:
                                                ?>
                                                    <span class="service-tag">General</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #94a3b8;">No services specified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($project['start_date'] && $project['end_date']): ?>
                                            <div style="font-size: 0.875rem;">
                                                <div style="margin-bottom: 0.25rem;">
                                                    <strong>Start:</strong> <?php echo date('M j, Y', strtotime($project['start_date'])); ?>
                                                </div>
                                                
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #94a3b8;">No timeline set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status <?php echo strtolower(str_replace(' ', '-', $project['status'])); ?>">
                                            <?php 
                                            switch($project['status']) {
                                                case 'Idea': echo '💡 Idea'; break;
                                                case 'In Progress': echo '🚀 In Progress'; break;
                                                case 'Review': echo '👁️ Review'; break;
                                                case 'Done': echo '✅ Done'; break;
                                                default: echo $project['status'];
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <button onclick="viewProject(<?php echo $project['id']; ?>)" 
                                                    class="action-btn btn-secondary" title="View Details">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                            <button onclick="editProject(<?php echo $project['id']; ?>)" 
                                                    class="action-btn btn-secondary" title="Edit Project">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>
                                            <button onclick="updateStatus(<?php echo $project['id']; ?>)" 
                                                    class="action-btn btn-success" title="Update Status">
                                                <i class="fa-regular fa-circle-check"></i>
                                            </button>
                                            <button onclick="deleteProject(<?php echo $project['id']; ?>, '<?php echo htmlspecialchars($project['title']); ?>')" 
                                                    class="action-btn btn-danger" title="Delete Project">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <style>
    
    
    .table th, .table td {
    padding: 17px;
}
       
           .sidebar {
    border-radius: 0px 20px 20px 0px;
    width: 250px;
    background: #ffffff;
    padding: 1.5rem;
    overflow-y: auto;
  box-shadow: none;
}
    
    
        .logo-icon img {
    width: 40px;   /* ya jo bhi size chahiye */
    height: auto;
}

.logo-icon {
    background: #0000!important;}

        .nav-item:hover {
    padding: 8px 20px;
}

.nav-title {
    color: #000000;
    margin-bottom: 0px;
}
        .header {
    border: solid 1px #e5e7eb !important;
    overflow: hidden;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    background: white;
    box-shadow:none !important;
    padding: 1.5rem !important;
    border-radius: 12px !important;
}


        /* Alert Styles */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left-color: #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border-left-color: #ef4444;
        }

        /* Service Tags */
        .services-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
        }

        .service-tag {
            background: #e0e7ff;
            color: #3730a3;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Action Button Styles */
        .action-btn {
            padding: 0.5rem !important;
            min-width: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        /* Status Styles */
        .status {
            padding: 0.375rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status.idea {
            background: #fef3c7;
            color: #92400e;
        }

        .status.in-progress {
            background: #dbeafe;
            color: #1e40af;
        }

        .status.review {
            background: #fce7f3;
            color: #be185d;
        }

        .status.done {
            background: #d1fae5;
            color: #065f46;
        }

        /* Delete confirmation modal styles */
        .delete-confirmation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1001;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .delete-modal {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }

        .delete-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .delete-icon {
            font-size: 3rem;
            color: #ef4444;
            margin-bottom: 1rem;
        }

        .delete-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .delete-message {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .delete-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .delete-confirm-btn {
            background: #ef4444;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .delete-confirm-btn:hover {
            background: #dc2626;
        }

        .delete-cancel-btn {
            background: #6b7280;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .delete-cancel-btn:hover {
            background: #4b5563;
        }

        @media (max-width: 768px) {
            .action-btn {
                padding: 0.4rem !important;
                min-width: 28px;
                font-size: 0.75rem;
            }

            .services-tags {
                flex-direction: column;
            }

            .service-tag {
                display: inline-block;
                margin-bottom: 0.25rem;
            }
        }
    </style>

    <script>
        function filterByClient() {
            const clientSelect = document.querySelector('.form-select');
            const clientId = clientSelect.value;
            const url = new URL(window.location);
            if (clientId) {
                url.searchParams.set('client_id', clientId);
            } else {
                url.searchParams.delete('client_id');
            }
            window.location = url;
        }
        
        function viewProject(id) {
            // Implement view project details
            alert('View project details for ID: ' + id + '\n\nThis will open project details page.');
        }
        
        function editProject(id) {
            window.location.href = 'add-project.php?edit=' + id;
        }

        function updateStatus(id) {
            const newStatus = prompt('Enter new status:\n\n1. Idea\n2. In Progress\n3. Review\n4. Done\n\nEnter status name:');
            if (newStatus) {
                const validStatuses = ['Idea', 'In Progress', 'Review', 'Done'];
                if (validStatuses.includes(newStatus)) {
                    window.location.href = `update-project-status.php?id=${id}&status=${encodeURIComponent(newStatus)}`;
                } else {
                    alert('Invalid status. Please use: Idea, In Progress, Review, or Done');
                }
            }
        }

        function deleteProject(projectId, projectTitle) {
            createDeleteModal(projectId, projectTitle);
        }

        function createDeleteModal(projectId, projectTitle) {
            // Remove existing modal if any
            const existingModal = document.getElementById('deleteModal');
            if (existingModal) {
                existingModal.remove();
            }

            // Create modal HTML
            const modalHTML = `
                <div id="deleteModal" class="delete-confirmation">
                    <div class="delete-modal">
                        <div class="delete-header">
                            <div class="delete-icon">⚠️</div>
                            <div class="delete-title">Delete Project</div>
                        </div>
                        
                        <div class="delete-message">
                            <p><strong>Project:</strong> ${projectTitle}</p>
                            <br>
                            <p>⚠️ <strong>Warning:</strong> This action will permanently delete:</p>
                            <ul style="margin: 1rem 0; padding-left: 1.5rem; color: #dc2626;">
                                <li>Project details and information</li>
                                <li>All associated tasks</li>
                                <li>Project timeline and deliverables</li>
                                <li>Any linked files or notes</li>
                            </ul>
                            <p><strong>This action cannot be undone!</strong></p>
                        </div>
                        
                        <div class="delete-actions">
                            <button class="delete-cancel-btn" onclick="closeDeleteModal()">
                                ❌ Cancel
                            </button>
                            <button class="delete-confirm-btn" onclick="confirmDelete(${projectId})">
                                🗑️ Delete Forever
                            </button>
                        </div>
                    </div>
                </div>
            `;

            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            if (modal) {
                modal.remove();
                document.body.style.overflow = 'auto';
            }
        }

        function confirmDelete(projectId) {
            // Show loading state
            const confirmBtn = document.querySelector('.delete-confirm-btn');
            const cancelBtn = document.querySelector('.delete-cancel-btn');
            
            confirmBtn.innerHTML = '⏳ Deleting...';
            confirmBtn.disabled = true;
            cancelBtn.disabled = true;
            
            // Redirect to delete script
            setTimeout(() => {
                window.location.href = `delete-project.php?id=${projectId}`;
            }, 500);
        }

        // Auto-hide alert messages
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });

            // Close modal when clicking outside
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('delete-confirmation')) {
                    closeDeleteModal();
                }
            });

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeDeleteModal();
                }
            });
        });
    </script>
</body>
</html>