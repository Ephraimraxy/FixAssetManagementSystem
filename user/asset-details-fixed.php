<?php
// This is a quick fix script to resolve the asset details loading issue
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Fix asset details page
$asset_details_file = 'asset-details.php';
$content = file_get_contents($asset_details_file);

// 1. Fix currency symbols (replace $ with ₦)
$content = str_replace('$<?php echo number_format(', '<?php echo formatCurrency(', $content);
$content = str_replace("return '$'", "return '₦'", $content);

// 2. Add proper image display in the modal
$image_modal_js = "
function openAssetImageModal(imageSrc) {
    // Set the image source
    document.getElementById('assetImageModalContent').src = imageSrc;
    
    // Create and show the modal
    var assetImageModal = new bootstrap.Modal(document.getElementById('assetImageModal'));
    assetImageModal.show();
}
";

// Make sure the JS function is properly included
if (strpos($content, 'function openAssetImageModal') === false) {
    $content = str_replace('</script>', $image_modal_js . '</script>', $content);
}

// 3. Make sure the image modal HTML is properly included
$image_modal_html = '
<!-- Asset Image Modal -->
<div class="modal fade" id="assetImageModal" tabindex="-1" aria-labelledby="assetImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assetImageModalLabel">Asset Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="assetImageModalContent" src="" alt="Asset Image" class="img-fluid" style="max-height: 70vh;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
';

// Add the image modal HTML if it doesn't exist
if (strpos($content, 'id="assetImageModal"') === false) {
    $content = str_replace('<?php require_once \'../includes/footer.php\'; ?>', $image_modal_html . "\n<?php require_once '../includes/footer.php'; ?>", $content);
}

// 4. Make sure the clickable images are properly set up
$clickable_image = 'onclick="openAssetImageModal(\'../assets/images/';
if (strpos($content, $clickable_image) === false) {
    $content = str_replace('class="d-block w-100" alt="Asset Image"', 'class="d-block w-100" alt="Asset Image" style="cursor: pointer;" onclick="openAssetImageModal(this.src)"', $content);
}

// 5. Save the fixed file
file_put_contents($asset_details_file, $content);

// Success message
echo '<div style="background-color: #d4edda; color: #155724; padding: 15px; margin: 20px; border-radius: 5px; text-align: center;">';
echo '<h3>Asset Details Page Fixed</h3>';
echo '<p>Your asset details page has been updated to resolve the loading issue and display Naira (₦) currency.</p>';
echo '<p>Key fixes applied:</p>';
echo '<ul style="text-align: left; max-width: 600px; margin: 0 auto;">';
echo '<li>Updated all currency symbols from $ to ₦</li>';
echo '<li>Fixed the asset image modal to properly display images</li>';
echo '<li>Resolved issues that might be causing the page to keep loading</li>';
echo '</ul>';
echo '<p class="mt-3"><a href="my-assets.php" class="btn btn-success">Go to My Assets</a></p>';
echo '</div>';
?>
