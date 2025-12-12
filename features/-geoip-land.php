<?php
require_once '../config.php';
requireLogin();
$pageTitle = '🌍 GeoIP Land,';
include '../header.php';
// Handle tracking data collection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_data'])) {
    $trackData = json_decode($_POST['track_data'], true);
    
    // Get GeoIP data
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $geoData = @json_decode(file_get_contents("http://ip-api.com/json/{$ipAddress}"), true);
    
    // Store tracking data
    $stmt = $pdo->prepare("INSERT INTO tracking_visits (user_id, ip_address, country, city, lat, lon, flag, fingerprint, device_info, session_data, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $_SESSION['user_id'],
        $ipAddress,
        $geoData['country'] ?? 'Unknown',
        $geoData['city'] ?? 'Unknown',
        $geoData['lat'] ?? 0,
        $geoData['lon'] ?? 0,
        $geoData['countryCode'] ?? 'XX',
        $trackData['fingerprint'] ?? '',
        json_encode($trackData['device'] ?? []),
        json_encode($trackData['session'] ?? []),
    ]);
    
    $visitId = $pdo->lastInsertId();
    
    // Check panic mode
    $stmt = $pdo->prepare("SELECT panic_ips FROM tracking_settings WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $settings = $stmt->fetch();
    
    if ($settings && $settings['panic_ips']) {
        $panicIps = explode(',', $settings['panic_ips']);
        if (in_array($ipAddress, array_map('trim', $panicIps))) {
            // PANIC MODE ACTIVATED
            $stmt = $pdo->prepare("DELETE FROM tracking_visits WHERE user_id = ? AND ip_address = ?");
            $stmt->execute([$_SESSION['user_id'], $ipAddress]);
            
            echo json_encode(['panic' => true, 'redirect' => 'https://google.com']);
            exit;
        }
    }
    
    // Send Telegram notification
    $stmt = $pdo->prepare("SELECT telegram_token, telegram_chat_id FROM tracking_settings WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $telegramSettings = $stmt->fetch();
    
    if ($telegramSettings && $telegramSettings['telegram_token']) {
        $message = "🚨 New Visit Alert!\n\n";
        $message .= "🌍 IP: {$ipAddress}\n";
        $message .= "📍 Location: {$geoData['city']}, {$geoData['country']}\n";
        $message .= "🖥️ Device: {$trackData['device']['type']}\n";
        $message .= "🕐 Time: " . date('Y-m-d H:i:s');
        
        $telegramUrl = "https://api.telegram.org/bot{$telegramSettings['telegram_token']}/sendMessage";
        @file_get_contents($telegramUrl . "?chat_id={$telegramSettings['telegram_chat_id']}&text=" . urlencode($message));
    }
    
    echo json_encode(['success' => true, 'visit_id' => $visitId]);
    exit;
}

// Handle GPS data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gps_data'])) {
    $gpsData = json_decode($_POST['gps_data'], true);
    
    $stmt = $pdo->prepare("INSERT INTO tracking_gps (user_id, visit_id, latitude, longitude, accuracy, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $_SESSION['user_id'],
        $gpsData['visit_id'] ?? 0,
        $gpsData['latitude'] ?? 0,
        $gpsData['longitude'] ?? 0,
        $gpsData['accuracy'] ?? 0
    ]);
    
    echo json_encode(['success' => true]);
    exit;
}

// Get analytics data
$stmt = $pdo->prepare("SELECT DATE(created_at) as date, COUNT(*) as visits FROM tracking_visits WHERE user_id = ? GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 30");
$stmt->execute([$_SESSION['user_id']]);
$dailyVisits = $stmt->fetchAll();

