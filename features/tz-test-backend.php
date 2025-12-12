<?php
require_once '../config.php';
requireLogin();
$pageTitle = 'tøz test - Settings';
include '../header.php';
?>
<main class="main-content"><div class="container"><h2>Adminpanel: Tøz Ultra IP Logger</h2>

<?php
$stmt = $pdo->query("SELECT * FROM ultra_log ORDER BY timestamp DESC LIMIT 100");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<table class="table table-hover">
  <thead>
    <tr>
      <th>ID</th>
      <th>IP</th>
      <th>Land</th>
      <th>By</th>
      <th>OS</th>
      <th>Browser</th>
      <th>Språk</th>
      <th>Besøk</th>
      <th>Tidspunkt</th>
      <th>Slett</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($logs as $row): ?>
    <tr>
      <td><?= $row['id'] ?></td>
      <td><?= $row['ip'] ?></td>
      <td><?= $row['country'] ?></td>
      <td><?= $row['city'] ?></td>
      <td><?= $row['os'] ?></td>
      <td><?= $row['browser'] ?></td>
      <td><?= $row['lang'] ?></td>
      <td><?= $row['visits'] ?></td>
      <td><?= $row['timestamp'] ?></td>
      <td><a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Slette denne loggposten?')">🗑️</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php
if (isset($_GET['delete'])) {
  $stmt = $pdo->prepare("DELETE FROM ultra_log WHERE id = ?");
  $stmt->execute([$_GET['delete']]);
  echo "<script>location.href='?deleted=true';</script>";
}
?><div class="back-link"><a href="/feature.php?slug=tz-test">← Back to Feature</a></div></div></main>
<?php include '../footer.php'; ?>