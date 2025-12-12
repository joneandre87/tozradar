<?php
require_once '../config.php';
requireLogin();
$pageTitle = 'tøz test';
include '../header.php';
?>
<main class="main-content"><div class="container"><h2>Tøz Ultra IP-Logg</h2>
<div id="log-container">
  <table id="ip-log" class="table table-striped">
    <thead>
      <tr>
        <th>Land</th>
        <th>By</th>
        <th>IP</th>
        <th>OS</th>
        <th>Browser</th>
        <th>Språk</th>
        <th>Besøk</th>
        <th>Sist sett</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<script>
fetch('?json=true')
  .then(res => res.json())
  .then(data => {
    const tbody = document.querySelector("#ip-log tbody");
    data.forEach(row => {
      const tr = document.createElement("tr");
      const flag = `<img src="https://flagcdn.com/24x18/${(row.country || 'xx').toLowerCase().slice(0,2)}.png" alt="${row.country}" />`;
      tr.innerHTML = `
        <td>${flag} ${row.country}</td>
        <td>${row.city}</td>
        <td>${row.ip}</td>
        <td>${row.os}</td>
        <td>${row.browser}</td>
        <td>${row.lang}</td>
        <td>${row.visits}</td>
        <td>${row.timestamp}</td>
      `;
      tbody.appendChild(tr);
    });
  });
</script>

<style>
#log-container { overflow-x: auto; }
#ip-log { width: 100%; border-collapse: collapse; }
#ip-log th, #ip-log td { padding: 8px 12px; text-align: left; }
#ip-log img { vertical-align: middle; margin-right: 4px; }
</style></div></main>
<?php include '../footer.php'; ?>