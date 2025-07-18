<?php
session_start();
$page_title = "About Us";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Fixed Asset Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/fams/assets/css/style.css" rel="stylesheet">
    <link href="/fams/assets/css/profile.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/fams/">
                <i class="fas fa-cubes me-2"></i>
                <span>EFFY'S CONCEPT - FAMS</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/fams/"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/fams/about.php"><i class="fas fa-info-circle me-1"></i> About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/fams/contact.php"><i class="fas fa-envelope me-1"></i> Contact</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/fams/user/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/fams/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/fams/login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-5">
                            <h1 class="display-4 fw-bold text-primary">About EFFY'S CONCEPT</h1>
                            <p class="lead text-muted">Fixed Asset Management System (FAMS)</p>
                        </div>
                        
                        <div class="row mb-5">
                            <div class="col-md-6 mb-4 mb-md-0">
                                <img src="assets/img/about-image.jpg" onerror="this.src='https://via.placeholder.com/600x400?text=FAMS'" class="img-fluid rounded shadow" alt="FAMS System">
                            </div>
                            <div class="col-md-6">
                                <h3 class="mb-4 text-primary">Our Mission</h3>
                                <p>At EFFY'S CONCEPT, we're dedicated to revolutionizing how organizations track, manage, and optimize their fixed assets. Our mission is to provide intuitive, powerful tools that enhance asset lifecycle management while reducing costs and improving organizational efficiency.</p>
                                <p>We believe that effective asset management is a cornerstone of operational excellence and financial health for any organization.</p>
                            </div>
                        </div>
                        
                        <div class="row mb-5">
                            <div class="col-12">
                                <h3 class="mb-4 text-primary">What is FAMS?</h3>
                                <p>The Fixed Asset Management System (FAMS) is a comprehensive solution designed to streamline the management of your organization's physical assets throughout their entire lifecycle. From acquisition to disposal, our system provides the tools you need to:</p>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="d-flex mb-3">
                                            <div class="me-3 text-primary">
                                                <i class="fas fa-check-circle fa-2x"></i>
                                            </div>
                                            <div>
                                                <h5>Track Asset Location</h5>
                                                <p class="text-muted">Know where your assets are at all times, across departments and locations.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex mb-3">
                                            <div class="me-3 text-primary">
                                                <i class="fas fa-chart-line fa-2x"></i>
                                            </div>
                                            <div>
                                                <h5>Monitor Depreciation</h5>
                                                <p class="text-muted">Automatically calculate and track asset depreciation using various methods.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex mb-3">
                                            <div class="me-3 text-primary">
                                                <i class="fas fa-tools fa-2x"></i>
                                            </div>
                                            <div>
                                                <h5>Maintenance Management</h5>
                                                <p class="text-muted">Schedule and track maintenance to extend asset lifespans and prevent downtime.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex mb-3">
                                            <div class="me-3 text-primary">
                                                <i class="fas fa-file-alt fa-2x"></i>
                                            </div>
                                            <div>
                                                <h5>Comprehensive Reporting</h5>
                                                <p class="text-muted">Generate detailed reports for auditing, financial planning, and decision making.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <h3 class="mb-4 text-primary">About the Developer</h3>
                                <div class="d-flex flex-column flex-md-row align-items-center">
                                    <div class="me-md-4 mb-3 mb-md-0">
                                        <img src="/fams/assets/img/photo_5832351402700163288_y.jpg" class="rounded-circle shadow-sm" width="150" height="150" alt="Hosea Ephraim" style="object-fit: cover;">
                                    </div>
                                    <div>
                                        <h4>Hosea Ephraim</h4>
                                        <p class="lead">Software Developer & Designer</p>
                                        <p>I'm passionate about creating elegant, efficient software solutions that solve real-world problems. FAMS represents my commitment to helping organizations better manage their valuable assets through thoughtful design and powerful functionality.</p>
                                        <div class="mt-3">
                                            <a href="https://github.com/Ephraimraxy" class="btn btn-outline-primary me-2" target="_blank"><i class="fab fa-github me-2"></i>GitHub</a>
                                            <a href="mailto:hoseaephraim50@gmail.com" class="btn btn-outline-primary"><i class="fas fa-envelope me-2"></i>Contact Me</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>
