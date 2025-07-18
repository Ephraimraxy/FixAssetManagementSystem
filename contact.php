<?php
session_start();
$page_title = "Contact Us";

// Process contact form
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Simple validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Please fill in all the fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // In a real application, you would send an email here
        // For demonstration purposes, we'll just show a success message
        $success_message = 'Thank you for your message! We will get back to you soon.';
        
        // Reset form fields after successful submission
        $name = $email = $subject = $message = '';
    }
}
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
                        <a class="nav-link active" href="/fams/contact.php"><i class="fas fa-envelope me-1"></i> Contact</a>
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
                            <h1 class="display-4 fw-bold text-primary">Contact Us</h1>
                            <p class="lead text-muted">Get in touch with the FAMS team</p>
                        </div>
                        
                        <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4 mb-md-0">
                                <h3 class="mb-4 text-primary">Send a Message</h3>
                                <form method="post" action="">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Your Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="subject" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="message" class="form-label">Message</label>
                                        <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                                    </div>
                                    <button type="submit" name="contact_submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Send Message
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <h3 class="mb-4 text-primary">Contact Information</h3>
                                <div class="bg-light p-4 rounded h-100">
                                    <div class="d-flex mb-4">
                                        <div class="me-3 text-primary">
                                            <i class="fas fa-user fa-2x"></i>
                                        </div>
                                        <div>
                                            <h5>Hosea Ephraim</h5>
                                            <p class="text-muted">Developer & Designer</p>
                                        </div>
                                    </div>
                                    <div class="d-flex mb-4">
                                        <div class="me-3 text-primary">
                                            <i class="fas fa-envelope fa-2x"></i>
                                        </div>
                                        <div>
                                            <h5>Email</h5>
                                            <p class="text-muted">
                                                <a href="mailto:hoseaephraim50@gmail.com" class="text-decoration-none">hoseaephraim50@gmail.com</a>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="d-flex mb-4">
                                        <div class="me-3 text-primary">
                                            <i class="fas fa-phone fa-2x"></i>
                                        </div>
                                        <div>
                                            <h5>Phone</h5>
                                            <p class="text-muted">
                                                <a href="tel:+2348087815454" class="text-decoration-none">+234 808 781 5454</a>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="d-flex mb-4">
                                        <div class="me-3 text-primary">
                                            <i class="fab fa-telegram fa-2x"></i>
                                        </div>
                                        <div>
                                            <h5>Telegram</h5>
                                            <p class="text-muted">
                                                <a href="t.me/Effy_boi" class="text-decoration-none" target="_blank">t.me/Effy_boi</a>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <h5 class="mb-3">Connect with Me</h5>
                                        <div class="d-flex">
                                            <a href="https://www.facebook.com/profile.php?id=100007187109436" class="text-decoration-none text-primary me-3" target="_blank">
                                                <i class="fab fa-facebook-f fa-2x"></i>
                                            </a>
                                            <a href="https://github.com/Ephraimraxy" class="text-decoration-none text-primary me-3" target="_blank">
                                                <i class="fab fa-github fa-2x"></i>
                                            </a>
                                            <a href="t.me/Effy_boi" class="text-decoration-none text-primary" target="_blank">
                                                <i class="fab fa-telegram fa-2x"></i>
                                            </a>
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
