<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get all assets assigned to the current user
$stmt = $pdo->prepare('
    SELECT a.*, d.depreciation_method, d.useful_life, d.salvage_value, 
           d.start_date, d.accumulated_depreciation
    FROM assets a
    LEFT JOIN depreciation d ON a.asset_id = d.asset_id
    WHERE a.assigned_to = ?
    ORDER BY a.asset_name
');
$stmt->execute([$_SESSION['user_id']]);
$assets = $stmt->fetchAll();

// Get asset categories for filtering
$stmt = $pdo->query('SELECT DISTINCT asset_type FROM assets ORDER BY asset_type');
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="fas fa-boxes me-2 text-primary"></i> My Assets</h1>
            <p class="text-muted">Manage and track all assets assigned to you</p>
        </div>
        <div>
            <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#filterModal">
                <i class="fas fa-filter me-2"></i> Filter
            </button>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary active" id="grid-view-btn">
                    <i class="fas fa-th-large"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" id="list-view-btn">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" id="searchAssets" placeholder="Search assets...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="categoryFilter">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>">
                                <?php echo htmlspecialchars($category); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="disposed">Disposed</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Assets Grid View -->
    <div class="row g-4" id="assets-grid">
        <?php if (count($assets) > 0): ?>
            <?php foreach ($assets as $asset): ?>
                <div class="col-md-6 col-lg-4 col-xl-3 asset-item" 
                     data-category="<?php echo htmlspecialchars($asset['asset_type']); ?>"
                     data-status="<?php echo htmlspecialchars($asset['status']); ?>">
                    <div class="card h-100 asset-card">
                        <div class="position-relative">
                            <?php
                            // Asset type to image mapping
                            $imageMap = [
                                'Electronics' => 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
                                'Furniture' => 'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
                                'Vehicle' => 'https://images.unsplash.com/photo-1553440569-bcc63803a83d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
                                'Machinery' => 'https://images.unsplash.com/photo-1537462715879-360eeb61a0ad?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
                                'Office Equipment' => 'https://images.unsplash.com/photo-1497215842964-222b430dc094?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
                                'IT Equipment' => 'https://images.unsplash.com/photo-1547082299-de196ea013d6?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
                                'Building' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80'
                            ];
                            
                            $defaultImage = 'https://images.unsplash.com/photo-1553729459-efe14ef6055d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80';
                            $imageUrl = $imageMap[$asset['asset_type']] ?? $defaultImage;
                            ?>
                            <img src="<?php echo $imageUrl; ?>" class="card-img-top asset-img" alt="<?php echo htmlspecialchars($asset['asset_name']); ?>">
                            <div class="position-absolute top-0 end-0 p-2">
                                <?php if ($asset['status'] == 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php elseif ($asset['status'] == 'inactive'): ?>
                                    <span class="badge bg-warning">Inactive</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Disposed</span>
                                <?php endif; ?>
                            </div>
                            <?php
                            // Calculate current value
                            $current_value = $asset['current_value'] ?? $asset['purchase_cost'];
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
                                    $annual_depreciation = ($purchase_cost - $salvage_value) / $useful_life;
                                    $total_depreciation = $annual_depreciation * $years_passed;
                                    $current_value = $purchase_cost - $total_depreciation;
                                }
                            }
                            $value_change = $current_value - $asset['purchase_cost'];
                            $value_change_percent = ($value_change / $asset['purchase_cost']) * 100;
                            ?>
                            <div class="position-absolute bottom-0 start-0 p-2">
                                <div class="d-flex align-items-center bg-dark bg-opacity-75 text-white rounded px-2 py-1">
                                    <i class="fas fa-chart-line me-1"></i>
                                    <span class="<?php echo $value_change < 0 ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo $value_change < 0 ? '' : '+'; ?><?php echo number_format($value_change_percent, 1); ?>%
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($asset['asset_name']); ?></h5>
                            <p class="card-text text-muted mb-1">
                                <i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($asset['asset_type']); ?>
                            </p>
                            <p class="card-text mb-1">
                                <i class="fas fa-map-marker-alt me-1 text-danger"></i> 
                                <?php echo htmlspecialchars($asset['location']); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <span class="fs-5 fw-bold"><?php echo formatCurrency($current_value); ?></span>
                                    <small class="text-muted">current value</small>
                                </div>
                                <a href="asset-details.php?id=<?php echo $asset['asset_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <div class="d-flex justify-content-between text-muted">
                                <small><i class="fas fa-calendar me-1"></i> <?php echo $asset['purchase_date']; ?></small>
                                <small><i class="fas fa-hashtag me-1"></i> <?php echo $asset['asset_id']; ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> You don't have any assets assigned to you yet.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Assets List View (Hidden by Default) -->
    <div class="card d-none" id="assets-list">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Asset</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Purchase Date</th>
                            <th>Current Value</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($assets) > 0): ?>
                            <?php foreach ($assets as $asset): ?>
                                <tr class="asset-item" 
                                    data-category="<?php echo htmlspecialchars($asset['asset_type']); ?>"
                                    data-status="<?php echo htmlspecialchars($asset['status']); ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php
                                            $imageMap = [
                                                'Electronics' => 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
                                                'Furniture' => 'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
                                                'Vehicle' => 'https://images.unsplash.com/photo-1553440569-bcc63803a83d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
                                                'Machinery' => 'https://images.unsplash.com/photo-1537462715879-360eeb61a0ad?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
                                                'Office Equipment' => 'https://images.unsplash.com/photo-1497215842964-222b430dc094?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
                                                'IT Equipment' => 'https://images.unsplash.com/photo-1547082299-de196ea013d6?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
                                                'Building' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80'
                                            ];
                                            
                                            $defaultImage = 'https://images.unsplash.com/photo-1553729459-efe14ef6055d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80';
                                            $imageUrl = $imageMap[$asset['asset_type']] ?? $defaultImage;
                                            ?>
                                            <img src="<?php echo $imageUrl; ?>" class="rounded me-3" width="48" height="48" alt="">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($asset['asset_name']); ?></h6>
                                                <small class="text-muted">#<?php echo $asset['asset_id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($asset['asset_type']); ?></td>
                                    <td><?php echo htmlspecialchars($asset['location']); ?></td>
                                    <td><?php echo $asset['purchase_date']; ?></td>
                                    <td>
                                        <?php
                                        // Calculate current value
                                        $current_value = $asset['current_value'] ?? $asset['purchase_cost'];
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
                                                $annual_depreciation = ($purchase_cost - $salvage_value) / $useful_life;
                                                $total_depreciation = $annual_depreciation * $years_passed;
                                                $current_value = $purchase_cost - $total_depreciation;
                                            }
                                        }
                                        $value_change = $current_value - $asset['purchase_cost'];
                                        $value_change_percent = ($value_change / $asset['purchase_cost']) * 100;
                                        ?>
                                        <div>
                                            <span class="fw-bold">$<?php echo number_format($current_value, 2); ?></span>
                                            <small class="ms-2 <?php echo $value_change < 0 ? 'text-danger' : 'text-success'; ?>">
                                                <?php echo $value_change < 0 ? '' : '+'; ?><?php echo number_format($value_change_percent, 1); ?>%
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($asset['status'] == 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php elseif ($asset['status'] == 'inactive'): ?>
                                            <span class="badge bg-warning">Inactive</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Disposed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="asset-details.php?id=<?php echo $asset['asset_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#qrModal" data-asset-id="<?php echo $asset['asset_id']; ?>">
                                            <i class="fas fa-qrcode"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i> You don't have any assets assigned to you yet.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">Advanced Filters</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label class="form-label">Asset Type</label>
                        <div class="row">
                            <?php foreach ($categories as $category): ?>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($category); ?>" id="category-<?php echo htmlspecialchars($category); ?>">
                                        <label class="form-check-label" for="category-<?php echo htmlspecialchars($category); ?>">
                                            <?php echo htmlspecialchars($category); ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="active" id="status-active">
                                    <label class="form-check-label" for="status-active">
                                        Active
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="inactive" id="status-inactive">
                                    <label class="form-check-label" for="status-inactive">
                                        Inactive
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="disposed" id="status-disposed">
                                    <label class="form-check-label" for="status-disposed">
                                        Disposed
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="purchaseAfter" class="form-label">Purchase Date Range</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="date" class="form-control" id="purchaseAfter" placeholder="From">
                            </div>
                            <div class="col-md-6">
                                <input type="date" class="form-control" id="purchaseBefore" placeholder="To">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="valueRange" class="form-label">Current Value Range</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="minValue" placeholder="Min">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="maxValue" placeholder="Max">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="applyFilters">Apply Filters</button>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Modal -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrModalLabel">Asset QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qrcode"></div>
                <p class="mt-3 mb-0">Scan to view asset details</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="downloadQR">Download</button>
            </div>
        </div>
    </div>
</div>

<!-- Add custom CSS for asset cards -->
<style>
.asset-card {
    transition: transform 0.3s, box-shadow 0.3s;
    border: none;
    overflow: hidden;
}
.asset-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}
.asset-img {
    height: 200px;
    object-fit: cover;
    transition: transform 0.5s;
}
.asset-card:hover .asset-img {
    transform: scale(1.05);
}
#assets-grid .card-title {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<!-- Add JavaScript for filtering and view switching -->
<script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // View switching
    const gridViewBtn = document.getElementById('grid-view-btn');
    const listViewBtn = document.getElementById('list-view-btn');
    const assetsGrid = document.getElementById('assets-grid');
    const assetsList = document.getElementById('assets-list');
    
    gridViewBtn.addEventListener('click', function() {
        assetsGrid.classList.remove('d-none');
        assetsList.classList.add('d-none');
        gridViewBtn.classList.add('active');
        listViewBtn.classList.remove('active');
    });
    
    listViewBtn.addEventListener('click', function() {
        assetsGrid.classList.add('d-none');
        assetsList.classList.remove('d-none');
        gridViewBtn.classList.remove('active');
        listViewBtn.classList.add('active');
    });
    
    // Search functionality
    const searchInput = document.getElementById('searchAssets');
    const assetItems = document.querySelectorAll('.asset-item');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        assetItems.forEach(item => {
            const assetName = item.querySelector('.card-title, h6').textContent.toLowerCase();
            const assetType = item.dataset.category.toLowerCase();
            const assetLocation = item.querySelector('.card-text:nth-child(3), td:nth-child(3)').textContent.toLowerCase();
            
            if (assetName.includes(searchTerm) || assetType.includes(searchTerm) || assetLocation.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Category filter
    const categoryFilter = document.getElementById('categoryFilter');
    
    categoryFilter.addEventListener('change', function() {
        const selectedCategory = this.value.toLowerCase();
        
        assetItems.forEach(item => {
            if (!selectedCategory || item.dataset.category.toLowerCase() === selectedCategory) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Status filter
    const statusFilter = document.getElementById('statusFilter');
    
    statusFilter.addEventListener('change', function() {
        const selectedStatus = this.value.toLowerCase();
        
        assetItems.forEach(item => {
            if (!selectedStatus || item.dataset.status.toLowerCase() === selectedStatus) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // QR Code generation
    const qrModal = document.getElementById('qrModal');
    let qrCode;
    
    qrModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const assetId = button.getAttribute('data-asset-id');
        const assetUrl = `${window.location.origin}/fams/user/asset-details.php?id=${assetId}`;
        
        if (!qrCode) {
            qrCode = new QRious({
                element: document.getElementById('qrcode'),
                value: assetUrl,
                size: 200
            });
        } else {
            qrCode.value = assetUrl;
        }
        
        document.getElementById('downloadQR').onclick = function() {
            const link = document.createElement('a');
            link.download = `asset-${assetId}-qr.png`;
            link.href = qrCode.toDataURL('image/png');
            link.click();
        };
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
