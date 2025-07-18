<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get asset ID from URL
$asset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if asset exists and belongs to the current user
$stmt = $pdo->prepare('
    SELECT a.*, d.depreciation_method, d.useful_life, d.salvage_value, d.start_date, 
           d.accumulated_depreciation, d.last_depreciation_date,
           u.username as assigned_username, u.full_name as assigned_fullname
    FROM assets a
    LEFT JOIN depreciation d ON a.asset_id = d.asset_id
    LEFT JOIN users u ON a.assigned_to = u.user_id
    WHERE a.asset_id = ? AND a.assigned_to = ?
');
$stmt->execute([$asset_id, $_SESSION['user_id']]);
$asset = $stmt->fetch();

if (!$asset) {
    header('Location: my-assets.php');
    exit;
}

// Get asset images
$stmt = $pdo->prepare('
    SELECT * FROM asset_images 
    WHERE asset_id = ?
    ORDER BY is_primary DESC, upload_date DESC
');
$stmt->execute([$asset_id]);
$images = $stmt->fetchAll();

// Get asset history for value changes
$stmt = $pdo->prepare('
    SELECT * FROM asset_history 
    WHERE asset_id = ?
    ORDER BY change_date DESC
');
$stmt->execute([$asset_id]);
$history = $stmt->fetchAll();

// Calculate current value based on depreciation method
$current_value = $asset['current_value'] ?? $asset['purchase_cost'];
$value_change = 0;
$value_change_percent = 0;

if ($asset['purchase_cost'] && $asset['depreciation_method'] && $asset['useful_life'] && $asset['start_date']) {
    $purchase_cost = $asset['purchase_cost'];
    $salvage_value = $asset['salvage_value'] ?? 0;
    $useful_life = $asset['useful_life'];
    $start_date = new DateTime($asset['start_date']);
    $today = new DateTime();
    $years_passed = $start_date->diff($today)->y;
    
    if ($years_passed >= $useful_life) {
        $current_value = $salvage_value;
    } else {
        if ($asset['depreciation_method'] === 'straight-line') {
            $annual_depreciation = calculateStraightLineDepreciation($purchase_cost, $salvage_value, $useful_life);
            $total_depreciation = $annual_depreciation * $years_passed;
            $current_value = $purchase_cost - $total_depreciation;
        }
        // Other depreciation methods would be calculated here
    }
    
    $value_change = $current_value - $purchase_cost;
    $value_change_percent = ($value_change / $purchase_cost) * 100;
}

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="my-assets.php">My Assets</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($asset['asset_name']); ?></li>
                </ol>
            </nav>
            <h1 class="mb-0"><i class="fas fa-box me-2"></i> <?php echo htmlspecialchars($asset['asset_name']); ?></h1>
            <p class="text-muted">Asset ID: <?php echo $asset['asset_id']; ?></p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="my-assets.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-2"></i> Back to My Assets</a>
            <a href="#" class="btn btn-outline-secondary ms-2" data-bs-toggle="modal" data-bs-target="#reportIssueModal">
                <i class="fas fa-exclamation-triangle me-2"></i> Report Issue
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Asset Image Carousel -->
                            <div id="assetImageCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
                                <div class="carousel-inner rounded shadow">
                                    <?php if (count($images) > 0): ?>
                                        <?php foreach ($images as $index => $image): ?>
                                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                                <img src="../uploads/assets/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                                     class="d-block w-100" alt="Asset Image" onclick="openAssetImageModal(this.src)"
                                                     onerror="this.onerror=null; this.src='../assets/images/<?php echo htmlspecialchars($image["image_path"]); ?>'; this.onerror=function(){this.src='https://via.placeholder.com/600x400?text=No+Image+Available'}">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="carousel-item active">
                                            <img src="../uploads/assets/<?php echo htmlspecialchars($asset['asset_image'] ?? ''); ?>" 
                                                 class="d-block w-100" alt="Asset Image" onclick="openAssetImageModal(this.src)"
                                                 onerror="this.onerror=null; this.src='../assets/images/<?php echo htmlspecialchars($asset['asset_image'] ?? ''); ?>'; this.onerror=function(){this.src='https://via.placeholder.com/600x400?text=No+Image+Available'}">
                                            <img src="../assets/images/<?php echo htmlspecialchars($asset['asset_image'] ?? ''); ?>" 
                                                 class="d-block w-100 cursor-pointer" alt="Asset Image"
                                                 onclick="openAssetImageModal('../assets/images/<?php echo htmlspecialchars($asset['asset_image'] ?? ''); ?>')" 
                                                 style="cursor: pointer;"
                                                 onerror="this.src='https://via.placeholder.com/600x400?text=No+Image+Available'">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if (count($images) > 1): ?>
                                    <button class="carousel-control-prev" type="button" data-bs-target="#assetImageCarousel" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Previous</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#assetImageCarousel" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Next</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="card-title">Asset Details</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th class="ps-0">Type:</th>
                                    <td><?php echo htmlspecialchars($asset['asset_type']); ?></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Status:</th>
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
                                <tr>
                                    <th class="ps-0">Purchase Date:</th>
                                    <td><?php echo $asset['purchase_date']; ?></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Purchase Cost:</th>
                                    <td><?php echo formatCurrency($asset['purchase_cost']); ?></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Current Value:</th>
                                    <td>
                                        ₦<?php echo number_format($current_value, 2); ?>
                                        <small class="ms-2 <?php echo $value_change < 0 ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo $value_change < 0 ? '' : '+'; ?><?php echo number_format($value_change_percent, 1); ?>%
                                        </small>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Location:</th>
                                    <td><?php echo htmlspecialchars($asset['location']); ?></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Location Details:</th>
                                    <td><?php echo htmlspecialchars($asset['location_details'] ?? 'N/A'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Value History Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i> Value History</h5>
                </div>
                <div class="card-body">
                    <canvas id="valueHistoryChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Asset History Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i> Asset History</h5>
                </div>
                <div class="card-body">
                    <?php if (count($history) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Previous Value</th>
                                        <th>New Value</th>
                                        <th>Change Type</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $record): ?>
                                        <tr>
                                            <td><?php echo $record['change_date']; ?></td>
                                            <td><?php echo formatCurrency($record['previous_value']); ?></td>
                                            <td><?php echo formatCurrency($record['new_value']); ?></td>
                                            <td>
                                                <?php if ($record['change_type'] == 'depreciation'): ?>
                                                    <span class="badge bg-warning">Depreciation</span>
                                                <?php elseif ($record['change_type'] == 'appreciation'): ?>
                                                    <span class="badge bg-success">Appreciation</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info">Revaluation</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['change_reason']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> No value history records available.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Depreciation Info Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calculator me-2"></i> Depreciation Info</h5>
                </div>
                <div class="card-body">
                    <?php if ($asset['depreciation_method']): ?>
                        <table class="table table-borderless">
                            <tr>
                                <th class="ps-0">Method:</th>
                                <td><?php echo htmlspecialchars($asset['depreciation_method']); ?></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Useful Life:</th>
                                <td><?php echo $asset['useful_life']; ?> years</td>
                            </tr>
                            <tr>
                                <th class="ps-0">Salvage Value:</th>
                                <td>$<?php echo number_format($asset['salvage_value'], 2); ?></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Start Date:</th>
                                <td><?php echo $asset['start_date']; ?></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Annual Depreciation:</th>
                                <td>
                                    <?php 
                                    $annual_depreciation = calculateStraightLineDepreciation(
                                        $asset['purchase_cost'], 
                                        $asset['salvage_value'], 
                                        $asset['useful_life']
                                    );
                                    echo '$' . number_format($annual_depreciation, 2); 
                                    ?>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="progress mb-3">
                            <?php 
                            $start_date = new DateTime($asset['start_date']);
                            $today = new DateTime();
                            $years_passed = $start_date->diff($today)->y;
                            $progress = min(100, ($years_passed / $asset['useful_life']) * 100);
                            ?>
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $progress; ?>%"
                                 aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo round($progress); ?>%
                            </div>
                        </div>
                        <small class="text-muted">
                            <?php echo $years_passed; ?> years passed out of <?php echo $asset['useful_life']; ?> years useful life
                        </small>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> No depreciation information available for this asset.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Location Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i> Location</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong><?php echo htmlspecialchars($asset['location']); ?></strong>
                        <?php if ($asset['location_details']): ?>
                            <p class="mb-0"><?php echo htmlspecialchars($asset['location_details']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <div class="ratio ratio-16x9">
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3153.7462082319283!2d-122.41941548468168!3d37.77492997975892!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8085809c6c8f4459%3A0xb10ed6d9b5050fa5!2sTwitter%20HQ!5e0!3m2!1sen!2sus!4v1620164138068!5m2!1sen!2sus" 
                                    style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                        </div>
                        <small class="text-muted">Map location is approximate</small>
                    </div>
                </div>
            </div>

            <!-- Maintenance Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tools me-2"></i> Maintenance</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i> Next maintenance due on: <strong>2023-12-15</strong>
                    </div>
                    <p class="text-muted">Regular maintenance helps extend the life of your assets.</p>
                    <a href="#" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#maintenanceHistoryModal">
                        <i class="fas fa-history me-2"></i> View Maintenance History
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Issue Modal -->
<div class="modal fade" id="reportIssueModal" tabindex="-1" aria-labelledby="reportIssueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportIssueModalLabel">Report an Issue</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="issueType" class="form-label">Issue Type</label>
                        <select class="form-select" id="issueType" required>
                            <option value="">Select Issue Type</option>
                            <option value="damage">Damage</option>
                            <option value="malfunction">Malfunction</option>
                            <option value="missing">Missing</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="issueDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="issueDescription" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="issuePriority" class="form-label">Priority</label>
                        <select class="form-select" id="issuePriority" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Submit Report</button>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance History Modal -->
<div class="modal fade" id="maintenanceHistoryModal" tabindex="-1" aria-labelledby="maintenanceHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="maintenanceHistoryModalLabel">Maintenance History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Cost</th>
                                <th>Performed By</th>
                                <th>Next Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>2023-06-15</td>
                                <td>Regular maintenance check</td>
                                <td><?php echo formatCurrency(120.00); ?></td>
                                <td>John Smith</td>
                                <td>2023-12-15</td>
                            </tr>
                            <tr>
                                <td>2022-12-10</td>
                                <td>Replaced worn parts</td>
                                <td><?php echo formatCurrency(350.00); ?></td>
                                <td>Jane Doe</td>
                                <td>2023-06-15</td>
                            </tr>
                            <tr>
                                <td>2022-06-22</td>
                                <td>Initial setup and calibration</td>
                                <td><?php echo formatCurrency(200.00); ?></td>
                                <td>John Smith</td>
                                <td>2022-12-10</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Value History Chart
    const valueHistoryCtx = document.getElementById('valueHistoryChart');
    if (valueHistoryCtx) {
        const valueHistoryChart = new Chart(valueHistoryCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php 
                    // Generate dates for the chart
                    $dates = [];
                    if (count($history) > 0) {
                        foreach ($history as $record) {
                            $dates[] = "'" . $record['change_date'] . "'";
                        }
                        echo implode(', ', array_reverse($dates));
                    } else {
                        // Sample data if no history
                        echo "'2022-01-01', '2022-07-01', '2023-01-01', '2023-07-01', 'Current'";
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Asset Value',
                    data: [
                        <?php 
                        // Generate values for the chart
                        $values = [];
                        if (count($history) > 0) {
                            // Add purchase cost as first value
                            $values[] = $asset['purchase_cost'];
                            foreach ($history as $record) {
                                $values[] = $record['new_value'];
                            }
                            echo implode(', ', array_reverse($values));
                        } else {
                            // Sample data if no history
                            $purchase_cost = $asset['purchase_cost'];
                            $current = $current_value;
                            $step = ($purchase_cost - $current) / 4;
                            echo $purchase_cost . ', ';
                            echo ($purchase_cost - $step) . ', ';
                            echo ($purchase_cost - $step * 2) . ', ';
                            echo ($purchase_cost - $step * 3) . ', ';
                            echo $current;
                        }
                        ?>
                    ],
                    borderColor: 'rgba(67, 97, 238, 1)',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₦' + context.raw.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) {
                                return '₦' + value;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<!-- Asset Image Modal -->
<div class="modal fade" id="assetImageModal" tabindex="-1" aria-labelledby="assetImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assetImageModalLabel"><?php echo htmlspecialchars($asset['asset_name']); ?> - Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="assetImageModalContent" src="" alt="Asset Image" class="img-fluid" style="max-height: 70vh;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Function to open the asset image modal
function openAssetImageModal(imageSrc) {
    document.getElementById('assetImageModalContent').src = imageSrc;
    var imageModal = new bootstrap.Modal(document.getElementById('assetImageModal'));
    imageModal.show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
