<?php
// Simple script to add sample assets to the FAMS database
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

// First, check the structure of the assets table
try {
    $stmt = $pdo->query("DESCRIBE assets");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div style='background-color: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>";
    echo "<h3>Assets Table Structure:</h3>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    echo "</div>";
} catch (PDOException $e) {
    echo "<div style='background-color: #f8d7da; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>";
    echo "<h3>Error checking assets table:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

// Sample assets to add - simplified version
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
        'status' => 'active'
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
        'status' => 'active'
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
        'status' => 'active'
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
        'status' => 'active'
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
        'status' => 'active'
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
        'status' => 'active'
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
        'status' => 'active'
    ]
];

// Function to add an asset - simplified version
function addAsset($pdo, $asset, $user_id) {
    try {
        // Get the column names from the assets table
        $stmt = $pdo->query("DESCRIBE assets");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Build the query dynamically based on available columns
        $fields = [];
        $placeholders = [];
        $values = [];
        
        // Always include these essential fields
        if (in_array('asset_name', $columns)) {
            $fields[] = 'asset_name';
            $placeholders[] = '?';
            $values[] = $asset['name'];
        }
        
        if (in_array('asset_type', $columns)) {
            $fields[] = 'asset_type';
            $placeholders[] = '?';
            $values[] = $asset['type'];
        }
        
        if (in_array('purchase_date', $columns)) {
            $fields[] = 'purchase_date';
            $placeholders[] = '?';
            $values[] = $asset['purchase_date'];
        }
        
        if (in_array('purchase_cost', $columns)) {
            $fields[] = 'purchase_cost';
            $placeholders[] = '?';
            $values[] = $asset['purchase_cost'];
        }
        
        if (in_array('current_value', $columns)) {
            $fields[] = 'current_value';
            $placeholders[] = '?';
            $values[] = $asset['current_value'];
        }
        
        if (in_array('location', $columns)) {
            $fields[] = 'location';
            $placeholders[] = '?';
            $values[] = $asset['location'];
        }
        
        if (in_array('location_details', $columns)) {
            $fields[] = 'location_details';
            $placeholders[] = '?';
            $values[] = $asset['location_details'];
        }
        
        if (in_array('description', $columns)) {
            $fields[] = 'description';
            $placeholders[] = '?';
            $values[] = $asset['description'];
        }
        
        if (in_array('status', $columns)) {
            $fields[] = 'status';
            $placeholders[] = '?';
            $values[] = $asset['status'];
        }
        
        if (in_array('assigned_to', $columns)) {
            $fields[] = 'assigned_to';
            $placeholders[] = '?';
            $values[] = $user_id;
        }
        
        // Build and execute the query
        $sql = "INSERT INTO assets (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        return [
            'success' => true,
            'asset_id' => $pdo->lastInsertId(),
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
                <?php foreach ($added_assets as $index => $asset): ?>
                    <div class="col">
                        <div class="card h-100 asset-card">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($asset['asset_name']); ?></h5>
                            </div>
                            <div class="card-body">
                                <p>Asset ID: <?php echo $asset['asset_id']; ?></p>
                                <a href="asset-details.php?id=<?php echo $asset['asset_id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                                    <i class="fas fa-eye me-2"></i> View Details
                                </a>
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
