<?php
require_once '../config.php';
requireLogin();
$pageTitle = 'Toz Bluetooth Scanner - Settings';
include '../header.php';
?>
<main class="main-content"><div class="container"><div class="bluetooth-backend">
    <h2><i class="fas fa-cog"></i> Bluetooth Scanner Settings</h2>

    <?php
    // Get current user's scan history
    global $pdo;
    $userId = $_SESSION['user_id'];

    // Handle settings update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
        $autoSave = isset($_POST['auto_save']) ? 1 : 0;
        $scanInterval = intval($_POST['scan_interval']);
        $alertThreshold = intval($_POST['alert_threshold']);

        $stmt = $pdo->prepare("INSERT INTO bluetooth_settings (user_id, auto_save, scan_interval, alert_threshold) 
                               VALUES (?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE auto_save = ?, scan_interval = ?, alert_threshold = ?");
        $stmt->execute([$userId, $autoSave, $scanInterval, $alertThreshold, $autoSave, $scanInterval, $alertThreshold]);

        echo '<div class="alert alert-success">Settings saved successfully!</div>';
    }

    // Get current settings
    $stmt = $pdo->prepare("SELECT * FROM bluetooth_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $settings = $stmt->fetch();

    if (!$settings) {
        $settings = ['auto_save' => 1, 'scan_interval' => 5, 'alert_threshold' => 10];
    }

    // Get scan statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_scans, 
                                  COUNT(DISTINCT device_name) as unique_devices,
                                  MAX(created_at) as last_scan
                           FROM bluetooth_devices 
                           WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
    ?>

    <div class="settings-stats">
        <div class="stat-box">
            <i class="fas fa-chart-bar"></i>
            <div>
                <h4><?php echo $stats['total_scans']; ?></h4>
                <p>Total Scans</p>
            </div>
        </div>
        <div class="stat-box">
            <i class="fas fa-broadcast-tower"></i>
            <div>
                <h4><?php echo $stats['unique_devices']; ?></h4>
                <p>Unique Devices</p>
            </div>
        </div>
        <div class="stat-box">
            <i class="fas fa-clock"></i>
            <div>
                <h4><?php echo $stats['last_scan'] ? date('M d, H:i', strtotime($stats['last_scan'])) : 'Never'; ?></h4>
                <p>Last Scan</p>
            </div>
        </div>
    </div>

    <div class="settings-form-container">
        <h3>Scanner Configuration</h3>
        <form method="POST" class="settings-form">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="auto_save" <?php echo $settings['auto_save'] ? 'checked' : ''; ?>>
                    Auto-save discovered devices to database
                </label>
            </div>

            <div class="form-group">
                <label for="scan_interval">Scan Interval (seconds)</label>
                <input type="number" id="scan_interval" name="scan_interval" class="form-control" 
                       value="<?php echo $settings['scan_interval']; ?>" min="1" max="60">
                <small>How often to refresh device list during active scan</small>
            </div>

            <div class="form-group">
                <label for="alert_threshold">Alert Threshold (devices)</label>
                <input type="number" id="alert_threshold" name="alert_threshold" class="form-control" 
                       value="<?php echo $settings['alert_threshold']; ?>" min="1" max="100">
                <small>Show alert when this many devices are detected</small>
            </div>

            <button type="submit" name="update_settings" class="btn btn-primary btn-glow">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </form>
    </div>

    <div class="scan-history-container">
        <h3>Recent Scan History</h3>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM bluetooth_devices WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$userId]);
        $devices = $stmt->fetchAll();

        if (count($devices) > 0):
        ?>
        <div class="history-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Device Name</th>
                        <th>MAC Address</th>
                        <th>Type</th>
                        <th>Signal (dBm)</th>
                        <th>Manufacturer</th>
                        <th>Detected At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($devices as $device): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($device['device_name']); ?></td>
                        <td><code><?php echo htmlspecialchars($device['mac_address']); ?></code></td>
                        <td><?php echo htmlspecialchars($device['device_type']); ?></td>
                        <td><?php echo $device['signal_strength']; ?></td>
                        <td><?php echo htmlspecialchars($device['manufacturer']); ?></td>
                        <td><?php echo date('M d, H:i:s', strtotime($device['created_at'])); ?></td>
                        <td>
                            <button class="btn btn-small btn-secondary" onclick="viewDevice(<?php echo $device['id']; ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No scan history yet. Start scanning to see devices here.</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="export-section">
        <h3>Export Data</h3>
        <p>Download your Bluetooth scan history in various formats:</p>
        <div class="export-buttons">
            <a href="/api/bluetooth/export.php?format=csv&user_id=<?php echo $userId; ?>" class="btn btn-secondary">
                <i class="fas fa-file-csv"></i> Export as CSV
            </a>
            <a href="/api/bluetooth/export.php?format=json&user_id=<?php echo $userId; ?>" class="btn btn-secondary">
                <i class="fas fa-file-code"></i> Export as JSON
            </a>
            <button onclick="clearHistory()" class="btn btn-danger">
                <i class="fas fa-trash"></i> Clear All History
            </button>
        </div>
    </div>
</div>

<style>
.bluetooth-backend {
    max-width: 1000px;
}

.bluetooth-backend h2 {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.bluetooth-backend h2 i {
    color: var(--primary-red);
}

.settings-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-box {
    background: var(--dark-surface);
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-box i {
    font-size: 2.5rem;
    color: var(--primary-red);
}

.stat-box h4 {
    font-size: 2rem;
    margin: 0;
}

.stat-box p {
    margin: 0;
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.settings-form-container,
.scan-history-container,
.export-section {
    background: var(--dark-surface);
    padding: 2rem;
    border-radius: 12px;
    border: 1px solid var(--border-color);
    margin-bottom: 2rem;
}

.settings-form-container h3,
.scan-history-container h3,
.export-section h3 {
    color: var(--primary-red);
    margin-bottom: 1.5rem;
}

.history-table-container {
    overflow-x: auto;
    margin-top: 1rem;
}

.export-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: 1rem;
}
</style>

<script>
function viewDevice(deviceId) {
    alert('Device details view - ID: ' + deviceId);
    // Implement detailed device view modal
}

function clearHistory() {
    if (confirm('Are you sure you want to clear all scan history? This cannot be undone.')) {
        fetch('/api/bluetooth/clear-history.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to clear history');
            }
        });
    }
}
</script><div class="back-link"><a href="/feature.php?slug=toz-bluetooth-scanner">← Back</a></div></div></main>
<?php include '../footer.php'; ?>