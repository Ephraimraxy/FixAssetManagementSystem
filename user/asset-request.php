<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_type = $_POST['request_type'] ?? '';
    $asset_id = isset($_POST['asset_id']) ? (int)$_POST['asset_id'] : null;
    $asset_type = $_POST['asset_type'] ?? null;
    $reason = $_POST['reason'] ?? '';
    
    // Validate input
    if (empty($request_type) || empty($reason)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        // For new_asset requests, asset_type is required
        if ($request_type === 'new_asset' && empty($asset_type)) {
            $error_message = 'Please specify the asset type for new asset requests.';
        } 
        // For transfer, disposal, return requests, asset_id is required
        elseif (in_array($request_type, ['transfer', 'disposal', 'return']) && empty($asset_id)) {
            $error_message = 'Please select an asset for this request type.';
        } else {
            // Insert asset request
            $stmt = $pdo->prepare('
                INSERT INTO asset_requests 
                (user_id, request_type, asset_id, asset_type, reason, status) 
                VALUES (?, ?, ?, ?, ?, "pending")
            ');
            
            if ($stmt->execute([$_SESSION['user_id'], $request_type, $asset_id, $asset_type, $reason])) {
                $success_message = 'Asset request submitted successfully.';
            } else {
                $error_message = 'Failed to submit asset request. Please try again.';
            }
        }
    }
}

// Get user's assets for dropdown
$stmt = $pdo->prepare('
    SELECT asset_id, asset_name, asset_type 
    FROM assets 
    WHERE assigned_to = ?
    ORDER BY asset_name
');
$stmt->execute([$_SESSION['user_id']]);
$assets = $stmt->fetchAll();

// Get all asset types for dropdown
$stmt = $pdo->query('SELECT DISTINCT asset_type FROM assets ORDER BY asset_type');
$asset_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="mb-0"><i class="fas fa-clipboard-check me-2 text-primary"></i> Asset Request</h1>
            <p class="text-muted">Request new assets, transfers, or returns</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="requests.php" class="btn btn-outline-primary">
                <i class="fas fa-list-alt me-2"></i> View My Requests
            </a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="post" action="asset-request.php">
                        <div class="mb-3">
                            <label for="request_type" class="form-label">Request Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="request_type" name="request_type" required onchange="toggleRequestFields()">
                                <option value="">-- Select Request Type --</option>
                                <option value="new_asset">New Asset (Request a new asset)</option>
                                <option value="transfer">Transfer (Request transfer of an existing asset)</option>
                                <option value="disposal">Disposal (Request disposal of an asset)</option>
                                <option value="return">Return (Return an assigned asset)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="asset_type_field" style="display: none;">
                            <label for="asset_type" class="form-label">Asset Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="asset_type" name="asset_type">
                                <option value="">-- Select Asset Type --</option>
                                <?php foreach ($asset_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>">
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3" id="other_type_field" style="display: none;">
                            <label for="other_type" class="form-label">Specify Asset Type</label>
                            <input type="text" class="form-control" id="other_type" name="other_type" placeholder="Specify the asset type">
                        </div>
                        
                        <div class="mb-3" id="asset_id_field" style="display: none;">
                            <label for="asset_id" class="form-label">Select Asset <span class="text-danger">*</span></label>
                            <select class="form-select" id="asset_id" name="asset_id">
                                <option value="">-- Select Asset --</option>
                                <?php foreach ($assets as $asset): ?>
                                    <option value="<?php echo $asset['asset_id']; ?>">
                                        <?php echo htmlspecialchars($asset['asset_name']); ?> 
                                        (<?php echo htmlspecialchars($asset['asset_type']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Request <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reason" name="reason" rows="4" placeholder="Explain why you need this asset or action..." required></textarea>
                            <div class="form-text">Please provide a detailed explanation for your request to help with approval.</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i> Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4 border-0 bg-light">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-info-circle me-2"></i> Request Types</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item bg-transparent px-0">
                            <h6 class="mb-1"><i class="fas fa-plus-circle text-success me-2"></i> New Asset</h6>
                            <p class="text-muted mb-0 small">Request a new asset that is not currently assigned to you.</p>
                        </li>
                        <li class="list-group-item bg-transparent px-0">
                            <h6 class="mb-1"><i class="fas fa-exchange-alt text-primary me-2"></i> Transfer</h6>
                            <p class="text-muted mb-0 small">Request transfer of an existing asset to you or to another user.</p>
                        </li>
                        <li class="list-group-item bg-transparent px-0">
                            <h6 class="mb-1"><i class="fas fa-trash-alt text-danger me-2"></i> Disposal</h6>
                            <p class="text-muted mb-0 small">Request disposal of an asset that is no longer functional or needed.</p>
                        </li>
                        <li class="list-group-item bg-transparent px-0">
                            <h6 class="mb-1"><i class="fas fa-undo text-warning me-2"></i> Return</h6>
                            <p class="text-muted mb-0 small">Return an asset that is currently assigned to you.</p>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i> Tips for Quick Approval</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="mb-2">Be specific about why you need the asset</li>
                        <li class="mb-2">Explain how it will help your work</li>
                        <li class="mb-2">Include any relevant project information</li>
                        <li class="mb-2">Mention any deadlines or time constraints</li>
                        <li>Reference any prior approvals or discussions</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleRequestFields() {
    const requestType = document.getElementById('request_type').value;
    const assetTypeField = document.getElementById('asset_type_field');
    const assetIdField = document.getElementById('asset_id_field');
    const otherTypeField = document.getElementById('other_type_field');
    
    // Hide all fields initially
    assetTypeField.style.display = 'none';
    assetIdField.style.display = 'none';
    
    if (requestType === 'new_asset') {
        assetTypeField.style.display = 'block';
    } else if (['transfer', 'disposal', 'return'].includes(requestType)) {
        assetIdField.style.display = 'block';
    }
    
    // Handle "Other" asset type
    document.getElementById('asset_type').addEventListener('change', function() {
        if (this.value === 'Other') {
            otherTypeField.style.display = 'block';
        } else {
            otherTypeField.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    toggleRequestFields();
});
</script>

<?php require_once '../includes/footer.php'; ?>
