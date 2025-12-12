<?php
// FEATURE ADD TEST - Upload to admin/superadmin/test_add.php
require_once '../../config.php';
requireSuperAdmin();

echo "<h1>Feature Add Test</h1>";
echo "<style>body{background:#0a0a0a;color:#fff;font-family:Arial;padding:20px;}
h1{color:#ff0000;} .success{color:#00ff88;} .error{color:#ff0033;} 
code{background:#1a1a1a;padding:2px 6px;}</style>";

echo "<h2>Session Check</h2>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";
echo "Username: " . $_SESSION['username'] . "<br>";
echo "Role: " . $_SESSION['role'] . "<br>";

echo "<h2>Database Check</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM features");
    echo "<div class='success'>✓ Can read features table: " . $stmt->fetchColumn() . " features</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Cannot read features: " . $e->getMessage() . "</div>";
}

echo "<h2>POST Test</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div class='success'>✓ POST request received!</div>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    // Try to insert a test feature
    try {
        $stmt = $pdo->prepare("INSERT INTO features (title, slug, description, frontend_code, backend_code, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            'Test Feature',
            'test-feature-' . time(),
            'Test description',
            '<p>Test frontend</p>',
            '<p>Test backend</p>',
            $_SESSION['user_id']
        ]);

        if ($result) {
            echo "<div class='success'>✓ Successfully inserted test feature! ID: " . $pdo->lastInsertId() . "</div>";
        } else {
            echo "<div class='error'>✗ Insert failed but no exception thrown</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>✗ Insert failed: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<p>No POST data yet. Submit the form below:</p>";
}

echo "<h2>Test Form</h2>";
?>
<form method="POST">
    <input type="text" name="test_field" value="test_value" style="padding:10px;margin:10px 0;display:block;width:300px;">
    <button type="submit" style="padding:10px 20px;background:#ff0000;color:#fff;border:none;cursor:pointer;">Submit Test</button>
</form>

<hr>
<p><a href="/admin/superadmin/features.php" style="color:#ff0000;">← Back to Features</a></p>