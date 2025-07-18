<?php
// This file will fix the syntax errors in profile.php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Read the current file
$file_path = 'profile.php';
$content = file_get_contents($file_path);

// Fix the syntax error - remove the extra closing brace that's causing issues
$content = preg_replace('/(\$error_message = \'Please select a valid image file.\';[\s\n]+}[\s\n]+}[\s\n]+})/', '$error_message = \'Please select a valid image file.\';
    }
}', $content);

// Also update default profile picture references to use your photo
$content = str_replace('../assets/img/default_profile.png', '../assets/img/photo_5832351402700163288_y.jpg', $content);

// Write the fixed content back to file
file_put_contents($file_path, $content);

// Success message
echo '<div style="background-color: #d4edda; color: #155724; padding: 15px; margin: 20px; border-radius: 5px; text-align: center;">';
echo '<h3>Profile Page Fixed</h3>';
echo '<p>Your profile page has been updated to fix syntax errors and use your photo as the default profile picture.</p>';
echo '<a href="profile.php" class="btn btn-success">Return to Profile</a>';
echo '</div>';
?>
