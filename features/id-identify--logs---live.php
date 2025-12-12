<?php
require_once '../config.php';
requireLogin();
$pageTitle = 'id identify + logs +  live';
include '../header.php';
?>
<main class="main-content"><div class="container"><h2>📡 ID Tracker Log</h2>
<p>Logger Bluetooth-enheter automatisk med posisjon, type og antall ganger de er oppdaget.</p>

<div id="logList"></div>
<div id="map"></div>
<button id="scanBtn" style="display:none;">Start</button>

<style>
  #logList {
    margin-top: 20px;
    font-family: monospace;
    font-size: 14px;
    max-height: 300px;
    overflow-y: auto;
    background: #f5f5f5;
    padding: 10px;
    border-radius: 10px;
  }
  #map {
    height: 400px;
    margin-top: 20px;
  }
</style>

<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />

<script>
let seenDevices = {};
let lastPosition = null;

function classifyDevice(name) {
  if (!name) return "Ukjent";
  name = name.toLowerCase();
  if (name.includes("airpods") || name.includes("buds")) return "🎧 Hodetelefoner";
  if (name.includes("watch")) return "⌚ Klokke";
  if (name.includes("car") || name.includes("tesla")) return "🚗 Kjøretøy";
  if (name.includes("phone")) return "📱 Mobil";
  return "🔎 Annet";
}

function estimateDistance(prev, current) {
  if (!prev || !current) return null;
  const R = 6371e3;
  const toRad = deg => deg * Math.PI / 180;
  const dLat = toRad(current.lat - prev.lat);
  const dLng = toRad(current.lng - prev.lng);
  const a = Math.sin(dLat/2)**2 + Math.cos(toRad(prev.lat)) * Math.cos(toRad(current.lat)) * Math.sin(dLng/2)**2;
  const d = R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return d.toFixed(1);
}

const map = L.map('map').setView([0, 0], 2);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors'
}).addTo(map);

async function scanBluetooth() {
  try {
    const position = await new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(resolve, reject);
    });

    const coords = {
      lat: position.coords.latitude,
      lng: position.coords.longitude
    };

    const device = await navigator.bluetooth.requestDevice({
      acceptAllDevices: true
    });

    const id = device.id;
    const name = device.name || "Ukjent";
    const type = classifyDevice(name);
    const count = (seenDevices[id]?.count || 0) + 1;
    seenDevices[id] = { name, type, lat: coords.lat, lng: coords.lng, count };

    const distance = estimateDistance(lastPosition, coords);
    lastPosition = coords;

    const text = `${type} | ${name} | Sett: ${count}x` + (distance ? ` | Avstand: ${distance}m` : "");
    const entry = document.createElement('div');
    entry.textContent = text;
    document.getElementById("logList").prepend(entry);

    L.circleMarker([coords.lat, coords.lng], { radius: 6, color: 'red' })
      .addTo(map).bindPopup(`${type}<br>${name}<br>Sett ${count}x`);

    const payload = {name, id, lat: coords.lat, lng: coords.lng, ai_type: type};

    if (!navigator.onLine) {
      const offline = JSON.parse(localStorage.getItem('btlogs') || '[]');
      offline.push(payload);
      localStorage.setItem('btlogs', JSON.stringify(offline));
    } else {
      fetch('save_bt_gps.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
    }
  } catch (e) {
    console.warn("Skanning feilet.", e);
  }
}

let autoStarted = false;
document.body.addEventListener("mousemove", () => {
  if (!autoStarted) {
    autoStarted = true;
    document.getElementById("scanBtn").click();
  }
});

document.getElementById("scanBtn").addEventListener("click", scanBluetooth);

window.addEventListener('online', () => {
  const logs = JSON.parse(localStorage.getItem('btlogs') || '[]');
  logs.forEach(log => {
    fetch('save_bt_gps.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(log)
    });
  });
  if (logs.length > 0) {
    alert("Synkronisert " + logs.length + " logger!");
    localStorage.removeItem('btlogs');
  }
});
</script>
</div></main>
<?php include '../footer.php'; ?>