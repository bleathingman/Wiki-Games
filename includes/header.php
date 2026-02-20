<?php
// includes/header.php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
$flash = getFlash();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?>WIKI GAMES</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Orbitron:wght@400;700;900&family=Exo+2:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/style.css">
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/favicon.svg">
</head>
<body>
<!-- Scanline overlay -->
<div class="scanlines"></div>
<!-- Noise texture -->
<div class="noise"></div>
<header class="site-header">
    <div class="header-inner">
        <a href="<?= APP_URL ?>/index.php" class="logo">
            <img src="<?= APP_URL ?>/assets/logo.png" alt="WG" class="logo-img">
        </a>
        <nav class="main-nav">
            <a href="<?= APP_URL ?>/index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <span class="nav-icon">◈</span> Catalogue
            </a>
            <?php if (isAdmin()): ?>
            <a href="<?= APP_URL ?>/admin/index.php" class="nav-link <?= str_starts_with($currentPage, 'admin') ? 'active' : '' ?>">
                <span class="nav-icon">⬡</span> Admin
            </a>
            <?php endif; ?>
        </nav>
        <div class="header-auth">
            <?php if (isLoggedIn()): ?>
                <a href="<?= APP_URL ?>/profile.php" class="user-tag" style="text-decoration:none">
                    <?php if (!empty($_SESSION['steam_avatar'])): ?>
                        <img src="<?= sanitize($_SESSION['steam_avatar']) ?>" alt="avatar" class="user-avatar">
                        <span class="steam-username"><?= sanitize($_SESSION['username']) ?></span>
                    <?php else: ?>
                        <span class="user-icon">◉</span>
                        <?= sanitize($_SESSION['username']) ?>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?><span class="admin-badge">ADMIN</span><?php endif; ?>
                </a>
                <a href="<?= APP_URL ?>/logout.php" class="btn-ghost">Déconnexion</a>
            <?php else: ?>
                <a href="<?= APP_URL ?>/login.php" class="btn-ghost">Connexion</a>
                <a href="<?= APP_URL ?>/register.php" class="btn-neon">Inscription</a>
            <?php endif; ?>
        </div>
        <button class="hamburger" onclick="toggleMenu()" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>
<?php if ($flash): ?>
<div class="flash-message flash-<?= $flash['type'] ?>" id="flash-msg">
    <span class="flash-icon"><?= $flash['type'] === 'success' ? '✓' : '✕' ?></span>
    <?= sanitize($flash['message']) ?>
    <button onclick="this.parentElement.remove()" class="flash-close">×</button>
</div>
<?php endif; ?>
<main class="main-content">