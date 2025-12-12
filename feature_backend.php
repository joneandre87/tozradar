<?php
require_once 'config.php';
requireLogin();

$slug = $_GET['slug'] ?? '';

if (!$slug) {
    header('Location: /admin/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM features WHERE slug = ? AND is_active = 1 AND backend_code IS NOT NULL AND backend_code != ''");
$stmt->execute([$slug]);
$feature = $stmt->fetch();

if (!$feature) {
    header('Location: /features.php');
    exit;
}

$pageTitle = $feature['title'] . ' Settings';
include 'header.php';
?>

<main class="main-content">
    <section class="feature-content-section">
        <div class="container">
            <div class="breadcrumb">
                <a href="/features.php">Features</a> /
                <a href="/feature.php?slug=<?php echo urlencode($feature['slug']); ?>"><?php echo htmlspecialchars($feature['title']); ?></a>
                / Settings
            </div>
            <?php echo $feature['backend_code']; ?>
            <div class="back-link" style="margin-top: 2rem;">
                <a href="/feature.php?slug=<?php echo urlencode($feature['slug']); ?>">← Back to Feature</a>
            </div>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>
