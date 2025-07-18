<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../admin-login.php');
    exit;
}

// Process request updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? 0;
    $request_type = $_POST['request_type'] ?? '';
    $action = $_POST['action'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if ($request_id && $request_type && $action) {
        $status = '';
        switch ($action) {
            case 'approve':
                $status = 'approved';
                break;
            case 'reject':
                $status = 'rejected';
                break;
            case 'in_progress':
                $status = 'in_progress';
                break;
            case 'complete':
                $status = 'completed';
                break;
            default:
                $status = '';
        }
        
        if ($status) {
            if ($request_type === 'maintenance') {
                $stmt = $pdo->prepare('
                    UPDATE maintenance_requests 
                    SET status = ?, approved_by = ?, approved_date = NOW(), notes = ? 
                    WHERE request_id = ?
                ');
            } else {
                $stmt = $pdo->prepare('
                    UPDATE asset_requests 
                    SET status = ?, approved_by = ?, approved_date = NOW(), notes = ? 
                    WHERE request_id = ?
                ');
            }
            
            if ($stmt->execute([$status, $_SESSION['user_id'], $notes, $request_id])) {
                $success_message = "Request #$request_id has been $action successfully.";
            } else {
                $error_message = "Failed to update request #$request_id.";
            }
        }
    }
}

// Get filter values
$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';

// Build where clause for maintenance requests
$where_maintenance = '1=1';
if ($status_filter !== 'all') {
    $where_maintenance .= " AND status = '$status_filter'";
}

// Build where clause for asset requests
$where_asset = '1=1';
if ($status_filter !== 'all') {
    $where_asset .= " AND status = '$status_filter'";
}

// Get maintenance requests
$stmt = $pdo->prepare("
    SELECT mr.*, a.asset_name, a.asset_type, u.username as requester_name
    FROM maintenance_requests mr
    JOIN assets a ON mr.asset_id = a.asset_id
    JOIN users u ON mr.user_id = u.user_id
    WHERE $where_maintenance
    ORDER BY 
        CASE 
            WHEN mr.priority = 'critical' THEN 1
            WHEN mr.priority = 'high' THEN 2
            WHEN mr.priority = 'medium' THEN 3
            WHEN mr.priority = 'low' THEN 4
        END,
        mr.request_date DESC
");
$stmt->execute();
$maintenance_requests = $stmt->fetchAll();

// Get asset requests
$stmt = $pdo->prepare("
    SELECT ar.*, u.username as requester_name, a.asset_name, a.asset_type
    FROM asset_requests ar
    JOIN users u ON ar.user_id = u.user_id
    LEFT JOIN assets a ON ar.asset_id = a.asset_id
    WHERE $where_asset
    ORDER BY ar.request_date DESC
");
$stmt->execute();
$asset_requests = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="mb-0"><i class="fas fa-tasks me-2 text-primary"></i> Request Management</h1>
            <p class="text-muted">Manage maintenance and asset requests</p>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <a href="?type=all&status=<?php echo $status_filter; ?>" class="btn btn-outline-primary <?php echo $type_filter === 'all' ? 'active' : ''; ?>">All Requests</a>
                <a href="?type=maintenance&status=<?php echo $status_filter; ?>" class="btn btn-outline-primary <?php echo $type_filter === 'maintenance' ? 'active' : ''; ?>">Maintenance</a>
                <a href="?type=asset&status=<?php echo $status_filter; ?>" class="btn btn-outline-primary <?php echo $type_filter === 'asset' ? 'active' : ''; ?>">Asset</a>
            </div>
            <div class="btn-group ms-2">
                <a href="?type=<?php echo $type_filter; ?>&status=all" class="btn btn-outline-secondary <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All Status</a>
                <a href="?type=<?php echo $type_filter; ?>&status=pending" class="btn btn-outline-secondary <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?type=<?php echo $type_filter; ?>&status=approved" class="btn btn-outline-secondary <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">Approved</a>
                <a href="?type=<?php echo $type_filter; ?>&status=in_progress" class="btn btn-outline-secondary <?php echo $status_filter === 'in_progress' ? 'active' : ''; ?>">In Progress</a>
            </div>
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

    <!-- Request Count Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h6 class="mb-0">Pending Requests</h6>
                        <h2 class="mb-0">
                            <?php 
                            $pending_count = 0;
                            foreach ($maintenance_requests as $req) {
                                if ($req['status'] === 'pending') $pending_count++;
                            }
                            foreach ($asset_requests as $req) {
                                if ($req['status'] === 'pending') $pending_count++;
                            }
                            echo $pending_count;
                            ?>
                        </h2>
                    </div>
                    <i class="fas fa-clock fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h6 class="mb-0">Critical Priority</h6>
                        <h2 class="mb-0">
                            <?php 
                            $critical_count = 0;
                            foreach ($maintenance_requests as $req) {
                                if ($req['priority'] === 'critical') $critical_count++;
                            }
                            echo $critical_count;
                            ?>
                        </h2>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h6 class="mb-0">In Progress</h6>
                        <h2 class="mb-0">
                            <?php 
                            $in_progress_count = 0;
                            foreach ($maintenance_requests as $req) {
                                if ($req['status'] === 'in_progress') $in_progress_count++;
                            }
                            foreach ($asset_requests as $req) {
                                if ($req['status'] === 'in_progress') $in_progress_count++;
                            }
                            echo $in_progress_count;
                            ?>
                        </h2>
                    </div>
                    <i class="fas fa-spinner fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h6 class="mb-0">Completed</h6>
                        <h2 class="mb-0">
                            <?php 
                            $completed_count = 0;
                            foreach ($maintenance_requests as $req) {
                                if ($req['status'] === 'completed') $completed_count++;
                            }
                            foreach ($asset_requests as $req) {
                                if ($req['status'] === 'completed') $completed_count++;
                            }
                            echo $completed_count;
                            ?>
                        </h2>
                    </div>
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance Requests Table -->
    <?php if ($type_filter === 'all' || $type_filter === 'maintenance'): ?>
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0"><i class="fas fa-tools me-2"></i> Maintenance Requests</h5>
            <span class="badge bg-primary rounded-pill"><?php echo count($maintenance_requests); ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (count($maintenance_requests) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Asset</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Requested By</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($maintenance_requests as $request): ?>
                                <tr>
                                    <td>#<?php echo $request['request_id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="asset-icon me-2 bg-light rounded-circle p-2">
                                                <i class="fas fa-laptop text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($request['asset_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($request['asset_type']); ?></small>
                                            </div>
                                        </div>
                                    </td>
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
                                    <td><?php echo htmlspecialchars($request['requester_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $status_badges = [
                                            'pending' => '<span class="badge bg-warning">Pending</span>',
                                            'approved' => '<span class="badge bg-info">Approved</span>',
                                            'in_progress' => '<span class="badge bg-primary">In Progress</span>',
                                            'completed' => '<span class="badge bg-success">Completed</span>',
                                            'rejected' => '<span class="badge bg-danger">Rejected</span>'
                                        ];
                                        echo $status_badges[$request['status']] ?? $request['status'];
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#actionModal" 
                                            data-request-id="<?php echo $request['request_id']; ?>"
                                            data-request-type="maintenance"
                                            data-asset="<?php echo htmlspecialchars($request['asset_name']); ?>"
                                            data-description="<?php echo htmlspecialchars($request['description']); ?>"
                                            data-status="<?php echo $request['status']; ?>"
                                            data-priority="<?php echo $request['priority']; ?>"
                                            data-requester="<?php echo htmlspecialchars($request['requester_name']); ?>"
                                            data-notes="<?php echo htmlspecialchars($request['notes'] ?? ''); ?>">
                                            <i class="fas fa-cogs"></i> Manage
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <img src="https://img.icons8.com/color/96/000000/maintenance.png" alt="No Maintenance Requests" class="mb-3" style="opacity: 0.5;">
                    <p class="text-muted">No maintenance requests found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Asset Requests Table -->
    <?php if ($type_filter === 'all' || $type_filter === 'asset'): ?>
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0"><i class="fas fa-box me-2"></i> Asset Requests</h5>
            <span class="badge bg-primary rounded-pill"><?php echo count($asset_requests); ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (count($asset_requests) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Request Type</th>
                                <th>Asset</th>
                                <th>Requested By</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th width="150">Actions</th>
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
                                            echo '<div class="fw-bold">' . htmlspecialchars($request['asset_name']) . '</div>';
                                            echo '<small class="text-muted">' . htmlspecialchars($request['asset_type']) . '</small>';
                                        } elseif ($request['asset_type']) {
                                            echo '<div class="fw-bold">' . htmlspecialchars($request['asset_type']) . '</div>';
                                            echo '<small class="text-muted">(Requested)</small>';
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['requester_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $status_badges = [
                                            'pending' => '<span class="badge bg-warning">Pending</span>',
                                            'approved' => '<span class="badge bg-info">Approved</span>',
                                            'rejected' => '<span class="badge bg-danger">Rejected</span>',
                                            'completed' => '<span class="badge bg-success">Completed</span>'
                                        ];
                                        echo $status_badges[$request['status']] ?? $request['status'];
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#actionModal" 
                                            data-request-id="<?php echo $request['request_id']; ?>"
                                            data-request-type="asset"
                                            data-asset="<?php echo htmlspecialchars($request['asset_name'] ?? $request['asset_type'] ?? 'N/A'); ?>"
                                            data-description="<?php echo htmlspecialchars($request['reason']); ?>"
                                            data-status="<?php echo $request['status']; ?>"
                                            data-requester="<?php echo htmlspecialchars($request['requester_name']); ?>"
                                            data-notes="<?php echo htmlspecialchars($request['notes'] ?? ''); ?>">
                                            <i class="fas fa-cogs"></i> Manage
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <img src="https://img.icons8.com/color/96/000000/box-important--v1.png" alt="No Asset Requests" class="mb-3" style="opacity: 0.5;">
                    <p class="text-muted">No asset requests found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Action Modal -->
<div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionModalLabel">Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted">Request Information</h6>
                        <table class="table table-borderless">
                            <tr>
                                <th class="ps-0">Request ID:</th>
                                <td id="modal-request-id"></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Requested By:</th>
                                <td id="modal-requester"></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Asset:</th>
                                <td id="modal-asset"></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Priority:</th>
                                <td id="modal-priority"></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Status:</th>
                                <td id="modal-status"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Description/Reason</h6>
                        <div class="border rounded p-3 bg-light">
                            <p id="modal-description" class="mb-0"></p>
                        </div>
                    </div>
                </div>
                
                <form id="actionForm" method="post" action="manage-requests.php">
                    <input type="hidden" id="request_id" name="request_id">
                    <input type="hidden" id="request_type" name="request_type">
                    
                    <div class="mb-3">
                        <label for="action" class="form-label">Select Action</label>
                        <select class="form-select" id="action" name="action" required>
                            <option value="">-- Select Action --</option>
                            <option value="approve">Approve Request</option>
                            <option value="reject">Reject Request</option>
                            <option value="in_progress">Mark as In Progress</option>
                            <option value="complete">Mark as Completed</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Add notes about your decision or instructions..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="actionForm" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle action modal
    const actionModal = document.getElementById('actionModal');
    if (actionModal) {
        actionModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const requestId = button.getAttribute('data-request-id');
            const requestType = button.getAttribute('data-request-type');
            const asset = button.getAttribute('data-asset');
            const description = button.getAttribute('data-description');
            const status = button.getAttribute('data-status');
            const priority = button.getAttribute('data-priority');
            const requester = button.getAttribute('data-requester');
            const notes = button.getAttribute('data-notes');
            
            document.getElementById('modal-request-id').textContent = '#' + requestId;
            document.getElementById('modal-requester').textContent = requester;
            document.getElementById('modal-asset').textContent = asset;
            document.getElementById('modal-description').textContent = description;
            
            // Set priority if available (maintenance requests only)
            const priorityElement = document.getElementById('modal-priority');
            if (priority) {
                const priorityMap = {
                    'low': '<span class="badge bg-success">Low</span>',
                    'medium': '<span class="badge bg-info">Medium</span>',
                    'high': '<span class="badge bg-warning">High</span>',
                    'critical': '<span class="badge bg-danger">Critical</span>'
                };
                priorityElement.innerHTML = priorityMap[priority] || priority;
            } else {
                priorityElement.textContent = 'N/A';
            }
            
            // Set status with appropriate badge
            const statusElement = document.getElementById('modal-status');
            const statusMap = {
                'pending': '<span class="badge bg-warning">Pending</span>',
                'approved': '<span class="badge bg-info">Approved</span>',
                'in_progress': '<span class="badge bg-primary">In Progress</span>',
                'completed': '<span class="badge bg-success">Completed</span>',
                'rejected': '<span class="badge bg-danger">Rejected</span>'
            };
            statusElement.innerHTML = statusMap[status] || status;
            
            // Set form values
            document.getElementById('request_id').value = requestId;
            document.getElementById('request_type').value = requestType;
            
            // Pre-fill notes if available
            if (notes) {
                document.getElementById('notes').value = notes;
            }
            
            // Disable certain actions based on current status
            const actionSelect = document.getElementById('action');
            actionSelect.options.length = 1; // Clear all options except the first
            
            if (status === 'pending') {
                // Can approve or reject
                actionSelect.add(new Option('Approve Request', 'approve'));
                actionSelect.add(new Option('Reject Request', 'reject'));
            } else if (status === 'approved') {
                // Can start work
                actionSelect.add(new Option('Mark as In Progress', 'in_progress'));
                actionSelect.add(new Option('Mark as Completed', 'complete'));
            } else if (status === 'in_progress') {
                // Can complete
                actionSelect.add(new Option('Mark as Completed', 'complete'));
            }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
