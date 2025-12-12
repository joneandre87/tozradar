<?php
require_once '../config.php';
requireLogin();
$pageTitle = 'TozRadar ID Tracker Pro - Settings';
include '../header.php';
?>
<main class="main-content"><div class="container"><?php
$pdo = new PDO("mysql:host=localhost;dbname=tozradar", "brukernavn", "passord");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Logg den besøkende
$ip = $_SERVER['REMOTE_ADDR'];
$browser = $_SERVER['HTTP_USER_AGENT'];

$stmt = $pdo->prepare("SELECT * FROM visitor_log WHERE ip = ?");
$stmt->execute([$ip]);

if ($stmt->rowCount() > 0) {
  $pdo->prepare("UPDATE visitor_log SET visits = visits + 1, last_seen = NOW() WHERE ip = ?")->execute([$ip]);
} else {
  $pdo->prepare("INSERT INTO visitor_log (ip, browser) VALUES (?, ?)")->execute([$ip, $browser]);
}

// Hent logg for visning
header('Content-Type: application/json');
$data = $pdo->query("SELECT ip, browser, visits, last_seen FROM visitor_log ORDER BY last_seen DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data);
?><div class="back-link"><a href="/feature.php?slug=tozradar-id-tracker-pro">← Back to Feature</a></div></div></main>
<?php include '../footer.php'; ?>