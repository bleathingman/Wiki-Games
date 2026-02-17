<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/admin/index.php');
    exit;
}

verifyCsrf();

$id = sanitizeInt($_POST['id'] ?? 0);
if (!$id) {
    setFlash('error', 'ID invalide.');
    header('Location: ' . APP_URL . '/admin/index.php');
    exit;
}

$db   = getDB();
$stmt = $db->prepare('SELECT * FROM games WHERE id = :id');
$stmt->execute([':id' => $id]);
$game = $stmt->fetch();

if (!$game) {
    setFlash('error', 'Jeu introuvable.');
    header('Location: ' . APP_URL . '/admin/index.php');
    exit;
}

// Delete local image if it's not a URL
if ($game['image'] && !str_starts_with($game['image'], 'http')) {
    $imgPath = UPLOAD_DIR . $game['image'];
    if (file_exists($imgPath)) {
        unlink($imgPath);
    }
}

$del = $db->prepare('DELETE FROM games WHERE id = :id');
$del->execute([':id' => $id]);

setFlash('success', '"' . $game['name'] . '" a été supprimé.');
header('Location: ' . APP_URL . '/admin/index.php');
exit;
