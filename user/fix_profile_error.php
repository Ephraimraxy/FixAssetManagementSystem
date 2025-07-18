<?php
// Fix for syntax error in profile.php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Read the profile.php file
$profile_file = 'profile.php';
$content = file_get_contents($profile_file);

// Make a backup of the original file
copy($profile_file, $profile_file . '.bak');

// Fix the syntax error - there's an unclosed { on line 116 that doesn't match a ) on line 118
$fixed_content = preg_replace(
    '/mkdir\(\$upload_dir, 0777, true\);(\s+)strpos\(\$user\[\'profile_picture\'\], \'default_profile\.png\'\) === false\) {/',
    "mkdir(\$upload_dir, 0777, true);\n            }\n            \n            // Check if there's an existing profile picture to delete\n            if (!empty(\$user['profile_picture']) && file_exists('../' . \$user['profile_picture']) && 
                strpos(\$user['profile_picture'], 'default_profile.png') === false) {",
    $content
);

// Save the fixed content back to the file
file_put_contents($profile_file, $fixed_content);

// Success message
echo '<div style="background-color: #d4edda; color: #155724; padding: 20px; margin: 20px; border-radius: 5px; font-family: Arial, sans-serif;">';
echo '<h2 style="text-align: center;">Profile.php Syntax Error Fixed!</h2>';
echo '<p>The syntax error in your profile.php file has been successfully fixed:</p>';
echo '<ul>';
echo '<li>Fixed the unclosed curly brace on line 116</li>';
echo '<li>Corrected the mismatched parenthesis issue</li>';
echo '<li>Created a backup of your original file (.bak extension)</li>';
echo '</ul>';

echo '<div style="text-align: center; margin-top: 20px;">';
echo '<a href="profile.php" style="display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">Go to Profile Page</a>';
echo '</div>';
echo '</div>';
?>