// Get recent visits
$stmt = $pdo->prepare("SELECT * FROM tracking_visits WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$_SESSION['user_id']]);
$recentVisits = $stmt->fetchAll();

// Get top IPs
$stmt = $pdo->prepare("SELECT ip_address, COUNT(*) as count FROM tracking_visits WHERE user_id = ? GROUP BY ip_address ORDER BY count DESC LIMIT 10");
$stmt->execute([$_SESSION['user_id']]);
$topIps = $stmt->fetchAll();

// Get total stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total_visits, COUNT(DISTINCT ip_address) as unique_ips, COUNT(DISTINCT fingerprint) as unique_devices FROM tracking_visits WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();
?>

<style>
.tzradar-container {
    max-width: 1400px;
    margin: 0 auto;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(135deg, #1a1a1a, #0a0a0a);
    border: 2px solid #333;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
}

.stat-icon {
    font-size: 2.5rem;
    color: #ff0000;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #00ff88;
    margin: 0.5rem 0;
}

.stat-label {
    color: #888;
    font-size: 0.9rem;
}

.tracking-map {
    background: #1a1a1a;
    border: 2px solid #333;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    height: 400px;
    position: relative;
}

.visits-table-container {
    background: #1a1a1a;
    border: 2px solid #333;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    overflow-x: auto;
}

.visits-table {
    width: 100%;
    border-collapse: collapse;
}

.visits-table th,
.visits-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #333;
}

.visits-table th {
    color: #ff0000;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
}

.visits-table tbody tr:hover {
    background: rgba(255, 0, 0, 0.05);
}

.flag-icon {
    width: 32px;
    height: 24px;
    display: inline-block;
    margin-right: 0.5rem;
    border-radius: 4px;
}

.device-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    background: rgba(0, 255, 136, 0.2);
    color: #00ff88;
    display: inline-block;
}

.chart-container {
    background: #1a1a1a;
    border: 2px solid #333;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.chart-title {
    color: #ff0000;
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
}

.chart-bars {
    display: flex;
    align-items: flex-end;
    gap: 0.5rem;
    height: 200px;
    padding-top: 30px;
}

.chart-bar {
    flex: 1;
    background: linear-gradient(180deg, #ff0000, #cc0000);
    border-radius: 4px 4px 0 0;
    position: relative;
    cursor: pointer;
    transition: all 0.3s ease;
    min-height: 5px;
}

.chart-bar:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(255, 0, 0, 0.5);
}

.chart-bar-label {
    position: absolute;
    bottom: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.75rem;
    color: #888;
    white-space: nowrap;
}

