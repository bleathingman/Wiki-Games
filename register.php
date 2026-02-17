<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$errors = [];
$vals   = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username = sanitize($_POST['username'] ?? '');
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $vals     = ['username' => $username, 'email' => $_POST['email'] ?? ''];

    if (strlen($username) < 3 || strlen($username) > 50)
        $errors['username'] = 'Le nom doit faire entre 3 et 50 caractères.';
    elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username))
        $errors['username'] = 'Caractères autorisés : lettres, chiffres, underscore.';

    if (!$email)
        $errors['email'] = 'Adresse email invalide.';

    if (strlen($password) < 8)
        $errors['password'] = 'Minimum 8 caractères.';
    elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password))
        $errors['password'] = 'Le mot de passe doit contenir au moins une majuscule et un chiffre.';

    if ($password !== $confirm)
        $errors['confirm_password'] = 'Les mots de passe ne correspondent pas.';

    if (empty($errors)) {
        $db = getDB();

        $check = $db->prepare('SELECT id FROM users WHERE username = :u OR email = :e');
        $check->execute([':u' => $username, ':e' => $email]);
        if ($check->fetch()) {
            $errors['general'] = 'Ce nom d\'utilisateur ou email est déjà utilisé.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare('INSERT INTO users (username, email, password) VALUES (:u, :e, :p)');
            $stmt->execute([':u' => $username, ':e' => $email, ':p' => $hash]);

            session_regenerate_id(true);
            $_SESSION['user_id']  = $db->lastInsertId();
            $_SESSION['username'] = $username;
            $_SESSION['role']     = 'user';

            setFlash('success', 'Bienvenue ' . $username . ' !');
            header('Location: ' . APP_URL . '/index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — WIKI GAMES</title>
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

        <h1 class="auth-title">Inscription</h1>
        <p class="auth-subtitle">Créez votre compte gratuit</p>

        <?php if (isset($errors['general'])): ?>
            <div class="flash-message flash-error" style="position:static;margin-bottom:1rem;animation:none">
                <span>✕</span> <?= sanitize($errors['general']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="form-group">
                <label class="form-label" for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username"
                    class="form-input <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                    value="<?= sanitize($vals['username']) ?>"
                    autocomplete="username" autofocus>
                <?php if (isset($errors['username'])): ?>
                    <p class="form-error"><?= sanitize($errors['username']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email"
                    class="form-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                    value="<?= sanitize($vals['email']) ?>"
                    autocomplete="email">
                <?php if (isset($errors['email'])): ?>
                    <p class="form-error"><?= sanitize($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Mot de passe</label>
                <input type="password" id="password" name="password"
                    class="form-input <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                    autocomplete="new-password">
                <p class="form-hint">Min. 8 caractères, 1 majuscule, 1 chiffre</p>
                <?php if (isset($errors['password'])): ?>
                    <p class="form-error"><?= sanitize($errors['password']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password"
                    class="form-input <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                    autocomplete="new-password">
                <?php if (isset($errors['confirm_password'])): ?>
                    <p class="form-error"><?= sanitize($errors['confirm_password']) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-solid form-full-btn">
                Créer mon compte →
            </button>
        </form>

        <p class="auth-alt">
            Déjà un compte ? <a href="<?= APP_URL ?>/login.php">Se connecter</a>
        </p>
    </div>
</div>
</main>
</body>
</html>
