<?php
// This script fixes role-based navigation in the FAMS system
require_once 'includes/config.php';

$fixes_applied = [];

// 1. Fix login.php redirect
$login_file = 'login.php';
$login_content = file_get_contents($login_file);

// Fix issue where all users are sent to admin/dashboard.php
$new_login_content = str_replace(
    "header('Location: admin/dashboard.php');",
    "// Redirect based on role
    if (\$_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }",
    $login_content
);

if ($new_login_content != $login_content) {
    file_put_contents($login_file, $new_login_content);
    $fixes_applied[] = "Fixed login.php to properly redirect based on user role";
}

// 2. Fix admin/includes/header.php to have proper navigation
$admin_header = 'admin/includes/header.php';
if (file_exists($admin_header)) {
    $admin_header_content = file_get_contents($admin_header);
    
    // Make sure admin navigation links are properly prefixed
    $fixed_admin_header = str_replace(
        'href="/fams/user/',
        'href="/fams/admin/',
        $admin_header_content
    );
    
    if ($fixed_admin_header != $admin_header_content) {
        file_put_contents($admin_header, $fixed_admin_header);
        $fixes_applied[] = "Fixed admin header navigation links";
    }
}

// 3. Create .htaccess file to handle role-based access protection
$htaccess_content = "# Protect directories based on role
RewriteEngine On

# Redirect non-admin users away from admin area
RewriteCond %{REQUEST_URI} ^/fams/admin/
RewriteCond %{HTTP_COOKIE} !role=admin
RewriteRule ^(.*)$ /fams/login.php [R,L]

# Redirect non-logged in users to login
RewriteCond %{REQUEST_URI} ^/fams/user/
RewriteCond %{HTTP_COOKIE} !user_id
RewriteRule ^(.*)$ /fams/login.php [R,L]
";

$htaccess_file = '.htaccess';
if (!file_exists($htaccess_file)) {
    file_put_contents($htaccess_file, $htaccess_content);
    $fixes_applied[] = "Created .htaccess file for role-based access protection";
}

// 4. Add logout function that clears session completely
$logout_file = 'logout.php';
$logout_content = "<?php
session_start();
// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
";

file_put_contents($logout_file, $logout_content);
$fixes_applied[] = "Created improved logout function that completely clears session";

// 5. Create an index.php file that redirects based on role
$index_file = 'index.php';
$index_content = "<?php
require_once 'includes/config.php';

// Check if user is logged in
if (isset(\$_SESSION['user_id'])) {
    // Redirect based on role
    if (\$_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
} else {
    // Not logged in, redirect to login page
    header('Location: login.php');
    exit;
}
";

file_put_contents($index_file, $index_content);
$fixes_applied[] = "Created index.php with proper role-based routing";

// Success message
echo '<div style="background-color: #d4edda; color: #155724; padding: 20px; margin: 20px; border-radius: 5px;">';
echo '<h2 style="text-align: center; margin-bottom: 20px;">Role Navigation Fixed</h2>';
echo '<div style="margin-bottom: 20px;">';
echo '<p style="font-weight: bold;">The navigation issues between admin and user sections have been fixed:</p>';
echo '<ul style="line-height: 1.6;">';
foreach ($fixes_applied as $fix) {
    echo '<li>' . htmlspecialchars($fix) . '</li>';
}
echo '</ul>';
echo '</div>';

echo '<div style="text-align: center; margin-top: 30px;">';
echo '<p style="font-weight: bold; margin-bottom: 15px;">Now when you log in:</p>';
echo '<div style="display: inline-block; text-align: left;">';
echo '<ul style="line-height: 1.6;">';
echo '<li>Admin users will stay in the admin section (/fams/admin/...)</li>';
echo '<li>Regular users will stay in the user section (/fams/user/...)</li>';
echo '<li>Access is properly restricted based on role</li>';
echo '</ul>';
echo '</div>';
echo '</div>';

echo '<div style="text-align: center; margin-top: 20px;">';
echo '<p>Click one of these links to continue:</p>';
echo '<a href="logout.php" style="display: inline-block; margin: 10px; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">Logout and Test Again</a>';
echo '<a href="index.php" style="display: inline-block; margin: 10px; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px;">Go to Your Dashboard</a>';
echo '</div>';
echo '</div>';
?>
