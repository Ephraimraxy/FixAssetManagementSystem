<?php
require_once '../includes/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../includes/header.php';

$error = '';
$success = '';
$assets = [];
$users = [];

// Get list of assets
$stmt = $pdo->query('SELECT asset_id, asset_name FROM assets ORDER BY asset_name');
$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get list of users
$stmt = $pdo->query('SELECT user_id, full_name FROM users ORDER BY full_name');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_id = intval($_POST['asset_id'] ?? 0);
    $user_id = intval($_POST['user_id'] ?? 0);
    $request_type = $_POST['request_type'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $description = trim($_POST['description'] ?? '');
    
    if (!$asset_id || !$user_id || !$request_type || !$description) {
        $error = 'All required fields must be filled.';
    } else {
        try {
            // Insert new maintenance request
            $stmt = $pdo->prepare('INSERT INTO maintenance_requests 
                                (asset_id, user_id, request_type, priority, description) 
                                VALUES (?, ?, ?, ?, ?)');
            
            if ($stmt->execute([$asset_id, $user_id, $request_type, $priority, $description])) {
                $success = 'Maintenance request created successfully!';
                
                // Redirect to maintenance management page after successful creation
                header('Location: maintenance.php?success=1');
                exit;
            } else {
                $error = 'Error creating maintenance request: ' . $stmt->errorInfo()[2];
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Maintenance Request - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fa; }
        .form-container { max-width: 800px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 32px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h1 class="mb-4">Add New Maintenance Request</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="post" action="add-maintenance.php">
            <div class="mb-3">
                <label for="asset_id" class="form-label">Asset</label>
                <select class="form-select" id="asset_id" name="asset_id" required>
                    <option value="">Select an asset</option>
                    <?php foreach ($assets as $asset): ?>
                        <option value="<?php echo $asset['asset_id']; ?>">
                            <?php echo htmlspecialchars($asset['asset_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="user_id" class="form-label">User</label>
                <select class="form-select" id="user_id" name="user_id" required>
                    <option value="">Select a user</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['user_id']; ?>">
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="request_type" class="form-label">Request Type</label>
                <select class="form-select" id="request_type" name="request_type" required>
                    <option value="repair">Repair</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="inspection">Inspection</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="priority" class="form-label">Priority</label>
                <select class="form-select" id="priority" name="priority" required>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Create Request</button>
            <a href="maintenance.php" class="btn btn-secondary ms-2">Cancel</a>
        </form>
    </div>
<?php require_once '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
