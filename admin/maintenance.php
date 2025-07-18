<?php
require_once '../includes/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../includes/header.php';

// Handle maintenance request status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = intval($_POST['request_id']);
    $status = $_POST['status'];
    $stmt = $pdo->prepare('UPDATE maintenance_requests SET status = ? WHERE request_id = ?');
    $stmt->execute([$status, $request_id]);
}

// Get all maintenance requests
try {
    $stmt = $pdo->query('SELECT mr.*, a.asset_name, u.full_name 
                        FROM maintenance_requests mr 
                        LEFT JOIN assets a ON mr.asset_id = a.asset_id 
                        LEFT JOIN users u ON mr.user_id = u.user_id 
                        ORDER BY mr.request_date DESC');
    $maintenance_requests = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error fetching maintenance requests: ' . $e->getMessage();
    $maintenance_requests = [];
}

// Display error if present
if (isset($error)) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fa; }
        .maintenance-container { max-width: 1200px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 32px; }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.9em;
        }
        .badge-pending { background-color: #ffc107; color: #000; }
        .badge-approved { background-color: #17a2b8; color: #fff; }
        .badge-in-progress { background-color: #007bff; color: #fff; }
        .badge-completed { background-color: #28a745; color: #fff; }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Maintenance Management</h1>
            <a href="add-maintenance.php" class="btn btn-primary">Add New Request</a>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Asset</th>
                        <th>User</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maintenance_requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['asset_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($request['full_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($request['description']); ?></td>
                            <td>
                                <span class="status-badge badge-<?php echo $request['status']; ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </td>
                            <td><?php 
                                $date = $request['request_date'] ?? '1970-01-01 00:00:00';
                                echo date('Y-m-d H:i', strtotime($date));
                            ?></td>
                            <td>
                                <a href="view-request.php?id=<?php echo $request['request_id']; ?>" class="btn btn-sm btn-info me-2">View</a>
                                <form method="post" action="maintenance.php" class="d-inline">
                                    <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                    <select name="status" onchange="this.form.submit()" class="form-select form-select-sm">
                                        <option value="pending" <?php echo $request['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo $request['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="in_progress" <?php echo $request['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="completed" <?php echo $request['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php require_once '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
