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
    $imgSrc = str_starts_with($game['image'], 'http') ? $game['image'] : UPLOAD_URL . $game['image'];
}

$extraImages = [];
if (!empty($game['extra_images'])) {
    foreach (explode('|', $game['extra_images']) as $img) {
        $img = trim($img);
        if ($img) $extraImages[] = $img;
    }
}

$gallery = $imgSrc ? array_merge([$imgSrc], $extraImages) : $extraImages;

$videoEmbed = '';
if (!empty($game['video_url'])) {
    $url = $game['video_url'];
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
        $videoEmbed = 'https://www.youtube.com/embed/' . $m[1] . '?rel=0&modestbranding=1';
    } elseif (str_contains($url, 'youtube.com/embed/')) {
        $videoEmbed = $url;
    }
}

$commentsStmt = $db->prepare('
    SELECT c.*, u.username FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.game_id = :id ORDER BY c.created_at DESC
');
$commentsStmt->execute([':id' => $id]);
$comments = $commentsStmt->fetchAll();

$commentError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    if (!isLoggedIn()) {
        $commentError = 'Vous devez être connecté pour commenter.';
    } else {
        verifyCsrf();
        $content = trim($_POST['comment'] ?? '');
        if (strlen($content) < 3) {
            $commentError = 'Le commentaire est trop court.';
        } elseif (strlen($content) > 2000) {
            $commentError = 'Le commentaire est trop long (max 2000 caractères).';
        } else {
            $ins = $db->prepare('INSERT INTO comments (game_id, user_id, content) VALUES (:gid, :uid, :content)');
            $ins->execute([':gid' => $id, ':uid' => $_SESSION['user_id'], ':content' => $content]);
            setFlash('success', 'Commentaire ajouté !');
            header('Location: ' . APP_URL . '/pages/game.php?id=' . $id . '#comments');
            exit;
        }
    }
}

if (isset($_GET['delete_comment']) && isLoggedIn()) {
    $cid = sanitizeInt($_GET['delete_comment']);
    $cstmt = $db->prepare('SELECT * FROM comments WHERE id = :id');
    $cstmt->execute([':id' => $cid]);
    $c = $cstmt->fetch();
    if ($c && ($c['user_id'] == $_SESSION['user_id'] || isAdmin())) {
        $db->prepare('DELETE FROM comments WHERE id = :id')->execute([':id' => $cid]);
        setFlash('success', 'Commentaire supprimé.');
        header('Location: ' . APP_URL . '/pages/game.php?id=' . $id . '#comments');
        exit;
    }
}

