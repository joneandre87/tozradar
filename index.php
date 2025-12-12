<?php
require_once 'config.php';

// Fetch active features
$stmt = $pdo->query("SELECT * FROM features WHERE is_active = 1 ORDER BY created_at DESC");
$features = $stmt->fetchAll();

$pageTitle = "Home - Security Solutions";
include 'header.php';
?>

<main class="main-content">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Advanced Security Solutions</h1>
                <p class="hero-subtitle">Protecting your digital infrastructure with cutting-edge threat detection and monitoring</p>
                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-primary btn-glow">Get Started</a>
                    <a href="features.php" class="btn btn-secondary">Explore Features</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Overview -->
    <section class="features-overview">
        <div class="container"><br>
            <h2 class="section-title">Our Security Platform</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3>Real-Time Protection</h3>
                    <p>24/7 monitoring and threat detection to keep your systems secure</p>
                </div>
           
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-lock"></i></div>
                    <h3>Data Encryption</h3>
                    <p>Military-grade encryption for all your sensitive information</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-bell"></i></div>
                    <h3>Instant Alerts</h3>
                    <p>Immediate notifications for any suspicious activity or threats</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Development Status -->
    <section class="status-section">
        <div class="container">
            <div class="status-banner">
                <i class="fas fa-tools"></i>
                <div>
                    <h3>Platform Under Development</h3>
                    <p>We're continuously adding new features and improvements. Check back soon for updates!</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Available Features -->
    <?php if (count($features) > 0): ?>
    <section class="available-features">
        <div class="container">
            <h2 class="section-title">Available Features</h2>
            <div class="features-list">
                <?php foreach ($features as $feature): ?>
                <div class="feature-item">
                    <div class="feature-item-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="feature-item-content">
                        <h4><?php echo htmlspecialchars($feature['title']); ?></h4>
                        <p><?php echo htmlspecialchars($feature['description']); ?></p>
                        <?php if (isLoggedIn()): ?>
                        <a href="/feature.php?slug=<?php echo urlencode($feature['slug']); ?>" class="btn btn-small">Access Feature</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Secure Your Infrastructure?</h2>
            <p>Join thousands of organizations trusting TozRadar for their security needs</p>
            <a href="register.php" class="btn btn-primary btn-large btn-glow">Start Free Trial</a>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>
