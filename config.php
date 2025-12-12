<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'tozradar_db');
define('DB_USER', 'tozradar_user');
define('DB_PASS', 'YOUR_PASSWORD_HERE');
define('DB_CHARSET', 'utf8mb4');

// Site configuration
define('SITE_URL', 'https://tozradar.com');
define('SITE_NAME', 'TozRadar');

// Security
define('SESSION_LIFETIME', 3600); // 1 hour

// Database connection with error handling
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isSuperAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireSuperAdmin() {
    requireLogin();
    if (!isSuperAdmin()) {
        header('Location: /admin/index.php');
        exit;
    }
}

function sanitizeFilename($string) {
    return preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $string)));
}

function getCustomSettings() {
    global $pdo;
    $stmt = $pdo->query("SELECT setting_key, setting_value, setting_type FROM settings");
    return $stmt->fetchAll();
}
?>
