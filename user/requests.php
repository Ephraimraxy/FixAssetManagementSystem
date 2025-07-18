<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Cancel request
if (isset($_GET['cancel']) && isset($_GET['type'])) {
    $request_id = (int)$_GET['cancel'];
    $request_type = $_GET['type'];
    
    if ($request_type === 'maintenance') {
        $stmt = $pdo->prepare('
            UPDATE maintenance_requests 
            SET status = "cancelled" 
            WHERE request_id = ? AND user_id = ? AND status = "pending"
        ');
    } else {
        $stmt = $pdo->prepare('
            UPDATE asset_requests 
            SET status = "cancelled" 
            WHERE request_id = ? AND user_id = ? AND status = "pending"
        ');
    }
    
    if ($stmt->execute([$request_id, $_SESSION['user_id']])) {
        $success_message = "Request cancelled successfully.";
    } else {
        $error_message = "Failed to cancel request.";
    }
}

// Get maintenance requests
$stmt = $pdo->prepare('
    SELECT mr.*, a.asset_name, a.asset_type 
    FROM maintenance_requests mr
    JOIN assets a ON mr.asset_id = a.asset_id
    WHERE mr.user_id = ?
    ORDER BY mr.request_date DESC
');
$stmt->execute([$_SESSION['user_id']]);
$maintenance_requests = $stmt->fetchAll();

// Get asset requests
$stmt = $pdo->prepare('
    SELECT ar.*, a.asset_name, a.asset_type 
    FROM asset_requests ar
    LEFT JOIN assets a ON ar.asset_id = a.asset_id
    WHERE ar.user_id = ?
    ORDER BY ar.request_date DESC
');
$stmt->execute([$_SESSION['user_id']]);
$asset_requests = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="mb-0"><i class="fas fa-clipboard-list me-2 text-primary"></i> My Requests</h1>
            <p class="text-muted">Track the status of your maintenance and asset requests</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="maintenance-request.php" class="btn btn-primary me-2">
                <i class="fas fa-tools me-2"></i> New Maintenance Request
            </a>
            <a href="asset-request.php" class="btn btn-outline-primary">
                <i class="fas fa-plus me-2"></i> New Asset Request
            </a>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Maintenance Requests -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0"><i class="fas fa-tools me-2"></i> Maintenance Requests</h5>
            <span class="badge bg-primary rounded-pill"><?php echo count($maintenance_requests); ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (count($maintenance_requests) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Asset</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($maintenance_requests as $request): ?>
                                <tr>
                                    <td>#<?php echo $request['request_id']; ?></td>
                                    <td><?php echo htmlspecialchars($request['asset_name']); ?></td>
                                    <td>
                                        <?php 
                                        $type_badges = [
                                            'repair' => '<span class="badge bg-danger">Repair</span>',
                                            'maintenance' => '<span class="badge bg-info">Maintenance</span>',
                                            'inspection' => '<span class="badge bg-warning">Inspection</span>',
                                            'other' => '<span class="badge bg-secondary">Other</span>'
                                        ];
                                        echo $type_badges[$request['request_type']] ?? $request['request_type'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $priority_badges = [
                                            'low' => '<span class="badge bg-success">Low</span>',
                                            'medium' => '<span class="badge bg-info">Medium</span>',
                                            'high' => '<span class="badge bg-warning">High</span>',
                                            'critical' => '<span class="badge bg-danger">Critical</span>'
                                        ];
                                        echo $priority_badges[$request['priority']] ?? $request['priority'];
                                        ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $status_badges = [
                                            'pending' => '<span class="badge bg-warning">Pending</span>',
                                            'approved' => '<span class="badge bg-info">Approved</span>',
                                            'in_progress' => '<span class="badge bg-primary">In Progress</span>',
                                            'completed' => '<span class="badge bg-success">Completed</span>',
                                            'rejected' => '<span class="badge bg-danger">Rejected</span>',
                                            'cancelled' => '<span class="badge bg-secondary">Cancelled</span>'
                                        ];
                                        echo $status_badges[$request['status']] ?? $request['status'];
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewRequestModal" 
                                            data-request-id="<?php echo $request['request_id']; ?>"
                                            data-request-type="maintenance"
                                            data-asset="<?php echo htmlspecialchars($request['asset_name']); ?>"
                                            data-description="<?php echo htmlspecialchars($request['description']); ?>"
                                            data-status="<?php echo $request['status']; ?>"
                                            data-notes="<?php echo htmlspecialchars($request['notes'] ?? ''); ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <a href="requests.php?cancel=<?php echo $request['request_id']; ?>&type=maintenance" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this request?');">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <img src="https://img.icons8.com/color/96/000000/maintenance.png" alt="No Maintenance Requests" class="mb-3" style="opacity: 0.5;">
                    <p class="text-muted">You haven't submitted any maintenance requests yet.</p>
                    <a href="maintenance-request.php" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-2"></i> Submit Maintenance Request
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Asset Requests -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0"><i class="fas fa-box me-2"></i> Asset Requests</h5>
            <span class="badge bg-primary rounded-pill"><?php echo count($asset_requests); ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (count($asset_requests) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Request Type</th>
                                <th>Asset</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($asset_requests as $request): ?>
                                <tr>
                                    <td>#<?php echo $request['request_id']; ?></td>
                                    <td>
                                        <?php 
                                        $type_badges = [
                                            'new_asset' => '<span class="badge bg-success">New Asset</span>',
                                            'transfer' => '<span class="badge bg-info">Transfer</span>',
                                            'disposal' => '<span class="badge bg-warning">Disposal</span>',
                                            'return' => '<span class="badge bg-secondary">Return</span>'
                                        ];
                                        echo $type_badges[$request['request_type']] ?? $request['request_type'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($request['asset_id']) {
                                            echo htmlspecialchars($request['asset_name']);
                                        } elseif ($request['asset_type']) {
                                            echo htmlspecialchars($request['asset_type']) . ' (Requested)';
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $status_badges = [
                                            'pending' => '<span class="badge bg-warning">Pending</span>',
                                            'approved' => '<span class="badge bg-info">Approved</span>',
                                            'rejected' => '<span class="badge bg-danger">Rejected</span>',
                                            'completed' => '<span class="badge bg-success">Completed</span>',
                                            'cancelled' => '<span class="badge bg-secondary">Cancelled</span>'
                                        ];
                                        echo $status_badges[$request['status']] ?? $request['status'];
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewRequestModal" 
                                            data-request-id="<?php echo $request['request_id']; ?>"
                                            data-request-type="asset"
                                            data-asset="<?php echo htmlspecialchars($request['asset_name'] ?? $request['asset_type'] ?? 'N/A'); ?>"
                                            data-description="<?php echo htmlspecialchars($request['reason']); ?>"
                                            data-status="<?php echo $request['status']; ?>"
                                            data-notes="<?php echo htmlspecialchars($request['notes'] ?? ''); ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <a href="requests.php?cancel=<?php echo $request['request_id']; ?>&type=asset" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this request?');">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <img src="https://img.icons8.com/color/96/000000/box-important--v1.png" alt="No Asset Requests" class="mb-3" style="opacity: 0.5;">
                    <p class="text-muted">You haven't submitted any asset requests yet.</p>
                    <a href="asset-request.php" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-2"></i> Submit Asset Request
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- View Request Modal -->
<div class="modal fade" id="viewRequestModal" tabindex="-1" aria-labelledby="viewRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewRequestModalLabel">Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="request-details">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Request ID</label>
                        <p id="modal-request-id"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Asset</label>
                        <p id="modal-asset"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description/Reason</label>
                        <p id="modal-description"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <p id="modal-status"></p>
                    </div>
                    <div class="mb-3 notes-section">
                        <label class="form-label fw-bold">Admin Notes</label>
                        <p id="modal-notes">No notes provided.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle view request modal
    const viewRequestModal = document.getElementById('viewRequestModal');
    if (viewRequestModal) {
        viewRequestModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const requestId = button.getAttribute('data-request-id');
            const requestType = button.getAttribute('data-request-type');
            const asset = button.getAttribute('data-asset');
            const description = button.getAttribute('data-description');
            const status = button.getAttribute('data-status');
            const notes = button.getAttribute('data-notes');
            
            document.getElementById('modal-request-id').textContent = '#' + requestId + ' (' + requestType + ')';
            document.getElementById('modal-asset').textContent = asset;
            document.getElementById('modal-description').textContent = description;
            
            const statusElement = document.getElementById('modal-status');
            
            // Set status with appropriate badge
            const statusMap = {
                'pending': '<span class="badge bg-warning">Pending</span>',
                'approved': '<span class="badge bg-info">Approved</span>',
                'in_progress': '<span class="badge bg-primary">In Progress</span>',
                'completed': '<span class="badge bg-success">Completed</span>',
                'rejected': '<span class="badge bg-danger">Rejected</span>',
                'cancelled': '<span class="badge bg-secondary">Cancelled</span>'
            };
            
            statusElement.innerHTML = statusMap[status] || status;
            
            // Set notes
            const notesElement = document.getElementById('modal-notes');
            if (notes && notes.trim() !== '') {
                notesElement.textContent = notes;
            } else {
                notesElement.textContent = 'No notes provided.';
            }
            
            // Show/hide notes section based on status
            const notesSection = document.querySelector('.notes-section');
            if (status === 'pending' || status === 'cancelled') {
                notesSection.style.display = 'none';
            } else {
                notesSection.style.display = 'block';
            }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
