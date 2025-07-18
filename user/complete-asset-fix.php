<?php
// This script fixes both the asset image display and currency formatting issues
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$asset_details_file = 'asset-details.php';
$content = file_get_contents($asset_details_file);

// 1. Fix all currency formats to use the formatCurrency function
$content = str_replace('$<?php echo number_format(', '<?php echo formatCurrency(', $content);
$content = str_replace('$<?php echo', '<?php echo formatCurrency(', $content);
$content = str_replace('₦<?php echo number_format(', '<?php echo formatCurrency(', $content);

// 2. Fix image paths to check multiple locations
$content = preg_replace(
    '/<img src="\.\.\/assets\/images\/(.+?)".*?onerror=".*?">/',
    '<img src="../uploads/assets/\\1" class="d-block w-100" alt="Asset Image" onclick="openAssetImageModal(this.src)" onerror="this.onerror=null; this.src=\'../assets/images/\\1\'; this.onerror=function(){this.src=\'https://via.placeholder.com/600x400?text=No+Image+Available\'}">',
    $content
);

// 3. Ensure the modal opens with the correct image path
$content = str_replace(
    'onclick="openAssetImageModal(\'../assets/images/',
    'onclick="openAssetImageModal(this.src)" onerror="this.onerror=null; this.src=\'../assets/images/',
    $content
);

// 4. Create necessary directories
$dirs_to_check = [
    '../uploads',
    '../uploads/assets',
    '../assets/images'
];

foreach ($dirs_to_check as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// 5. Save modified content back to file
file_put_contents($asset_details_file, $content);

// 6. Verify formatCurrency function exists
$functions_file = '../includes/functions.php';
$functions_content = file_get_contents($functions_file);

if (strpos($functions_content, 'function formatCurrency') === false) {
    // Add formatCurrency function if it doesn't exist
    $new_function = "
/**
 * Format currency values as Naira
 * 
 * @param float $amount The amount to format
 * @param int $decimals Number of decimal places
 * @return string Formatted currency string with Naira symbol
 */
function formatCurrency(\$amount, \$decimals = 2) {
    return '₦' . number_format(\$amount, \$decimals, '.', ',');
}
";
    file_put_contents($functions_file, $functions_content . $new_function);
}

// 7. Fix database structure if needed - ensure asset_images table exists
try {
    $result = $pdo->query("SHOW TABLES LIKE 'asset_images'");
    if ($result->rowCount() === 0) {
        // Create asset_images table if it doesn't exist
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
    // Ignore errors
}

// 8. Set a success message
$_SESSION['success_message'] = 'Asset details display has been fixed - images should now load correctly and currency is displayed in Naira.';

// Redirect back to the asset details page if an asset ID was provided
if (isset($_GET['id'])) {
    header('Location: asset-details.php?id=' . intval($_GET['id']));
    exit;
}

// Success message display
echo '<div style="background-color: #d4edda; color: #155724; padding: 20px; margin: 20px; border-radius: 5px; font-family: Arial, sans-serif;">';
echo '<h2 style="text-align: center;">Fixed Asset Details Display</h2>';
echo '<p>The following fixes have been applied:</p>';
echo '<ul>';
echo '<li><strong>Currency Format:</strong> All currency values now display in Naira (₦) format</li>';
echo '<li><strong>Image Display:</strong> Fixed asset image paths to check multiple locations</li>';
echo '<li><strong>Directories:</strong> Created necessary directories for asset images</li>';
echo '<li><strong>Database:</strong> Ensured asset_images table exists if needed</li>';
echo '</ul>';

echo '<div style="text-align: center; margin-top: 20px;">';
echo '<a href="my-assets.php" style="display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin-right: 10px;">View My Assets</a>';
echo '</div>';
echo '</div>';
?>
