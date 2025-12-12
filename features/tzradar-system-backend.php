<?php
require_once '../config.php';
requireLogin();
$pageTitle = 'tøzradar system - Settings';
include '../header.php';
?>
<main class="main-content"><div class="container"><h2>Adminpanel: Tøz Ultra IP Logger</h2>
<?php
if (!isset($_GET['json'])) {
  header("HTTP/1.1 403 Forbidden");
  exit("Access denied");
}

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=tozradar", "BRUKERNAVN", "PASSORD");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $ip = $_SERVER['REMOTE_ADDR'];
    $browser = $_SERVER['HTTP_USER_AGENT'] ?? 'ukjent';
    $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'ukjent';
    $os = PHP_OS;

    $geo = @json_decode(file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,city"), true);
    $country = $geo['country'] ?? 'Ukjent';
    $city = $geo['city'] ?? 'Ukjent';

    $stmt = $pdo->prepare("SELECT * FROM ultra_log WHERE ip = ?");
    $stmt->execute([$ip]);

    if ($stmt->rowCount() > 0) {
        $pdo->prepare("UPDATE ultra_log SET visits = visits + 1, timestamp = NOW() WHERE ip = ?")->execute([$ip]);
    } else {
        $pdo->prepare("INSERT INTO ultra_log (ip, country, city, os, browser, lang) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$ip, $country, $city, $os, $browser, $lang]);
    }

    $res = $pdo->query("SELECT ip, country, city, os, browser, lang, visits, timestamp FROM ultra_log ORDER BY timestamp DESC LIMIT 50");
    $data = $res->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
exit;
?>
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
?><!-- Backend PHP flyttet til ultralogger.php -->
<p>Denne funksjonen henter IP-data automatisk.</p><div class="back-link"><a href="/feature.php?slug=tzradar-system">← Back to Feature</a></div></div></main>
<?php include '../footer.php'; ?>