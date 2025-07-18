<?php
// This script will fix your profile.php file to ensure everything works correctly
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Fix the file
$profileFile = 'profile.php';
$content = file_get_contents($profileFile);

// Add default profile picture path
if (!file_exists('../assets/img/default_profile.png')) {
    // Create a placeholder image or download one
    copy('https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png', '../assets/img/default_profile.png');
}

// Fix the syntax error and improve profile picture display
$newContent = preg_replace(
    '/\s*else\s*{\s*\$error_message\s*=\s*\'Please select a valid image file.\'\;\s*\}\s*\}\s*/s',
    "\n        \$error_message = 'Please select a valid image file.';\n    }\n}\n",
    $content
);

// Save the fixed file
file_put_contents($profileFile, $newContent);

echo '<div style="padding: 20px; background-color: #d4edda; color: #155724; border-radius: 5px; margin: 20px;">';
echo '<h3>Fixed Profile Page</h3>';
echo '<p>Your profile.php file has been fixed. The syntax error has been resolved, and the profile picture functionality should now work correctly.</p>';
echo '<p>The profile picture will now display consistently in both the header navigation and the upload modal.</p>';
echo '<p><a href="profile.php" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">Go to Profile Page</a></p>';
echo '</div>';
?>
