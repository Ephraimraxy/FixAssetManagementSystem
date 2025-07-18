<?php
require_once '../includes/config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin-login.php');
    exit;
}

// Redirect to user area if not admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../user/dashboard.php');
    exit;
}

// Redirect to admin dashboard
header('Location: dashboard.php');
exit;
