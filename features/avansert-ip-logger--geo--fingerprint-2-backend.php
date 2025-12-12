<?php
require_once '../config.php';
requireLogin();
$pageTitle = 'Avansert IP Logger – Geo + Fingerprint 2 - Settings';
include '../header.php';
?>
<main class="main-content"><div class="container"><?php
if (!isset($_GET['json'])) return;

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = new PDO("mysql:host=localhost;dbname=tozradar", "brukernavn", "passord");
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
?><div class="back-link"><a href="/feature.php?slug=avansert-ip-logger--geo--fingerprint-2">← Back to Feature</a></div></div></main>
<?php include '../footer.php'; ?>