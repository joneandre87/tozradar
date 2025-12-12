<?php
require_once '../config.php';
requireLogin();
$pageTitle = 'Avansert IP Logger – Geo + Fingerprint - Settings';
include '../header.php';
?>
<main class="main-content"><div class="container"><?php
$pdo = new PDO("mysql:host=localhost;dbname=tozradar", "brukernavn", "passord");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// IP og headers
$ip = $_SERVER['REMOTE_ADDR'];
$browser = $_SERVER['HTTP_USER_AGENT'];
$lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
$os = PHP_OS;

// GeoIP API
$geo = json_decode(file_get_contents("http://ip-api.com/json/{$ip}"), true);
$country = $geo['country'] ?? 'Ukjent';
$city = $geo['city'] ?? 'Ukjent';

// Lagre
$stmt = $pdo->prepare("INSERT INTO geo_log (ip, browser, os, lang, country, city) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$ip, $browser, $os, $lang, $country, $city]);

// Returner
$data = $pdo->query("SELECT ip, browser, os, lang, country, city, timestamp FROM geo_log ORDER BY timestamp DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($data);
?><div class="back-link"><a href="/feature.php?slug=avansert-ip-logger--geo--fingerprint">← Back to Feature</a></div></div></main>
<?php include '../footer.php'; ?>