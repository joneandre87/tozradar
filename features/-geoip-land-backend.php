<?php
require_once '../config.php';
requireLogin();
$pageTitle = '🌍 GeoIP Land,';
include '../header.php';
// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telegramToken = trim($_POST['telegram_token'] ?? '');
    $telegramChatId = trim($_POST['telegram_chat_id'] ?? '');
    $panicIps = trim($_POST['panic_ips'] ?? '');
    $dataRetention = intval($_POST['data_retention'] ?? 30);
    $enableGPS = isset($_POST['enable_gps']) ? 1 : 0;
    
    $stmt = $pdo->prepare("SELECT id FROM tracking_settings WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        $stmt = $pdo->prepare("UPDATE tracking_settings SET telegram_token = ?, telegram_chat_id = ?, panic_ips = ?, data_retention = ?, enable_gps = ? WHERE user_id = ?");
        $stmt->execute([$telegramToken, $telegramChatId, $panicIps, $dataRetention, $enableGPS, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO tracking_settings (user_id, telegram_token, telegram_chat_id, panic_ips, data_retention, enable_gps) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $telegramToken, $telegramChatId, $panicIps, $dataRetention, $enableGPS]);
    }
    
    $message = '✅ Settings saved successfully!';
}

if (isset($_GET['telegram_token']) && isset($_GET['telegram_chat_id'])) {
    $stmt = $pdo->prepare("INSERT INTO tracking_settings (user_id, telegram_token, telegram_chat_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE telegram_token = ?, telegram_chat_id = ?");
    $stmt->execute([$_SESSION['user_id'], $_GET['telegram_token'], $_GET['telegram_chat_id'], $_GET['telegram_token'], $_GET['telegram_chat_id']]);
    $message = '✅ Telegram configured!';
}

if (isset($_GET['panic_ips'])) {
    $stmt = $pdo->prepare("INSERT INTO tracking_settings (user_id, panic_ips) VALUES (?, ?) ON DUPLICATE KEY UPDATE panic_ips = ?");
    $stmt->execute([$_SESSION['user_id'], $_GET['panic_ips'], $_GET['panic_ips']]);
    $message = '🚨 Panic mode configured!';
}

