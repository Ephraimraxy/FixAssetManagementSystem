<?php
require_once '../includes/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}


// Get total assets
$stmt = $pdo->query('SELECT COUNT(*) AS total_assets FROM assets');
$total_assets = $stmt->fetch()['total_assets'];

// Get total users
$stmt = $pdo->query('SELECT COUNT(*) AS total_users FROM users');
$total_users = $stmt->fetch()['total_users'];

// Get pending maintenance requests (next_due_date is today or earlier)
$stmt = $pdo->prepare('SELECT COUNT(*) AS pending_maintenance FROM maintenance WHERE next_due_date IS NOT NULL AND next_due_date <= CURDATE()');
$stmt->execute();
$pending_maintenance = $stmt->fetch()['pending_maintenance'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #16a34a;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --background-color: #f8fafc;
            --card-background: #ffffff;
            --text-color: #1e293b;
            --border-radius: 12px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        body {
            background: var(--background-color);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
        }

        .welcome {
            font-size: 1.1em;
            color: var(--primary-color);
            text-align: right;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        h1 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 2rem;
            font-size: 2.5rem;
            font-weight: 700;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: var(--card-background);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s ease-in-out;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .stat-box h2 {
            margin: 0 0 1rem 0;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-color);
        }

        .stat-box p {
            margin: 0;
            color: var(--text-color);
            font-size: 1.1rem;
            font-weight: 500;
        }

        .stat-box i {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .stat-box:nth-child(1) i { color: var(--primary-color); }
        .stat-box:nth-child(2) i { color: var(--secondary-color); }
        .stat-box:nth-child(3) i { color: var(--warning-color); }

        .overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .overview-card {
            background: var(--card-background);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
        }

        .overview-card h3 {
            color: var(--text-color);
            margin-bottom: 1rem;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .overview-card .chart {
            height: 200px;
            background: #f8fafc;
            border-radius: var(--border-radius);
            padding: 1rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .action-card {
            background: var(--card-background);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            text-align: center;
            transition: transform 0.2s ease-in-out;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .action-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .action-card:nth-child(1) i { color: var(--primary-color); }
        .action-card:nth-child(2) i { color: var(--secondary-color); }
        .action-card:nth-child(3) i { color: var(--warning-color); }
        .action-card:nth-child(4) i { color: var(--success-color); }

        .action-card h4 {
            margin: 0;
            color: var(--text-color);
            font-size: 1.1rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }

            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="welcome">
            Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!
        </div>
        <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="adminNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="assets.php">Assets</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">Users</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="maintenance.php">Maintenance</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="depreciation.php">Depreciation</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <h1>Admin Dashboard</h1>

        <!-- Stats Section -->
        <div class="stats">
            <div class="stat-box">
                <i class="fas fa-box"></i>
                <h2><?php echo $total_assets; ?></h2>
                <p>Total Assets</p>
            </div>
            <div class="stat-box">
                <i class="fas fa-users"></i>
                <h2><?php echo $total_users; ?></h2>
                <p>Total Users</p>
            </div>
            <div class="stat-box">
                <i class="fas fa-tools"></i>
                <h2><?php echo $pending_maintenance; ?></h2>
                <p>Pending Maintenance</p>
            </div>
        </div>

        <!-- Overview Section -->
        <div class="overview">
            <div class="overview-card">
                <h3>Recent Activity</h3>
                <div class="chart">
                    <!-- Chart will be added via JavaScript -->
                </div>
            </div>
            <div class="overview-card">
                <h3>Asset Status</h3>
                <div class="chart">
                    <!-- Chart will be added via JavaScript -->
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="assets.php" class="action-card">
                <i class="fas fa-plus-circle"></i>
                <h4>Add New Asset</h4>
            </a>
            <a href="users.php" class="action-card">
                <i class="fas fa-user-plus"></i>
                <h4>Add New User</h4>
            </a>
            <a href="maintenance.php" class="action-card">
                <i class="fas fa-wrench"></i>
                <h4>Manage Maintenance</h4>
            </a>
            <a href="depreciation.php" class="action-card">
                <i class="fas fa-chart-line"></i>
                <h4>View Depreciation</h4>
            </a>
        </div>
    </div>
<?php  ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="dashboard.js"></script>
</body>
</html>
