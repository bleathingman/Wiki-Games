<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireAdmin();

$db = getDB();

// Stats
$totalGames = (int) $db->query('SELECT COUNT(*) FROM games')->fetchColumn();
$totalUsers = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$freeGames  = (int) $db->query('SELECT COUNT(*) FROM games WHERE price = 0')->fetchColumn();
$avgRating  = number_format((float) $db->query('SELECT AVG(rating) FROM games WHERE rating > 0')->fetchColumn(), 1);

// Games list
$search  = sanitize($_GET['q'] ?? '');
$where   = $search ? 'WHERE name LIKE :s' : '';
$params  = $search ? [':s' => "%$search%"] : [];
$stmt    = $db->prepare("SELECT * FROM games $where ORDER BY created_at DESC LIMIT 100");
$stmt->execute($params);
$games   = $stmt->fetchAll();

$pageTitle = 'Admin Dashboard';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="container" style="padding-top:2rem">
    <div class="admin-header" style="border-radius:12px;margin-bottom:1.5rem">
        <div>
            <h1 class="page-title"><span>ADMIN</span> PANEL</h1>
            <p class="page-subtitle">Gestion du catalogue de jeux</p>
        </div>
        <a href="<?= APP_URL ?>/admin/add.php" class="btn-solid">+ Ajouter un jeu</a>
    </div>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card">
        <p class="stat-label">Jeux total</p>
        <p class="stat-value"><?= $totalGames ?></p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Jeux gratuits</p>
        <p class="stat-value"><?= $freeGames ?></p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Note moyenne</p>
        <p class="stat-value"><?= $avgRating ?></p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Utilisateurs</p>
        <p class="stat-value"><?= $totalUsers ?></p>
    </div>
</div>

<!-- Table -->
<div class="admin-table-wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;flex-wrap:wrap;gap:1rem">
        <h2 style="font-family:var(--font-ui);font-size:0.8rem;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:var(--text-muted)">
            Liste des jeux (<?= count($games) ?>)
        </h2>
        <form method="GET" style="display:flex;gap:0.6rem">
            <input type="text" name="q" class="search-input" placeholder="Rechercher..." value="<?= sanitize($search) ?>" style="min-width:220px">
            <button type="submit" class="btn-ghost">Chercher</button>
        </form>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Nom</th>
                <th>Genre</th>
                <th>Plateforme</th>
                <th>Prix</th>
                <th>Note</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($games as $game): ?>
            <tr>
                <td>
                    <?php if ($game['image']): ?>
                        <?php $s = str_starts_with($game['image'], 'http') ? $game['image'] : UPLOAD_URL . $game['image']; ?>
                        <img class="td-img" src="<?= $s ?>" alt="">
                    <?php else: ?>
                        <div style="width:50px;height:35px;background:var(--bg-elevated);border-radius:4px;display:flex;align-items:center;justify-content:center;color:var(--text-muted)">◈</div>
                    <?php endif; ?>
                </td>
                <td class="td-name"><?= sanitize($game['name']) ?></td>
                <td><?= sanitize($game['genre'] ?? '—') ?></td>
                <td><?= sanitize($game['platform'] ?? '—') ?></td>
                <td><?= $game['price'] == 0 ? '<span style="color:var(--neon-green)">Gratuit</span>' : number_format($game['price'], 2) . ' €' ?></td>
                <td><?= $game['rating'] > 0 ? number_format($game['rating'], 1) : '—' ?></td>
                <td><?= date('d/m/Y', strtotime($game['created_at'])) ?></td>
                <td>
                    <div class="table-actions">
                        <a href="<?= APP_URL ?>/pages/game.php?id=<?= $game['id'] ?>" class="card-btn card-btn-view">Voir</a>
                        <a href="<?= APP_URL ?>/admin/edit.php?id=<?= $game['id'] ?>" class="card-btn card-btn-edit">✎</a>
                        <button class="card-btn card-btn-delete" onclick="confirmDelete(<?= $game['id'] ?>, '<?= sanitize(addslashes($game['name'])) ?>')">✕</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Delete Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon">⚠</div>
        <h3 class="modal-title">Confirmer la suppression</h3>
        <p class="modal-text" id="modal-game-name"></p>
        <div class="modal-actions">
            <button class="btn-ghost" onclick="closeModal()">Annuler</button>
            <form id="deleteForm" method="POST" action="<?= APP_URL ?>/process/delete_game.php" style="display:contents">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="id" id="deleteGameId">
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
function closeModal() { document.getElementById('deleteModal').classList.remove('open'); }
document.getElementById('deleteModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
