<?php
require_once '../config.php';
requireLogin();
$pageTitle = 'id identify + logs +  live - Settings';
include '../header.php';
?>
<main class="main-content"><div class="container"><h3>📡 Bluetooth-logg</h3>
<table>
  <thead>
    <tr>
      <th>Dato</th>
      <th>Navn</th>
      <th>AI-type</th>
      <th>Antall ganger</th>
      <th>GPS</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $stmt = $pdo->prepare("SELECT device_name, ai_type, lat, lng, times_seen, created_at FROM bluetooth_gps_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
    $stmt->execute([$_SESSION['user_id']]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      echo "<tr>";
      echo "<td>{$row['created_at']}</td>";
      echo "<td>{$row['device_name']}</td>";
      echo "<td>{$row['ai_type']}</td>";
      echo "<td>{$row['times_seen']}</td>";
      echo "<td><a href='https://maps.google.com/?q={$row['lat']},{$row['lng']}' target='_blank'>📍</a></td>";
      echo "</tr>";
    }
    ?>
  </tbody>
</table>

<style>
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
  }
  th, td {
    border: 1px solid #ccc;
    padding: 8px;
  }
  th {
    background: #eee;
  }
</style>
<div class="back-link"><a href="/feature.php?slug=id-identify--logs---live">← Back to Feature</a></div></div></main>
<?php include '../footer.php'; ?>