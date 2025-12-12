<?php
require_once '../../config.php';
requireSuperAdmin();

$message = '';
$messageType = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message .= "POST received! ";

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
            // Generate slug
            $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $title)));

            // Check if exists
            $stmt = $pdo->prepare("SELECT id FROM features WHERE slug = ?");
            $stmt->execute([$slug]);

            if ($stmt->fetch()) {
                $message = "Error: A feature with this title already exists!";
                $messageType = 'error';
            } else {
                // Insert into database
                $stmt = $pdo->prepare("INSERT INTO features (title, slug, description, frontend_code, backend_code, sql_code, created_by, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                $result = $stmt->execute([$title, $slug, $description, $frontendCode, $backendCode, $sqlCode, $_SESSION['user_id']]);

                if ($result) {
                    $featureId = $pdo->lastInsertId();

                    // Execute SQL if provided
                    if (!empty($sqlCode)) {
                        try {
                            $statements = array_filter(array_map('trim', explode(';', $sqlCode)));
                            foreach ($statements as $sql) {
                                if (!empty($sql)) {
                                    $pdo->exec($sql);
                                }
                            }
                            $message .= "SQL executed | ";
                        } catch (Exception $e) {
                            $message .= "SQL Error: " . $e->getMessage() . " | ";
                        }
                    }

                    $message = "SUCCESS! Feature created: <a href='/feature.php?slug=$slug' style='color:#00ff88;'>View Feature</a>";
                    if (!empty($backendCode)) {
                        $message .= " | <a href='/feature_backend.php?slug=$slug' style='color:#ffaa00;'>Settings Panel</a>";
                    }
                    $messageType = 'success';

                    // Clear form
                    $_POST = [];
                } else {
                    $message = "Database insert failed!";
                    $messageType = 'error';
                }
            }
        } catch (Exception $e) {
            $message = "Exception: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$pageTitle = "Add New Feature";
include '../../header.php';
?>

<style>
.debug-box {
    background: rgba(255, 170, 0, 0.2);
    border: 2px solid #ffaa00;
    padding: 15px;
    margin: 20px 0;
    border-radius: 8px;
    font-family: monospace;
}
.success-box {
    background: rgba(0, 255, 136, 0.2);
    border: 2px solid #00ff88;
    padding: 15px;
    margin: 20px 0;
    border-radius: 8px;
}
.error-box {
    background: rgba(255, 0, 51, 0.2);
    border: 2px solid #ff0033;
    padding: 15px;
    margin: 20px 0;
    border-radius: 8px;
}
</style>

<main class="main-content">
    <div class="container">
        <h1 class="page-title">Add New Feature</h1>
        <p class="page-subtitle">Create a new feature with frontend, backend, and database integration</p>

        <?php if ($message): ?>
        <div class="<?php echo $messageType === 'error' ? 'error-box' : ($messageType === 'success' ? 'success-box' : 'debug-box'); ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="settings-card" style="background: var(--dark-surface); padding: 2rem; border-radius: 12px;">
            <form method="POST" id="simpleForm">
                <h3>Step 1: Title & Description</h3>
                <div class="form-group">
                    <label for="title">Feature Title *</label>
                    <input type="text" id="title" name="title" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                           placeholder="e.g., Bluetooth Scanner">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3" 
                              placeholder="What does this feature do?"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <hr style="border-color: var(--border-color); margin: 2rem 0;">

                <h3>Step 2: Frontend Code</h3>
                <div class="form-group">
                    <label for="frontend_code">Frontend HTML/PHP</label>
                    <textarea id="frontend_code" name="frontend_code" class="form-control code-editor" rows="10" 
                              placeholder="<h2>My Feature</h2><p>Content here</p>"><?php echo htmlspecialchars($_POST['frontend_code'] ?? ''); ?></textarea>
                    <p style="color: var(--text-secondary); margin-top: 0.5rem; font-size: 0.9rem;">
                        Ask AI to give you frontend HTML/PHP code with the functionality and user interface for your feature. Request code WITHOUT PHP opening tags, header, or footer - just the main content including HTML, CSS styles, and JavaScript. The code should be responsive.
                    </p>
                </div>

                <hr style="border-color: var(--border-color); margin: 2rem 0;">

                <h3>Step 3: Backend Code (Optional)</h3>
                <div class="form-group">
                    <label for="backend_code">Backend HTML/PHP</label>
                    <textarea id="backend_code" name="backend_code" class="form-control code-editor" rows="8" 
                              placeholder="<h3>Settings</h3>"><?php echo htmlspecialchars($_POST['backend_code'] ?? ''); ?></textarea>
                    <p style="color: var(--text-secondary); margin-top: 0.5rem; font-size: 0.9rem;">
                        Ask AI to give you backend HTML/PHP code for the feature's settings or admin panel. Request code WITHOUT PHP opening tags, header, or footer - just the main content. This should include configuration forms, statistics displays, and data management. Use $pdo for database operations and $_SESSION['user_id'] for the current user. Backend content will be rendered through the shared settings page for each feature.
                    </p>
                </div>

                <hr style="border-color: var(--border-color); margin: 2rem 0;">

                <h3>Step 4: SQL Code (Optional)</h3>
                <div class="form-group">
                    <label for="sql_code">SQL Statements</label>
                    <textarea id="sql_code" name="sql_code" class="form-control code-editor" rows="6" 
                              placeholder="CREATE TABLE..."><?php echo htmlspecialchars($_POST['sql_code'] ?? ''); ?></textarea>
                    <p style="color: var(--text-secondary); margin-top: 0.5rem; font-size: 0.9rem;">
                        Ask AI to give you SQL CREATE TABLE statements for the database tables needed to make this feature work. Use MySQL syntax with the database name 'tozradar_db'. Include user_id columns that reference the users table, proper indexes, and InnoDB engine.
                    </p>
                </div>

                <hr style="border-color: var(--border-color); margin: 2rem 0;">

                <h3>Step 5: Create</h3>
                <button type="submit" class="btn btn-primary btn-large btn-glow">
                    <i class="fas fa-plus"></i> Create Feature Now
                </button>
            </form>
        </div>

        <div class="back-link">
            <a href="/admin/superadmin/features.php">← Back to Features</a>
        </div>
    </div>
</main>

<script>
document.getElementById('simpleForm').addEventListener('submit', function(e) {
    console.log('Form submitting...');
    console.log('Title:', document.getElementById('title').value);
    console.log('Form action:', this.action);
    console.log('Form method:', this.method);
});
console.log('Feature creation form loaded');
</script>

<?php include '../../footer.php'; ?>
