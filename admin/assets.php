<?php
require_once '../includes/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../includes/header.php';

// Handle asset deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_asset'])) {
    $asset_id = intval($_POST['asset_id']);
    $stmt = $pdo->prepare('DELETE FROM assets WHERE asset_id = ?');
    $stmt->execute([$asset_id]);
}

// Get all assets
$stmt = $pdo->query('SELECT * FROM assets ORDER BY asset_name');
$assets = $stmt->fetchAll();
?>






    <style>
        body { background: #f4f6fa; }
        .asset-container { max-width: 1200px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 32px; }
        .asset-card { margin-bottom: 20px; }
        .delete-btn { color: #dc3545; text-decoration: none; }
        .delete-btn:hover { color: #c82333; }
    </style>


    <div class="asset-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Asset Management</h1>
            <a href="add-asset.php" class="btn btn-primary">Add New Asset</a>
        </div>
        
        <div class="row">
            <?php foreach ($assets as $asset): ?>
                <div class="col-md-4">
                    <div class="card asset-card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($asset['asset_name']); ?></h5>
                            <p class="card-text">
                                <strong>Type:</strong> <?php echo htmlspecialchars($asset['asset_type']); ?><br>
                                <strong>Location:</strong> <?php echo htmlspecialchars($asset['location']); ?><br>
                                <strong>Status:</strong> <?php echo htmlspecialchars($asset['status']); ?><br>
                                <strong>Assigned To:</strong> <?php 
                                    $stmt = $pdo->prepare('SELECT full_name FROM users WHERE user_id = ?');
                                    $stmt->execute([$asset['assigned_to']]);
                                    $user = $stmt->fetch();
                                    echo htmlspecialchars($user ? $user['full_name'] : 'Not assigned');
                                ?>
                            </p>
                            <form method="post" action="assets.php" class="d-inline">
                                <input type="hidden" name="asset_id" value="<?php echo $asset['asset_id']; ?>">
                                <button type="submit" name="delete_asset" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this asset?')">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php require_once '../includes/footer.php'; ?>



