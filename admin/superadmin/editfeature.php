<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

require_once '../../config.php';
requireSuperAdmin();

$message = '';
$messageType = '';

// Get feature ID
$featureId = intval($_GET['id'] ?? 0);

if ($featureId === 0) {
    header("Location: /admin/superadmin/features.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $frontendCode = $_POST['frontend_code'] ?? '';
    $backendCode = $_POST['backend_code'] ?? '';
    $sqlCode = trim($_POST['sql_code'] ?? '');

    if (empty($title)) {
        $message = "Error: Title is required!";
        $messageType = 'error';
    } else {
        try {
            // Get current slug
            $stmt = $pdo->prepare("SELECT slug FROM features WHERE id = ?");
            $stmt->execute([$featureId]);
            $currentFeature = $stmt->fetch();
            
            if (!$currentFeature) {
                $message = "Error: Feature not found!";
                $messageType = 'error';
            } else {
                $slug = $currentFeature['slug'];
                
                // Update database
                $stmt = $pdo->prepare("UPDATE features SET title = ?, description = ?, frontend_code = ?, backend_code = ?, sql_code = ? WHERE id = ?");
                $result = $stmt->execute([$title, $description, $frontendCode, $backendCode, $sqlCode, $featureId]);

                if ($result) {
                    // Execute SQL if provided and changed
                    if (!empty($sqlCode)) {
                        try {
                            $statements = array_filter(array_map('trim', explode(';', $sqlCode)));
                            foreach ($statements as $sql) {
                                if (!empty($sql)) {
                                    $pdo->exec($sql);
                                }
                            }
                        } catch (Exception $e) {
                            $message = "Warning: SQL execution error: " . $e->getMessage();
                            $messageType = 'warning';
                        }
                    }

                    // Update feature files
                    $featureDir = '../../features';

                    // Frontend file
                    $frontendFile = $featureDir . '/' . $slug . '.php';
                    $frontendContent = "<?php\nrequire_once '../config.php';\nrequireLogin();\n\$pageTitle = '" . addslashes($title) . "';\ninclude '../header.php';\n?>\n<main class=\"main-content\"><div class=\"container\">" . $frontendCode . "</div></main>\n<?php include '../footer.php'; ?>";
                    file_put_contents($frontendFile, $frontendContent);
                    chmod($frontendFile, 0644);

                    // Backend file
                    if (!empty($backendCode)) {
                        $backendFile = $featureDir . '/' . $slug . '-backend.php';
                        $backendContent = "<?php\nrequire_once '../config.php';\nrequireLogin();\n\$pageTitle = '" . addslashes($title) . " - Settings';\ninclude '../header.php';\n?>\n<main class=\"main-content\"><div class=\"container\">" . $backendCode . "<div class=\"back-link\"><a href=\"/feature.php?slug=" . $slug . "\">← Back to Feature</a></div></div></main>\n<?php include '../footer.php'; ?>";
                        file_put_contents($backendFile, $backendContent);
                        chmod($backendFile, 0644);
                    } else {
                        // Delete backend file if code is empty
                        $backendFile = $featureDir . '/' . $slug . '-backend.php';
                        if (file_exists($backendFile)) {
                            unlink($backendFile);
                        }
                    }

                    $message = "✓ Feature updated successfully! <a href='/feature.php?slug=$slug' style='color:#00ff88;'>View Feature</a>";
                    if (!empty($backendCode)) {
                        $message .= " | <a href='/features/$slug-backend.php' style='color:#ffaa00;'>View Settings</a>";
                    }
                    $messageType = 'success';
                } else {
                    $message = "Database update failed!";
                    $messageType = 'error';
                }
            }
        } catch (Exception $e) {
            $message = "Exception: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Fetch feature data
$stmt = $pdo->prepare("SELECT * FROM features WHERE id = ?");
$stmt->execute([$featureId]);
$feature = $stmt->fetch();

if (!$feature) {
    header("Location: /admin/superadmin/features.php");
    exit;
}

$pageTitle = "Edit Feature";
include '../../header.php';
ob_end_flush();
?>

<style>
.message {
    padding: 15px;
    margin: 20px 0;
    border-radius: 8px;
    border: 2px solid;
}
.success {
    background: rgba(0, 255, 136, 0.2);
    border-color: #00ff88;
    color: #00ff88;
}
.error {
    background: rgba(255, 0, 51, 0.2);
    border-color: #ff0033;
    color: #ff0033;
}
.warning {
    background: rgba(255, 170, 0, 0.2);
    border-color: #ffaa00;
    color: #ffaa00;
}
.form-group {
    margin-bottom: 1.5rem;
}
.form-group label {
    display: block;
    color: #fff;
    font-weight: 600;
    margin-bottom: 0.5rem;
}
.form-control {
    width: 100%;
    padding: 0.75rem;
    background: #0a0a0a;
    border: 1px solid #333;
    border-radius: 8px;
    color: #fff;
    font-family: inherit;
    font-size: 14px;
}
.code-editor {
    font-family: 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.5;
}
.btn-group {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}
.btn-primary {
    background: linear-gradient(135deg, #ff0000, #cc0000);
    color: #fff;
    padding: 1rem 2rem;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 0, 0, 0.5);
}
.btn-secondary {
    background: #333;
    color: #fff;
    padding: 1rem 2rem;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.feature-info {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 2rem;
}
</style>

<main class="main-content">
    <div class="container">
        <h1 class="page-title"><i class="fas fa-edit"></i> Edit Feature</h1>
        <p class="page-subtitle">Modify frontend and backend code for this feature</p>

        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="feature-info">
            <strong>Feature Slug:</strong> de><?php echo htmlspecialchars($feature['slug']); ?></code><br>
            <strong>Created:</strong> <?php echo date('F j, Y, g:i a', strtotime($feature['created_at'])); ?><br>
            <strong>Status:</strong> 
            <span style="color: <?php echo $feature['is_active'] ? '#00ff88' : '#ffaa00'; ?>">
                <?php echo $feature['is_active'] ? 'Active' : 'Inactive'; ?>
            </span>
        </div>

        <div class="settings-card" style="background: var(--dark-surface); padding: 2rem; border-radius: 12px;">
            <form method="POST">
                <h3 style="color: #ff0000; margin-bottom: 1rem;"><i class="fas fa-info-circle"></i> Basic Information</h3>
                <div class="form-group">
                    <label for="title">Feature Title *</label>
                    <input type="text" id="title" name="title" class="form-control" required 
                           value="<?php echo htmlspecialchars($feature['title']); ?>">
                    <small style="color: #888;">Note: Changing the title will not change the slug or file names.</small>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($feature['description']); ?></textarea>
                </div>

                <hr style="border-color: var(--border-color); margin: 2rem 0;">

                <h3 style="color: #ff0000; margin-bottom: 1rem;"><i class="fas fa-desktop"></i> Frontend Code</h3>
                <div class="form-group">
                    <label for="frontend_code">Frontend HTML/PHP</label>
                    <textarea id="frontend_code" name="frontend_code" class="form-control code-editor" rows="20"><?php echo htmlspecialchars($feature['frontend_code']); ?></textarea>
                    <small style="color: #888;">This is what users see when they access the feature.</small>
                </div>

                <hr style="border-color: var(--border-color); margin: 2rem 0;">

                <h3 style="color: #ff0000; margin-bottom: 1rem;"><i class="fas fa-cogs"></i> Backend Code (Optional)</h3>
                <div class="form-group">
                    <label for="backend_code">Backend HTML/PHP</label>
                    <textarea id="backend_code" name="backend_code" class="form-control code-editor" rows="15"><?php echo htmlspecialchars($feature['backend_code']); ?></textarea>
                    <small style="color: #888;">Settings and configuration panel for this feature.</small>
                </div>

                <hr style="border-color: var(--border-color); margin: 2rem 0;">

                <h3 style="color: #ff0000; margin-bottom: 1rem;"><i class="fas fa-database"></i> SQL Code (Optional)</h3>
                <div class="form-group">
                    <label for="sql_code">SQL Statements</label>
                    <textarea id="sql_code" name="sql_code" class="form-control code-editor" rows="10"><?php echo htmlspecialchars($feature['sql_code']); ?></textarea>
                    <small style="color: #ffaa00;">⚠️ Warning: SQL will be executed when you save. Be careful!</small>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="/admin/superadmin/features.php" class="btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
console.log('Edit feature page loaded');
console.log('Feature ID:', <?php echo $featureId; ?>);
console.log('Feature Slug:', '<?php echo $feature['slug']; ?>');
</script>

<?php include '../../footer.php'; ?>
