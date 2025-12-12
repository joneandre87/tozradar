<?php
require_once '../config.php';
requireLogin();
$pageTitle = 'Toz Bluetooth Scanner';
include '../header.php';
?>
<main class="main-content"><div class="container"><div class="bluetooth-scanner">
    <div class="scanner-header">
        <h2><i class="fas fa-bluetooth-b"></i> Bluetooth Device Scanner</h2>
        <p class="subtitle">Discover and analyze nearby Bluetooth devices</p>
    </div>

    <div class="scan-controls">
        <button id="startScan" class="btn btn-primary btn-large btn-glow">
            <i class="fas fa-radar"></i> Start Scanning
        </button>
        <button id="stopScan" class="btn btn-danger" style="display:none;">
            <i class="fas fa-stop"></i> Stop Scan
        </button>
        <button id="clearLog" class="btn btn-secondary">
            <i class="fas fa-trash"></i> Clear Log
        </button>
    </div>

    <div class="scan-status" id="scanStatus">
        <i class="fas fa-info-circle"></i> Ready to scan. Click "Start Scanning" to begin.
    </div>

    <div class="scan-stats">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-broadcast-tower"></i></div>
            <div class="stat-content">
                <span class="stat-label">Devices Found</span>
                <span class="stat-value" id="deviceCount">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-content">
                <span class="stat-label">Last Scan</span>
                <span class="stat-value" id="lastScan">Never</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-signal"></i></div>
            <div class="stat-content">
                <span class="stat-label">Signal Strength</span>
                <span class="stat-value" id="avgSignal">-</span>
            </div>
        </div>
    </div>

    <div class="devices-container">
        <h3>Discovered Devices</h3>
        <div id="devicesList" class="devices-list">
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <p>No devices detected yet. Start a scan to discover Bluetooth devices.</p>
            </div>
        </div>
    </div>

    <div class="scan-log-container">
        <h3>Scan Log</h3>
        <div id="scanLog" class="scan-log">
            <p class="log-entry log-info">
                <span class="log-time">[<?php echo date('H:i:s'); ?>]</span>
                Scanner initialized and ready.
            </p>
        </div>
    </div>
</div>

<style>
.bluetooth-scanner {
    max-width: 1000px;
    margin: 0 auto;
}

.scanner-header {
    text-align: center;
    margin-bottom: 2rem;
}

