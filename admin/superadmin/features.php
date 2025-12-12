<?php
// Enable error display to see what's wrong
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to prevent header issues
ob_start();

require_once '../../config.php';
requireSuperAdmin();

$message = '';
$messageType = '';
$debugLog = [];

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debugLog[] = "POST Request Received";
    $debugLog[] = "POST Data: " . print_r($_POST, true);

    // TOGGLE
    if (isset($_POST['toggle_feature'])) {
        $featureId = intval($_POST['feature_id']);
        $debugLog[] = "Attempting to TOGGLE feature ID: $featureId";

        try {
            $stmt = $pdo->prepare("UPDATE features SET is_active = NOT is_active WHERE id = ?");
            $result = $stmt->execute([$featureId]);

            if ($result) {
                $affected = $stmt->rowCount();
                $message = "✓ Toggle SUCCESS! Rows affected: $affected";
                $messageType = 'success';
                $debugLog[] = "Toggle successful, rows affected: $affected";
            } else {
                $message = "✗ Toggle failed";
                $messageType = 'error';
                $debugLog[] = "Toggle failed - execute returned false";
            }
        } catch (Exception $e) {
            $message = "✗ Exception: " . $e->getMessage();
            $messageType = 'error';
            $debugLog[] = "Exception caught: " . $e->getMessage();
        }
    }

    // DELETE
    if (isset($_POST['delete_feature'])) {
        $featureId = intval($_POST['feature_id']);
        $debugLog[] = "Attempting to DELETE feature ID: $featureId";

        try {
            // Delete from DB
            $stmt = $pdo->prepare("DELETE FROM features WHERE id = ?");
            $result = $stmt->execute([$featureId]);

            if ($result) {
                $affected = $stmt->rowCount();
                $message = "✓ Delete SUCCESS! Rows affected: $affected";
                $messageType = 'success';
                $debugLog[] = "Delete successful, rows affected: $affected";
            } else {
                $message = "✗ Delete failed";
                $messageType = 'error';
                $debugLog[] = "Delete failed - execute returned false";
            }
        } catch (Exception $e) {
            $message = "✗ Exception: " . $e->getMessage();
            $messageType = 'error';
            $debugLog[] = "Exception caught: " . $e->getMessage();
        }
    }
}

// Fetch all features
try {
    $stmt = $pdo->query("SELECT * FROM features ORDER BY created_at DESC");
    $features = $stmt->fetchAll();
} catch (Exception $e) {
    $features = [];
    $debugLog[] = "Error fetching features: " . $e->getMessage();
}
$pageTitle = "Manage Features";
include '../../header.php';

// Flush output buffer
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toggle/Delete Test</title>
    <style>
        body {
            background: #0a0a0a;
            color: #fff;
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 { color: #ff0000; }
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
        .debug {
            background: rgba(255, 170, 0, 0.2);
            border-color: #ffaa00;
            color: #ffaa00;
            font-family: monospace;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            border: 1px solid #333;
            text-align: left;
        }
        th {
            background: #1a1a1a;
        }
        button, .btn-edit {
            padding: 8px 15px;
            margin: 2px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: #fff;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-edit { background: #0066ff; }
        .btn-toggle { background: #ffaa00; }
        .btn-delete { background: #ff0033; }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .badge-active { background: #00ff88; color: #000; }
        .badge-inactive { background: #ffaa00; color: #000; }
    </style>
</head>
<body>
    <br>
    

    <h1>Manage features</h1>
    <p>This page can toggle status (active/inactive) and delete features on the site.

    <?php if ($message): ?>
    <div class="message <?php echo $messageType; ?>">
        <strong><?php echo $message; ?></strong>
    </div>
    <?php endif; ?>

    <?php if (!empty($debugLog)): ?>
    <div class="message debug">
        <strong>Debug Log:</strong><br>
        <?php foreach ($debugLog as $log): ?>
            • <?php echo htmlspecialchars($log); ?><br>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <h2>Current Features (<?php echo count($features); ?>)</h2><a href="/admin/superadmin/newfeatures.php" class="btn-add">
                <i class="fas fa-plus"></i> Add New Feature
            </a>

    <?php if (count($features) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($features as $feature): ?>
            <tr>
                <td><?php echo $feature['id']; ?></td>
                <td><?php echo htmlspecialchars($feature['title']); ?></td>
                <td><code><?php echo htmlspecialchars($feature['slug']); ?></code></td>
                <td>
                    <span class="badge badge-<?php echo $feature['is_active'] ? 'active' : 'inactive'; ?>">
                        <?php echo $feature['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </td>
                <td><?php echo date('M d, H:i', strtotime($feature['created_at'])); ?></td>
                <td>
                    <a href="/admin/superadmin/editfeature.php?id=<?php echo $feature['id']; ?>" class="btn-edit">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="feature_id" value="<?php echo $feature['id']; ?>">
                        <button type="submit" name="toggle_feature" class="btn-toggle" 
                                onclick="console.log('Toggle clicked for ID: <?php echo $feature['id']; ?>');">
                            Toggle
                        </button>
                    </form>

                    <form method="POST" style="display:inline;" 
                          onsubmit="console.log('Delete clicked for ID: <?php echo $feature['id']; ?>'); return confirm('Delete this feature?');">
                        <input type="hidden" name="feature_id" value="<?php echo $feature['id']; ?>">
                        <button type="submit" name="delete_feature" class="btn-delete">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No features found.</p>
    <?php endif; ?>

    <hr style="border-color: #333; margin: 30px 0;">



    <script>
        console.log('Test page loaded');
        console.log('Total features:', <?php echo count($features); ?>);
        console.log('POST method:', '<?php echo $_SERVER['REQUEST_METHOD']; ?>');
    </script>
