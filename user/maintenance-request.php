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
    $asset_id = $_POST['asset_id'] ?? 0;
    $request_type = $_POST['request_type'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $description = $_POST['description'] ?? '';
    
    // Validate input
    if (empty($asset_id) || empty($request_type) || empty($description)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        // Insert maintenance request
        $stmt = $pdo->prepare('
            INSERT INTO maintenance_requests 
            (asset_id, user_id, request_type, priority, description, status) 
            VALUES (?, ?, ?, ?, ?, "pending")
        ');
        
        if ($stmt->execute([$asset_id, $_SESSION['user_id'], $request_type, $priority, $description])) {
            $success_message = 'Maintenance request submitted successfully.';
        } else {
            $error_message = 'Failed to submit maintenance request. Please try again.';
        }
    }
}

// Get user's assets for dropdown
$stmt = $pdo->prepare('
    SELECT asset_id, asset_name, asset_type 
    FROM assets 
    WHERE assigned_to = ? AND status = "active"
    ORDER BY asset_name
');
$stmt->execute([$_SESSION['user_id']]);
$assets = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="mb-0"><i class="fas fa-tools me-2 text-primary"></i> Submit Maintenance Request</h1>
            <p class="text-muted">Request maintenance or repair for your assigned assets</p>
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
                    <form method="post" action="maintenance-request.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="asset_id" class="form-label">Select Asset <span class="text-danger">*</span></label>
                            <select class="form-select" id="asset_id" name="asset_id" required>
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
                            <label for="request_type" class="form-label">Request Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="request_type" name="request_type" required>
                                <option value="">-- Select Request Type --</option>
                                <option value="repair">Repair (Fix broken/damaged asset)</option>
                                <option value="maintenance">Maintenance (Regular service)</option>
                                <option value="inspection">Inspection (Safety/compliance check)</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority Level</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="low">Low (No urgency, schedule when convenient)</option>
                                <option value="medium" selected>Medium (Normal priority)</option>
                                <option value="high">High (Urgent, needs prompt attention)</option>
                                <option value="critical">Critical (Immediate attention required)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="5" placeholder="Describe the issue in detail..." required></textarea>
                            <div class="form-text">Please provide specific details about the problem, when it started, and any other relevant information.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="images" class="form-label">Upload Images (Optional)</label>
                            <input class="form-control" type="file" id="images" name="images[]" multiple accept="image/*">
                            <div class="form-text">You can upload photos of the issue to help maintenance staff (Max 5MB per image).</div>
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
            <div class="card mb-4 bg-light border-0">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-info-circle me-2"></i> Request Guidelines</h5>
                    <p class="card-text">Follow these tips to ensure your maintenance request is processed quickly:</p>
                    <ul>
                        <li>Be specific about the issue you're experiencing</li>
                        <li>Include when the problem started or was first noticed</li>
                        <li>Mention any troubleshooting steps already taken</li>
                        <li>Upload clear photos if the issue is visible</li>
                        <li>Choose the appropriate priority level</li>
                    </ul>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i> Response Times</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Critical:</span>
                        <span class="fw-bold">Within 4 hours</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>High:</span>
                        <span class="fw-bold">Within 24 hours</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Medium:</span>
                        <span class="fw-bold">Within 3 business days</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Low:</span>
                        <span class="fw-bold">Within 7 business days</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
