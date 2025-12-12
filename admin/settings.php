<?php
require_once '../config.php';
requireLogin();

$success = '';
$error = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userData = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_info'])) {
        $email = trim($_POST['email']);

        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
        if ($stmt->execute([$email, $_SESSION['user_id']])) {
            $success = 'Account information updated successfully';
        }
    }

    if (isset($_POST['update_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        if (password_verify($currentPassword, $userData['password'])) {
            if ($newPassword === $confirmPassword && strlen($newPassword) >= 8) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");

                if ($stmt->execute([$hashedPassword, $_SESSION['user_id']])) {
                    $success = 'Password updated successfully';
                }
            } else {
                $error = 'New passwords do not match or are too short';
            }
        } else {
            $error = 'Current password is incorrect';
        }
    }
}

$pageTitle = "Account Settings";
include '../header.php';
?>

<main class="main-content">
    <div class="container">
        <h1 class="page-title">Account Settings</h1>

        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="settings-container">
            <div class="settings-card">
                <h2>Account Information</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($userData['username']); ?>" class="form-control" disabled>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" class="form-control" required>
                    </div>
                    <button type="submit" name="update_info" class="btn btn-primary">Update Information</button>
                </form>
            </div>

            <div class="settings-card">
                <h2>Change Password</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="update_password" class="btn btn-primary">Update Password</button>
                </form>
            </div>
        </div>

        <div class="back-link">
            <a href="/admin/index.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
</main>

<?php include '../footer.php'; ?>
