<?php
require_once '../../config.php';
requireSuperAdmin();

$success = '';
$error = '';

// Get current custom JS
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'custom_js'");
$stmt->execute();
$currentJS = $stmt->fetchColumn() ?: '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customJS = $_POST['custom_js'] ?? '';

    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ?, updated_by = ? WHERE setting_key = 'custom_js'");
    if ($stmt->execute([$customJS, $_SESSION['user_id']])) {
        $success = 'Custom JavaScript updated successfully';
        $currentJS = $customJS;
    } else {
        $error = 'Failed to update JavaScript';
    }
}

$pageTitle = "Custom Scripts (JS)";
include '../../header.php';
?>

<main class="main-content">
    <div class="container">
        <h1 class="page-title">Custom Scripts (JavaScript)</h1>
        <p class="page-subtitle">Add custom JavaScript that will be executed globally across the site</p>

        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="code-editor-container">
            <form method="POST">
                <div class="form-group">
                    <label for="custom_js">Global JavaScript Code</label>
                    <textarea id="custom_js" name="custom_js" class="form-control code-editor" rows="20" placeholder="// Your custom JavaScript&#10;console.log('Custom script loaded');&#10;&#10;document.addEventListener('DOMContentLoaded', function() {&#10;    // Your code here&#10;});"><?php echo htmlspecialchars($currentJS); ?></textarea>
                    <small>This JavaScript will be executed on every page after the main scripts</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-glow">
                        <i class="fas fa-save"></i> Save JavaScript
                    </button>
                    <a href="/admin/index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <div class="help-section">
            <h3>JavaScript Tips</h3>
            <ul>
                <li>Wrap your code in <code>document.addEventListener('DOMContentLoaded', ...)</code> to ensure DOM is ready</li>
                <li>Avoid conflicts by using unique variable names or wrapping in IIFE</li>
                <li>Test thoroughly as JavaScript errors can break site functionality</li>
                <li>Use console.log() for debugging</li>
            </ul>
        </div>

        <div class="back-link">
            <a href="/admin/index.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
</main>

<?php include '../../footer.php'; ?>
