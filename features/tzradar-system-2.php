<?php
require_once '../config.php';
requireLogin();
$pageTitle = 'tøzradar system 3';
include '../header.php';
?>
<main class="main-content"><div class="container"><h2>🌍 Tøz UltraTracker v3.0</h2>
<p>Logger IP og info når du åpner denne funksjonen.</p>

<table border="1" style="width:100%; font-family:monospace;" id="logTable">
  <thead>
    <tr><th>IP</th><th>Land</th><th>By</th><th>OS</th><th>Nettleser</th><th>Språk</th><th>Besøk</th><th>Tidspunkt</th></tr>
  </thead>
  <tbody id="logBody">
    <tr><td colspan="8" style="text-align:center;">⏳ Laster data...</td></tr>
  </tbody>
</table>

<script>
function loadLogs() {
  fetch("?json=1")
    .then(r => r.json())
    .then(data => {
      const tbody = document.getElementById("logBody");
      tbody.innerHTML = "";
      data.forEach(row => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${row.ip}</td>
          <td>${row.country}</td>
          <td>${row.city}</td>
          <td>${row.os}</td>
          <td>${row.browser.slice(0, 30)}</td>
          <td>${row.lang}</td>
          <td>${row.visits}</td>
          <td>${row.timestamp}</td>
        `;
        tbody.appendChild(tr);
      });
    })
    .catch(e => {
      document.getElementById("logBody").innerHTML = `<tr><td colspan="8" style="color:red;">Feil ved lasting</td></tr>`;
    });
}

loadLogs();
setInterval(loadLogs, 7000);
</script></div></main>
<?php include '../footer.php'; ?>