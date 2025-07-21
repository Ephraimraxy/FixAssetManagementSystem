<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$user = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

// Get user settings and profile picture if logged in
$profile_picture = null;
$theme = 'light';

if ($user_id) {
    // Database connection
    if (!isset($pdo)) {
        require_once dirname(__FILE__) . '/config.php';
    }
    
    // Get profile picture
    $stmt = $pdo->prepare('SELECT profile_picture FROM users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && !empty($result['profile_picture'])) {
        $profile_picture = $result['profile_picture'];
    }
    
    // Get theme settings
    $stmt = $pdo->prepare('SELECT theme FROM user_settings WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $theme = $result['theme'];
    }
    
    // Handle auto theme based on user's system preference
    if ($theme === 'auto') {
        // We'll use JS to detect system preference
        $theme = 'light';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAMS - Fixed Asset Management System</title>
    <!-- Load local Bootstrap first -->
    <link href="/fams/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <!-- Fallback to CDN if local copy is unavailable -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/fams/assets/css/style.css" rel="stylesheet">
    <link href="/fams/assets/css/profile.css" rel="stylesheet">
    <style>
      .navbar{position:relative;z-index:1050;}
    </style>
    
    <?php if ($theme === 'dark'): ?>
    <link href="/fams/assets/css/dark-mode.css" rel="stylesheet">
    <style>
        :root {
            --bs-body-bg: #121212;
            --bs-body-color: #e0e0e0;
            --bs-border-color: #333;
        }
        body {
            background-color: #121212;
            color: #e0e0e0;
        }
        .card, .navbar, .dropdown-menu, .list-group-item, .modal-content {
            background-color: #1e1e1e;
            border-color: #333;
        }
        .bg-white, .bg-light {
            background-color: #1e1e1e !important;
        }
        .nav-tabs .nav-link.active {
            background-color: #1e1e1e;
            color: #fff;
            border-color: #333;
        }
        .nav-link, .navbar-brand, h1, h2, h3, h4, h5, h6 {
            color: #fff;
        }
        .dropdown-item, .form-control, .form-select {
            color: #e0e0e0;
            background-color: #2d2d2d;
            border-color: #444;
        }
        .dropdown-item:hover {
            background-color: #333;
            color: #fff;
        }
        .table {
            color: #e0e0e0;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        .text-muted {
            color: #aaa !important;
        }
        .border {
            border-color: #333 !important;
        }
        .alert-light, .alert-info {
            background-color: #2d3748;
            color: #e0e0e0;
            border-color: #333;
        }
    </style>
    <?php endif; ?>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="/fams/">
      <i class="fas fa-cubes fa-2x me-2 text-primary"></i>
      <span class="fw-bold">FAMS</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php if ($role === 'admin'): ?>
          <li class="nav-item"><a class="nav-link" href="/fams/admin/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="/fams/admin/assets.php"><i class="fas fa-boxes me-1"></i> Assets</a></li>
          <li class="nav-item"><a class="nav-link" href="/fams/admin/users.php"><i class="fas fa-users me-1"></i> Users</a></li>
          <li class="nav-item"><a class="nav-link" href="/fams/admin/depreciation.php"><i class="fas fa-chart-line me-1"></i> Depreciation</a></li>
          <li class="nav-item"><a class="nav-link" href="/fams/admin/maintenance.php"><i class="fas fa-tools me-1"></i> Maintenance</a></li>
          <li class="nav-item"><a class="nav-link text-danger" href="/fams/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
        <?php elseif ($user): ?>
          <li class="nav-item"><a class="nav-link" href="/fams/user/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="/fams/user/my-assets.php"><i class="fas fa-boxes me-1"></i> My Assets</a></li>
          <li class="nav-item"><a class="nav-link" href="/fams/user/requests.php"><i class="fas fa-file-alt me-1"></i> Requests</a></li>
        <?php endif; ?>
        
        <?php if ($user): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?php if ($profile_picture): ?>
                <img src="/fams/<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="rounded-circle me-1" width="24" height="24" style="object-fit: cover;">
                <?php else: ?>
                <img src="/fams/assets/img/photo_5832351402700163288_y.jpg" alt="Profile" class="rounded-circle me-1" width="24" height="24" style="object-fit: cover;">
                <?php endif; ?>
                <?php echo htmlspecialchars($_SESSION['username']); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
              <li><a class="dropdown-item" href="/fams/user/profile.php"><i class="fas fa-id-card me-2"></i> Profile</a></li>
              <li><a class="dropdown-item" href="/fams/user/settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
              <?php if ($role !== 'admin'): ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="/fams/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
              <?php endif; ?>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="btn btn-primary ms-2" href="/fams/login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
