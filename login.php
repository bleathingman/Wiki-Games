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

        <p class="auth-alt">
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
