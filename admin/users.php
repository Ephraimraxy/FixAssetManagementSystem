<?php
require_once '../includes/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../includes/header.php';

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    // Don't allow deletion of the current user
    if ($user_id !== $_SESSION['user_id']) {
        $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
        $stmt->execute([$user_id]);
    }
}

// Get all users
$stmt = $pdo->query('SELECT * FROM users ORDER BY full_name');
$users = $stmt->fetchAll();
?>






    <style>
        body { background: #f4f6fa; }
        .user-container { max-width: 1200px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 32px; }
        .user-card { margin-bottom: 20px; }
        .delete-btn { color: #dc3545; text-decoration: none; }
        .delete-btn:hover { color: #c82333; }
    </style>


    <div class="user-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>User Management</h1>
            <a href="add-user.php" class="btn btn-primary">Add New User</a>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td>
                                <?php 
                                $status = $user['status'] ?? 'active';
                                echo htmlspecialchars($status);
                                ?>
                            </td>
                            <td>
                                <a href="edit-user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-info me-2">Edit</a>
                                <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                                    <form method="post" action="users.php" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">
                                            Delete
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php require_once '../includes/footer.php'; ?>



