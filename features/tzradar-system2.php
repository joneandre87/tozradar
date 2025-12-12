<?php
require_once '../config.php';
requireLogin();
$pageTitle = 'tøzradar system2';
include '../header.php';
?>
<main class="main-content"><div class="container"><script>
document.addEventListener("DOMContentLoaded", function() {
  fetch("https://tozradar.com/backend/ultralogger.php?json=1")
    .then(response => response.json())
    .then(data => {
      console.log(data);
      const table = document.createElement("table");
      table.innerHTML = `
        <tr><th>IP</th><th>Land</th><th>By</th><th>OS</th><th>Browser</th><th>Språk</th><th>Besøk</th><th>Dato</th></tr>
      `;
      data.forEach(row => {
        table.innerHTML += `
          <tr>
            <td>${row.ip}</td>
            <td>${row.country}</td>
            <td>${row.city}</td>
            <td>${row.os}</td>
            <td>${row.browser}</td>
            <td>${row.lang}</td>
            <td>${row.visits}</td>
            <td>${row.timestamp}</td>
          </tr>
        `;
      });
      document.body.appendChild(table);
    })
    .catch(err => {
      console.error("FEIL VED LASTING:", err);
      document.body.innerHTML = "<p>Kunne ikke hente data fra loggeren.</p>";
    });
});
</script></div></main>
<?php include '../footer.php'; ?>