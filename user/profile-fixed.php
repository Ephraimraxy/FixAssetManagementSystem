<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get user data
$stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user data not found, redirect to login
if (!$user) {
    header('Location: ../login.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($username) || empty($email) || empty($full_name)) {
        $error_message = 'Username, email and full name are required fields.';
    } else {
        // Check if username is already taken by another user
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = ? AND user_id != ?');
        $stmt->execute([$username, $user_id]);
        if ($stmt->rowCount() > 0) {
            $error_message = 'Username is already taken. Please choose a different one.';
        } else {
            // Check if email is already taken by another user
            $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ?');
            $stmt->execute([$email, $user_id]);
            if ($stmt->rowCount() > 0) {
                $error_message = 'Email address is already in use. Please use a different one.';
            } else {
                // If user wants to change password
                if (!empty($current_password) && !empty($new_password)) {
                    // Verify current password
                    if (!password_verify($current_password, $user['password'])) {
                        $error_message = 'Current password is incorrect.';
                    } else if ($new_password !== $confirm_password) {
                        $error_message = 'New passwords do not match.';
                    } else if (strlen($new_password) < 8) {
                        $error_message = 'New password must be at least 8 characters long.';
                    } else {
                        // Hash the new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        
                        // Update user data with new password
                        $stmt = $pdo->prepare('
                            UPDATE users 
                            SET username = ?, email = ?, full_name = ?, phone = ?, 
                                department = ?, position = ?, password = ?, updated_at = NOW() 
                            WHERE user_id = ?
                        ');
                        if ($stmt->execute([$username, $email, $full_name, $phone, $department, $position, $hashed_password, $user_id])) {
                            $success_message = 'Profile updated successfully with new password.';
                            
                            // Refresh user data
                            $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
                            $stmt->execute([$user_id]);
                            $user = $stmt->fetch();
                        } else {
                            $error_message = 'An error occurred while updating your profile.';
                        }
                    }
                } else {
                    // Update user data without changing password
                    $stmt = $pdo->prepare('
                        UPDATE users 
                        SET username = ?, email = ?, full_name = ?, phone = ?, 
                            department = ?, position = ?, updated_at = NOW() 
                        WHERE user_id = ?
                    ');
                    if ($stmt->execute([$username, $email, $full_name, $phone, $department, $position, $user_id])) {
                        $success_message = 'Profile updated successfully.';
                        
                        // Refresh user data
                        $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
                        $stmt->execute([$user_id]);
                        $user = $stmt->fetch();
                    } else {
                        $error_message = 'An error occurred while updating your profile.';
                    }
                }
            }
        }
    }
}

// Process profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_picture']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // Create upload directory if it doesn't exist
            $upload_dir = '../uploads/profile_pictures/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Remove old profile picture if it exists and is not the default
            if (!empty($user['profile_picture']) && file_exists('../' . $user['profile_picture']) && 
                strpos($user['profile_picture'], 'default_profile.png') === false) {
                unlink('../' . $user['profile_picture']);
            }
            
            // Generate unique filename
            $file_name = $user_id . '_' . time() . '_' . basename($_FILES['profile_picture']['name']);
            $target_file = $upload_dir . $file_name;
            
            // Upload file
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                // Update database
                $profile_picture_path = 'uploads/profile_pictures/' . $file_name;
                $stmt = $pdo->prepare('UPDATE users SET profile_picture = ? WHERE user_id = ?');
                $success = $stmt->execute([$profile_picture_path, $user_id]);
                
                if ($success) {
                    $success_message = 'Profile picture updated successfully.';
                    
                    // Refresh user data
                    $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error_message = 'Failed to update profile picture in the database.';
                }
            } else {
                $error_message = 'Failed to upload profile picture. Please try again.';
            }
        } else {
            $error_message = 'Please select a valid image file.';
        }
    }
}

// Count user assets
$stmt = $pdo->prepare('SELECT COUNT(*) FROM assets WHERE assigned_to = ?');
$stmt->execute([$user_id]);
$asset_count = $stmt->fetchColumn();

