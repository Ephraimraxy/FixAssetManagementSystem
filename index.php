<?php
require_once 'includes/config.php';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}

// Process user registration
$registration_success = false;
$registration_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['reg_username'] ?? '');
    $email = trim($_POST['reg_email'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name)) {
        $registration_error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $registration_error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $registration_error = 'Password must be at least 8 characters long.';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $registration_error = 'Username or email already exists.';
        } else {
            // Create new user account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, "user")');
            if ($stmt->execute([$username, $hashed_password, $email, $full_name])) {
                $registration_success = true;
            } else {
                $registration_error = 'Registration failed. Please try again later.';
            }
        }
    }
}

// Process login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: user/dashboard.php');
            }
            exit;
        } else {
            $login_error = 'Invalid username or password.';
        }
    } else {
        $login_error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to FAMS - Fixed Asset Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1) 0%, rgba(114, 9, 183, 0.1) 100%);
            padding: 100px 0;
        }
        .auth-tabs .nav-link {
            padding: 15px 25px;
            font-weight: 600;
            color: #6c757d;
            border: none;
            border-radius: 0;
        }
        .auth-tabs .nav-link.active {
            color: var(--primary);
            background: transparent;
            border-bottom: 3px solid var(--primary);
        }
        .auth-card {
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .feature-card {
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Header with Navigation -->
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
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                    <li class="nav-item"><a class="btn btn-outline-primary ms-2" href="admin-login.php">Admin Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Auth -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <h1 class="display-4 fw-bold mb-4">Manage Your Assets Efficiently</h1>
                    <p class="lead mb-4">Track, monitor, and optimize your organization's assets with our comprehensive Fixed Asset Management System.</p>
                    <div class="d-flex">
                        <a href="#features" class="btn btn-primary me-3">Learn More</a>
                        <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#demoModal">Request Demo</a>
                    </div>
                </div>
                <div class="col-lg-5 offset-lg-1">
                    <div class="card auth-card">
                        <div class="card-body p-4">
                            <ul class="nav nav-tabs auth-tabs mb-4" id="authTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab" aria-controls="login" aria-selected="true">Login</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab" aria-controls="register" aria-selected="false">Register</button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="authTabContent">
                                <!-- Login Tab -->
                                <div class="tab-pane fade show active" id="login" role="tabpanel" aria-labelledby="login-tab">
                                    <?php if ($login_error): ?>
                                        <div class="alert alert-danger"><?php echo htmlspecialchars($login_error); ?></div>
                                    <?php endif; ?>
                                    <form method="post" action="">
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Username</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                <input type="text" class="form-control" id="username" name="username" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                <input type="password" class="form-control" id="password" name="password" required>
                                            </div>
                                        </div>
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="remember">
                                            <label class="form-check-label" for="remember">Remember me</label>
                                        </div>
                                        <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                                    </form>
                                </div>
                                
                                <!-- Register Tab -->
                                <div class="tab-pane fade" id="register" role="tabpanel" aria-labelledby="register-tab">
                                    <?php if ($registration_success): ?>
                                        <div class="alert alert-success">Registration successful! You can now login.</div>
                                    <?php elseif ($registration_error): ?>
                                        <div class="alert alert-danger"><?php echo htmlspecialchars($registration_error); ?></div>
                                    <?php endif; ?>
                                    <form method="post" action="">
                                        <div class="mb-3">
                                            <label for="full_name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="reg_username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="reg_username" name="reg_username" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="reg_email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="reg_email" name="reg_email" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="reg_password" class="form-label">Password</label>
                                            <input type="password" class="form-control" id="reg_password" name="reg_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        <button type="submit" name="register" class="btn btn-primary w-100">Register</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Key Features</h2>
                <p class="text-muted">Discover what makes our asset management system stand out</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 feature-card">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-chart-line feature-icon"></i>
                            <h4>Depreciation Tracking</h4>
                            <p class="text-muted">Automatically calculate and track asset depreciation using multiple methods.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 feature-card">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-map-marker-alt feature-icon"></i>
                            <h4>Location Management</h4>
                            <p class="text-muted">Keep track of where your assets are located and who they're assigned to.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 feature-card">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-tools feature-icon"></i>
                            <h4>Maintenance Scheduling</h4>
                            <p class="text-muted">Schedule and track maintenance to extend the life of your assets.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="py-5 bg-light" id="about">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <img src="assets/images/about.jpg" alt="About FAMS" class="img-fluid rounded shadow" onerror="this.src='https://via.placeholder.com/600x400?text=About+FAMS'">
                </div>
                <div class="col-lg-6">
                    <h2 class="fw-bold mb-4">About FAMS</h2>
                    <p class="lead">FAMS is a comprehensive Fixed Asset Management System designed to help organizations track and manage their assets efficiently.</p>
                    <p>Our system provides tools for tracking asset depreciation, maintenance schedules, and location management, helping you optimize your asset lifecycle and reduce costs.</p>
                    <p>Whether you're a small business or a large enterprise, FAMS scales to meet your needs.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-5" id="contact">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Contact Us</h2>
                <p class="text-muted">Have questions? We're here to help!</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow">
                        <div class="card-body p-4">
                            <form>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="subject" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="subject" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="message" class="form-label">Message</label>
                                        <textarea class="form-control" id="message" rows="5" required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">Send Message</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Demo Modal -->
    <div class="modal fade" id="demoModal" tabindex="-1" aria-labelledby="demoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="demoModalLabel">Request a Demo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="demoName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="demoName" required>
                        </div>
                        <div class="mb-3">
                            <label for="demoEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="demoEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="demoCompany" class="form-label">Company</label>
                            <input type="text" class="form-control" id="demoCompany" required>
                        </div>
                        <div class="mb-3">
                            <label for="demoPhone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="demoPhone">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Request Demo</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <h5 class="mb-3 text-primary"><i class="fas fa-cubes me-2"></i> EFFY'S CONCEPT</h5>
                    <p>A comprehensive solution for tracking and managing your organization's assets.</p>
                    <p><small>Designed & Developed with <i class="fas fa-heart text-danger"></i> by Hosea Ephraim</small></p>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <h5 class="mb-3 text-primary">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="/fams/" class="text-decoration-none text-light"><i class="fas fa-home me-2 text-primary"></i>Home</a></li>
                        <li class="mb-2"><a href="/fams/about.php" class="text-decoration-none text-light"><i class="fas fa-info-circle me-2 text-primary"></i>About</a></li>
                        <li class="mb-2"><a href="/fams/contact.php" class="text-decoration-none text-light"><i class="fas fa-envelope me-2 text-primary"></i>Contact</a></li>
                        <li class="mb-2"><a href="/fams/terms.php" class="text-decoration-none text-light"><i class="fas fa-gavel me-2 text-primary"></i>Terms of Use</a></li>
                        <li><a href="#about" class="text-decoration-none text-muted"><i class="fas fa-info-circle me-2"></i>About</a></li>
                        <li><a href="#contact" class="text-decoration-none text-muted"><i class="fas fa-envelope me-2"></i>Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Contact Us</h5>
                    <ul class="list-unstyled text-muted">
                        <li><i class="fas fa-envelope me-2"></i> info@fams.com</li>
                        <li><i class="fas fa-phone me-2"></i> +1 (555) 123-4567</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> 123 Asset Street, Suite 456</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 bg-secondary">
            <div class="d-flex justify-content-between align-items-center">
                <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> Fixed Asset Management System. All rights reserved.</p>
                <div>
                    <a href="#" class="text-decoration-none text-muted me-2"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-decoration-none text-muted me-2"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-decoration-none text-muted"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