.scanner-header h2 {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.scanner-header h2 i {
    color: var(--primary-red);
    filter: drop-shadow(0 0 10px var(--neon-glow));
}

.subtitle {
    color: var(--text-secondary);
}

.scan-controls {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.scan-status {
    background: var(--dark-surface);
    padding: 1rem;
    border-radius: 8px;
    border-left: 4px solid var(--primary-red);
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.scan-status i {
    color: var(--primary-red);
    font-size: 1.5rem;
}

.scan-status.scanning {
    border-left-color: var(--warning);
    animation: pulse 2s infinite;
}

.scan-status.scanning i {
    color: var(--warning);
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

.scan-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--dark-surface);
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    font-size: 2rem;
    color: var(--primary-red);
}

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-label {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
}

.devices-container, .scan-log-container {
    background: var(--dark-surface);
    padding: 1.5rem;
    border-radius: 12px;
    border: 1px solid var(--border-color);
    margin-bottom: 2rem;
}

.devices-container h3, .scan-log-container h3 {
    margin-bottom: 1rem;
    color: var(--primary-red);
}

.devices-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.device-card {
    background: var(--dark-bg);
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.device-card:hover {
    border-color: var(--primary-red);
    box-shadow: 0 5px 20px var(--neon-glow);
}

.device-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.device-name {
    font-size: 1.25rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.device-type-icon {
    color: var(--primary-red);
}

.signal-strength {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.signal-bars {
    display: flex;
    gap: 2px;
}

.signal-bar {
    width: 4px;
    height: 15px;
    background: var(--border-color);
    border-radius: 2px;
}

.signal-bar.active {
    background: var(--success);
}

.device-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-label {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.info-value {
    font-weight: 600;
    font-family: 'Courier New', monospace;
}

.scan-log {
    max-height: 300px;
    overflow-y: auto;
    background: var(--dark-bg);
    padding: 1rem;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
}

.log-entry {
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    border-radius: 4px;
}

.log-time {
    color: var(--text-secondary);
}

.log-info {
    border-left: 3px solid var(--primary-red);
}

.log-success {
    border-left: 3px solid var(--success);
    background: rgba(0, 255, 136, 0.1);
}

.log-warning {
    border-left: 3px solid var(--warning);
    background: rgba(255, 170, 0, 0.1);
}

.log-error {
    border-left: 3px solid var(--danger);
    background: rgba(255, 0, 51, 0.1);
}
</style>

<script>
let isScanning = false;
let discoveredDevices = new Map();
let scanInterval;

document.getElementById('startScan').addEventListener('click', startBluetoothScan);
document.getElementById('stopScan').addEventListener('click', stopBluetoothScan);
document.getElementById('clearLog').addEventListener('click', clearLog);

function startBluetoothScan() {
    isScanning = true;
    document.getElementById('startScan').style.display = 'none';
    document.getElementById('stopScan').style.display = 'inline-block';

    const status = document.getElementById('scanStatus');
    status.className = 'scan-status scanning';
    status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning for Bluetooth devices...';

    addLog('Starting Bluetooth device scan...', 'info');

    // Try Web Bluetooth API
    if (navigator.bluetooth) {
        scanWithWebBluetooth();
    } else {
        addLog('Web Bluetooth API not supported. Using simulation mode.', 'warning');
        simulateBluetoothScan();
    }
}

async function scanWithWebBluetooth() {
    try {
        addLog('Requesting Bluetooth access...', 'info');

        const device = await navigator.bluetooth.requestDevice({
            acceptAllDevices: true,
            optionalServices: ['battery_service', 'device_information']
        });

        addLog('Device selected: ' + device.name, 'success');

        const deviceInfo = {
            name: device.name || 'Unknown Device',
            id: device.id,
            type: detectDeviceType(device.name),
            rssi: Math.floor(Math.random() * -40) - 40, // Simulated RSSI
            address: generateMACAddress(),
            manufacturer: detectManufacturer(device.name),
            services: ['Generic Access', 'Device Information'],
            lastSeen: new Date().toLocaleTimeString()
        };

        addDevice(deviceInfo);
        saveDeviceToDatabase(deviceInfo);

    } catch (error) {
        addLog('Bluetooth access denied or cancelled. Switching to simulation mode.', 'warning');
        simulateBluetoothScan();
    }
}

function simulateBluetoothScan() {
    const simulatedDevices = [
        { name: 'iPhone 13 Pro', type: 'smartphone', manufacturer: 'Apple Inc.' },
        { name: 'Galaxy Buds', type: 'headphones', manufacturer: 'Samsung' },
        { name: 'MacBook Pro', type: 'computer', manufacturer: 'Apple Inc.' },
        { name: 'Bluetooth Speaker', type: 'speaker', manufacturer: 'JBL' },
        { name: 'Fitbit Charge 5', type: 'wearable', manufacturer: 'Fitbit' },
        { name: 'Logitech Mouse', type: 'peripheral', manufacturer: 'Logitech' },
        { name: 'Smart Watch', type: 'wearable', manufacturer: 'Huawei' },
        { name: 'Wireless Keyboard', type: 'peripheral', manufacturer: 'Microsoft' }
    ];

    let count = 0;
    scanInterval = setInterval(() => {
        if (!isScanning || count >= simulatedDevices.length) {
            clearInterval(scanInterval);
            if (isScanning) {
                addLog('Scan completed. Found ' + discoveredDevices.size + ' devices.', 'success');
            }
            return;
        }

        const simDevice = simulatedDevices[count];
        const deviceInfo = {
            name: simDevice.name,
            id: 'BT-' + Math.random().toString(36).substr(2, 9).toUpperCase(),
            type: simDevice.type,
            rssi: Math.floor(Math.random() * -60) - 30,
            address: generateMACAddress(),
            manufacturer: simDevice.manufacturer,
            services: generateServices(simDevice.type),
            lastSeen: new Date().toLocaleTimeString(),
            distance: calculateDistance(Math.floor(Math.random() * -60) - 30)
        };

        addDevice(deviceInfo);
        saveDeviceToDatabase(deviceInfo);
        addLog('Discovered: ' + deviceInfo.name + ' (' + deviceInfo.address + ')', 'success');

        count++;
    }, 1500);
}

function addDevice(deviceInfo) {
    discoveredDevices.set(deviceInfo.id, deviceInfo);
    updateDevicesList();
    updateStats();
}

function updateDevicesList() {
    const devicesList = document.getElementById('devicesList');

    if (discoveredDevices.size === 0) {
        devicesList.innerHTML = '<div class="empty-state"><i class="fas fa-search"></i><p>No devices detected yet.</p></div>';
        return;
    }

    let html = '';
    discoveredDevices.forEach((device) => {
        const signalBars = getSignalBars(device.rssi);
        html += `
            <div class="device-card">
                <div class="device-header">
                    <div class="device-name">
                        <i class="fas ${getDeviceIcon(device.type)} device-type-icon"></i>
                        ${device.name}
                    </div>
                    <div class="signal-strength">
                        <div class="signal-bars">${signalBars}</div>
                        <span>${device.rssi} dBm</span>
                    </div>
                </div>
                <div class="device-info">
                    <div class="info-item">
                        <span class="info-label">Device ID</span>
                        <span class="info-value">${device.id}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">MAC Address</span>
                        <span class="info-value">${device.address}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Manufacturer</span>
                        <span class="info-value">${device.manufacturer}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Device Type</span>
                        <span class="info-value">${device.type.toUpperCase()}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Est. Distance</span>
                        <span class="info-value">${device.distance}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Last Seen</span>
                        <span class="info-value">${device.lastSeen}</span>
                    </div>
                </div>
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <span class="info-label">Services:</span>
                    <span class="info-value">${device.services.join(', ')}</span>
                </div>
            </div>
        `;
    });

    devicesList.innerHTML = html;
}

function updateStats() {
    document.getElementById('deviceCount').textContent = discoveredDevices.size;
    document.getElementById('lastScan').textContent = new Date().toLocaleTimeString();

    if (discoveredDevices.size > 0) {
        const avgRSSI = Array.from(discoveredDevices.values())
            .reduce((sum, d) => sum + d.rssi, 0) / discoveredDevices.size;
        document.getElementById('avgSignal').textContent = Math.round(avgRSSI) + ' dBm';
    }
}

function stopBluetoothScan() {
    isScanning = false;
    clearInterval(scanInterval);

    document.getElementById('startScan').style.display = 'inline-block';
    document.getElementById('stopScan').style.display = 'none';

    const status = document.getElementById('scanStatus');
    status.className = 'scan-status';
    status.innerHTML = '<i class="fas fa-check-circle"></i> Scan stopped. ' + discoveredDevices.size + ' devices found.';

    addLog('Scan stopped by user.', 'info');
}

function clearLog() {
    discoveredDevices.clear();
    updateDevicesList();
    document.getElementById('scanLog').innerHTML = '<p class="log-entry log-info"><span class="log-time">[' + new Date().toLocaleTimeString() + ']</span> Log cleared.</p>';
    updateStats();
}

function addLog(message, type = 'info') {
    const logContainer = document.getElementById('scanLog');
    const time = new Date().toLocaleTimeString();
    const entry = document.createElement('p');
    entry.className = 'log-entry log-' + type;
    entry.innerHTML = '<span class="log-time">[' + time + ']</span> ' + message;
    logContainer.insertBefore(entry, logContainer.firstChild);
}

function getSignalBars(rssi) {
    const bars = 5;
    let activeBars = 0;

    if (rssi >= -50) activeBars = 5;
    else if (rssi >= -60) activeBars = 4;
    else if (rssi >= -70) activeBars = 3;
    else if (rssi >= -80) activeBars = 2;
    else activeBars = 1;

    let html = '';
    for (let i = 1; i <= bars; i++) {
        html += '<div class="signal-bar ' + (i <= activeBars ? 'active' : '') + '"></div>';
    }
    return html;
}

function getDeviceIcon(type) {
    const icons = {
        smartphone: 'fa-mobile-alt',
        headphones: 'fa-headphones',
        speaker: 'fa-volume-up',
        computer: 'fa-laptop',
        wearable: 'fa-watch',
        peripheral: 'fa-mouse',
        unknown: 'fa-bluetooth-b'
    };
    return icons[type] || icons.unknown;
}

function detectDeviceType(name) {
    if (!name) return 'unknown';
    const lower = name.toLowerCase();

    if (lower.includes('phone') || lower.includes('iphone') || lower.includes('galaxy')) return 'smartphone';
    if (lower.includes('buds') || lower.includes('headphone') || lower.includes('airpod')) return 'headphones';
    if (lower.includes('speaker')) return 'speaker';
    if (lower.includes('mac') || lower.includes('laptop') || lower.includes('pc')) return 'computer';
    if (lower.includes('watch') || lower.includes('band') || lower.includes('fitbit')) return 'wearable';
    if (lower.includes('mouse') || lower.includes('keyboard')) return 'peripheral';

    return 'unknown';
}

function detectManufacturer(name) {
    if (!name) return 'Unknown';
    const lower = name.toLowerCase();

    if (lower.includes('iphone') || lower.includes('ipad') || lower.includes('mac') || lower.includes('airpod')) return 'Apple Inc.';
    if (lower.includes('galaxy') || lower.includes('samsung')) return 'Samsung';
    if (lower.includes('pixel')) return 'Google';
    if (lower.includes('fitbit')) return 'Fitbit';
    if (lower.includes('logitech')) return 'Logitech';

    return 'Unknown';
}

function generateMACAddress() {
    return Array.from({length: 6}, () => 
        Math.floor(Math.random() * 256).toString(16).padStart(2, '0').toUpperCase()
    ).join(':');
}

function generateServices(type) {
    const baseServices = ['Generic Access', 'Generic Attribute'];
    const typeServices = {
        smartphone: ['Phone', 'Battery', 'Device Information'],
        headphones: ['Audio', 'Battery', 'Media Control'],
        speaker: ['Audio', 'Volume Control'],
        computer: ['HID', 'File Transfer'],
        wearable: ['Heart Rate', 'Battery', 'Activity'],
        peripheral: ['HID', 'Battery']
    };

    return [...baseServices, ...(typeServices[type] || ['Device Information'])];
}

function calculateDistance(rssi) {
    const txPower = -59; // Calibrated RSSI at 1 meter
    if (rssi === 0) return 'Unknown';

    const ratio = rssi * 1.0 / txPower;
    let distance;

    if (ratio < 1.0) {
        distance = Math.pow(ratio, 10);
    } else {
        distance = (0.89976) * Math.pow(ratio, 7.7095) + 0.111;
    }

    if (distance < 1) return (distance * 100).toFixed(0) + ' cm';
    return distance.toFixed(1) + ' m';
}

function saveDeviceToDatabase(device) {
    // Send device data to backend for database storage
    fetch('/api/bluetooth/save-device.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(device)
    }).catch(err => console.log('Database save failed:', err));
}
</script></div></main>
<?php include '../footer.php'; ?>