<?php
require_once '../includes/config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
require_once '../includes/header.php';

// Get assigned assets for this user
$stmt = $pdo->prepare('SELECT COUNT(*) AS total_assets FROM assets WHERE assigned_to = ?');
$stmt->execute([$_SESSION['user_id']]);
$assigned_assets = $stmt->fetch()['total_assets'];

// Get pending maintenance requests for user's assets
$stmt = $pdo->prepare('
    SELECT COUNT(*) AS pending_maintenance 
    FROM maintenance m
    JOIN assets a ON m.asset_id = a.asset_id
    WHERE a.assigned_to = ? AND m.next_due_date IS NOT NULL AND m.next_due_date <= CURDATE()
');
$stmt->execute([$_SESSION['user_id']]);
$pending_maintenance = $stmt->fetch()['pending_maintenance'];

// Get recent asset assignments
$stmt = $pdo->prepare('
    SELECT a.*, d.depreciation_method, d.useful_life, d.salvage_value
    FROM assets a
    LEFT JOIN depreciation d ON a.asset_id = d.asset_id
    WHERE a.assigned_to = ?
    ORDER BY a.purchase_date DESC
    LIMIT 5
');
$stmt->execute([$_SESSION['user_id']]);
$recent_assets = $stmt->fetchAll();
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i> User Dashboard</h1>
            <p class="text-muted">Welcome to your asset management dashboard</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="my-assets.php" class="btn btn-primary"><i class="fas fa-boxes me-2"></i> View All My Assets</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-4 mb-lg-0">
            <div class="card h-100">
                <div class="card-body stat-card">
                    <i class="fas fa-boxes"></i>
                    <h3><?php echo $assigned_assets; ?></h3>
                    <p>Assigned Assets</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4 mb-lg-0">
            <div class="card h-100">
                <div class="card-body stat-card">
                    <i class="fas fa-tools"></i>
                    <h3><?php echo $pending_maintenance; ?></h3>
                    <p>Pending Maintenance</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4 mb-lg-0">
            <div class="card h-100">
                <div class="card-body stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <h3>0</h3>
                    <p>Upcoming Audits</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body stat-card">
                    <i class="fas fa-file-alt"></i>
                    <h3>0</h3>
                    <p>Pending Requests</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-boxes me-2"></i> My Recent Assets</h5>
                    <a href="my-assets.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (count($recent_assets) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Asset Name</th>
                                        <th>Type</th>
                                        <th>Purchase Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_assets as $asset): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($asset['asset_name']); ?></td>
                                            <td><?php echo htmlspecialchars($asset['asset_type']); ?></td>
                                            <td><?php echo $asset['purchase_date']; ?></td>
                                            <td>
                                                <?php if ($asset['status'] == 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php elseif ($asset['status'] == 'inactive'): ?>
                                                    <span class="badge bg-warning">Inactive</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Disposed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> You don't have any assets assigned to you yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i> Upcoming Maintenance</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-tools me-2"></i> You have <?php echo $pending_maintenance; ?> asset(s) due for maintenance.
                    </div>
                    <p class="text-muted">Regular maintenance helps extend the life of your assets and prevents unexpected breakdowns.</p>
                    <a href="maintenance-schedule.php" class="btn btn-outline-primary w-100">View Maintenance Schedule</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
