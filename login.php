<?php
require_once 'config.php';

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isLoggedIn()) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';
$debug = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $debug .= "Login attempt for username: " . htmlspecialchars($username) . "<br>";

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user) {
            $debug .= "User found in database<br>";
            $debug .= "Stored hash (first 20): " . substr($user['password'], 0, 20) . "...<br>";

            // Try password verification
            if (password_verify($password, $user['password'])) {
                $debug .= "Password verified successfully!<br>";

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                $debug .= "Session variables set:<br>";
                $debug .= "- user_id: " . $_SESSION['user_id'] . "<br>";
                $debug .= "- username: " . $_SESSION['username'] . "<br>";
                $debug .= "- role: " . $_SESSION['role'] . "<br>";

                // Regenerate session ID for security
                session_regenerate_id(true);

                // Log the successful login
                try {
                    $stmt = $pdo->prepare("INSERT INTO login_log (user_id, ip_address, success) VALUES (?, ?, 1)");
                    $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);
                } catch (Exception $e) {
                    // Login log table might not exist, that's okay
                }

                // Redirect to dashboard
                header('Location: /admin/index.php');
                exit;
            } else {
                $error = 'Invalid username or password';
                $debug .= "Password verification FAILED<br>";
                $debug .= "Trying to verify '" . $password . "' against stored hash<br>";
            }
        } else {
            $error = 'Invalid username or password';
            $debug .= "User NOT found in database<br>";
        }
    }
}

$pageTitle = "Sign In";
include 'header.php';
?>

<main class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Sign In</h1>
            <p class="auth-subtitle">Access your security dashboard</p>

            <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php 
            // Show debug info if there's an error and we're in development
            if ($error && $debug && isset($_GET['debug'])): 
            ?>
            <div class="alert alert-warning">
                <strong>Debug Info:</strong><br>
                <?php echo $debug; ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                           autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required class="form-control" autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-glow">Sign In</button>
            </form>

            <p class="auth-footer">
                Don't have an account? <a href="/register.php">Register here</a><br>
           
            </p>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>