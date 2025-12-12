<?php
require_once '../config.php';
requireLogin();
$pageTitle = 'Avansert IP Logger – Geo + Fingerprint 2';
include '../header.php';
?>
<main class="main-content"><div class="container"><h2 style="text-align:center;">🧠 Tøz Ultra IP Tracker™</h2>
<p style="text-align:center;">Logger IP, sted, system og nettleser automatisk. Oppdateres live.</p>

<table border="1" style="width:95%; margin:auto; font-family:monospace; background:#111; color:#0f0; border-collapse:collapse;">
  <thead style="background:#222;">
    <tr>
      <th>IP</th>
      <th>Land</th>
      <th>By</th>
      <th>OS</th>
      <th>Browser</th>
      <th>Språk</th>
      <th>Besøk</th>
      <th>Tid</th>
    </tr>
  </thead>
  <tbody id="log-body">
    <tr><td colspan="8" style="text-align:center;">🔄 Laster data...</td></tr>
  </tbody>
</table>

<script>
function loadLogs() {
  fetch("?json=1")
    .then(res => {
      if (!res.ok) throw new Error("Feil ved lasting av data.");
      return res.json();
    })
    .then(data => {
      const tbody = document.getElementById("log-body");
      tbody.innerHTML = "";

      if (!Array.isArray(data)) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; color:orange;">⚠️ Ugyldig svar fra server</td></tr>`;
        return;
      }

      if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;">⚠️ Ingen logger funnet</td></tr>`;
        return;
      }

      data.forEach(row => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${row.ip}</td>
          <td>${row.country}</td>
          <td>${row.city}</td>
          <td>${row.os}</td>
          <td>${row.browser ? row.browser.slice(0, 40) : '–'}</td>
          <td>${row.lang}</td>
          <td>${row.visits}</td>
          <td>${row.timestamp}</td>
        `;
        tbody.appendChild(tr);
      });
    })
    .catch(err => {
      document.getElementById("log-body").innerHTML = `<tr><td colspan="8" style="text-align:center; color:red;">❌ ${err.message}</td></tr>`;
    });
}

loadLogs();
setInterval(loadLogs, 7000);
</script></div></main>
<?php include '../footer.php'; ?>