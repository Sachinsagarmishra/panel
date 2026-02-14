<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

// Handle search
$search = $_GET['search'] ?? '';

// Get clients with search functionality
$sql = "SELECT * FROM clients WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR brand_name LIKE ? OR country LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll();

    // Calculate statistics
    $totalClients = count($clients);
    $countries = array_unique(array_column($clients, 'country'));
    $countriesCount = count(array_filter($countries));

    // Get most common country
    $countryStats = array_count_values(array_filter(array_column($clients, 'country')));
    $topCountry = $countryStats ? array_keys($countryStats)[0] : 'N/A';

} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Clients</title>
    <link href="assets/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

</head>

<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header fade-in">
                <div>
                    <h1>Clients</h1>
                    <p>Manage your client relationships</p>
                </div>
                <a href="add-client.php" class="btn btn-primary">
                    <span>Add Client</span>
                </a>
            </header>

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

            <!-- Search and Filters -->
            <div class="filters fade-in">
                <div class="filter-group">
                    <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                        <input type="text" name="search" class="form-input"
                            placeholder="Search clients, brands, countries..."
                            value="<?php echo htmlspecialchars($search); ?>" style="width: 300px;">
                        <button type="submit" class="btn btn-secondary">Search</button>
                        <?php if ($search): ?>
                            <a href="clients.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="filter-group">
                    <a href="add-client.php" class="btn btn-primary">
                        <span>Add New Client</span>
                    </a>
                </div>
            </div>

            <!-- Clients Table -->
            <div class="table-container fade-in">
                <div class="table-header">
                    <div class="table-title">
                        <span>All Clients (<?php echo count($clients); ?>)</span>
                    </div>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Client Information</th>
                            <th>Contact Details</th>
                            <th>Location</th>
                            <th>Brand/Company</th>
                            <th>Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #64748b; padding: 3rem;">
                                    <div style="font-size: 3rem; margin-bottom: 1rem;">👥</div>
                                    <div style="font-weight: 600; margin-bottom: 0.5rem;">No clients yet</div>
                                    <div>Add your first client to get started!</div>
                                    <a href="add-client.php" class="btn btn-primary" style="margin-top: 1rem;">Add
                                        Client</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div class="client-avatar">
                                                <?php echo strtoupper(substr($client['name'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <div style="font-size: 14px;font-weight: 600; color: #1e293b;">
                                                    <?php echo htmlspecialchars($client['name']); ?>
                                                </div>
                                                <div style="color: #64748b; font-size: 12px;">
                                                    ID: #<?php echo str_pad($client['id'], 4, '0', STR_PAD_LEFT); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div style="margin-bottom: 0.5rem;">
                                                <strong style="font-size: 12px;">Email:</strong>
                                                <div style="color: #000;font-size: 14px; text-decoration: underline;">
                                                    <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>"
                                                        style="color: inherit; text-decoration: none;">
                                                        <?php echo htmlspecialchars($client['email']); ?>
                                                    </a>
                                                </div>
                                            </div>
                                            <?php if ($client['phone']): ?>
                                                <div>
                                                    <strong style="font-size: 12px;">Phone:</strong>
                                                    <div style="color: #868b89;font-size: 14px;">
                                                        <a href="tel:<?php echo htmlspecialchars($client['phone']); ?>"
                                                            style="color: inherit; text-decoration: none;">
                                                            <?php echo htmlspecialchars($client['phone']); ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="font-size: 1.2rem;">
                                                <?php
                                                // Country flag emojis
                                                $countryFlags = [
                                                    'India' => '🇮🇳',
                                                    'United States' => '🇺🇸',
                                                    'United Kingdom' => '🇬🇧',
                                                    'Canada' => '🇨🇦',
                                                    'Australia' => '🇦🇺',
                                                    'Germany' => '🇩🇪',
                                                    'France' => '🇫🇷',
                                                    'Singapore' => '🇸🇬',
                                                    'UAE' => '🇦🇪',
                                                    'Netherlands' => '🇳🇱'
                                                ];
                                                echo $countryFlags[$client['country']] ?? '🌍';
                                                ?>
                                            </span>
                                            <div>
                                                <div style="font-weight: 600; color: #1e293b;">
                                                    <?php echo htmlspecialchars($client['country'] ?? 'Not specified'); ?>
                                                </div>
                                                <div style="color: #64748b; font-size: 0.875rem;">
                                                    Location
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($client['brand_name']): ?>
                                            <div>
                                                <div style="font-weight: 600; color: #1e293b; margin-bottom: 0.25rem;">
                                                    <?php echo htmlspecialchars($client['brand_name']); ?>
                                                </div>
                                                <div style="color: #64748b; font-size: 0.875rem;">
                                                    Company/Brand
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #94a3b8;">Individual</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.875rem;">
                                            <div style="font-weight: 600; color: #1e293b;">
                                                <?php echo date('M j, Y', strtotime($client['created_at'])); ?>
                                            </div>
                                            <div style="color: #64748b;">
                                                <?php echo date('g:i A', strtotime($client['created_at'])); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <!--<button onclick="viewClient(<?php echo $client['id']; ?>)" -->
                                            <!--        class="action-btn btn-secondary" title="View Details">-->
                                            <!--  <i class="fa-regular fa-eye"></i>-->
                                            <!--</button>-->
                                            <button onclick="editClient(<?php echo $client['id']; ?>)"
                                                class="action-btn btn-secondary" title="Edit Client">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>
                                            <button
                                                onclick="deleteClient(<?php echo $client['id']; ?>, '<?php echo htmlspecialchars($client['name']); ?>')"
                                                class="action-btn btn-danger" title="Delete Client">
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

        /* Client Avatar */
        .client-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #9a9a9a 0%, #000000 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.875rem;
            flex-shrink: 0;
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

            .client-avatar {
                width: 40px;
                height: 40px;
                font-size: 0.75rem;
            }
        }
    </style>

    <script>
        function viewClient(id) {
            // Implement view client details
            alert('View client details for ID: ' + id + '\n\nThis will show client information, projects, and communication history.');
        }

        function editClient(id) {
            window.location.href = 'add-client.php?edit=' + id;
        }

        function emailClient(email) {
            window.open('mailto:' + email, '_blank');
        }

        function deleteClient(clientId, clientName) {
            createDeleteModal(clientId, clientName);
        }

        function createDeleteModal(clientId, clientName) {
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
                            <div class="delete-title">Delete Client</div>
                        </div>
                        
                        <div class="delete-message">
                            <p><strong>Client:</strong> ${clientName}</p>
                            <br>
                            <p>⚠️ <strong>Warning:</strong> This action will permanently delete:</p>
                            <ul style="margin: 1rem 0; padding-left: 1.5rem; color: #dc2626;">
                                <li>Client profile and contact information</li>
                                <li>All associated projects</li>
                                <li>Project details and deliverables</li>
                                <li>Communication history</li>
                                <li>All related invoices and proposals</li>
                            </ul>
                            <p><strong>This action cannot be undone!</strong></p>
                        </div>
                        
                        <div class="delete-actions">
                            <button class="delete-cancel-btn" onclick="closeDeleteModal()">
                                ❌ Cancel
                            </button>
                            <button class="delete-confirm-btn" onclick="confirmDelete(${clientId})">
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

        function confirmDelete(clientId) {
            // Show loading state
            const confirmBtn = document.querySelector('.delete-confirm-btn');
            const cancelBtn = document.querySelector('.delete-cancel-btn');

            confirmBtn.innerHTML = '⏳ Deleting...';
            confirmBtn.disabled = true;
            cancelBtn.disabled = true;

            // Redirect to delete script
            setTimeout(() => {
                window.location.href = `delete-client.php?id=${clientId}`;
            }, 500);
        }

        // Auto-hide alert messages
        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });

            // Close modal when clicking outside
            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('delete-confirmation')) {
                    closeDeleteModal();
                }
            });

            // Close modal with Escape key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeDeleteModal();
                }
            });
        });
    </script>
</body>

</html>