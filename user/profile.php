<?php
// profile_v2.php  – clean redesign of user profile page

require_once '../includes/config.php';

// -----------------------------------------------------------------------------
// Guard: only logged-in users
// -----------------------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message   = '';

// -----------------------------------------------------------------------------
// Fetch current user data
// -----------------------------------------------------------------------------
$stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// -----------------------------------------------------------------------------
// Quick stats queries
// -----------------------------------------------------------------------------
$asset_count = 0;
$open_requests = 0;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM assets WHERE assigned_to = ?');
$stmt->execute([$user_id]);
$asset_count = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM maintenance_requests WHERE user_id = ? AND status IN ("pending","approved","in_progress")');
$stmt->execute([$user_id]);
$open_requests = $stmt->fetchColumn();

if (!$user) {
    // Corrupted session → force logout
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// -----------------------------------------------------------------------------
// Handle profile-picture upload
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type     = mime_content_type($_FILES['profile_picture']['tmp_name']);

        if (!in_array($file_type, $allowed_types)) {
            $error_message = 'Only JPG, PNG or GIF images are allowed.';
        } elseif ($_FILES['profile_picture']['size'] > 5 * 1024 * 1024) { // 5 MB
            $error_message = 'Image must be smaller than 5 MB.';
        } else {
            // Ensure upload folder exists
            $upload_dir = '../uploads/profile_pictures/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Remove previous custom picture if any
            if (!empty($user['profile_picture']) && file_exists('../' . $user['profile_picture']) && !str_contains($user['profile_picture'], 'default_profile.png')) {
                @unlink('../' . $user['profile_picture']);
            }

            // Compose safe unique filename
            $ext        = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $file_name  = $user_id . '_' . time() . '.' . strtolower($ext);
            $file_name  = preg_replace('/[^a-zA-Z0-9._-]/', '', $file_name);
            $targetPath = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) {
                chmod($targetPath, 0644);
                $relativePath = 'uploads/profile_pictures/' . $file_name;

                // Update DB
                $stmt = $pdo->prepare('UPDATE users SET profile_picture = ?, updated_at = NOW() WHERE user_id = ?');
                $stmt->execute([$relativePath, $user_id]);
                $success_message = 'Profile photo updated!';

                // Refresh in-memory user data
                $user['profile_picture'] = $relativePath;
            } else {
                $error_message = 'Could not upload image. Try again.';
            }
        }
    } else {
        $error_message = 'Please select a valid image.';
    }
}

// -----------------------------------------------------------------------------
// Handle password change
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $error_message = 'All password fields are required.';
    } elseif (!password_verify($current, $user['password'])) {
        $error_message = 'Current password is incorrect.';
    } elseif ($new !== $confirm) {
        $error_message = 'New passwords do not match.';
    } elseif (strlen($new) < 8) {
        $error_message = 'Password must be at least 8 characters.';
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt   = $pdo->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?');
        $stmt->execute([$hashed, $user_id]);
        $success_message = 'Password changed successfully!';
    }
}

// -----------------------------------------------------------------------------
// HTML OUTPUT – Bootstrap 5
// -----------------------------------------------------------------------------
$profile = $user; // preserve user array before including header (header redefines $user)
require_once '../includes/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body text-center p-4">
                    <?php
                    $profile_image = '../assets/img/default_profile.png';
                    if (!empty($profile['profile_picture'])) {
                        $profile_image = '../' . $profile['profile_picture'];
                    }
                    ?>
                    <img src="<?= htmlspecialchars($profile_image) ?>" class="rounded-circle mb-3" style="width: 140px;height: 140px;object-fit: cover;" alt="Profile" />

                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($profile['full_name']) ?></h4>
                    <p class="text-muted mb-3">
                        <?= htmlspecialchars($profile['department']) ?> | <?= htmlspecialchars($profile['position']) ?>
                    </p>

                    <ul class="list-group list-group-flush text-start mb-4">
                                                <li class="list-group-item"><strong>Email:</strong> <?= htmlspecialchars($profile['email']) ?></li>
                    </ul>

                    <div class="row text-center mb-3">
                            <div class="col-6">
                                <p class="mb-0 text-muted small">MY ASSETS</p>
                                <h5 class="mb-0"><?= $asset_count ?></h5>
                            </div>
                            <div class="col-6">
                                <p class="mb-0 text-muted small">OPEN REQUESTS</p>
                                <h5 class="mb-0"><?= $open_requests ?></h5>
                            </div>
                        </div>
                        
                        <!-- Change Picture Trigger -->
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#pictureModal"><i class="fas fa-camera me-2"></i>Change Photo</button>
                </div>
            </div>

            <!-- Password Change Accordion -->
            <div class="accordion mt-4" id="passwordAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingPw">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePw" aria-expanded="false" aria-controls="collapsePw">
                            Change Password
                        </button>
                    </h2>
                    <div id="collapsePw" class="accordion-collapse collapse" aria-labelledby="headingPw" data-bs-parent="#passwordAccordion">
                        <div class="accordion-body">
                            <form method="post" action="profile.php">
                                <input type="hidden" name="change_password" value="1" />
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" name="current_password" id="current_password" class="form-control" required />
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" name="new_password" id="new_password" class="form-control" required />
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required />
                                </div>
                                <button class="btn btn-primary w-100" type="submit"><i class="fas fa-save me-2"></i>Update Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Picture Modal -->
<div class="modal fade" id="pictureModal" tabindex="-1" aria-labelledby="pictureModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pictureModalLabel">Update Profile Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="profile.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="upload_picture" value="1" />
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Choose Image</label>
                        <input type="file" name="profile_picture" id="profile_picture" class="form-control" accept="image/jpeg,image/png,image/gif" required />
                        <div class="form-text">Max size: 5 MB. Allowed: JPG, PNG, GIF.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
