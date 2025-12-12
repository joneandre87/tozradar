<?php
// PASSWORD RESET UTILITY
// Upload this file and access it ONCE to reset admin password
// DELETE THIS FILE IMMEDIATELY AFTER USE!

require_once 'config.php';

$updated = false;
$message = '';

// Generate a fresh password hash for 'tozradar69'
$newPassword = 'tozradar69';
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);

// Update the admin user
try {
    // First check if admin exists
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();

    if ($admin) {
        // Update existing admin
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
        $stmt->execute([$newHash]);
        $updated = true;
        $message = "✓ Admin password has been reset to: <strong>tozradar69</strong>";
    } else {
        // Create admin user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@tozradar.com', $newHash, 'superadmin']);
        $updated = true;
        $message = "✓ Admin user created with password: <strong>tozradar69</strong>";
    }

    // Also create a subscription if needed
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    $adminId = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$adminId]);
    $subExists = $stmt->fetchColumn();

    if (!$subExists) {
        $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, plan_name, plan_type, price, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$adminId, 'Enterprise Plan', 'enterprise', 99.99, 'active']);
        $subId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("UPDATE users SET subscription_id = ? WHERE id = ?");
        $stmt->execute([$subId, $adminId]);
    }

} catch (Exception $e) {
    $message = "✗ Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: #0a0a0a;
            color: #fff;
            max-width: 600px;
            margin: 0 auto;
        }
        h1 { color: #ff0000; }
        .success {
            background: rgba(0, 255, 136, 0.2);
            border: 2px solid #00ff88;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .error {
            background: rgba(255, 0, 51, 0.2);
            border: 2px solid #ff0033;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .warning {
            background: rgba(255, 170, 0, 0.2);
            border: 2px solid #ffaa00;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        a {
            color: #ff0000;
            text-decoration: none;
            font-weight: bold;
        }
        a:hover { color: #ff0033; }
        code {
            background: #1a1a1a;
            padding: 2px 6px;
            border-radius: 4px;
            color: #00ff88;
        }
    </style>
</head>
<body>
    <h1>Password Reset Utility</h1>

    <?php if ($updated): ?>
    <div class="success">
        <?php echo $message; ?>
    </div>

    <div class="warning">
        <h3>⚠️ IMPORTANT SECURITY NOTICE</h3>
        <ol>
            <li>Login now at: <a href="/login.php">login.php</a></li>
            <li><strong>DELETE THIS FILE IMMEDIATELY!</strong></li>
            <li>Change your password after logging in</li>
        </ol>

        <p><strong>Login Credentials:</strong></p>
        <ul>
            <li>Username: <code>admin</code></li>
            <li>Password: <code>tozradar69</code></li>
        </ul>
    </div>

    <p><a href="/login.php">→ Go to Login Page</a></p>

    <?php else: ?>
    <div class="error">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>
</body>
</html>