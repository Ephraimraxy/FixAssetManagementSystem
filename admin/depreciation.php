<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Process new depreciation settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asset_id'])) {
    $asset_id = $_POST['asset_id'];
    $depreciation_method = $_POST['depreciation_method'];
    $useful_life = $_POST['useful_life'];
    $salvage_value = $_POST['salvage_value'];
    $start_date = $_POST['start_date'];
    
    // Check if depreciation record exists
    $stmt = $pdo->prepare('SELECT * FROM depreciation WHERE asset_id = ?');
    $stmt->execute([$asset_id]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        // Update existing record
        $stmt = $pdo->prepare('
            UPDATE depreciation 
            SET depreciation_method = ?, useful_life = ?, salvage_value = ?, start_date = ? 
            WHERE asset_id = ?
        ');
        $stmt->execute([$depreciation_method, $useful_life, $salvage_value, $start_date, $asset_id]);
        $success_message = "Depreciation settings updated successfully!";
    } else {
        // Insert new record
        $stmt = $pdo->prepare('
            INSERT INTO depreciation (asset_id, depreciation_method, useful_life, salvage_value, start_date) 
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$asset_id, $depreciation_method, $useful_life, $salvage_value, $start_date]);
        $success_message = "Depreciation settings added successfully!";
    }
}

