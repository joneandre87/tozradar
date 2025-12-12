<?php
require_once '../config.php';
requireLogin();
$pageTitle = 'Avansert IP Logger – Geo + Fingerprint';
include '../header.php';
?>
<main class="main-content"><div class="container"><h2 style="text-align:center;">🌍 TøzGeo Logger Ultra™</h2>
<p style="text-align:center;">Logger IP, sted, system, språk og tid.</p>

<table border="1" style="width:95%; margin:auto; font-family:monospace; background:#111; color:#0f0;">
  <thead><tr>
    <th>IP</th><th>Land</th><th>By</th><th>OS</th><th>Browser</th><th>Språk</th><th>Tid</th>
  </tr></thead>
  <tbody id="log-body"></tbody>
</table>

<script>
async function loadLogs() {
  const res = await fetch("backend/geo.php");
  const data = await res.json();
  const tbody = document.getElementById("log-body");
  tbody.innerHTML = "";
  data.forEach(row => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${row.ip}</td>
      <td>${row.country}</td>
      <td>${row.city}</td>
      <td>${row.os}</td>
      <td>${row.browser}</td>
      <td>${row.lang}</td>
      <td>${row.timestamp}</td>
    `;
    tbody.appendChild(tr);
  });
}
loadLogs();
setInterval(loadLogs, 8000);
</script></div></main>
<?php include '../footer.php'; ?>