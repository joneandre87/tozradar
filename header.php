<?php
if (!isset($pageTitle)) {
    $pageTitle = SITE_NAME;
}

// Get custom settings
$customSettings = getCustomSettings();
$customCSS = '';
$customJS = '';
foreach ($customSettings as $setting) {
    if ($setting['setting_key'] === 'custom_css') $customCSS = $setting['setting_value'];
    if ($setting['setting_key'] === 'custom_js') $customJS = $setting['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle . ' | ' . SITE_NAME); ?></title>
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if ($customCSS): ?>
    <style><?php echo $customCSS; ?></style>
    <?php endif; ?>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <a href="/" class="logo">
                    <i class="fas fa-radar"></i>
                    <span><?php echo SITE_NAME; ?></span>
                </a>
                <nav class="main-nav">
                    <a href="/" class="nav-link">Home</a>
                    <a href="/features.php" class="nav-link">Features</a>
                    <?php if (isLoggedIn()): ?>
                        <a href="/admin/index.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <a href="/logout.php" class="btn btn-small btn-outline">Logout</a>
                    <?php else: ?>
                        <a href="/login.php" class="btn btn-small btn-outline">Sign In</a>
                        <a href="/register.php" class="btn btn-small btn-primary">Register</a>
                    <?php endif; ?>
                </nav>
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>
