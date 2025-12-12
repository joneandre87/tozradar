<?php
require_once 'config.php';
requireLogin();

// Check if user already has subscription
$stmt = $pdo->prepare("SELECT subscription_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user['subscription_id']) {
    header('Location: /admin/index.php');
    exit;
}

$plans = [
    ['name' => 'Free', 'type' => 'free', 'price' => 0, 'features' => ['Basic Security Scan', 'Email Alerts', 'Community Support']],
    ['name' => 'Basic', 'type' => 'basic', 'price' => 29.99, 'features' => ['All Free Features', 'Advanced Scanning', 'Priority Support', '10 Assets']],
    ['name' => 'Pro', 'type' => 'pro', 'price' => 79.99, 'features' => ['All Basic Features', 'Real-Time Monitoring', '24/7 Support', 'Unlimited Assets', 'Custom Reports']],
    ['name' => 'Enterprise', 'type' => 'enterprise', 'price' => 199.99, 'features' => ['All Pro Features', 'Dedicated Account Manager', 'Custom Integration', 'SLA Guarantee', 'Advanced Analytics']]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $planType = $_POST['plan_type'] ?? '';
    $planData = array_filter($plans, fn($p) => $p['type'] === $planType);

    if ($planData) {
        $plan = array_values($planData)[0];

        $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, plan_name, plan_type, price, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$_SESSION['user_id'], $plan['name'], $plan['type'], $plan['price']]);
        $subscriptionId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("UPDATE users SET subscription_id = ? WHERE id = ?");
        $stmt->execute([$subscriptionId, $_SESSION['user_id']]);

        header('Location: /admin/index.php');
        exit;
    }
}

$pageTitle = "Choose Your Plan";
include 'header.php';
?>

<main class="main-content">
    <section class="subscription-section">
        <div class="container">
            <h1 class="page-title">Choose Your Security Plan</h1>
            <p class="page-subtitle">Select the plan that best fits your security needs</p>

            <div class="plans-grid">
                <?php foreach ($plans as $plan): ?>
                <div class="plan-card <?php echo $plan['type'] === 'pro' ? 'plan-featured' : ''; ?>">
                    <?php if ($plan['type'] === 'pro'): ?>
                    <div class="plan-badge">Most Popular</div>
                    <?php endif; ?>
                    <h3 class="plan-name"><?php echo $plan['name']; ?></h3>
                    <div class="plan-price">
                        <span class="price-currency">$</span>
                        <span class="price-amount"><?php echo number_format($plan['price'], 2); ?></span>
                        <span class="price-period">/month</span>
                    </div>
                    <ul class="plan-features">
                        <?php foreach ($plan['features'] as $feature): ?>
                        <li><i class="fas fa-check"></i> <?php echo $feature; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <form method="POST">
                        <input type="hidden" name="plan_type" value="<?php echo $plan['type']; ?>">
                        <button type="submit" class="btn btn-primary btn-block <?php echo $plan['type'] === 'pro' ? 'btn-glow' : ''; ?>">
                            Select <?php echo $plan['name']; ?>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>
