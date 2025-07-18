<?php
// Script to add sample assets to the FAMS database
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$added_assets = [];
$errors = [];

// Create uploads directory if it doesn't exist
$uploads_dir = '../uploads/assets';
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}

// Sample assets to add
$sample_assets = [
    [
        'name' => 'MacBook Pro (2023)',
        'type' => 'Office Equipment',
        'purchase_date' => '2023-01-15',
        'purchase_cost' => 2499.00,
        'current_value' => 2124.15, // Depreciated value
        'location' => 'Main Office',
        'location_details' => 'IT Department, 3rd Floor',
        'description' => 'M2 Pro chip, 16GB RAM, 512GB SSD, Space Gray',
        'status' => 'active',
        'useful_life' => 3,
        'image' => 'macbook_pro.jpg',
        'placeholder_image' => 'https://via.placeholder.com/800x600/007bff/FFFFFF?text=MacBook+Pro+(2023)'
    ],
    [
        'name' => 'Ergonomic Office Chair',
        'type' => 'Furniture & Fixtures',
        'purchase_date' => '2022-05-10',
        'purchase_cost' => 599.00,
        'current_value' => 539.10, // Depreciated value
        'location' => 'Main Office',
        'location_details' => 'Marketing Department, 2nd Floor',
        'description' => 'Adjustable height, lumbar support, mesh back, black',
        'status' => 'active',
        'useful_life' => 5,
        'image' => 'office_chair.jpg',
        'placeholder_image' => 'https://via.placeholder.com/800x600/28a745/FFFFFF?text=Ergonomic+Office+Chair'
    ],
    [
        'name' => 'Ford Transit Van',
        'type' => 'Vehicles',
        'purchase_date' => '2021-03-22',
        'purchase_cost' => 35000.00,
        'current_value' => 26250.00, // Depreciated value
        'location' => 'Company Garage',
        'location_details' => 'Parking Space #12',
        'description' => 'White, diesel, cargo van with company logo, license plate: ABC-123',
        'status' => 'active',
        'useful_life' => 8,
        'image' => 'ford_transit.jpg',
        'placeholder_image' => 'https://via.placeholder.com/800x600/fd7e14/FFFFFF?text=Ford+Transit+Van'
    ],
    [
        'name' => 'Industrial 3D Printer',
        'type' => 'Machinery',
        'purchase_date' => '2023-11-05',
        'purchase_cost' => 12500.00,
        'current_value' => 11875.00, // Depreciated value
        'location' => 'Production Facility',
        'location_details' => 'Prototyping Room, Building B',
        'description' => 'Large format industrial 3D printer with multi-material capabilities',
        'status' => 'active',
        'useful_life' => 7,
        'image' => '3d_printer.jpg',
        'placeholder_image' => 'https://via.placeholder.com/800x600/6f42c1/FFFFFF?text=Industrial+3D+Printer'
    ],
    [
        'name' => 'Main Office Building',
        'type' => 'Buildings',
        'purchase_date' => '2010-09-01',
        'purchase_cost' => 500000.00,
        'current_value' => 333333.33, // Depreciated value
        'location' => 'Corporate Headquarters',
        'location_details' => '123 Business Street, Lagos',
        'description' => '3-story office building with 20,000 sq ft of space',
        'status' => 'active',
        'useful_life' => 30,
        'image' => 'office_building.jpg',
        'placeholder_image' => 'https://via.placeholder.com/800x600/20c997/FFFFFF?text=Main+Office+Building'
    ],
    [
        'name' => 'Warehouse Lot A',
        'type' => 'Land',
        'purchase_date' => '2015-07-15',
        'purchase_cost' => 150000.00,
        'current_value' => 180000.00, // Appreciated value
        'location' => 'Industrial Zone',
        'location_details' => 'Plot 45, Industrial Estate, Lagos',
        'description' => '2 acres of commercial land zoned for industrial use',
        'status' => 'active',
        'useful_life' => 0, // Land doesn't depreciate
        'image' => 'land_lot.jpg',
        'placeholder_image' => 'https://via.placeholder.com/800x600/e83e8c/FFFFFF?text=Warehouse+Lot+A'
    ],
    [
        'name' => 'Dell PowerEdge Server',
        'type' => 'IT Infrastructure',
        'purchase_date' => '2022-08-12',
        'purchase_cost' => 8200.00,
        'current_value' => 6765.00, // Depreciated value
        'location' => 'Server Room',
        'location_details' => 'Rack 3, Unit 5-7, Main Office Basement',
        'description' => 'PowerEdge R740 Server with 128GB RAM, 8TB storage, dual power supply',
        'status' => 'active',
        'useful_life' => 4,
        'image' => 'server.jpg',
        'placeholder_image' => 'https://via.placeholder.com/800x600/dc3545/FFFFFF?text=Dell+PowerEdge+Server'
    ]
];

