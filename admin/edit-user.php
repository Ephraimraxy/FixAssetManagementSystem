<?php
require_once '../includes/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../includes/header.php';

$user_id = intval($_GET['id'] ?? 0);
if ($user_id <= 0) {
    header('Location: users.php');
    exit;
}

// Fetch existing user
$stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) {
    header('Location: users.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? $user['role'];
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? $user['status'];
    $new_password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($email) || empty($full_name)) {
        $error = 'Email and full name are required.';
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check email uniqueness (exclude current user)
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ?');
        $stmt->execute([$email, $user_id]);
        if ($stmt->rowCount() > 0) {
            $error = 'Email address already exists.';
        } else {
            // Build update query
            $fields = 'email = ?, full_name = ?, role = ?, department = ?, position = ?, phone = ?, status = ?';
            $params = [$email, $full_name, $role, $department, $position, $phone, $status, $user_id];
            if (!empty($new_password)) {
                $fields .= ', password = ?';
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                array_splice($params, -1, 0, $hashed_password); // insert before user_id
            }
            $sql = "UPDATE users SET $fields WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $success = 'User updated successfully!';
                // refresh user data
                $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } else {
                $error = 'Error updating user: ' . $stmt->errorInfo()[2];
            }
        }
    }
}
?>






    <style>
        body{background:#f0f4ff;} .form-container{max-width:600px;margin:40px auto;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:32px;}
    </style>


    <div class="form-container">
        <h1 class="mb-4">Edit User</h1>
        <?php if($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="post" action="edit-user.php?id=<?= $user_id ?>">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <option value="user" <?= $user['role']==='user'?'selected':'' ?>>User</option>
                    <option value="admin" <?= $user['role']==='admin'?'selected':'' ?>>Admin</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Department</label>
                <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($user['department'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Position</label>
                <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($user['position'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= ($user['status']??'active')==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= ($user['status']??'active')==='inactive'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
            <hr>
            <h5 class="mt-3">Change Password (optional)</h5>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Update User</button>
            <a href="users.php" class="btn btn-secondary ms-2">Back</a>
        </form>
    </div>
<?php require_once '../includes/footer.php'; ?>



