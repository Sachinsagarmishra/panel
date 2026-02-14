<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Handle Add/Edit Goal
if (isset($_POST['save_goal'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $priority = $_POST['priority'];
    $target_date = $_POST['target_date'] ?: null;
    $motivation_note = $_POST['motivation_note'];
    $goal_id = $_POST['goal_id'] ?? null;

    try {
        if ($goal_id) {
            $stmt = $pdo->prepare("UPDATE goals SET title = ?, description = ?, category = ?, priority = ?, target_date = ?,
motivation_note = ? WHERE id = ?");
            $stmt->execute([$title, $description, $category, $priority, $target_date, $motivation_note, $goal_id]);
            $msg = "Goal updated!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO goals (title, description, category, priority, target_date, motivation_note, status)
VALUES (?, ?, ?, ?, ?, ?, 'Dreaming')");
            $stmt->execute([$title, $description, $category, $priority, $target_date, $motivation_note]);
            $goal_id = $pdo->lastInsertId();
            $msg = "New goal added to your bucket list!";
        }

        // Handle Milestones
        if (isset($_POST['milestones'])) {
            // Simple approach: clear and re-add milestones if editing
            if ($goal_id) {
                $pdo->prepare("DELETE FROM goal_milestones WHERE goal_id = ?")->execute([$goal_id]);
            }
            $insM = $pdo->prepare("INSERT INTO goal_milestones (goal_id, title, is_completed) VALUES (?, ?, 0)");
            foreach ($_POST['milestones'] as $mTitle) {
                if (!empty($mTitle)) {
                    $insM->execute([$goal_id, $mTitle]);
                }
            }
        }

        header("Location: bucket-list.php?success=" . urlencode($msg));
        exit;
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Toggle Milestone Status
if (isset($_GET['toggle_milestone'])) {
    $mid = $_GET['toggle_milestone'];
    $status = $_GET['status'];
    $newStatus = $status == '1' ? 0 : 1;
    $pdo->prepare("UPDATE goal_milestones SET is_completed = ? WHERE id = ?")->execute([$newStatus, $mid]);

    // Check if all milestones are done to update goal status
    $stmt = $pdo->prepare("SELECT goal_id FROM goal_milestones WHERE id = ?");
    $stmt->execute([$mid]);
    $gid = $stmt->fetchColumn();

    $total = $pdo->prepare("SELECT COUNT(*) FROM goal_milestones WHERE goal_id = ?");
    $total->execute([$gid]);
    $t = $total->fetchColumn();

    $done = $pdo->prepare("SELECT COUNT(*) FROM goal_milestones WHERE goal_id = ? AND is_completed = 1");
    $done->execute([$gid]);
    $d = $done->fetchColumn();

    if ($t > 0 && $t == $d) {
        $pdo->prepare("UPDATE goals SET status = 'Accomplished' WHERE id = ?")->execute([$gid]);
    } elseif ($d > 0) {
        $pdo->prepare("UPDATE goals SET status = 'In Progress' WHERE id = ?")->execute([$gid]);
    } else {
        $pdo->prepare("UPDATE goals SET status = 'Dreaming' WHERE id = ?")->execute([$gid]);
    }

    header("Location: bucket-list.php#goal-" . $gid);
    exit;
}

// Delete Goal
if (isset($_GET['delete_goal'])) {
    $pdo->prepare("DELETE FROM goals WHERE id = ?")->execute([$_GET['delete_goal']]);
    header("Location: bucket-list.php?success=Goal deleted");
    exit;
}

// Fetch Goals
$goals = $pdo->query("SELECT * FROM goals ORDER BY FIELD(priority, 'High', 'Medium', 'Low'), target_date
ASC")->fetchAll();
foreach ($goals as &$g) {
    $mStmt = $pdo->prepare("SELECT * FROM goal_milestones WHERE goal_id = ?");
    $mStmt->execute([$g['id']]);
    $g['milestones'] = $mStmt->fetchAll();

    $totalM = count($g['milestones']);
    $completedM = 0;
    foreach ($g['milestones'] as $m) {
        if ($m['is_completed'])
            $completedM++;
    }
    $g['progress'] = $totalM > 0 ? round(($completedM / $totalM) * 100) : 0;
}

include 'includes/header.php';
?>

<div class="header">
    <div>
        <h1>🪣 Advance Bucket List</h1>
        <p>Dream big, plan smart, and track your achievements.</p>
    </div>
    <button onclick="openGoalModal()" class="btn btn-primary" style="background: #000;">
        <i class="fas fa-plus"></i> Add New Goal
    </button>
</div>

<?php if (isset($_GET['success'])): ?>
    <div
        style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #bbf7d0;">
        ✅
        <?php echo htmlspecialchars($_GET['success']); ?>
    </div>
<?php endif; ?>

<!-- Goals Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
    <?php foreach ($goals as $g): ?>
        <div id="goal-<?php echo $g['id']; ?>" class="stat-card"
            style="display: flex; flex-direction: column; gap: 1rem; padding: 1.5rem; background: white; border-radius: 16px; position: relative;">

            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <span class="status <?php echo strtolower(str_replace(' ', '-', $g['status'])); ?>"
                    style="font-size: 0.65rem;">
                    <?php echo $g['status']; ?>
                </span>
                <div style="display: flex; gap: 0.5rem;">
                    <button onclick='editGoal(<?php echo json_encode($g); ?>)'
                        style="background: none; border: none; color: #64748b; cursor: pointer;" title="Edit">
                        <i class="fas fa-pen-to-square"></i>
                    </button>
                    <a href="?delete_goal=<?php echo $g['id']; ?>" onclick="return confirm('Are you sure?')"
                        style="color: #ef4444;" title="Delete">
                        <i class="fas fa-trash-can"></i>
                    </a>
                </div>
            </div>

            <div>
                <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 0.25rem; color: #1e293b;">
                    <?php echo htmlspecialchars($g['title']); ?>
                </h3>
                <div
                    style="display: flex; gap: 0.5rem; align-items: center; font-size: 0.75rem; color: #64748b; margin-bottom: 0.5rem;">
                    <span style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                        <?php echo $g['category']; ?>
                    </span>
                    <?php if ($g['target_date']): ?>
                        <span><i class="far fa-calendar-alt"></i>
                            <?php echo date('M j, Y', strtotime($g['target_date'])); ?>
                        </span>
                    <?php endif; ?>
                    <span
                        style="color: <?php echo $g['priority'] == 'High' ? '#ef4444' : ($g['priority'] == 'Medium' ? '#f59e0b' : '#10b981'); ?>; font-weight: 700;">
                        <?php echo $g['priority']; ?> Priority
                    </span>
                </div>
                <p style="font-size: 0.875rem; color: #64748b; line-height: 1.5;">
                    <?php echo htmlspecialchars($g['description']); ?>
                </p>
            </div>

            <!-- Progress Bar -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span style="font-size: 0.75rem; font-weight: 600; color: #1e293b;">Progress</span>
                    <span style="font-size: 0.75rem; font-weight: 700; color: #4f46e5;">
                        <?php echo $g['progress']; ?>%
                    </span>
                </div>
                <div style="width: 100%; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                    <div
                        style="width: <?php echo $g['progress']; ?>%; height: 100%; background: linear-gradient(90deg, #4f46e5, #0ea5e9); transition: width 0.3s ease;">
                    </div>
                </div>
            </div>

            <!-- Milestones List -->
            <div style="background: #f8fafc; padding: 1rem; border-radius: 12px;">
                <h4
                    style="font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
                    Milestones</h4>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($g['milestones'] as $m): ?>
                        <a href="?toggle_milestone=<?php echo $m['id']; ?>&status=<?php echo $m['is_completed']; ?>"
                            style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; color: <?php echo $m['is_completed'] ? '#94a3b8' : '#334155'; ?>; font-size: 0.875rem;">
                            <i class="<?php echo $m['is_completed'] ? 'fas fa-check-circle' : 'far fa-circle'; ?>"
                                style="color: <?php echo $m['is_completed'] ? '#10b981' : '#cbd5e1'; ?>;"></i>
                            <span style="<?php echo $m['is_completed'] ? 'text-decoration: line-through;' : ''; ?>">
                                <?php echo htmlspecialchars($m['title']); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (empty($g['milestones'])): ?>
                        <p style="font-size: 0.75rem; color: #94a3b8; font-style: italic;">No milestones added yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($g['motivation_note']): ?>
                <div
                    style="font-size: 0.75rem; color: #64748b; background: #fffbeb; padding: 0.75rem; border-radius: 8px; border-left: 3px solid #f59e0b;">
                    <strong>💡 Motivation:</strong>
                    <?php echo htmlspecialchars($g['motivation_note']); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>


<!-- Add/Edit Goal Modal -->
<div id="goalModal" class="form-modal" style="display: none;">
    <div class="form-content" style="max-width: 600px;">
        <div class="form-header">
            <h2 id="modalTitle">🚀 Set a New Goal</h2>
            <button onclick="closeGoalModal()" class="close-btn">✕</button>
        </div>
        <form method="POST" style="padding: 1.5rem;">
            <input type="hidden" name="save_goal" value="1">
            <input type="hidden" name="goal_id" id="modal_goal_id">

            <div class="form-group">
                <label class="form-label">Goal Title</label>
                <input type="text" name="title" id="modal_title" class="form-input"
                    placeholder="What do you want to achieve?" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" id="modal_category" class="form-select">
                        <option value="Personal">Personal</option>
                        <option value="Business">Business</option>
                        <option value="Travel">Travel</option>
                        <option value="Health">Health</option>
                        <option value="Wealth">Wealth</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select name="priority" id="modal_priority" class="form-select">
                        <option value="High">High Priority</option>
                        <option value="Medium" selected>Medium Priority</option>
                        <option value="Low">Low Priority</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Short Description</label>
                <textarea name="description" id="modal_description" class="form-input" style="height: 80px;"
                    placeholder="Briefly describe your goal..."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Target Date</label>
                <input type="date" name="target_date" id="modal_target_date" class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">Milestones (Sub-tasks)</label>
                <div id="milestonesContainer" style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" name="milestones[]" class="form-input" placeholder="Add a step...">
                        <button type="button" onclick="addMilestoneRow()" class="btn btn-secondary"
                            style="padding: 0 1rem;"><i class="fas fa-plus"></i></button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Motivation Note (Why is this important?)</label>
                <input type="text" name="motivation_note" id="modal_motivation" class="form-input"
                    placeholder="Success is the only option...">
            </div>

            <div class="form-actions" style="margin-top: 2rem;">
                <button type="button" onclick="closeGoalModal()" class="btn btn-secondary">Discard</button>
                <button type="submit" class="btn btn-primary" style="background: #000;">Save Goal</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openGoalModal() {
        document.getElementById('modalTitle').innerText = '🚀 Set a New Goal';
        document.getElementById('modal_goal_id').value = '';
        document.getElementById('modal_title').value = '';
        document.getElementById('modal_description').value = '';
        document.getElementById('modal_category').value = 'Personal';
        document.getElementById('modal_priority').value = 'Medium';
        document.getElementById('modal_target_date').value = '';
        document.getElementById('modal_motivation').value = '';
        document.getElementById('milestonesContainer').innerHTML = `
        <div style="display: flex; gap: 0.5rem;">
            <input type="text" name="milestones[]" class="form-input" placeholder="Add a step...">
            <button type="button" onclick="addMilestoneRow()" class="btn btn-secondary" style="padding: 0 1rem;"><i class="fas fa-plus"></i></button>
        </div>
    `;
        document.getElementById('goalModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function addMilestoneRow(val = '') {
        const div = document.createElement('div');
        div.style = "display: flex; gap: 0.5rem;";
        div.innerHTML = `
        <input type="text" name="milestones[]" class="form-input" placeholder="Add another step..." value="${val}">
        <button type="button" onclick="this.parentElement.remove()" class="btn btn-secondary" style="padding: 0 1rem; color: #ef4444;"><i class="fas fa-times"></i></button>
    `;
        document.getElementById('milestonesContainer').appendChild(div);
    }

    function closeGoalModal() {
        document.getElementById('goalModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function editGoal(g) {
        openGoalModal();
        document.getElementById('modalTitle').innerText = '⚙️ Edit Your Goal';
        document.getElementById('modal_goal_id').value = g.id;
        document.getElementById('modal_title').value = g.title;
        document.getElementById('modal_description').value = g.description;
        document.getElementById('modal_category').value = g.category;
        document.getElementById('modal_priority').value = g.priority;
        document.getElementById('modal_target_date').value = g.target_date;
        document.getElementById('modal_motivation').value = g.motivation_note;

        const container = document.getElementById('milestonesContainer');
        container.innerHTML = '';
        if (g.milestones && g.milestones.length > 0) {
            g.milestones.forEach((m, idx) => {
                const div = document.createElement('div');
                div.style = "display: flex; gap: 0.5rem;";
                div.innerHTML = `
                <input type="text" name="milestones[]" class="form-input" value="${m.title}">
                ${idx === 0 ?
                        `<button type="button" onclick="addMilestoneRow()" class="btn btn-secondary" style="padding: 0 1rem;"><i class="fas fa-plus"></i></button>` :
                        `<button type="button" onclick="this.parentElement.remove()" class="btn btn-secondary" style="padding: 0 1rem; color: #ef4444;"><i class="fas fa-times"></i></button>`}
            `;
                container.appendChild(div);
            });
        } else {
            container.innerHTML = `
            <div style="display: flex; gap: 0.5rem;">
                <input type="text" name="milestones[]" class="form-input" placeholder="Add a step...">
                <button type="button" onclick="addMilestoneRow()" class="btn btn-secondary" style="padding: 0 1rem;"><i class="fas fa-plus"></i></button>
            </div>
        `;
        }
    }
</script>

<?php include 'includes/footer.php'; ?>