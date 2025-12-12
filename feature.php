<?php
require_once 'config.php';
requireLogin();

$slug = $_GET['slug'] ?? '';

if (!$slug) {
    header('Location: /features.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM features WHERE slug = ? AND is_active = 1");
$stmt->execute([$slug]);
$feature = $stmt->fetch();

if (!$feature) {
    header('Location: /features.php');
    exit;
}

$pageTitle = $feature['title'];
include 'header.php';
?>

<main class="main-content">
    <section class="feature-content-section">
        <div class="container">
            <div class="breadcrumb">
                <a href="/features.php">Features</a> / <?php echo htmlspecialchars($feature['title']); ?>
            </div>
            <?php echo $feature['frontend_code']; ?>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>
