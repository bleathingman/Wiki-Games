<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$id = sanitizeInt($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/404.php'); exit; }

$db   = getDB();
$stmt = $db->prepare('SELECT * FROM games WHERE id = :id');
$stmt->execute([':id' => $id]);
$game = $stmt->fetch();

if (!$game) { header('Location: ' . APP_URL . '/404.php'); exit; }

$imgSrc = '';
if ($game['image']) {
    $imgSrc = str_starts_with($game['image'], 'http')
        ? $game['image']
        : UPLOAD_URL . $game['image'];
}

$pageTitle = $game['name'];
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="game-detail">
    <a href="<?= APP_URL ?>/index.php" class="btn-ghost" style="margin-bottom:2rem;display:inline-flex">
        ← Retour au catalogue
    </a>

    <div class="game-detail-grid">
        <!-- Cover -->
        <div class="game-cover">
            <?php if ($imgSrc): ?>
                <img src="<?= $imgSrc ?>" alt="<?= sanitize($game['name']) ?>">
            <?php else: ?>
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-family:var(--font-display);font-size:4rem;background:var(--bg-elevated)">◈</div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="game-info-panel">
            <h1 class="game-detail-title"><?= sanitize($game['name']) ?></h1>

            <div class="game-meta-row">
                <?php if ($game['genre']): ?>
                    <span class="meta-tag genre"><?= sanitize($game['genre']) ?></span>
                <?php endif; ?>
                <?php if ($game['platform']): ?>
                    <span class="meta-tag">📺 <?= sanitize($game['platform']) ?></span>
                <?php endif; ?>
                <?php if ($game['release_year']): ?>
                    <span class="meta-tag">📅 <?= $game['release_year'] ?></span>
                <?php endif; ?>
                <?php if ($game['rating'] > 0): ?>
                    <span class="meta-tag">★ <?= number_format($game['rating'], 1) ?>/10</span>
                <?php endif; ?>
            </div>

            <div class="divider"></div>

            <?php if ($game['description']): ?>
                <p class="game-description"><?= nl2br(sanitize($game['description'])) ?></p>
                <div class="divider"></div>
            <?php endif; ?>

            <div class="price-display <?= $game['price'] == 0 ? 'free' : '' ?>">
                <?= $game['price'] == 0 ? 'GRATUIT' : number_format($game['price'], 2) . ' €' ?>
            </div>

            <div style="display:flex;gap:0.8rem;flex-wrap:wrap">
                <?php if ($game['game_url']): ?>
                    <a href="<?= sanitizeUrl($game['game_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn-solid">
                        Jouer / Acheter ↗
                    </a>
                <?php endif; ?>
                <?php if (isAdmin()): ?>
                    <a href="<?= APP_URL ?>/admin/edit.php?id=<?= $game['id'] ?>" class="btn-neon btn-purple">
                        ✎ Modifier
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
