<?php
require_once '../config.php';
requireLogin();

// Get user data
$stmt = $pdo->prepare("
    SELECT u.*, s.plan_name, s.plan_type, s.status as sub_status 
    FROM users u 
    LEFT JOIN subscriptions s ON u.subscription_id = s.id 
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$userData = $stmt->fetch();

// Get all active features with backend code
$stmt = $pdo->query("
    SELECT id, title, slug, description, backend_code 
    FROM features 
    WHERE is_active = 1 AND backend_code IS NOT NULL AND backend_code != '' 
    ORDER BY title ASC
");
$featuresWithBackend = $stmt->fetchAll();

$pageTitle = "Dashboard";
include '../header.php';
?>

<style>
.features-section {
    margin-top: 3rem;
}

.features-section-title {
    color: #ff0000;
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.feature-link-card {
    background: linear-gradient(135deg, #1a1a1a, #0a0a0a);
    border: 2px solid #333;
    border-radius: 12px;
    padding: 1.5rem;
    text-decoration: none;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.feature-link-card:hover {
    border-color: #ff0000;
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(255, 0, 0, 0.3);
}

.feature-link-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 0, 0, 0.1);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ff0000;
    font-size: 1.5rem;
}

.feature-link-title {
    color: #fff;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
}

.feature-link-desc {
    color: #888;
    font-size: 0.9rem;
    margin: 0;
    line-height: 1.4;
}

.feature-link-arrow {
    color: #ff0000;
    font-size: 1.2rem;
    margin-left: auto;
    transition: transform 0.3s ease;
}

.feature-link-card:hover .feature-link-arrow {
    transform: translateX(5px);
}

.empty-features {
    text-align: center;
    padding: 3rem;
    background: #1a1a1a;
    border-radius: 12px;
    border: 2px dashed #333;
}

.empty-features i {
    font-size: 3rem;
    color: #333;
    margin-bottom: 1rem;
}

.empty-features p {
    color: #888;
    margin-bottom: 1.5rem;
}
</style>

<main class="main-content">
    <div class="container">
        <div class="dashboard">
            <h1 class="page-title">Welcome, <?php echo htmlspecialchars($userData['username']); ?>!</h1>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-user"></i></div>
                    <div class="card-content">
                        <h3>Account Type</h3>
                        <p class="card-value"><?php echo ucfirst($userData['role']); ?></p>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-crown"></i></div>
                    <div class="card-content">
                        <h3>Subscription</h3>
                        <p class="card-value"><?php echo htmlspecialchars($userData['plan_name'] ?? 'None'); ?></p>
                        <span class="badge badge-<?php echo $userData['sub_status'] === 'active' ? 'success' : 'warning'; ?>">
                            <?php echo ucfirst($userData['sub_status'] ?? 'N/A'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Feature Settings Section -->
            <?php if (count($featuresWithBackend) > 0): ?>
            <div class="features-section">
                <h2 class="features-section-title">
                    <i class="fas fa-sliders-h"></i>
                    Feature Settings
                </h2>
                <div class="features-grid">
                    <?php foreach ($featuresWithBackend as $feature): ?>
                    <a href="/features/<?php echo urlencode($feature['slug']); ?>-backend.php" class="feature-link-card">
                        <div class="feature-link-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <h3 class="feature-link-title"><?php echo htmlspecialchars($feature['title']); ?></h3>
                        <?php if (!empty($feature['description'])): ?>
                        <p class="feature-link-desc"><?php echo htmlspecialchars(substr($feature['description'], 0, 80)) . (strlen($feature['description']) > 80 ? '...' : ''); ?></p>
                        <?php endif; ?>
                        <div style="display: flex; align-items: center; margin-top: auto;">
                            <span style="color: #888; font-size: 0.85rem;">Configure settings</span>
                            <i class="fas fa-arrow-right feature-link-arrow"></i>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Admin Navigation -->
            <div class="features-section">
                <h2 class="features-section-title">
                    <i class="fas fa-tools"></i>
                    Admin Tools
                </h2>
                <div class="dashboard-nav">
                    <a href="/admin/settings.php" class="dashboard-link">
                        <i class="fas fa-user-cog"></i>
                        <span>Account Settings</span>
                    </a>

                    <?php if (isSuperAdmin()): ?>
                    <a href="/admin/superadmin/features.php" class="dashboard-link">
                        <i class="fas fa-puzzle-piece"></i>
                        <span>Manage Features</span>
                    </a>
                    <a href="/admin/superadmin/newfeatures.php" class="dashboard-link">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add New Feature</span>
                    </a>
                    <a href="/admin/superadmin/design.php" class="dashboard-link">
                        <i class="fas fa-palette"></i>
                        <span>Custom Design (CSS)</span>
                    </a>
                    <a href="/admin/superadmin/script.php" class="dashboard-link">
                        <i class="fas fa-code"></i>
                        <span>Custom Scripts (JS)</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../footer.php'; ?>
