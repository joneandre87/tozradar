<?php
require_once 'config.php';

$stmt = $pdo->query("SELECT * FROM features WHERE is_active = 1 ORDER BY created_at DESC");
$features = $stmt->fetchAll();

$pageTitle = "Features";
include 'header.php';
?>

<main class="main-content">
    <section class="features-page">
        <div class="container">
            <h1 class="page-title">Security Features</h1>
            <p class="page-subtitle">Explore our comprehensive security solutions</p>

            <?php if (count($features) > 0): ?>
            <div class="features-grid-large">
                <?php foreach ($features as $feature): ?>
                <div class="feature-card-large">
                    <div class="feature-card-header">
                        <i class="fas fa-shield-alt"></i>
                        <h3><?php echo htmlspecialchars($feature['title']); ?></h3>
                    </div>
                    <p class="feature-description"><?php echo htmlspecialchars($feature['description']); ?></p>
                    <?php if (isLoggedIn()): ?>
                    <a href="/feature.php?slug=<?php echo urlencode($feature['slug']); ?>" class="btn btn-primary">Access Feature</a>
                    <?php else: ?>
                    <a href="/register.php" class="btn btn-secondary">Sign Up to Access</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No features available yet. Check back soon!</p>
            </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>
