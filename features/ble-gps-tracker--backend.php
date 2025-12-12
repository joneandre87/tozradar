<?php
require_once '../config.php';
requireLogin();
$pageTitle = 'ble +gps tracker +++ - Settings';
include '../header.php';
?>
<main class="main-content"><div class="container"><h3>📡 Bluetooth + GPS Logs (Adminpanel)</h3>

<form method="GET">
  <input type="text" name="search" placeholder="Søk etter navn eller tag" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
  <button type="submit">Søk</button>
</form>

<table>
  <thead>
    <tr>
      <th>Tid</th>
      <th>Enhetsnavn</th>
      <th>AI-Type</th>
      <th>Tag</th>
      <th>Posisjon</th>
      <th>Kart</th>
    </tr>
  </thead>
  <tbody>
    <?php
    session_start();
    require 'config.php';

    $user_id = $_SESSION['user_id'];
    $search = $_GET['search'] ?? '';

    $sql = "SELECT l.device_name, l.device_id, l.lat, l.lng, l.ai_type, l.created_at, t.label
            FROM bluetooth_gps_logs l
            LEFT JOIN bluetooth_tags t ON l.device_id = t.device_id AND t.user_id = l.user_id
            WHERE l.user_id = :uid";

    if (!empty($search)) {
      $sql .= " AND (l.device_name LIKE :search OR t.label LIKE :search)";
    }

    $sql .= " ORDER BY l.created_at DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $params = ['uid' => $user_id];
    if (!empty($search)) {
      $params['search'] = "%$search%";
    }
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      echo "<tr>";
      echo "<td>{$row['created_at']}</td>";
      echo "<td>{$row['device_name']}</td>";
      echo "<td>{$row['ai_type']}</td>";
      echo "<td>{$row['label']}</td>";
      echo "<td>{$row['lat']}, {$row['lng']}</td>";
      echo "<td><a href='https://maps.google.com/?q={$row['lat']},{$row['lng']}' target='_blank'>📍</a></td>";
      echo "</tr>";
    }
    ?>
  </tbody>
</table>

<form method="POST" action="export_bt_data.php" style="margin-top:20px;">
  <button type="submit">📤 Eksporter CSV</button>
</form>

<style>
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
  }
  th, td {
    border: 1px solid #ddd;
    padding: 6px;
    font-size: 14px;
  }
  th {
    background: #f2f2f2;
  }
</style>
<div class="back-link"><a href="/feature.php?slug=ble-gps-tracker-">← Back to Feature</a></div></div></main>
<?php include '../footer.php'; ?>