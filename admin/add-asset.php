<?php
require_once '../includes/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../includes/header.php';

$error = '';
$success = '';
$users = [];

// Get list of users for assignment
$stmt = $pdo->query('SELECT user_id, full_name FROM users ORDER BY full_name');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_name = trim($_POST['asset_name'] ?? '');
    $asset_type = trim($_POST['asset_type'] ?? '');
    $purchase_date = trim($_POST['purchase_date'] ?? '');
    $purchase_cost = trim($_POST['purchase_cost'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $assigned_to = intval($_POST['assigned_to'] ?? 0);

    if (empty($asset_name) || empty($asset_type) || empty($purchase_date) || empty($purchase_cost)) {
        $error = 'All required fields must be filled.';
    } else {
        // Insert new asset
        $stmt = $pdo->prepare('INSERT INTO assets (asset_name, asset_type, purchase_date, purchase_cost, location, status, assigned_to) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)');
        $success = $stmt->execute([$asset_name, $asset_type, $purchase_date, $purchase_cost, $location, $status, $assigned_to]) 
            ? 'Asset created successfully!' 
            : 'Error creating asset.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Asset - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fa; }
        .form-container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 32px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h1 class="mb-4">Add New Asset</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="post" action="add-asset.php">
            <div class="mb-3">
                <label for="asset_name" class="form-label">Asset Name</label>
                <input type="text" class="form-control" id="asset_name" name="asset_name" required>
            </div>
            
            <div class="mb-3">
                <label for="asset_type" class="form-label">Asset Type</label>
                <input type="text" class="form-control" id="asset_type" name="asset_type" required>
            </div>
            
            <div class="mb-3">
                <label for="purchase_date" class="form-label">Purchase Date</label>
                <input type="date" class="form-control" id="purchase_date" name="purchase_date" required>
            </div>
            
            <div class="mb-3">
                <label for="purchase_cost" class="form-label">Purchase Cost</label>
                <input type="number" class="form-control" id="purchase_cost" name="purchase_cost" step="0.01" required>
            </div>
            
            <div class="mb-3">
                <label for="location" class="form-label">Location</label>
                <input type="text" class="form-control" id="location" name="location" required>
            </div>
            
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="disposed">Disposed</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="assigned_to" class="form-label">Assigned To</label>
                <select class="form-select" id="assigned_to" name="assigned_to">
                    <option value="0">Not Assigned</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['user_id']; ?>">
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Create Asset</button>
            <a href="assets.php" class="btn btn-secondary ms-2">Cancel</a>
        </form>
    </div>
<?php require_once '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