// Get assets with depreciation info
$stmt = $pdo->query('
    SELECT a.*, d.depreciation_id, d.depreciation_method, d.useful_life, d.salvage_value, 
           d.start_date, d.accumulated_depreciation, d.last_depreciation_date
    FROM assets a
    LEFT JOIN depreciation d ON a.asset_id = d.asset_id
    ORDER BY a.asset_name
');
$assets = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="mb-0"><i class="fas fa-chart-line me-2"></i> Asset Depreciation</h1>
            <p class="text-muted">Manage depreciation settings for your assets</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#runDepreciationModal">
                <i class="fas fa-calculator me-2"></i> Run Depreciation
            </button>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-boxes me-2"></i> Assets Depreciation Overview</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Asset</th>
                            <th>Purchase Date</th>
                            <th>Purchase Cost</th>
                            <th>Method</th>
                            <th>Useful Life</th>
                            <th>Salvage Value</th>
                            <th>Current Value</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($asset['asset_name']); ?></td>
                                <td><?php echo $asset['purchase_date']; ?></td>
                                <td>$<?php echo number_format($asset['purchase_cost'], 2); ?></td>
                                <td>
                                    <?php 
                                    if ($asset['depreciation_method']) {
                                        echo htmlspecialchars($asset['depreciation_method']);
                                    } else {
                                        echo '<span class="text-muted">Not set</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($asset['useful_life']) {
                                        echo $asset['useful_life'] . ' years';
                                    } else {
                                        echo '<span class="text-muted">Not set</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($asset['salvage_value']) {
                                        echo '$' . number_format($asset['salvage_value'], 2);
                                    } else {
                                        echo '<span class="text-muted">Not set</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($asset['purchase_cost'] && $asset['depreciation_method'] && $asset['useful_life'] && $asset['start_date']) {
                                        // Calculate current value based on straight-line depreciation
                                        $purchase_cost = $asset['purchase_cost'];
                                        $salvage_value = $asset['salvage_value'] ?? 0;
                                        $useful_life = $asset['useful_life'];
                                        $start_date = new DateTime($asset['start_date']);
                                        $today = new DateTime();
                                        $years_passed = $start_date->diff($today)->y;
                                        
                                        if ($years_passed >= $useful_life) {
                                            $current_value = $salvage_value;
                                        } else {
                                            $annual_depreciation = calculateStraightLineDepreciation($purchase_cost, $salvage_value, $useful_life);
                                            $total_depreciation = $annual_depreciation * $years_passed;
                                            $current_value = $purchase_cost - $total_depreciation;
                                        }
                                        
                                        echo '$' . number_format($current_value, 2);
                                    } else {
                                        echo '<span class="text-muted">Not calculated</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#depreciationModal"
                                            data-asset-id="<?php echo $asset['asset_id']; ?>"
                                            data-asset-name="<?php echo htmlspecialchars($asset['asset_name']); ?>"
                                            data-method="<?php echo htmlspecialchars($asset['depreciation_method'] ?? ''); ?>"
                                            data-useful-life="<?php echo $asset['useful_life'] ?? ''; ?>"
                                            data-salvage-value="<?php echo $asset['salvage_value'] ?? ''; ?>"
                                            data-start-date="<?php echo $asset['start_date'] ?? ''; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> About Depreciation Methods</h5>
                </div>
                <div class="card-body">
                    <h6>Straight-Line Depreciation</h6>
                    <p>The simplest and most commonly used method. The formula is:</p>
                    <p><code>Annual Depreciation = (Cost - Salvage Value) / Useful Life</code></p>
                    
                    <h6>Declining Balance</h6>
                    <p>Accelerated depreciation method that applies a higher depreciation rate in the earlier years.</p>
                    
                    <h6>Sum-of-the-Years'-Digits (SYD)</h6>
                    <p>Another accelerated method that allocates more depreciation in the earlier years.</p>
                    
                    <h6>Units of Production</h6>
                    <p>Based on actual usage or production, not time. Useful for assets whose value is closely related to output.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Depreciation Summary</h5>
                </div>
                <div class="card-body">
                    <canvas id="depreciationChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Depreciation Settings Modal -->
<div class="modal fade" id="depreciationModal" tabindex="-1" aria-labelledby="depreciationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="depreciationModalLabel">Depreciation Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" id="asset_id" name="asset_id">
                    <div class="mb-3">
                        <label for="asset_name" class="form-label">Asset</label>
                        <input type="text" class="form-control" id="asset_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="depreciation_method" class="form-label">Depreciation Method</label>
                        <select class="form-select" id="depreciation_method" name="depreciation_method" required>
                            <option value="">Select Method</option>
                            <option value="straight-line">Straight-Line</option>
                            <option value="declining-balance">Declining Balance</option>
                            <option value="sum-of-years-digits">Sum-of-Years'-Digits</option>
                            <option value="units-of-production">Units of Production</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="useful_life" class="form-label">Useful Life (Years)</label>
                        <input type="number" class="form-control" id="useful_life" name="useful_life" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="salvage_value" class="form-label">Salvage Value ($)</label>
                        <input type="number" class="form-control" id="salvage_value" name="salvage_value" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Run Depreciation Modal -->
<div class="modal fade" id="runDepreciationModal" tabindex="-1" aria-labelledby="runDepreciationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="runDepreciationModalLabel">Run Depreciation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>This will calculate and update the accumulated depreciation for all assets up to today's date.</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> This operation will update the financial values of your assets. It's recommended to run this at the end of each financial period.
                </div>
                <div class="mb-3">
                    <label for="depreciation_date" class="form-label">Depreciation Date</label>
                    <input type="date" class="form-control" id="depreciation_date" name="depreciation_date" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Run Depreciation</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize depreciation modal
    const depreciationModal = document.getElementById('depreciationModal');
    if (depreciationModal) {
        depreciationModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const assetId = button.getAttribute('data-asset-id');
            const assetName = button.getAttribute('data-asset-name');
            const method = button.getAttribute('data-method');
            const usefulLife = button.getAttribute('data-useful-life');
            const salvageValue = button.getAttribute('data-salvage-value');
            const startDate = button.getAttribute('data-start-date');
            
            document.getElementById('asset_id').value = assetId;
            document.getElementById('asset_name').value = assetName;
            
            if (method) document.getElementById('depreciation_method').value = method;
            if (usefulLife) document.getElementById('useful_life').value = usefulLife;
            if (salvageValue) document.getElementById('salvage_value').value = salvageValue;
            if (startDate) document.getElementById('start_date').value = startDate;
        });
    }
    
    // Sample chart data - in a real application, this would come from the server
    const ctx = document.getElementById('depreciationChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Computers', 'Furniture', 'Vehicles', 'Machinery', 'Buildings'],
                datasets: [{
                    label: 'Original Cost',
                    data: [50000, 30000, 75000, 120000, 500000],
                    backgroundColor: 'rgba(67, 97, 238, 0.7)',
                }, {
                    label: 'Current Value',
                    data: [30000, 24000, 45000, 96000, 450000],
                    backgroundColor: 'rgba(114, 9, 183, 0.7)',
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Value ($)'
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