$pageTitle = $game['name'];
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="game-detail">
    <a href="<?= APP_URL ?>/index.php" class="btn-ghost" style="margin-bottom:2rem;display:inline-flex">← Retour au catalogue</a>

    <div class="game-detail-grid">

        <!-- Galerie -->
        <div class="gallery-col">
            <div class="gallery-main" id="galleryMain">
                <?php if ($gallery): ?>
                    <img src="<?= $gallery[0] ?>" alt="<?= sanitize($game['name']) ?>" id="mainImg">
                <?php else: ?>
                    <div class="gallery-placeholder">◈</div>
                <?php endif; ?>
                <?php if ($videoEmbed): ?>
                    <button class="gallery-video-btn" onclick="showVideo()">▶ Trailer</button>
                <?php endif; ?>
            </div>

            <?php if ($videoEmbed): ?>
            <div class="gallery-video-wrap" id="videoWrap" style="display:none">
                <iframe id="gameVideo" src="" data-src="<?= sanitize($videoEmbed) ?>" frameborder="0" allowfullscreen></iframe>
                <button class="gallery-video-close" onclick="hideVideo()">✕ Image</button>
            </div>
            <?php endif; ?>

            <?php if (count($gallery) > 1 || $videoEmbed): ?>
            <div class="gallery-thumbs">
                <?php foreach ($gallery as $i => $img): ?>
                    <button class="thumb <?= $i === 0 ? 'active' : '' ?>" onclick="setMainImg('<?= $img ?>', this)">
                        <img src="<?= $img ?>" alt="">
                    </button>
                <?php endforeach; ?>
                <?php if ($videoEmbed): ?>
                    <button class="thumb thumb-video" onclick="showVideo()" id="videoThumb"><span>▶</span></button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Infos -->
        <div class="game-info-panel">
            <h1 class="game-detail-title"><?= sanitize($game['name']) ?></h1>
            <div class="game-meta-row">
                <?php if ($game['genre']): ?><span class="meta-tag genre"><?= sanitize($game['genre']) ?></span><?php endif; ?>
                <?php if ($game['platform']): ?><span class="meta-tag">📺 <?= sanitize($game['platform']) ?></span><?php endif; ?>
                <?php if ($game['release_year']): ?><span class="meta-tag">📅 <?= $game['release_year'] ?></span><?php endif; ?>
                <?php if ($game['rating'] > 0): ?><span class="meta-tag">★ <?= number_format($game['rating'], 1) ?>/10</span><?php endif; ?>
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
                    <a href="<?= sanitizeUrl($game['game_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn-solid">Jouer / Acheter ↗</a>
                <?php endif; ?>
                <?php if (isAdmin()): ?>
                    <a href="<?= APP_URL ?>/admin/edit.php?id=<?= $game['id'] ?>" class="btn-neon btn-purple">✎ Modifier</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- COMMENTAIRES -->
    <div class="comments-section" id="comments">
        <h2 class="comments-title">
            <span class="comments-icon">◈</span>
            Commentaires
            <span class="comments-count"><?= count($comments) ?></span>
        </h2>

        <?php if (isLoggedIn()): ?>
        <div class="comment-form-wrap">
            <?php if ($commentError): ?>
                <div class="flash-message flash-error" style="position:static;margin-bottom:1rem;animation:none">✕ <?= sanitize($commentError) ?></div>
            <?php endif; ?>
            <form method="POST" action="#comments" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <div class="comment-input-row">
                    <div class="comment-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
                    <div style="flex:1">
                        <textarea name="comment" class="form-input comment-textarea" placeholder="Partagez votre avis sur ce jeu..." maxlength="2000" rows="3"><?= sanitize($_POST['comment'] ?? '') ?></textarea>
                        <div style="display:flex;justify-content:flex-end;margin-top:0.6rem">
                            <button type="submit" class="btn-neon">Publier →</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="comment-login-prompt">
            <a href="<?= APP_URL ?>/login.php">Connectez-vous</a> pour laisser un commentaire.
        </div>
        <?php endif; ?>

        <div class="comments-list">
            <?php if (empty($comments)): ?>
                <div class="comments-empty"><p>Aucun commentaire pour l'instant. Soyez le premier !</p></div>
            <?php else: ?>
                <?php foreach ($comments as $c): ?>
                <div class="comment-item">
                    <div class="comment-avatar"><?= strtoupper(substr($c['username'], 0, 1)) ?></div>
                    <div class="comment-body">
                        <div class="comment-header">
                            <span class="comment-author"><?= sanitize($c['username']) ?></span>
                            <span class="comment-date"><?= date('d/m/Y à H:i', strtotime($c['created_at'])) ?></span>
                            <?php if (isLoggedIn() && ($c['user_id'] == $_SESSION['user_id'] || isAdmin())): ?>
                                <a href="?id=<?= $id ?>&delete_comment=<?= $c['id'] ?>#comments" class="comment-delete" onclick="return confirm('Supprimer ce commentaire ?')">✕</a>
                            <?php endif; ?>
                        </div>
                        <p class="comment-content"><?= nl2br(sanitize($c['content'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.gallery-col { display:flex;flex-direction:column;gap:0.8rem; }
.gallery-main { position:relative;border-radius:var(--radius-lg);overflow:hidden;border:1px solid var(--border-subtle);background:var(--bg-elevated);aspect-ratio:16/9; }
.gallery-main img { width:100%;height:100%;object-fit:cover;transition:opacity 0.2s; }
.gallery-placeholder { width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-family:var(--font-display);font-size:4rem; }
.gallery-video-btn { position:absolute;bottom:0.8rem;right:0.8rem;font-family:var(--font-ui);font-size:0.8rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;padding:0.4rem 0.9rem;border-radius:var(--radius);border:1px solid var(--neon-cyan);color:var(--neon-cyan);background:rgba(5,5,8,0.85);cursor:pointer;backdrop-filter:blur(6px);transition:var(--transition); }
.gallery-video-btn:hover { background:rgba(183,110,255,0.15);box-shadow:0 0 16px rgba(183,110,255,0.3); }
.gallery-video-wrap { position:relative;border-radius:var(--radius-lg);overflow:hidden;border:1px solid var(--border-glow);aspect-ratio:16/9; }
.gallery-video-wrap iframe { width:100%;height:100%;border:none; }
.gallery-video-close { position:absolute;top:0.6rem;right:0.6rem;font-family:var(--font-ui);font-size:0.75rem;font-weight:700;padding:0.3rem 0.8rem;border-radius:var(--radius);border:1px solid var(--border-subtle);background:rgba(5,5,8,0.85);color:var(--text-secondary);cursor:pointer;backdrop-filter:blur(6px);transition:var(--transition); }
.gallery-video-close:hover { color:var(--text-primary); }
.gallery-thumbs { display:flex;gap:0.5rem;flex-wrap:wrap; }
.thumb { width:72px;height:48px;border-radius:6px;overflow:hidden;border:2px solid transparent;cursor:pointer;background:var(--bg-elevated);transition:var(--transition);flex-shrink:0;padding:0; }
.thumb img { width:100%;height:100%;object-fit:cover; }
.thumb:hover { border-color:rgba(183,110,255,0.5); }
.thumb.active { border-color:var(--neon-cyan); }
.thumb-video { display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:var(--neon-cyan);border-color:rgba(183,110,255,0.3); }
.thumb-video:hover { border-color:var(--neon-cyan);background:rgba(183,110,255,0.1); }

.comments-section { max-width:900px;margin:3rem auto 0;padding-top:2rem;border-top:1px solid var(--border-subtle); }
.comments-title { font-size:1.1rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;display:flex;align-items:center;gap:0.6rem;margin-bottom:1.5rem; }
.comments-icon { color:var(--neon-cyan);font-size:0.8rem; }
.comments-count { font-size:0.75rem;background:rgba(183,110,255,0.15);border:1px solid rgba(183,110,255,0.3);color:var(--neon-cyan);padding:0.1rem 0.5rem;border-radius:20px; }
.comment-form-wrap { margin-bottom:2rem;background:var(--bg-card);border:1px solid var(--border-subtle);border-radius:var(--radius-lg);padding:1.2rem; }
.comment-input-row { display:flex;gap:1rem;align-items:flex-start; }
.comment-avatar { width:38px;height:38px;border-radius:50%;background:var(--gradient-neon);color:#000;font-family:var(--font-display);font-weight:900;font-size:0.9rem;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.comment-textarea { min-height:80px;resize:vertical; }
.comment-login-prompt { text-align:center;padding:1.5rem;background:var(--bg-card);border:1px solid var(--border-subtle);border-radius:var(--radius-lg);color:var(--text-muted);font-size:0.95rem;margin-bottom:2rem; }
.comment-login-prompt a { color:var(--neon-cyan);font-weight:600; }
.comments-list { display:flex;flex-direction:column;gap:1rem; }
.comment-item { display:flex;gap:1rem;align-items:flex-start;padding:1.1rem;background:var(--bg-card);border:1px solid var(--border-subtle);border-radius:var(--radius-lg);transition:var(--transition); }
.comment-item:hover { border-color:rgba(183,110,255,0.2); }
.comment-body { flex:1;min-width:0; }
.comment-header { display:flex;align-items:center;gap:0.6rem;margin-bottom:0.5rem;flex-wrap:wrap; }
.comment-author { font-family:var(--font-ui);font-weight:700;font-size:0.9rem;color:var(--neon-cyan); }
.comment-date { font-size:0.75rem;color:var(--text-muted); }
.comment-delete { margin-left:auto;font-size:0.75rem;color:var(--text-muted);border:1px solid transparent;border-radius:4px;padding:0.1rem 0.4rem;transition:var(--transition); }
.comment-delete:hover { color:var(--neon-pink);border-color:rgba(255,45,120,0.4); }
.comment-content { font-size:0.9rem;color:var(--text-secondary);line-height:1.6;word-break:break-word; }
.comments-empty { text-align:center;padding:2.5rem;color:var(--text-muted);background:var(--bg-card);border:1px dashed var(--border-subtle);border-radius:var(--radius-lg); }
</style>

<script>
function setMainImg(src, btn) {
    document.getElementById('mainImg').src = src;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    hideVideo(false);
}
function showVideo() {
    const iframe = document.getElementById('gameVideo');
    if (!iframe.src) iframe.src = iframe.dataset.src;
    document.getElementById('galleryMain').style.display = 'none';
    document.getElementById('videoWrap').style.display = 'block';
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    const vt = document.getElementById('videoThumb');
    if (vt) vt.classList.add('active');
}
function hideVideo(resetThumb = true) {
    const wrap = document.getElementById('videoWrap');
    if (!wrap) return;
    wrap.style.display = 'none';
    document.getElementById('galleryMain').style.display = 'block';
    if (resetThumb) {
        document.querySelectorAll('.thumb').forEach((t, i) => t.classList.toggle('active', i === 0));
    }
}
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
