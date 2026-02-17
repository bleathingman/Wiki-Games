<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

http_response_code(404);
$pageTitle = '404 — Page introuvable';
include __DIR__ . '/includes/header.php';
?>

<div class="error-page container">
    <p class="error-code">404</p>
    <h1 style="font-family:var(--font-display);font-size:1.5rem;margin:1rem 0 0.5rem;letter-spacing:0.1em">PAGE INTROUVABLE</h1>
    <p style="color:var(--text-muted);margin-bottom:2rem">Ce contenu n'existe pas ou a été supprimé.</p>
    <a href="<?= APP_URL ?>/index.php" class="btn-solid">← Retour au catalogue</a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
