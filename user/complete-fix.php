<?php
// This script will fix multiple issues across the system
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$fixes_applied = [];

// 1. Fix "Back to Profile" link in settings.php
$settings_file = 'settings.php';
$settings_content = file_get_contents($settings_file);

if (strpos($settings_content, 'href="profile.php"') === false) {
    // Add the Back to Profile link if it doesn't exist
    $new_content = str_replace(
        '<div class="col-md-6 text-md-end">',
        '<div class="col-md-6 text-md-end">
            <a href="profile.php" class="btn btn-outline-primary mb-3"><i class="fas fa-arrow-left me-2"></i> Back to Profile</a>',
        $settings_content
    );
    
    if ($new_content != $settings_content) {
        file_put_contents($settings_file, $new_content);
        $fixes_applied[] = "Added 'Back to Profile' link in settings page";
    }
}

// 2. Fix asset images display in asset-details.php
$asset_details_file = 'asset-details.php';
if (file_exists($asset_details_file)) {
    $asset_content = file_get_contents($asset_details_file);
    
    // Fix image path issues - ensure the asset image displays correctly
    $fixed_image_path = str_replace(
        'src="../assets/images/',
        'src="../uploads/assets/',
        $asset_content
    );
    
    // Fix loading issue by ensuring proper JavaScript
    if ($fixed_image_path != $asset_content) {
        file_put_contents($asset_details_file, $fixed_image_path);
        $fixes_applied[] = "Fixed asset image paths in asset details page";
    }
    
    // Create assets directory if it doesn't exist
    if (!file_exists('../uploads/assets')) {
        mkdir('../uploads/assets', 0777, true);
        $fixes_applied[] = "Created assets upload directory";
    }
}

// 3. Fix all currency display to use Naira
$files_to_check = ['asset-details.php', 'my-assets.php', 'dashboard.php'];
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Replace direct $ signs with ₦
        $content = str_replace('$<?php echo number_format(', '₦<?php echo number_format(', $content);
        $content = str_replace('$<?php echo', '₦<?php echo', $content);
        $content = str_replace('$<', '₦<', $content);
        $content = str_replace('span>$', 'span>₦', $content);
        
        // Fix chart currency
        $content = str_replace("return '$'", "return '₦'", $content);
        $content = str_replace('return "$"', 'return "₦"', $content);
        
        // Replace with formatCurrency where possible
        $content = str_replace('₦<?php echo number_format(', '<?php echo formatCurrency(', $content);
        
        file_put_contents($file, $content);
        $fixes_applied[] = "Fixed currency display in $file";
    }
}

// Create a simple diagnostic page for asset-details.php
$diagnostic_file = "asset-details-diagnostic.php";
$diagnostic_content = '<?php
require_once "../includes/config.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

$asset_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

// Get asset details
$stmt = $pdo->prepare("SELECT * FROM assets WHERE asset_id = ?");
$stmt->execute([$asset_id]);
$asset = $stmt->fetch(PDO::FETCH_ASSOC);

// Get images
$stmt = $pdo->prepare("SELECT * FROM asset_images WHERE asset_id = ?");
$stmt->execute([$asset_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "../includes/header.php";
?>

<div class="container py-4">
    <h1>Asset Details Diagnostic</h1>
    <div class="card mb-4">
        <div class="card-body">
            <h5>Asset Information</h5>
            <pre><?php print_r($asset); ?></pre>
            
            <h5>Asset Images</h5>
            <?php if (count($images) > 0): ?>
                <pre><?php print_r($images); ?></pre>
            <?php else: ?>
                <p>No separate images found in asset_images table.</p>
            <?php endif; ?>
            
            <h5>Main Asset Image</h5>
            <?php if (!empty($asset["asset_image"])): ?>
                <p>Image path: <?php echo htmlspecialchars($asset["asset_image"]); ?></p>
                <div class="mb-3">
                    <img src="../uploads/assets/<?php echo htmlspecialchars($asset["asset_image"]); ?>" 
                         alt="Asset Image" class="img-thumbnail" style="max-height: 200px"
                         onerror="this.onerror=null; this.src=\'../assets/images/<?php echo htmlspecialchars($asset["asset_image"]); ?>\'; this.onerror=function(){this.src=\'https://via.placeholder.com/400x300?text=Image+Not+Found\'};">
                </div>
                
                <div class="mb-3">
                    <h6>Alternative path check:</h6>
                    <img src="../assets/images/<?php echo htmlspecialchars($asset["asset_image"]); ?>" 
                         alt="Asset Image" class="img-thumbnail" style="max-height: 200px"
                         onerror="this.src=\'https://via.placeholder.com/400x300?text=Image+Not+Found\'">
                </div>
            <?php else: ?>
                <p>No image path defined for this asset.</p>
            <?php endif; ?>
            
            <a href="my-assets.php" class="btn btn-primary">Back to Assets</a>
            <a href="asset-details.php?id=<?php echo $asset_id; ?>" class="btn btn-secondary">Go to Regular Asset Details</a>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
';

file_put_contents($diagnostic_file, $diagnostic_content);
$fixes_applied[] = "Created asset details diagnostic tool to help troubleshoot image issues";

// Success message
echo '<div style="background-color: #d4edda; color: #155724; padding: 15px; margin: 20px; border-radius: 5px;">';
echo '<h3 class="text-center">System Fixes Applied</h3>';
echo '<ul>';
foreach ($fixes_applied as $fix) {
    echo '<li>' . htmlspecialchars($fix) . '</li>';
}
echo '</ul>';
echo '<div class="text-center mt-4">';
echo '<p>The following tools are now available to help you:</p>';
echo '<a href="settings.php" class="btn btn-success me-2">Go to Settings</a>';
echo '<a href="my-assets.php" class="btn btn-success me-2">View My Assets</a>';
echo '<a href="asset-details-diagnostic.php?id=1" class="btn btn-info me-2">Asset Image Diagnostic</a>';
echo '</div>';
echo '</div>';
?>
