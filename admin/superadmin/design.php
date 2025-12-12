<?php
require_once '../../config.php';
requireSuperAdmin();

$success = '';
$error = '';

// Get current custom CSS
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'custom_css'");
$stmt->execute();
$currentCSS = $stmt->fetchColumn() ?: '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customCSS = $_POST['custom_css'] ?? '';

    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ?, updated_by = ? WHERE setting_key = 'custom_css'");
    if ($stmt->execute([$customCSS, $_SESSION['user_id']])) {
        $success = 'Custom CSS updated successfully';
        $currentCSS = $customCSS;
    } else {
        $error = 'Failed to update CSS';
    }
}

$pageTitle = "Custom Design (CSS)";
include '../../header.php';
?>

<main class="main-content">
    <div class="container">
        <h1 class="page-title">Custom Design (CSS)</h1>
        <p class="page-subtitle">Add custom CSS styles that will be applied globally across the site</p>

        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="code-editor-container">
            <form method="POST">
                <div class="form-group">
                    <label for="custom_css">Global CSS Code</label>
                    <textarea id="custom_css" name="custom_css" class="form-control code-editor" rows="20" placeholder="/* Your custom CSS */&#10;.custom-class {&#10;    color: #ff0000;&#10;}"><?php echo htmlspecialchars($currentCSS); ?></textarea>
                    <small>This CSS will be loaded on every page after the main stylesheet</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-glow">
                        <i class="fas fa-save"></i> Save CSS
                    </button>
                    <a href="/admin/index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <div class="help-section">
            <h3>CSS Tips</h3>
            <ul>
                <li>Override existing styles by using more specific selectors</li>
                <li>Use <code>!important</code> sparingly for critical overrides</li>
                <li>Test your changes on different pages to ensure consistency</li>
                <li>Consider mobile responsiveness with media queries</li>
            </ul>
        </div>

        <div class="back-link">
            <a href="/admin/index.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
</main>

<?php include '../../footer.php'; ?>
