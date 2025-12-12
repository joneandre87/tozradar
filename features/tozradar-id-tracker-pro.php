<?php
require_once '../config.php';
requireLogin();
$pageTitle = 'TozRadar ID Tracker Pro';
include '../header.php';
?>
<main class="main-content"><div class="container"><h2 style="text-align:center;">🧠 Tøz IP Logger Pro™</h2>
<p style="text-align:center;">Logger IP, nettleser og antall besøk.</p>

<table border="1" style="width:95%; margin:auto; font-family:monospace; background:#111; color:#0f0; border-collapse:collapse;">
  <thead style="background:#222;">
    <tr><th>IP</th><th>Browser</th><th>Besøk</th><th>Tidspunkt</th></tr>
  </thead>
  <tbody id="log-body"></tbody>
</table>

<script>
function loadLogs() {
  fetch("backend/iplogger.php")
    .then(res => res.json())
    .then(data => {
      const tbody = document.getElementById("log-body");
      tbody.innerHTML = "";
      data.forEach(row => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${row.ip}</td>
          <td>${row.browser.substring(0, 30)}...</td>
          <td>${row.visits}</td>
          <td>${row.last_seen}</td>
        `;
        tbody.appendChild(tr);
      });
    });
}
loadLogs();
setInterval(loadLogs, 5000); // auto-refresh
</script></div></main>
<?php include '../footer.php'; ?>