<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDB();

// Params
$page   = max(1, sanitizeInt($_GET['page'] ?? 1));
$search = sanitize($_GET['q'] ?? '');
$genre  = sanitize($_GET['genre'] ?? '');
$offset = ($page - 1) * GAMES_PER_PAGE;

// Genres list
$genres = $db->query("SELECT DISTINCT genre FROM games WHERE genre IS NOT NULL AND genre != '' ORDER BY genre")->fetchAll(PDO::FETCH_COLUMN);

// Query
$where  = [];
$params = [];

if ($search !== '') {
    $where[]    = '(g.name LIKE :search OR g.description LIKE :search2)';
    $params[':search']  = "%$search%";
    $params[':search2'] = "%$search%";
}
if ($genre !== '') {
    $where[]     = 'g.genre = :genre';
    $params[':genre'] = $genre;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int) $db->prepare("SELECT COUNT(*) FROM games g $whereSQL")->execute($params) ? $db->prepare("SELECT COUNT(*) FROM games g $whereSQL")->execute($params) : 0;

$countStmt = $db->prepare("SELECT COUNT(*) FROM games g $whereSQL");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$totalPages = max(1, (int) ceil($total / GAMES_PER_PAGE));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * GAMES_PER_PAGE;

$stmt = $db->prepare("SELECT * FROM games g $whereSQL ORDER BY g.created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit',  GAMES_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,        PDO::PARAM_INT);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$games = $stmt->fetchAll();

$pageTitle = 'Catalogue';

// Build query string helper
function buildQuery(array $extra): string {
    $params = array_filter(array_merge([
        'q'     => $_GET['q'] ?? '',
        'genre' => $_GET['genre'] ?? '',
    ], $extra));
    return $params ? '?' . http_build_query($params) : '';
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="page-hero">
    <h1 class="page-title">
        <span class="glitch" data-text="CATALOGUE">CATALOGUE</span>
        <span> DE JEUX</span>
    </h1>
    <p class="page-subtitle"><?= $total ?> jeu<?= $total !== 1 ? 'x' : '' ?> répertorié<?= $total !== 1 ? 's' : '' ?></p>
</div>

<div class="search-bar-wrapper">
    <form method="GET" action="" style="display:contents">
        <div class="search-input-wrap">
            <input
                type="text"
                name="q"
                class="search-input"
                placeholder="Rechercher un jeu..."
                value="<?= sanitize($_GET['q'] ?? '') ?>"
                autocomplete="off"
            >
        </div>

        <select name="genre" class="filter-select" onchange="this.form.submit()">
            <option value="">Tous les genres</option>
            <?php foreach ($genres as $g): ?>
                <option value="<?= sanitize($g) ?>" <?= ($genre === $g) ? 'selected' : '' ?>>
                    <?= sanitize($g) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn-neon">Filtrer</button>
        <?php if ($search || $genre): ?>
            <a href="<?= APP_URL ?>/index.php" class="btn-ghost">Réinitialiser</a>
        <?php endif; ?>
    </form>

    <?php if (isAdmin()): ?>
        <a href="<?= APP_URL ?>/admin/add.php" class="btn-solid" style="margin-left:auto">
            + Ajouter un jeu
        </a>
    <?php endif; ?>
</div>

<div class="games-grid">
    <?php if (empty($games)): ?>
        <div class="empty-state">
            <div class="empty-icon">◈</div>
            <h3>Aucun jeu trouvé</h3>
            <p>Essayez une autre recherche ou <a href="<?= APP_URL ?>/index.php" style="color:var(--neon-cyan)">réinitialisez les filtres</a>.</p>
        </div>
    <?php else: ?>
        <?php foreach ($games as $game): ?>
        <div class="game-card fade-in">
            <a href="<?= APP_URL ?>/pages/game.php?id=<?= $game['id'] ?>" class="card-img-wrap">
                <?php if ($game['image']): ?>
                    <?php
                    $imgSrc = (str_starts_with($game['image'], 'http'))
                        ? $game['image']
                        : UPLOAD_URL . sanitize($game['image']);
                    ?>
                    <img src="<?= $imgSrc ?>" alt="<?= sanitize($game['name']) ?>" loading="lazy">
                <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-family:var(--font-display);font-size:2rem;">◈</div>
                <?php endif; ?>
                <?php if ($game['genre']): ?>
                    <span class="card-genre-badge"><?= sanitize($game['genre']) ?></span>
                <?php endif; ?>
            </a>

            <div class="card-body">
                <h3 class="card-title"><?= sanitize($game['name']) ?></h3>
                <?php if ($game['platform']): ?>
                    <p class="card-platform">📺 <?= sanitize($game['platform']) ?></p>
                <?php endif; ?>
                <?php if ($game['rating'] > 0): ?>
                    <span class="card-rating"><?= number_format($game['rating'], 1) ?> / 10</span>
                <?php endif; ?>
            </div>

            <div class="card-footer">
                <span class="card-price <?= $game['price'] == 0 ? 'free' : '' ?>">
                    <?= $game['price'] == 0 ? 'Gratuit' : number_format($game['price'], 2) . ' €' ?>
                </span>
                <div class="card-actions">
                    <a href="<?= APP_URL ?>/pages/game.php?id=<?= $game['id'] ?>" class="card-btn card-btn-view">Voir</a>
                    <?php if (isAdmin()): ?>
                        <a href="<?= APP_URL ?>/admin/edit.php?id=<?= $game['id'] ?>" class="card-btn card-btn-edit">✎</a>
                        <button
                            class="card-btn card-btn-delete"
                            onclick="confirmDelete(<?= $game['id'] ?>, '<?= sanitize(addslashes($game['name'])) ?>')"
                        >✕</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <a href="<?= APP_URL . '/index.php' . buildQuery(['page' => $page - 1]) ?>"
       class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">‹</a>

    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <a href="<?= APP_URL . '/index.php' . buildQuery(['page' => $i]) ?>"
           class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>

    <a href="<?= APP_URL . '/index.php' . buildQuery(['page' => $page + 1]) ?>"
       class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">›</a>
</div>
<?php endif; ?>

<!-- Delete Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon">⚠</div>
        <h3 class="modal-title">Confirmer la suppression</h3>
        <p class="modal-text" id="modal-game-name">Supprimer ce jeu ?</p>
        <div class="modal-actions">
            <button class="btn-ghost" onclick="closeModal()">Annuler</button>
            <form id="deleteForm" method="POST" action="<?= APP_URL ?>/process/delete_game.php" style="display:contents">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="id" id="deleteGameId" value="">
                <button type="submit" class="btn-neon btn-danger">Supprimer</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteGameId').value = id;
    document.getElementById('modal-game-name').textContent = 'Supprimer "' + name + '" définitivement ?';
    document.getElementById('deleteModal').classList.add('open');
}
function closeModal() {
    document.getElementById('deleteModal').classList.remove('open');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