$stmt = $pdo->prepare("SELECT * FROM tracking_settings WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$settings = $stmt->fetch();

if (!$settings) {
    $settings = [
        'telegram_token' => '',
        'telegram_chat_id' => '',
        'panic_ips' => '',
        'data_retention' => 30,
        'enable_gps' => 0
    ];
}

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tracking_visits WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalVisits = $stmt->fetch()['total'];
?>

<style>
.settings-container {
    max-width: 900px;
    margin: 0 auto;
}

.settings-section {
    background: #1a1a1a;
    border: 2px solid #333;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.settings-section h3 {
    color: #ff0000;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
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
    border: 2px solid #333;
    border-radius: 8px;
    color: #fff;
    font-family: inherit;
}

.form-control:focus {
    border-color: #ff0000;
    outline: none;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.checkbox-group input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.help-text {
    color: #888;
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

.danger-zone {
    background: rgba(255, 0, 51, 0.1);
    border: 2px solid #ff0033;
}

.danger-zone h3 {
    color: #ff0033;
}

.btn-save {
    background: linear-gradient(135deg, #ff0000, #cc0000);
    color: #fff;
    padding: 1rem 2rem;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 0, 0, 0.5);
}

.btn-danger {
    background: #ff0033;
    color: #fff;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.info-box {
    background: rgba(0, 102, 255, 0.1);
    border: 2px solid #0066ff;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.info-box h4 {
    color: #0066ff;
    margin-bottom: 0.5rem;
}

.success-message {
    background: rgba(0, 255, 136, 0.2);
    border: 2px solid #00ff88;
    color: #00ff88;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}
</style>

<div class="settings-container">
    <h1 style="color: #ff0000; margin-bottom: 2rem;">
        <i class="fas fa-cog"></i> TzRadar Settings
    </h1>

    <?php if (isset($message)): ?>
    <div class="success-message">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <form method="POST">
        <div class="settings-section">
            <h3><i class="fab fa-telegram"></i> Telegram Notifications</h3>
            
            <div class="info-box">
                <h4>How to setup:</h4>
                <ol style="margin: 0.5rem 0; padding-left: 1.5rem; color: #ccc;">
                    <li>Open Telegram and search for <strong>@BotFather</strong></li>
                    <li>Send de>/newbot</code> and follow instructions</li>
                    <li>Copy your Bot Token</li>
                    <li>Start a chat with your bot</li>
                    <li>Get your Chat ID from <strong>@userinfobot</strong></li>
                </ol>
            </div>
            
            <div class="form-group">
                <label for="telegram_token">Telegram Bot Token</label>
                <input type="text" id="telegram_token" name="telegram_token" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['telegram_token']); ?>"
                       placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz">
                <small class="help-text">Your Telegram bot token from BotFather</small>
            </div>
            
            <div class="form-group">
                <label for="telegram_chat_id">Telegram Chat ID</label>
                <input type="text" id="telegram_chat_id" name="telegram_chat_id" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['telegram_chat_id']); ?>"
                       placeholder="123456789">
                <small class="help-text">Your Telegram chat ID</small>
            </div>
        </div>

        <div class="settings-section">
            <h3><i class="fas fa-map-marked-alt"></i> GPS & Location Tracking</h3>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="enable_gps" name="enable_gps" value="1" <?php echo $settings['enable_gps'] ? 'checked' : ''; ?>>
                    <label for="enable_gps" style="margin: 0;">Enable GPS tracking for visitors</label>
                </div>
                <small class="help-text">Visitors will be prompted to share their location</small>
            </div>
        </div>

        <div class="settings-section">
            <h3><i class="fas fa-database"></i> Data Management</h3>
            
            <div class="form-group">
                <label for="data_retention">Data Retention (days)</label>
                <input type="number" id="data_retention" name="data_retention" class="form-control" 
                       value="<?php echo $settings['data_retention']; ?>" min="1" max="365">
                <small class="help-text">Automatically delete visits older than this many days</small>
            </div>
            
            <div style="margin-top: 1rem; padding: 1rem; background: rgba(0, 255, 136, 0.1); border-radius: 8px;">
                <strong style="color: #00ff88;">Current Storage:</strong> 
                <?php echo number_format($totalVisits); ?> visits tracked
            </div>
        </div>

        <div class="settings-section danger-zone">
            <h3><i class="fas fa-exclamation-triangle"></i> Panic Mode</h3>
            
            <div class="info-box" style="background: rgba(255, 0, 51, 0.1); border-color: #ff0033;">
                <h4 style="color: #ff0033;">⚠️ What is Panic Mode?</h4>
                <p style="margin: 0.5rem 0; color: #ccc;">When a specified IP address visits your tracked page:</p>
                <ul style="margin: 0.5rem 0; padding-left: 1.5rem; color: #ccc;">
                    <li>All logs for that IP are instantly deleted</li>
                    <li>Visitor is redirected to a safe URL</li>
                    <li>You receive an instant Telegram alert</li>
                    <li>No trace is left in your system</li>
                </ul>
            </div>
            
            <div class="form-group">
                <label for="panic_ips">Panic Mode IP Addresses</label>
                <textarea id="panic_ips" name="panic_ips" class="form-control" rows="4" 
                          placeholder="192.168.1.100, 10.0.0.1, 172.16.0.1"><?php echo htmlspecialchars($settings['panic_ips']); ?></textarea>
                <small class="help-text">Comma-separated list of IP addresses that trigger panic mode</small>
            </div>
        </div>

        <div style="text-align: center;">
            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i> Save All Settings
            </button>
        </div>
    </form>

    <div class="settings-section danger-zone" style="margin-top: 3rem;">
        <h3><i class="fas fa-radiation"></i> Danger Zone</h3>
        
        <p style="color: #888; margin-bottom: 1.5rem;">
            These actions are permanent and cannot be undone.
        </p>
        
        <button class="btn-danger" onclick="if(confirm('Delete ALL tracking data? This cannot be undone!')) alert('Feature coming soon!')">
            <i class="fas fa-trash"></i> Delete All Tracking Data
        </button>
    </div>
</div>

<script>
console.log('⚙️ TzRadar Settings Page Loaded');
</script>