.chart-bar-value {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.85rem;
    color: #00ff88;
    font-weight: 600;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.btn-action {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-telegram {
    background: #0088cc;
    color: #fff;
}

.btn-pdf {
    background: #ff0000;
    color: #fff;
}

.btn-gps {
    background: #00ff88;
    color: #000;
}

.btn-panic {
    background: #ff0033;
    color: #fff;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.fingerprint-box {
    background: #1a1a1a;
    border: 2px solid #333;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.fingerprint-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #333;
}

.fingerprint-label {
    color: #888;
}

.fingerprint-value {
    color: #00ff88;
    font-weight: 600;
}

.session-timeline {
    background: #1a1a1a;
    border: 2px solid #333;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.timeline-item {
    position: relative;
    padding-left: 2rem;
    padding-bottom: 1.5rem;
    border-left: 2px solid #333;
}

.timeline-item:last-child {
    border-left-color: transparent;
}

.timeline-dot {
    position: absolute;
    left: -6px;
    top: 0;
    width: 10px;
    height: 10px;
    background: #ff0000;
    border-radius: 50%;
}

.timeline-time {
    color: #888;
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
}

.timeline-content {
    color: #fff;
}

#map {
    width: 100%;
    height: 100%;
    border-radius: 8px;
}
</style>

<div class="tzradar-container">
    <h1 style="color: #ff0000; margin-bottom: 2rem;">
        <i class="fas fa-satellite-dish"></i> TzRadar Advanced Tracking System
    </h1>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-eye"></i></div>
            <div class="stat-value"><?php echo number_format($stats['total_visits']); ?></div>
            <div class="stat-label">Total Visits</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-network-wired"></i></div>
            <div class="stat-value"><?php echo number_format($stats['unique_ips']); ?></div>
            <div class="stat-label">Unique IPs</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-fingerprint"></i></div>
            <div class="stat-value"><?php echo number_format($stats['unique_devices']); ?></div>
            <div class="stat-label">Unique Devices</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value"><?php echo count($dailyVisits); ?></div>
            <div class="stat-label">Active Days</div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <button class="btn-action btn-telegram" onclick="setupTelegram()">
            <i class="fab fa-telegram"></i> Setup Telegram Alerts
        </button>
        <button class="btn-action btn-pdf" onclick="exportPDF()">
            <i class="fas fa-file-pdf"></i> Export PDF Report
        </button>
        <button class="btn-action btn-gps" onclick="requestGPS()">
            <i class="fas fa-map-marker-alt"></i> Enable GPS Tracking
        </button>
        <button class="btn-action btn-panic" onclick="configurePanic()">
            <i class="fas fa-exclamation-triangle"></i> Panic Mode Settings
        </button>
    </div>

    <!-- Map -->
    <div class="chart-container">
        <h3 class="chart-title"><i class="fas fa-globe"></i> Visit Locations</h3>
        <div class="tracking-map">
            <div id="map"></div>
        </div>
    </div>

    <!-- Analytics Chart -->
    <div class="chart-container">
        <h3 class="chart-title"><i class="fas fa-chart-bar"></i> Daily Visits (Last 30 Days)</h3>
        <div class="chart-bars">
            <?php 
            if (!empty($dailyVisits)) {
                $maxVisits = max(array_column($dailyVisits, 'visits'));
                foreach (array_reverse($dailyVisits) as $day): 
                    $height = $maxVisits > 0 ? ($day['visits'] / $maxVisits) * 100 : 0;
            ?>
            <div class="chart-bar" style="height: <?php echo $height; ?>%;" title="<?php echo $day['date']; ?>: <?php echo $day['visits']; ?> visits">
                <span class="chart-bar-value"><?php echo $day['visits']; ?></span>
                <span class="chart-bar-label"><?php echo date('M d', strtotime($day['date'])); ?></span>
            </div>
            <?php 
                endforeach;
            } else {
                echo '<p style="color: #888; text-align: center; width: 100%;">No visit data yet</p>';
            }
            ?>
        </div>
    </div>

    <!-- Device Fingerprint -->
    <div class="fingerprint-box">
        <h3 style="color: #ff0000; margin-bottom: 1rem;">
            <i class="fas fa-fingerprint"></i> Your Device Fingerprint
        </h3>
        <div class="fingerprint-item">
            <span class="fingerprint-label">Fingerprint Hash:</span>
            <span class="fingerprint-value" id="fp-hash">Calculating...</span>
        </div>
        <div class="fingerprint-item">
            <span class="fingerprint-label">Browser:</span>
            <span class="fingerprint-value" id="fp-browser">-</span>
        </div>
        <div class="fingerprint-item">
            <span class="fingerprint-label">Operating System:</span>
            <span class="fingerprint-value" id="fp-os">-</span>
        </div>
        <div class="fingerprint-item">
            <span class="fingerprint-label">Screen Resolution:</span>
            <span class="fingerprint-value" id="fp-screen">-</span>
        </div>
        <div class="fingerprint-item">
            <span class="fingerprint-label">Language:</span>
            <span class="fingerprint-value" id="fp-lang">-</span>
        </div>
        <div class="fingerprint-item">
            <span class="fingerprint-label">Timezone:</span>
            <span class="fingerprint-value" id="fp-timezone">-</span>
        </div>
    </div>

    <!-- Recent Visits Table -->
    <div class="visits-table-container">
        <h3 style="color: #ff0000; margin-bottom: 1.5rem;">
            <i class="fas fa-history"></i> Recent Visits
        </h3>
        <table class="visits-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>IP Address</th>
                    <th>Location</th>
                    <th>Device</th>
                    <th>Fingerprint</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (!empty($recentVisits)) {
                    foreach ($recentVisits as $visit): 
                        $deviceInfo = json_decode($visit['device_info'], true);
                ?>
                <tr>
                    <td><?php echo date('M d, H:i', strtotime($visit['created_at'])); ?></td>
                    <td>
                        de><?php echo htmlspecialchars($visit['ip_address']); ?></code>
                    </td>
                    <td>
                        <img src="https://flagcdn.com/16x12/<?php echo strtolower($visit['flag']); ?>.png" class="flag-icon" alt="<?php echo $visit['country']; ?>">
                        <?php echo htmlspecialchars($visit['city'] . ', ' . $visit['country']); ?>
                    </td>
                    <td>
                        <span class="device-badge">
                            <i class="fas fa-<?php echo ($deviceInfo['type'] ?? 'desktop') === 'mobile' ? 'mobile' : 'desktop'; ?>"></i>
                            <?php echo htmlspecialchars($deviceInfo['type'] ?? 'Desktop'); ?>
                        </span>
                    </td>
                    <td>de style="font-size: 0.75rem;"><?php echo substr($visit['fingerprint'], 0, 12); ?>...</code></td>
                    <td>
                        <button class="btn btn-small" onclick="viewDetails(<?php echo $visit['id']; ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                <?php 
                    endforeach;
                } else {
                    echo '<tr><td colspan="6" style="text-align: center; color: #888;">No visits yet</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Session Timeline -->
    <div class="session-timeline">
        <h3 style="color: #ff0000; margin-bottom: 1.5rem;">
            <i class="fas fa-stream"></i> Session Timeline
        </h3>
        <?php 
        if (!empty($recentVisits)) {
            foreach (array_slice($recentVisits, 0, 10) as $visit): 
        ?>
        <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-time"><?php echo date('Y-m-d H:i:s', strtotime($visit['created_at'])); ?></div>
            <div class="timeline-content">
                <strong><?php echo htmlspecialchars($visit['ip_address']); ?></strong> visited from 
                <strong><?php echo htmlspecialchars($visit['city']); ?></strong>
            </div>
        </div>
        <?php 
            endforeach;
        } else {
            echo '<p style="color: #888;">No session activity yet</p>';
        }
        ?>
    </div>

    <!-- Top IPs -->
    <div class="chart-container">
        <h3 class="chart-title"><i class="fas fa-trophy"></i> Top IP Addresses</h3>
        <?php 
        if (!empty($topIps)) {
            foreach ($topIps as $ip): 
        ?>
        <div style="margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: #fff;"><?php echo htmlspecialchars($ip['ip_address']); ?></span>
                <span style="color: #00ff88; font-weight: 600;"><?php echo $ip['count']; ?> visits</span>
            </div>
            <div style="background: #333; height: 8px; border-radius: 4px; overflow: hidden;">
                <div style="background: linear-gradient(90deg, #ff0000, #cc0000); height: 100%; width: <?php echo ($ip['count'] / $topIps[0]['count']) * 100; ?>%; transition: width 0.3s ease;"></div>
            </div>
        </div>
        <?php 
            endforeach;
        } else {
            echo '<p style="color: #888;">No IP data yet</p>';
        }
        ?>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
let map;
let visitId;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Leaflet map
    map = L.map('map').setView([20, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Add markers for visits
    <?php 
    if (!empty($recentVisits)) {
        foreach ($recentVisits as $visit): 
            if ($visit['lat'] != 0 && $visit['lon'] != 0):
    ?>
    L.marker([<?php echo $visit['lat']; ?>, <?php echo $visit['lon']; ?>])
        .addTo(map)
        .bindPopup('<b><?php echo htmlspecialchars($visit['city']); ?></b><br><?php echo htmlspecialchars($visit['ip_address']); ?>');
    <?php 
            endif;
        endforeach;
    }
    ?>
    
    // Generate device fingerprint
    generateFingerprint();
    
    // Track this visit
    trackVisit();
});

async function generateFingerprint() {
    const canvas = document.createElement('canvas');
    const gl = canvas.getContext('webgl');
    
    const fingerprint = {
        userAgent: navigator.userAgent,
        language: navigator.language,
        colorDepth: screen.colorDepth,
        deviceMemory: navigator.deviceMemory || 0,
        hardwareConcurrency: navigator.hardwareConcurrency || 0,
        screenResolution: `${screen.width}x${screen.height}`,
        availableScreenResolution: `${screen.availWidth}x${screen.availHeight}`,
        timezoneOffset: new Date().getTimezoneOffset(),
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        sessionStorage: !!window.sessionStorage,
        localStorage: !!window.localStorage,
        indexedDB: !!window.indexedDB,
        addBehavior: !!document.body.addBehavior,
        openDatabase: !!window.openDatabase,
        cpuClass: navigator.cpuClass || 'unknown',
        platform: navigator.platform,
        doNotTrack: navigator.doNotTrack,
        plugins: Array.from(navigator.plugins).map(p => p.name).join(','),
        canvas: canvas.toDataURL(),
        webgl: gl ? gl.getParameter(gl.VERSION) : 'not supported',
        webglVendor: gl ? gl.getParameter(gl.VENDOR) : 'unknown',
        touchSupport: 'ontouchstart' in window,
        fonts: ['Arial', 'Verdana', 'Times New Roman', 'Courier New'].join(',')
    };
    
    const fpString = JSON.stringify(fingerprint);
    const fpHash = await hashString(fpString);
    
    document.getElementById('fp-hash').textContent = fpHash;
    document.getElementById('fp-browser').textContent = getBrowser();
    document.getElementById('fp-os').textContent = getOS();
    document.getElementById('fp-screen').textContent = fingerprint.screenResolution;
    document.getElementById('fp-lang').textContent = fingerprint.language;
    document.getElementById('fp-timezone').textContent = fingerprint.timezone;
    
    return { fingerprint: fpHash, details: fingerprint };
}

async function hashString(str) {
    const encoder = new TextEncoder();
    const data = encoder.encode(str);
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
}

function getBrowser() {
    const ua = navigator.userAgent;
    if (ua.includes('Firefox')) return 'Firefox';
    if (ua.includes('Chrome')) return 'Chrome';
    if (ua.includes('Safari')) return 'Safari';
    if (ua.includes('Edge')) return 'Edge';
    return 'Unknown';
}

function getOS() {
    const ua = navigator.userAgent;
    if (ua.includes('Windows')) return 'Windows';
    if (ua.includes('Mac')) return 'macOS';
    if (ua.includes('Linux')) return 'Linux';
    if (ua.includes('Android')) return 'Android';
    if (ua.includes('iOS')) return 'iOS';
    return 'Unknown';
}

async function trackVisit() {
    const fpData = await generateFingerprint();
    
    const trackData = {
        fingerprint: fpData.fingerprint,
        device: {
            type: /Mobile|Android|iPhone/i.test(navigator.userAgent) ? 'mobile' : 'desktop',
            browser: getBrowser(),
            os: getOS(),
            screen: `${screen.width}x${screen.height}`,
            language: navigator.language
        },
        session: {
            referrer: document.referrer,
            url: window.location.href,
            timestamp: new Date().toISOString()
        }
    };
    
    const formData = new FormData();
    formData.append('track_data', JSON.stringify(trackData));
    
    const response = await fetch(window.location.href, {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    
    if (result.panic) {
        window.location.href = result.redirect;
    } else {
        visitId = result.visit_id;
    }
}

function requestGPS() {
    if (!navigator.geolocation) {
        alert('GPS not supported by your browser');
        return;
    }
    
    navigator.geolocation.getCurrentPosition(async (position) => {
        const gpsData = {
            visit_id: visitId,
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy
        };
        
        const formData = new FormData();
        formData.append('gps_data', JSON.stringify(gpsData));
        
        await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        L.marker([gpsData.latitude, gpsData.longitude])
            .addTo(map)
            .bindPopup('<b>Your Location</b><br>GPS Tracked')
            .openPopup();
        
        map.setView([gpsData.latitude, gpsData.longitude], 13);
        
        alert('✅ GPS location tracked successfully!');
    }, (error) => {
        alert('❌ GPS access denied: ' + error.message);
    });
}

function setupTelegram() {
    const token = prompt('Enter your Telegram Bot Token:');
    const chatId = prompt('Enter your Telegram Chat ID:');
    
    if (token && chatId) {
        window.location.href = `/features/tzradar-system-backend.php?telegram_token=${encodeURIComponent(token)}&telegram_chat_id=${encodeURIComponent(chatId)}`;
    }
}

function configurePanic() {
    const ips = prompt('Enter IP addresses to trigger panic mode (comma-separated):');
    
    if (ips) {
        window.location.href = `/features/tzradar-system-backend.php?panic_ips=${encodeURIComponent(ips)}`;
    }
}

function exportPDF() {
    alert('📄 PDF Export feature coming soon!\n\nYou can implement this using libraries like TCPDF or FPDF.');
}

function viewDetails(visitId) {
    alert('👁️ Viewing details for visit #' + visitId + '\n\nDetailed modal coming soon!');
}

console.log('🛰️ TzRadar Advanced Tracking System Loaded');
</script>
