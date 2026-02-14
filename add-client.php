<?php
require_once 'auth.php';
checkAuth();
require_once 'config/database.php';

// Check if this is an edit operation
$isEdit = isset($_GET['edit']);
$clientId = $_GET['edit'] ?? null;
$client = null;

if ($isEdit && $clientId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch();
        
        if (!$client) {
            header("Location: clients.php?error=" . urlencode("Client not found"));
            exit;
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle form submission
if ($_POST) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $brand_name = trim($_POST['brand_name']);
    $country = trim($_POST['country']);
    
    // Handle client_since date
    $client_since = $_POST['client_since'] ?? null;
    if ($client_since) {
        // If editing, preserve original time, otherwise use current time
        if ($isEdit && $client) {
            $original_time = date('H:i:s', strtotime($client['created_at']));
            $client_since = $client_since . ' ' . $original_time;
        } else {
            $client_since = $client_since . ' ' . date('H:i:s');
        }
    } else {
        $client_since = date('Y-m-d H:i:s'); // Use current datetime
    }
    
    try {
        if ($isEdit && $clientId) {
            // Update existing client
            $stmt = $pdo->prepare("
                UPDATE clients 
                SET name = ?, email = ?, phone = ?, brand_name = ?, country = ?, created_at = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $email, $phone, $brand_name, $country, $client_since, $clientId]);
            $success = "Client updated successfully!";
        } else {
            // Add new client
            $stmt = $pdo->prepare("
                INSERT INTO clients (name, email, phone, brand_name, country, created_at) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $email, $phone, $brand_name, $country, $client_since]);
            $success = "Client added successfully!";
        }
        
        header("Location: clients.php?success=" . urlencode($success));
        exit;
        
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "Email already exists! Please use a different email address.";
        } else {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Popular countries list
$countries = [
    'India', 'United States', 'United Kingdom', 'Canada', 'Australia', 
    'Germany', 'France', 'Singapore', 'UAE', 'Netherlands', 'Switzerland',
    'Sweden', 'Norway', 'Denmark', 'Japan', 'South Korea', 'China',
    'Brazil', 'Mexico', 'Argentina', 'South Africa', 'Egypt', 'Nigeria',
    'Kenya', 'Morocco', 'Israel', 'Turkey', 'Russia', 'Ukraine',
    'Poland', 'Italy', 'Spain', 'Portugal', 'Belgium', 'Austria',
    'Ireland', 'New Zealand', 'Thailand', 'Malaysia', 'Indonesia',
    'Philippines', 'Vietnam', 'Pakistan', 'Bangladesh', 'Sri Lanka'
];
sort($countries);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Client - FreelancePro</title>
    <link href="assets/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header fade-in">
                <div>
                    <h1><?php echo $isEdit ? 'Edit Client' : 'Add New Client'; ?></h1>
                    <p><?php echo $isEdit ? 'Update client information' : 'Add a new client to your portfolio'; ?></p>
                </div>
                <a href="clients.php" class="btn btn-secondary">
                    <span>← Back to Clients</span>
                </a>
            </header>

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

            <!-- Client Form -->
            <div class="form-container fade-in">
                <div class="form-card">
                    <div class="form-header">
                        <h2><?php echo $isEdit ? 'Edit Client Information' : 'Client Information'; ?></h2>
                    </div>

                    <form method="POST" class="client-form">
                        <div class="form-grid">
                            <div class="form-column">
                                <div class="form-group">
                                    <label class="form-label" for="name">Full Name *</label>
                                    <input type="text" id="name" name="name" class="form-input" 
                                           placeholder="Enter client's full name" 
                                           value="<?php echo $client ? htmlspecialchars($client['name']) : ''; ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="email">Email Address *</label>
                                    <input type="email" id="email" name="email" class="form-input" 
                                           placeholder="client@example.com" 
                                           value="<?php echo $client ? htmlspecialchars($client['email']) : ''; ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="phone">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" class="form-input" 
                                           placeholder="+1 (555) 123-4567" 
                                           value="<?php echo $client ? htmlspecialchars($client['phone']) : ''; ?>">
                                </div>
                            </div>

                            <div class="form-column">
                                <div class="form-group">
                                    <label class="form-label" for="brand_name">Brand/Company Name</label>
                                    <input type="text" id="brand_name" name="brand_name" class="form-input" 
                                           placeholder="Company or brand name" 
                                           value="<?php echo $client ? htmlspecialchars($client['brand_name']) : ''; ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="country">Country *</label>
                                    <select id="country" name="country" class="form-select" required>
                                        <option value="">Select Country...</option>
                                        <?php foreach ($countries as $countryOption): ?>
                                            <option value="<?php echo $countryOption; ?>" 
                                                    <?php echo ($client && $client['country'] == $countryOption) ? 'selected' : ''; ?>>
                                                <?php echo $countryOption; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="client_since">Client Since <?php echo $isEdit ? '*' : ''; ?></label>
                                    <input type="date" id="client_since" name="client_since" class="form-input" 
                                           value="<?php echo $client ? date('Y-m-d', strtotime($client['created_at'])) : date('Y-m-d'); ?>" 
                                           <?php echo $isEdit ? 'required' : ''; ?>>
                                    <?php if ($isEdit && $client): ?>
                                        <small style="color: #64748b; font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                                            📅 Original: <?php echo date('M j, Y g:i A', strtotime($client['created_at'])); ?>
                                        </small>
                                    <?php else: ?>
                                        <small style="color: #64748b; font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                                            💡 Leave as today's date or choose a different date
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="clients.php" class="btn btn-secondary">
                                <span>Cancel</span>
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <span><?php echo $isEdit ? 'Update Client' : 'Add Client'; ?></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <style>
    
        .main-content {
            background: #fafafa !important;
        }

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

        /* Form Container */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-card {
    background: white;
    border-radius: 12px;
    border: solid 1px #e5e7eb;
    overflow: hidden;
        }

        .form-header {
    background: #171717;
    color: white;
    padding: 1rem;
    text-align: center;
        }

        .form-header h2 {
            font-size: 18px;
            font-weight: 600;
        }

        .form-header p {
            opacity: 0.9;
            font-size: 12px;
        }

        /* Form Styles */
        .client-form {
            padding: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .form-column {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-input,
        .form-select {
            padding: 8px 12px;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.2s;
            background: white;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-info {
            padding: 0.75rem;
            background: #f3f4f6;
            border-radius: 8px;
            color: #6b7280;
            font-weight: 500;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
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

        .btn-secondary:hover {
            background: #e5e7eb;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <script>
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
        });
    </script>
</body>
</html>