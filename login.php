<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '') $errors['username'] = 'Nom d\'utilisateur requis.';
    if ($password === '') $errors['password'] = 'Mot de passe requis.';

    if (empty($errors)) {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1');
        $stmt->execute([':username' => $username, ':email' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            $redirect = $_SESSION['redirect_after_login'] ?? APP_URL . '/index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $errors['general'] = 'Identifiants incorrects.';
        }
    }
}

// Génère l'URL de connexion Steam
function steamLoginUrl() {
    $returnUrl = APP_URL . '/steam_callback.php';
    $params = [
        'openid.ns'         => 'http://specs.openid.net/auth/2.0',
        'openid.mode'       => 'checkid_setup',
        'openid.return_to'  => $returnUrl,
        'openid.realm'      => APP_URL,
        'openid.identity'   => 'http://specs.openid.net/auth/2.0/identifier_select',
        'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
    ];
    return 'https://steamcommunity.com/openid/login?' . http_build_query($params);
}

$pageTitle = 'Connexion';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — WIKI GAMES</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Orbitron:wght@400;700;900&family=Exo+2:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/style.css">
</head>
<body>
<div class="scanlines"></div>
<div class="noise"></div>

<main style="min-height:100vh">
<div class="auth-page">
    <div class="auth-box">
        <div class="auth-logo">
            <span class="logo-bracket">[</span>
            WIKI<span class="logo-accent">GAMES</span>
            <span class="logo-bracket">]</span>
        </div>

        <h1 class="auth-title">Connexion</h1>
        <p class="auth-subtitle">Accédez à votre compte</p>

        <?php if (isset($errors['general'])): ?>
            <div class="flash-message flash-error" style="position:static;margin-bottom:1rem;animation:none">
                <span class="flash-icon">✕</span>
                <?= sanitize($errors['general']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="form-group">
                <label class="form-label" for="username">Nom d'utilisateur ou email</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-input <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                    value="<?= sanitize($username) ?>"
                    autocomplete="username"
                    autofocus
                >
                <?php if (isset($errors['username'])): ?>
                    <p class="form-error"><?= sanitize($errors['username']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                    autocomplete="current-password"
                >
                <?php if (isset($errors['password'])): ?>
                    <p class="form-error"><?= sanitize($errors['password']) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-solid form-full-btn">
                Se connecter →
            </button>
        </form>

        <div class="divider">ou</div>

        <a href="<?= steamLoginUrl() ?>" class="btn-steam">
            <!-- Icône Steam SVG -->
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M11.979 0C5.678 0 .511 4.86.022 11.037l6.432 2.658c.545-.371 1.203-.59 1.912-.59.063 0 .125.004.187.008l2.861-4.142V8.91c0-2.495 2.028-4.524 4.524-4.524 2.494 0 4.524 2.029 4.524 4.524s-2.03 4.524-4.524 4.524h-.105l-4.076 2.911c0 .052.004.105.004.159 0 1.875-1.515 3.396-3.39 3.396-1.635 0-3.016-1.173-3.331-2.727L.436 15.27C1.862 20.307 6.486 24 11.979 24c6.627 0 11.999-5.373 11.999-12S18.606 0 11.979 0zM7.54 18.21l-1.473-.61c.262.543.714.999 1.314 1.25 1.297.539 2.793-.076 3.332-1.375.263-.63.264-1.319.005-1.949s-.75-1.121-1.377-1.383c-.624-.26-1.29-.249-1.878-.03l1.523.63c.956.4 1.409 1.5 1.009 2.455-.397.957-1.497 1.41-2.455 1.012H7.54zm11.415-9.303c0-1.662-1.353-3.015-3.015-3.015-1.665 0-3.015 1.353-3.015 3.015 0 1.665 1.35 3.015 3.015 3.015 1.663 0 3.015-1.35 3.015-3.015zm-5.273-.005c0-1.252 1.013-2.266 2.265-2.266 1.249 0 2.266 1.014 2.266 2.266 0 1.251-1.017 2.265-2.266 2.265-1.252 0-2.265-1.014-2.265-2.265z"/>
            </svg>
            Se connecter avec Steam
        </a>

        <p class="auth-alt" style="margin-top:1.25rem">
            Pas encore de compte ? <a href="<?= APP_URL ?>/register.php">S'inscrire</a>
        </p>
        <p class="auth-alt" style="margin-top:0.5rem">
            <a href="<?= APP_URL ?>/index.php">← Retour au catalogue</a>
        </p>
    </div>
</div>
</main>
</body>
</html>