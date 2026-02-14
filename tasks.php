<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

// Handle form submission
if ($_POST) {
    $task_name = trim($_POST['task_name']);
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'] ?? null;
    $project_id = $_POST['project_id'] ?? null;
    $notes = trim($_POST['notes']);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO tasks (task_name, priority, due_date, project_id, notes) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$task_name, $priority, $due_date, $project_id, $notes]);
        $success = "Task added successfully!";
        
        // Redirect to avoid form resubmission
        header("Location: tasks.php?success=" . urlencode($success));
        exit;
        
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle status updates
if (isset($_GET['update_status'])) {
    $taskId = $_GET['update_status'];
    $newStatus = $_GET['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $taskId]);
        header("Location: tasks.php");
        exit;
    } catch(PDOException $e) {
        $error = "Error updating task: " . $e->getMessage();
    }
}

// Get tasks with project info
try {
    $tasksStmt = $pdo->query("
        SELECT t.*, p.title as project_title, c.name as client_name
        FROM tasks t 
        LEFT JOIN projects p ON t.project_id = p.id
        LEFT JOIN clients c ON p.client_id = c.id
        ORDER BY 
            CASE t.priority 
                WHEN 'High' THEN 1 
                WHEN 'Medium' THEN 2 
                WHEN 'Low' THEN 3 
            END,
            t.due_date ASC,
            t.created_at DESC
    ");
    $tasks = $tasksStmt->fetchAll();
    
    // Get projects for dropdown
    $projectsStmt = $pdo->query("
        SELECT p.id, p.title, c.name as client_name 
        FROM projects p 
        JOIN clients c ON p.client_id = c.id 
        ORDER BY p.title
    ");
    $projects = $projectsStmt->fetchAll();
    
    // Calculate statistics
    $totalTasks = count($tasks);
    $todoTasks = count(array_filter($tasks, function($task) { return $task['status'] == 'Todo'; }));
    $inProgressTasks = count(array_filter($tasks, function($task) { return $task['status'] == 'In Progress'; }));
    $completedTasks = count(array_filter($tasks, function($task) { return $task['status'] == 'Completed'; }));
    $overdueTasks = 0;
    
    foreach ($tasks as $task) {
        if ($task['due_date'] && $task['status'] != 'Completed') {
            $daysLeft = ceil((strtotime($task['due_date']) - time()) / (60*60*24));
            if ($daysLeft < 0) {
                $overdueTasks++;
            }
        }
    }
    
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Group tasks by status
$statusGroups = [
    'Todo' => [],
    'In Progress' => [],
    'Completed' => []
];

foreach ($tasks as $task) {
    $statusGroups[$task['status']][] = $task;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks</title>
    <link href="assets/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        <main class="main-content">
            <header class="header fade-in">
                <div>
                    <h1>Task Management</h1>
                    <p>Organize and track your project tasks</p>
                </div>
                <button onclick="toggleTaskForm()" class="btn btn-primary">
                    <span>New Task</span>
                </button>
            </header>

            <!-- Statistics Cards -->
            <div class="stats-grid fade-in">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Tasks</div>
                            <div class="stat-value"><?php echo $totalTasks; ?></div>
                        </div>
                    </div>
                    <div class="stat-change neutral">
                        <span>📊</span>
                        <span>All tasks</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">In Progress</div>
                            <div class="stat-value"><?php echo $inProgressTasks; ?></div>
                        </div>
                    </div>
                    <div class="stat-change positive">
                        <span>⚡</span>
                        <span>Active work</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Completed</div>
                            <div class="stat-value"><?php echo $completedTasks; ?></div>
                        </div>
                    </div>
                    <div class="stat-change positive">
                        <span>🎉</span>
                        <span>Finished</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Overdue</div>
                            <div class="stat-value"><?php echo $overdueTasks; ?></div>
                        </div>
                    </div>
                    <div class="stat-change <?php echo $overdueTasks > 0 ? 'negative' : 'neutral'; ?>">
                        <span><?php echo $overdueTasks > 0 ? '⚠️' : '✨'; ?></span>
                        <span><?php echo $overdueTasks > 0 ? 'Need attention' : 'All on track'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    ✅ <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

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

            <!-- Task Form Modal -->
            <div id="taskForm" class="form-modal" style="display: none;">
                <div class="form-content">
                    <div class="form-header">
                        <h2>Add New Task</h2>
                        <button type="button" onclick="toggleTaskForm()" class="close-btn">✕</button>
                    </div>
                    
                    <form method="POST" id="taskFormElement">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                            <div>
                                <div class="form-group">
                                    <label class="form-label" for="task_name">Task Name *</label>
                                    <input type="text" id="task_name" name="task_name" class="form-input" 
                                           placeholder="Enter task description..." required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="priority">Priority *</label>
                                    <select id="priority" name="priority" class="form-select" required>
                                        <option value="Medium">🟡 Medium Priority</option>
                                        <option value="High">🔴 High Priority</option>
                                        <option value="Low">🟢 Low Priority</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <div class="form-group">
                                    <label class="form-label" for="due_date">Due Date</label>
                                    <input type="date" id="due_date" name="due_date" class="form-input">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="project_id">Linked Project</label>
                                    <select id="project_id" name="project_id" class="form-select">
                                        <option value="">Select Project...</option>
                                        <?php foreach ($projects as $project): ?>
                                            <option value="<?php echo $project['id']; ?>">
                                                <?php echo htmlspecialchars($project['title']); ?>
                                                (<?php echo htmlspecialchars($project['client_name']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="notes">Task Notes</label>
                            <textarea id="notes" name="notes" class="form-textarea" 
                                      rows="4" placeholder="Add any additional details or requirements..."></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="button" onclick="toggleTaskForm()" class="btn btn-secondary">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Task</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tasks Kanban Board -->
            <div class="kanban-board fade-in">
                <?php foreach ($statusGroups as $status => $statusTasks): ?>
                    <div class="kanban-column">
                        <div class="kanban-header">
                            <span class="kanban-title">
                                <?php 
                                switch($status) {
                                    case 'Todo': echo '📋 To Do'; break;
                                    case 'In Progress': echo '🚀 In Progress'; break;
                                    case 'Completed': echo '✅ Completed'; break;
                                    default: echo $status;
                                }
                                ?>
                            </span>
                            <span class="kanban-count"><?php echo count($statusTasks); ?></span>
                        </div>
                        
                        <?php foreach ($statusTasks as $task): ?>
                            <div class="kanban-card task-card" data-task-id="<?php echo $task['id']; ?>">
                                <div class="card-header">
                                    <div class="card-title">
                                        <?php echo htmlspecialchars($task['task_name']); ?>
                                    </div>
                                    <span class="priority-badge priority-<?php echo strtolower($task['priority']); ?>">
                                        <?php 
                                        switch($task['priority']) {
                                            case 'High': echo '🔴'; break;
                                            case 'Medium': echo '🟡'; break;
                                            case 'Low': echo '🟢'; break;
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <?php if ($task['project_title']): ?>
                                    <div class="card-project">
                                        📁 <?php echo htmlspecialchars($task['project_title']); ?>
                                        <?php if ($task['client_name']): ?>
                                            <span class="client-name">(<?php echo htmlspecialchars($task['client_name']); ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($task['due_date']): ?>
                                    <div class="card-dates">
                                        📅 <?php echo date('M j, Y', strtotime($task['due_date'])); ?>
                                        <?php 
                                        $daysLeft = ceil((strtotime($task['due_date']) - time()) / (60*60*24));
                                        if ($task['status'] != 'Completed'):
                                            if ($daysLeft < 0): ?>
                                                <span class="overdue-badge">
                                                    ⚠️ <?php echo abs($daysLeft); ?> days overdue
                                                </span>
                                            <?php elseif ($daysLeft <= 3 && $daysLeft >= 0): ?>
                                                <span class="urgent-badge">
                                                    ⏰ <?php echo $daysLeft; ?> days left
                                                </span>
                                            <?php endif;
                                        endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($task['notes']): ?>
                                    <div class="card-notes">
                                        💭 <?php echo htmlspecialchars(substr($task['notes'], 0, 60)); ?><?php echo strlen($task['notes']) > 60 ? '...' : ''; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="task-actions">
                                    <div class="action-buttons">
                                        <?php if ($task['status'] != 'Completed'): ?>
                                            <button onclick="updateTaskStatus(<?php echo $task['id']; ?>, '<?php echo $task['status'] == 'Todo' ? 'In Progress' : 'Completed'; ?>')" 
                                                    class="action-btn btn-success">
                                                <?php echo $task['status'] == 'Todo' ? '🚀 Start' : '✅ Complete'; ?>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($task['status'] == 'Completed'): ?>
                                            <button onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'Todo')" 
                                                    class="action-btn btn-warning">
                                                🔄 Reopen
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button onclick="deleteTask(<?php echo $task['id']; ?>, '<?php echo htmlspecialchars($task['task_name']); ?>')" 
                                            class="action-btn btn-danger" title="Delete Task">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($statusTasks)): ?>
                            <div class="empty-column">
                                <div class="empty-icon">
                                    <?php 
                                    switch($status) {
                                        case 'Todo': echo '📋'; break;
                                        case 'In Progress': echo '🚀'; break;
                                        case 'Completed': echo '✅'; break;
                                    }
                                    ?>
                                </div>
                                <div class="empty-text">
                                    <?php 
                                    switch($status) {
                                        case 'Todo': echo 'No pending tasks'; break;
                                        case 'In Progress': echo 'No active tasks'; break;
                                        case 'Completed': echo 'No completed tasks'; break;
                                    }
                                    ?>
                                </div>
                                <?php if ($status == 'Todo'): ?>
                                    <button onclick="toggleTaskForm()" class="empty-action-btn">
                                        ➕ Add First Task
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <style>
    
    
    .stat-card {
            background: linear-gradient(135deg, #9a9a9a00 0%, #00000008 100%);
    padding: 20px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: none;
}

.stat-title {
    color: #000000;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 4px;
    letter-spacing: 0.5px;
}

.stat-change.positive {
    color: #a1a1a1;
    font-size: 12px;
}
.stat-card::before {
    background: linear-gradient(135deg, #9a9a9a 0%, #000000 100%);
}
    .user-avatar{
            background: linear-gradient(135deg, #9a9a9a 0%, #000000 100%);
    }
    
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
        
        .main-content {
    background: #fafafa !important;
}
       .nav-item {
    gap: 10px;
    color: #000000;
    margin-bottom: -0.75rem;
    font-size: 14px;
} 
        .nav-item:hover {
    padding: 8px 20px;
}
        .nav-item.active {
    background: #171717 !important;
    color: #fff !important;
    font-weight: 600 !important;
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

.header h1 {
    font-size: 16px;
    font-weight: 600;
}
.header p {
    font-size: 12px;
    margin-top: 0px;
}

        
         .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
                box-shadow: none !important;
            background: #171717;
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        
        /* 6-Card Grid */
        .stats-grid-6 {
            display: grid;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 1.5rem;
            margin-bottom: 2rem;
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

        /* Modal Styles */
        .form-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-header h2 {
            color: #1e293b;
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #64748b;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .close-btn:hover {
            background: #f1f5f9;
            color: #1e293b;
        }

        .form-content form {
            padding: 2rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }

        /* Enhanced Task Card Styles */
        .task-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .task-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
            border-left-color: #6366f1;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .card-title {
            flex: 1;
            font-weight: 600;
            font-size: 0.95rem;
            line-height: 1.4;
            color: #1e293b;
            margin-right: 0.5rem;
        }

        .priority-badge {
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .card-project {
            font-size: 0.8rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .client-name {
            color: #9ca3af;
            font-weight: 400;
        }

        .card-dates {
            font-size: 0.8rem;
            color: #6b7280;
            margin-bottom: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .overdue-badge {
            color: #dc2626;
            font-weight: 600;
            font-size: 0.75rem;
            background: #fee2e2;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            display: inline-block;
        }

        .urgent-badge {
            color: #d97706;
            font-weight: 600;
            font-size: 0.75rem;
            background: #fed7aa;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            display: inline-block;
        }

        .card-notes {
            font-size: 0.8rem;
            color: #64748b;
            background: #f8fafc;
            padding: 0.5rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border-left: 3px solid #e2e8f0;
        }

        .task-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 0.75rem;
            border-top: 1px solid #f1f5f9;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #ef4444;
            color: white;
            padding: 0.4rem;
            min-width: 32px;
            justify-content: center;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        /* Empty Column Styles */
        .empty-column {
            text-align: center;
            padding: 3rem 1rem;
            color: #9ca3af;
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
            background: #fafafa;
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.6;
        }

        .empty-text {
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .empty-action-btn {
            background: #6366f1;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .empty-action-btn:hover {
            background: #4f46e5;
            transform: translateY(-1px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .kanban-board {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .task-actions {
                flex-direction: column;
                gap: 0.5rem;
                align-items: stretch;
            }

            .action-buttons {
                justify-content: center;
            }
        }
    </style>

    <script>
        function toggleTaskForm() {
            const form = document.getElementById('taskForm');
            const isVisible = form.style.display !== 'none';
            
            if (isVisible) {
                form.style.display = 'none';
                document.body.style.overflow = 'auto';
                resetForm();
            } else {
                form.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        function resetForm() {
            document.getElementById('taskFormElement').reset();
        }

        function updateTaskStatus(taskId, newStatus) {
            window.location.href = `tasks.php?update_status=${taskId}&status=${encodeURIComponent(newStatus)}`;
        }

        function deleteTask(taskId, taskName) {
            if (confirm(`Are you sure you want to delete the task "${taskName}"?\n\nThis action cannot be undone.`)) {
                window.location.href = `delete-task.php?id=${taskId}`;
            }
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
            const modal = document.getElementById('taskForm');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        toggleTaskForm();
                    }
                });
            }

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const taskModal = document.getElementById('taskForm');
                    if (taskModal && taskModal.style.display !== 'none') {
                        toggleTaskForm();
                    }
                }
            });
        });
    </script>
</body>
</html>