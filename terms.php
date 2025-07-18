<?php
session_start();
$page_title = "Terms of Use";
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
                        <a class="nav-link" href="/fams/about.php"><i class="fas fa-info-circle me-1"></i> About</a>
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
                            <h1 class="display-4 fw-bold text-primary">Terms of Use</h1>
                            <p class="lead text-muted">Last Updated: <?php echo date('F d, Y'); ?></p>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="mb-3 text-primary">1. Acceptance of Terms</h3>
                            <p>By accessing and using the Fixed Asset Management System (FAMS) by EFFY'S CONCEPT, you acknowledge that you have read, understood, and agree to be bound by these Terms of Use. If you do not agree to these terms, please do not use this system.</p>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="mb-3 text-primary">2. Description of Service</h3>
                            <p>FAMS is a comprehensive solution designed to help organizations track, manage, and optimize their fixed assets throughout their lifecycle. The system provides tools for asset registration, tracking, maintenance scheduling, depreciation calculation, and reporting.</p>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="mb-3 text-primary">3. User Accounts and Security</h3>
                            <p>To access certain features of FAMS, you may be required to create a user account. You are responsible for:</p>
                            <ul>
                                <li>Maintaining the confidentiality of your account credentials</li>
                                <li>All activities that occur under your account</li>
                                <li>Notifying us immediately of any unauthorized use of your account</li>
                            </ul>
                            <p>EFFY'S CONCEPT is not liable for any loss or damage arising from your failure to comply with these security obligations.</p>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="mb-3 text-primary">4. User Conduct</h3>
                            <p>While using FAMS, you agree not to:</p>
                            <ul>
                                <li>Violate any applicable laws or regulations</li>
                                <li>Infringe upon the rights of others</li>
                                <li>Distribute viruses or malicious code</li>
                                <li>Attempt to gain unauthorized access to the system</li>
                                <li>Use the system for any illegal or unauthorized purpose</li>
                            </ul>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="mb-3 text-primary">5. Data Privacy</h3>
                            <p>We respect your privacy and are committed to protecting your personal data. Any information you provide while using FAMS will be handled in accordance with our Privacy Policy. By using FAMS, you consent to the collection, processing, and storage of your data as described in the Privacy Policy.</p>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="mb-3 text-primary">6. Intellectual Property</h3>
                            <p>All content, features, and functionality of FAMS, including but not limited to text, graphics, logos, icons, images, audio clips, digital downloads, and software, are the exclusive property of EFFY'S CONCEPT and are protected by copyright, trademark, and other intellectual property laws.</p>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="mb-3 text-primary">7. Limitation of Liability</h3>
                            <p>EFFY'S CONCEPT shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including but not limited to loss of profits, data, or use, arising out of or in any way connected with the use of FAMS.</p>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="mb-3 text-primary">8. Modifications to Terms</h3>
                            <p>EFFY'S CONCEPT reserves the right to modify these Terms of Use at any time. We will notify users of any material changes to these terms. Your continued use of FAMS after any changes to the Terms constitutes your acceptance of the revised Terms.</p>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="mb-3 text-primary">9. Termination</h3>
                            <p>EFFY'S CONCEPT reserves the right to terminate or suspend your access to FAMS, without prior notice or liability, for any reason, including but not limited to a breach of these Terms.</p>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="mb-3 text-primary">10. Contact Information</h3>
                            <p>If you have any questions about these Terms of Use, please contact us at:</p>
                            <address>
                                <strong>Email:</strong> <a href="mailto:hoseaephraim50@gmail.com">hoseaephraim50@gmail.com</a><br>
                                <strong>Phone:</strong> <a href="tel:+2348087815454">+234 808 781 5454</a><br>
                                <strong>Telegram:</strong> <a href="t.me/Effy_boi" target="_blank">t.me/Effy_boi</a>
                            </address>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>