// Get open maintenance requests
$stmt = $pdo->prepare('
    SELECT COUNT(*) FROM maintenance_requests 
    WHERE user_id = ? AND status IN ("pending", "approved", "in_progress")
');
$stmt->execute([$user_id]);
$open_requests = $stmt->fetchColumn();

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-0"><i class="fas fa-user-circle me-2 text-primary"></i> User Profile</h1>
            <p class="text-muted">View and update your profile information</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="settings.php" class="btn btn-outline-primary">
                <i class="fas fa-cog me-2"></i> Settings
            </a>
        </div>
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
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <?php 
                        $profile_image = '../assets/img/photo_5832351402700163288_y.jpg'; // Default image
                        if (!empty($user['profile_picture'])) {
                            $profile_image = '../' . $user['profile_picture'];
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Picture" 
                             class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                    <p class="text-muted mb-3">
                        <?php 
                        $role_text = ($user['role'] === 'admin') ? 'Administrator' : 'User';
                        echo htmlspecialchars($role_text); 
                        ?>
                    </p>
                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#uploadPictureModal">
                        <i class="fas fa-camera me-2"></i> Change Picture
                    </button>
                </div>
                <div class="card-footer bg-light">
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <p class="mb-0 text-muted small">MY ASSETS</p>
                            <h5 class="mb-0"><?php echo $asset_count; ?></h5>
                        </div>
                        <div class="col-6">
                            <p class="mb-0 text-muted small">OPEN REQUESTS</p>
                            <h5 class="mb-0"><?php echo $open_requests; ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2 text-primary"></i> Edit Profile
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" action="profile.php">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                    value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                    value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                    value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                    value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department" 
                                    value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="position" class="form-label">Position</label>
                                <input type="text" class="form-control" id="position" name="position" 
                                    value="<?php echo htmlspecialchars($user['position'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        <h6 class="mb-3">Change Password (leave blank to keep current password)</h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            <div class="col-md-4">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            <div class="col-md-4">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Profile Picture Modal -->
<div class="modal fade" id="uploadPictureModal" tabindex="-1" aria-labelledby="uploadPictureModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadPictureModalLabel">Upload Profile Picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="profile.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="upload_picture" value="1">
                    
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Select Image</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/jpeg, image/png, image/gif" required>
                        <div class="form-text">
                            Accepted formats: JPG, PNG, GIF. Maximum file size: 5MB.
                        </div>
                    </div>
                    
                    <?php
                    // Get the same profile picture used in the header
                    $profile_pic = '';
                    if (isset($_SESSION['user_id'])) {
                        $stmt = $pdo->prepare('SELECT profile_picture FROM users WHERE user_id = ?');
                        $stmt->execute([$_SESSION['user_id']]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($result && !empty($result['profile_picture'])) {
                            $profile_pic = $result['profile_picture'];
                        }
                    }
                    ?>
                    <div class="text-center mt-3">
                        <p>Current Profile Picture for <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong>:</p>
                        <img src="<?php echo !empty($profile_pic) ? '../' . htmlspecialchars($profile_pic) : '../assets/img/photo_5832351402700163288_y.jpg'; ?>" 
                             alt="Current Profile" class="img-fluid rounded-circle border" style="width: 120px; height: 120px; object-fit: cover;">
                    </div>
                    
                    <div class="text-center mt-3 preview-container" style="display: none;">
                        <p>Preview:</p>
                        <img id="image-preview" src="#" alt="Preview" class="img-fluid rounded-circle" style="max-width: 150px; max-height: 150px; object-fit: cover;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview image before upload
    const profilePictureInput = document.getElementById('profile_picture');
    const imagePreview = document.getElementById('image-preview');
    const previewContainer = document.querySelector('.preview-container');
    
    profilePictureInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                previewContainer.style.display = 'block';
            }
            
            reader.readAsDataURL(file);
        } else {
            previewContainer.style.display = 'none';
        }
    });

    // No auto-refresh - removed as per user request
});
</script>

<?php require_once '../includes/footer.php'; ?>
