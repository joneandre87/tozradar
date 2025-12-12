<?php
require_once '../config.php';
requireLogin();
$pageTitle = 'ble +gps tracker +++';
include '../header.php';
?>
<main class="main-content"><div class="container"><h2>Bluetooth-GPS HeatMap Tracker 🔥</h2>
<p>Skann etter Bluetooth-enheter, logg med GPS og vis dem på kartet. Offline-støtte, AI-analyse og QR-manual tagging inkludert.</p>

<button id="scanStart">Start Skanning</button>
<div id="map" style="height:400px;margin-top:20px;"></div>

<ul id="logList"></ul>

<h3>Manuell QR/Tagging</h3>
<form id="tagForm">
  <input type="text" name="device_name" placeholder="Enhetsnavn" required>
  <input type="text" name="device_id" placeholder="Bluetooth ID" required>
  <input type="text" name="label" placeholder="QR/Felt-tagg" required>
  <button type="submit">Lagre tag</button>
</form>

<style>
  button {
    padding: 10px 20px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    margin-bottom: 10px;
  }
  ul#logList {
    list-style: none;
    padding: 0;
    margin-top: 20px;
  }
  #tagForm input {
    display: block;
    margin: 5px 0;
    padding: 8px;
    width: 100%;
    max-width: 300px;
  }
</style>

<!-- Map & Bluetooth Script -->
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<script>
  const map = L.map('map').setView([0, 0], 2);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  function classifyDevice(name) {
    if (!name) return 'Ukjent';
    name = name.toLowerCase();
    if (name.includes('airpods') || name.includes('earbuds')) return '🎧 Hodetelefoner';
    if (name.includes('tesla') || name.includes('car')) return '🚗 Kjøretøy';
    if (name.includes('watch')) return '⌚ Smartklokke';
    if (name.includes('phone')) return '📱 Mobil';
    return '🔎 Annet';
  }

  document.getElementById('scanStart').addEventListener('click', async () => {
    if (!navigator.bluetooth || !navigator.geolocation) {
      alert("Bluetooth eller GPS støttes ikke.");
      return;
    }

    navigator.geolocation.getCurrentPosition(async (position) => {
      try {
        const device = await navigator.bluetooth.requestDevice({
          acceptAllDevices: true
        });

        const coords = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        };

        const type = classifyDevice(device.name);

        const listItem = document.createElement('li');
        listItem.textContent = `${type} - ${device.name || 'Ukjent'} @ ${coords.lat}, ${coords.lng}`;
        document.getElementById('logList').appendChild(listItem);

        L.circleMarker([coords.lat, coords.lng], {
          radius: 6,
          color: 'red'
        }).addTo(map).bindPopup(`${type}<br>${device.name}`);

        // Offline fallback
        if (!navigator.onLine) {
          const logs = JSON.parse(localStorage.getItem('btlogs') || '[]');
          logs.push({name: device.name, id: device.id, lat: coords.lat, lng: coords.lng, ai_type: type});
          localStorage.setItem('btlogs', JSON.stringify(logs));
          alert("Offline: data lagret lokalt");
        } else {
          fetch('/save_bt_gps.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
              name: device.name,
              id: device.id,
              lat: coords.lat,
              lng: coords.lng,
              ai_type: type
            })
          });
        }

        map.setView([coords.lat, coords.lng], 14);
      } catch (err) {
        alert("Skanning avbrutt eller feilet.");
      }
    });
  });

  // Sync offline data når online
  window.addEventListener('online', () => {
    const logs = JSON.parse(localStorage.getItem('btlogs') || '[]');
    logs.forEach(log => {
      fetch('/save_bt_gps.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(log)
      });
    });
    if (logs.length > 0) {
      alert("Offline-logger synkronisert!");
      localStorage.removeItem('btlogs');
    }
  });

  // QR-taggform
  document.getElementById('tagForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await fetch('/save_bt_tag.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(data)
    });
    const result = await res.text();
    alert(result);
  });
</script>
</div></main>
<?php include '../footer.php'; ?>