// Function to add an asset
function addAsset($pdo, $asset, $user_id) {
    try {
        // 1. Insert into assets table
        $stmt = $pdo->prepare("
            INSERT INTO assets (
                asset_name, asset_type, purchase_date, purchase_cost, current_value,
                location, location_details, description, status, assigned_to, asset_image
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // Download placeholder image if needed
        $image_filename = uniqid() . '_' . $asset['image'];
        $image_path = '../uploads/assets/' . $image_filename;
        file_put_contents($image_path, file_get_contents($asset['placeholder_image']));
        
        $stmt->execute([
            $asset['name'],
            $asset['type'],
            $asset['purchase_date'],
            $asset['purchase_cost'],
            $asset['current_value'],
            $asset['location'],
            $asset['location_details'],
            $asset['description'],
            $asset['status'],
            $user_id,
            $image_filename
        ]);
        
        $asset_id = $pdo->lastInsertId();
        
        // 2. Insert into depreciation table
        if ($asset['useful_life'] > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO depreciation (
                    asset_id, depreciation_method, useful_life, salvage_value, 
                    start_date, accumulated_depreciation
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $salvage_value = $asset['purchase_cost'] * 0.1; // 10% salvage value
            $accumulated_depreciation = $asset['purchase_cost'] - $asset['current_value'];
            
            $stmt->execute([
                $asset_id,
                'straight-line',
                $asset['useful_life'],
                $salvage_value,
                $asset['purchase_date'],
                $accumulated_depreciation
            ]);
        }
        
        // 3. Insert into asset_images table
        $stmt = $pdo->prepare("
            INSERT INTO asset_images (
                asset_id, image_path, is_primary
            ) VALUES (?, ?, 1)
        ");
        
        $stmt->execute([
            $asset_id,
            $image_filename
        ]);
        
        return [
            'success' => true,
            'asset_id' => $asset_id,
            'asset_name' => $asset['name']
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'asset_name' => $asset['name']
        ];
    }
}

// Check if the assets_images table exists, create if not
try {
    $result = $pdo->query("SHOW TABLES LIKE 'asset_images'");
    if ($result->rowCount() === 0) {
        // Create asset_images table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `asset_images` (
                `image_id` int(11) NOT NULL AUTO_INCREMENT,
                `asset_id` int(11) NOT NULL,
                `image_path` varchar(255) NOT NULL,
                `is_primary` tinyint(1) NOT NULL DEFAULT 0,
                `upload_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`image_id`),
                KEY `asset_id` (`asset_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
} catch (PDOException $e) {
    $errors[] = "Error creating asset_images table: " . $e->getMessage();
}

// Check if the depreciation table exists, create if not
try {
    $result = $pdo->query("SHOW TABLES LIKE 'depreciation'");
    if ($result->rowCount() === 0) {
        // Create depreciation table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `depreciation` (
                `depreciation_id` int(11) NOT NULL AUTO_INCREMENT,
                `asset_id` int(11) NOT NULL,
                `depreciation_method` enum('straight-line','double-declining','sum-of-years') NOT NULL DEFAULT 'straight-line',
                `useful_life` int(11) NOT NULL,
                `salvage_value` decimal(10,2) NOT NULL DEFAULT 0.00,
                `start_date` date NOT NULL,
                `accumulated_depreciation` decimal(10,2) NOT NULL DEFAULT 0.00,
                `last_depreciation_date` date DEFAULT NULL,
                PRIMARY KEY (`depreciation_id`),
                KEY `asset_id` (`asset_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
} catch (PDOException $e) {
    $errors[] = "Error creating depreciation table: " . $e->getMessage();
}

// Add each sample asset
foreach ($sample_assets as $asset) {
    $result = addAsset($pdo, $asset, $user_id);
    if ($result['success']) {
        $added_assets[] = $result;
    } else {
        $errors[] = "Failed to add {$result['asset_name']}: {$result['error']}";
    }
}

// Display results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Assets Added - FAMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .asset-card {
            transition: transform 0.3s;
        }
        .asset-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .asset-icon {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        .card-img-top {
            height: 180px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1><i class="fas fa-boxes me-2 text-primary"></i> Sample Assets Added</h1>
                <p class="text-muted">The following sample assets have been added to your account</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="my-assets.php" class="btn btn-primary">
                    <i class="fas fa-list me-2"></i> View My Assets
                </a>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle me-2"></i> Some assets could not be added:</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($added_assets)): ?>
            <div class="alert alert-success">
                <h5><i class="fas fa-check-circle me-2"></i> Successfully added <?php echo count($added_assets); ?> sample assets to your account!</h5>
                <p>These assets are now available in your My Assets page.</p>
            </div>

            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mt-3">
                <?php foreach ($sample_assets as $index => $asset): ?>
                    <div class="col">
                        <div class="card h-100 asset-card">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($asset['name']); ?></h5>
                            </div>
                            <img src="<?php echo htmlspecialchars($asset['placeholder_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($asset['name']); ?>">
                            <div class="card-body">
                                <p class="badge bg-primary"><?php echo htmlspecialchars($asset['type']); ?></p>
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th>Purchase Date:</th>
                                        <td><?php echo htmlspecialchars($asset['purchase_date']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Purchase Cost:</th>
                                        <td><?php echo formatCurrency($asset['purchase_cost']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Current Value:</th>
                                        <td><?php echo formatCurrency($asset['current_value']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Useful Life:</th>
                                        <td><?php echo $asset['useful_life'] > 0 ? $asset['useful_life'] . ' years' : 'N/A (Land)'; ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="card-footer">
                                <?php if (isset($added_assets[$index]) && $added_assets[$index]['success']): ?>
                                    <a href="asset-details.php?id=<?php echo $added_assets[$index]['asset_id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                                        <i class="fas fa-eye me-2"></i> View Details
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary w-100" disabled>
                                        <i class="fas fa-times-circle me-2"></i> Failed to Add
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
