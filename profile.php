<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

define('STEAM_API_KEY', 'F623F5D1BDD4666094D118E33CEA2ED2');

$db = getDB();

if (isset($_GET['id'])) {
    $stmt = $db->prepare('SELECT * FROM users WHERE steam_id = :steam_id LIMIT 1');
    $stmt->execute([':steam_id' => $_GET['id']]);
} elseif (isset($_GET['user'])) {
    $stmt = $db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $_GET['user']]);
} elseif (isLoggedIn()) {
    $stmt = $db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $_SESSION['user_id']]);
} else {
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

$profileUser = $stmt->fetch();
if (!$profileUser) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$steamId     = $profileUser['steam_id'] ?? null;
$steamData   = null;
$gamesData   = null;
$levelData   = null;
$recentGames = null;

if ($steamId) {
    $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key=" . STEAM_API_KEY . "&steamids={$steamId}";
    $res = @file_get_contents($url);
    if ($res) $steamData = json_decode($res, true)['response']['players'][0] ?? null;

    $url = "https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/?key=" . STEAM_API_KEY . "&steamid={$steamId}&include_appinfo=1";
    $res = @file_get_contents($url);
    if ($res) $gamesData = json_decode($res, true)['response'] ?? null;

    $url = "https://api.steampowered.com/IPlayerService/GetSteamLevel/v1/?key=" . STEAM_API_KEY . "&steamid={$steamId}";
    $res = @file_get_contents($url);
    if ($res) $levelData = json_decode($res, true)['response'] ?? null;

    $url = "https://api.steampowered.com/IPlayerService/GetRecentlyPlayedGames/v1/?key=" . STEAM_API_KEY . "&steamid={$steamId}&count=3";
    $res = @file_get_contents($url);
    if ($res) $recentGames = json_decode($res, true)['response'] ?? null;
}

$totalGames   = $gamesData['game_count'] ?? 0;
$steamLevel   = $levelData['player_level'] ?? 0;
$totalMinutes = 0;
if (!empty($gamesData['games'])) {
    foreach ($gamesData['games'] as $g) $totalMinutes += $g['playtime_forever'] ?? 0;
}
$totalHours = round($totalMinutes / 60);

$personaStates = [0=>'Hors ligne',1=>'En ligne',2=>'Occupé',3=>'Absent',4=>'Endormi',5=>'Cherche à jouer',6=>'Cherche à trader'];
$onlineState   = $personaStates[$steamData['personastate'] ?? 0] ?? 'Hors ligne';
$isOnline      = ($steamData['personastate'] ?? 0) !== 0;
$isOwnProfile  = isLoggedIn() && $_SESSION['user_id'] == $profileUser['id'];

$inventoryValue   = $profileUser['inventory_value'] ?? null;
$inventoryDetails = !empty($profileUser['inventory_details']) ? json_decode($profileUser['inventory_details'], true) : null;
$inventoryUpdated = $profileUser['inventory_updated_at'] ?? null;

$pageTitle = $profileUser['username'] . ' — Profil';
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="profile-page">

    <div class="profile-hero">
        <div class="profile-hero-bg"></div>
        <div class="profile-hero-content">
            <div class="profile-identity">
                <div class="profile-avatar-wrap">
                    <?php if (!empty($profileUser['steam_avatar'])): ?>
                        <img src="<?= sanitize($profileUser['steam_avatar']) ?>" alt="avatar" class="profile-avatar">
                    <?php else: ?>
                        <div class="profile-avatar-placeholder">◉</div>
                    <?php endif; ?>
                    <span class="profile-status <?= $isOnline ? 'online' : 'offline' ?>"></span>
                </div>
                <div class="profile-identity-info">
                    <h1 class="profile-username"><?= sanitize($profileUser['username']) ?></h1>
                    <div class="profile-meta">
                        <?php if ($steamId): ?>
                            <span class="profile-badge steam">
                                <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><path d="M11.979 0C5.678 0 .511 4.86.022 11.037l6.432 2.658c.545-.371 1.203-.59 1.912-.59.063 0 .125.004.187.008l2.861-4.142V8.91c0-2.495 2.028-4.524 4.524-4.524 2.494 0 4.524 2.029 4.524 4.524s-2.03 4.524-4.524 4.524h-.105l-4.076 2.911c0 .052.004.105.004.159 0 1.875-1.515 3.396-3.39 3.396-1.635 0-3.016-1.173-3.331-2.727L.436 15.27C1.862 20.307 6.486 24 11.979 24c6.627 0 11.999-5.373 11.999-12S18.606 0 11.979 0z"/></svg>
                                Connecté via Steam
                            </span>
                        <?php endif; ?>
                        <span class="profile-state <?= $isOnline ? 'online' : '' ?>"><?= $onlineState ?></span>
                    </div>
                    <?php if (!empty($steamData['profileurl'])): ?>
                        <a href="<?= sanitize($steamData['profileurl']) ?>" target="_blank" class="btn-ghost profile-steam-link">
                            Voir profil Steam →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container">

        <div class="profile-stats-grid">
            <div class="profile-stat-card">
                <div class="profile-stat-icon">🎮</div>
                <div class="profile-stat-value"><?= number_format($totalGames) ?></div>
                <div class="profile-stat-label">Jeux possédés</div>
            </div>
            <div class="profile-stat-card">
                <div class="profile-stat-icon">⏱️</div>
                <div class="profile-stat-value"><?= number_format($totalHours) ?></div>
                <div class="profile-stat-label">Heures de jeu</div>
            </div>
            <div class="profile-stat-card highlight">
                <div class="profile-stat-icon">⭐</div>
                <div class="profile-stat-value"><?= $steamLevel ?></div>
                <div class="profile-stat-label">Niveau Steam</div>
                <div class="level-bar-wrap">
                    <div class="level-bar" style="width: <?= min(($steamLevel % 10) * 10, 100) ?>%"></div>
                </div>
            </div>
            <div class="profile-stat-card">
                <div class="profile-stat-icon">📅</div>
                <div class="profile-stat-value"><?= date('Y', strtotime($profileUser['created_at'])) ?></div>
                <div class="profile-stat-label">Membre depuis</div>
            </div>
        </div>

        <!-- Section Inventaire -->
        <?php if ($steamId): ?>
        <div class="profile-section">
            <h2 class="profile-section-title"><span class="title-accent">◈</span> Valeur de l'inventaire Steam</h2>

            <div class="inventory-card" id="inventory-card">

                <?php if ($inventoryValue !== null && $inventoryUpdated): ?>
                <div class="inventory-total-wrap">
                    <div class="inventory-total-label">Valeur estimée totale (CS2 + TF2 + Dota 2)</div>
                    <div class="inventory-total-value">≈ <?= number_format((float)$inventoryValue, 2) ?> €</div>
                    <div class="inventory-updated">
                        Dernière mise à jour : <?= date('d/m/Y à H:i', strtotime($inventoryUpdated)) ?>
                        <?php if ($isOwnProfile): ?>
                            <button class="inv-refresh-btn" onclick="calculateInventory(true)">↺ Recalculer</button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($inventoryDetails)): ?>
                <div class="inventory-games-grid">
                    <?php foreach ($inventoryDetails as $gameName => $gameData): ?>
                    <div class="inventory-game-card">
                        <div class="inv-game-header">
                            <?php if (!empty($gameData['appid']) && !empty($gameData['icon'])): ?>
                                <img src="https://media.steampowered.com/steamcommunity/public/images/apps/<?= $gameData['appid'] ?>/<?= $gameData['icon'] ?>.jpg" class="inv-game-img" onerror="this.style.display='none'">
                            <?php else: ?>
                                <span class="inv-game-icon">🎮</span>
                            <?php endif; ?>
                            <span class="inv-game-name"><?= sanitize($gameName) ?></span>
                            <span class="inv-game-total"><?= number_format($gameData['total'], 2) ?> €</span>
                        </div>
                        <div class="inv-game-items"><?= $gameData['items'] ?> items vendables</div>
                        <?php if (!empty($gameData['top_items'])): ?>
                        <div class="inv-top-items">
                            <?php foreach ($gameData['top_items'] as $item): ?>
                            <div class="inv-item-row">
                                <span class="inv-item-name"><?= sanitize($item['name']) ?></span>
                                <span class="inv-item-count">x<?= $item['count'] ?></span>
                                <span class="inv-item-price"><?= number_format($item['total'], 2) ?> €</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php elseif ($isOwnProfile): ?>
                <div class="inventory-empty" id="inv-empty">
                    <div class="inventory-empty-icon">💰</div>
                    <p>Calcule la valeur de ton inventaire CS2, TF2 et Dota 2</p>
                    <p class="inventory-notice">⚠️ Ton inventaire doit être <strong>public</strong> sur Steam<br>Le calcul peut prendre 1 à 2 minutes selon la taille de l'inventaire.</p>
                    <button class="btn-neon" onclick="calculateInventory(false)" id="inv-btn">
                        💰 Calculer la valeur
                    </button>
                </div>

                <?php else: ?>
                <div class="inventory-empty">
                    <div class="inventory-empty-icon">💰</div>
                    <p style="color:var(--text-muted)">Cet utilisateur n'a pas encore calculé son inventaire.</p>
                </div>
                <?php endif; ?>

                <div class="inventory-loading" id="inv-loading" style="display:none">
                    <div class="inv-spinner"></div>
                    <p id="inv-loading-text">Connexion à Steam...</p>
                    <p class="inventory-notice">Cette opération peut prendre 1 à 2 minutes.<br>Ne ferme pas cette page.</p>
                </div>

            </div>
        </div>
        <?php endif; ?>

        <!-- Jeux récemment joués -->
        <?php if (!empty($recentGames['games'])): ?>
        <div class="profile-section">
            <h2 class="profile-section-title"><span class="title-accent">◈</span> Récemment joué</h2>
            <div class="recent-games-grid">
                <?php foreach ($recentGames['games'] as $game): ?>
                <div class="recent-game-card">
                    <img
                        src="https://media.steampowered.com/steamcommunity/public/images/apps/<?= $game['appid'] ?>/<?= $game['img_icon_url'] ?>.jpg"
                        alt="<?= sanitize($game['name']) ?>"
                        class="recent-game-icon"
                        onerror="this.style.display='none'"
                    >
                    <div class="recent-game-info">
                        <div class="recent-game-name"><?= sanitize($game['name']) ?></div>
                        <div class="recent-game-hours"><?= round($game['playtime_2weeks'] / 60, 1) ?>h ces 2 dernières semaines</div>
                        <div class="recent-game-total"><?= round($game['playtime_forever'] / 60, 1) ?>h au total</div>
                    </div>
                    <div class="recent-game-bar-wrap">
                        <div class="recent-game-bar" style="height: <?= min(($game['playtime_2weeks'] / 60) * 5, 100) ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($steamId && empty($gamesData['games']) && $totalGames === 0): ?>
        <div class="profile-private-notice">
            <span class="notice-icon">🔒</span>
            <p>Ce profil Steam est privé — certaines stats ne sont pas disponibles.</p>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php if ($steamId && $isOwnProfile): ?>
<script>
const STEAM_ID = '<?= $steamId ?>';
const API_URL  = '<?= APP_URL ?>/api/inventory_value.php';

function calculateInventory(force = false) {
    const emptyEl   = document.getElementById('inv-empty');
    const loadingEl = document.getElementById('inv-loading');
    const btnEl     = document.getElementById('inv-btn');
    const txtEl     = document.getElementById('inv-loading-text');

    if (emptyEl)   emptyEl.style.display   = 'none';
    if (loadingEl) loadingEl.style.display = 'flex';
    if (btnEl)     btnEl.disabled = true;

    const messages = [
        'Récupération de l\'inventaire TF2...',
        'Récupération de l\'inventaire CS2...',
        'Récupération de l\'inventaire Dota 2...',
        'Calcul des prix du marché Steam...',
        'Calcul en cours, encore quelques instants...',
    ];
    let i = 0;
    const interval = setInterval(() => {
        if (i < messages.length - 1) i++;
        if (txtEl) txtEl.textContent = messages[i];
    }, 15000);

    fetch(`${API_URL}?steam_id=${STEAM_ID}${force ? '&force=1' : ''}`)
        .then(r => r.json())
        .then(data => {
            clearInterval(interval);
            if (data.error) {
                alert('Erreur : ' + data.error);
                if (emptyEl)   emptyEl.style.display   = 'flex';
                if (loadingEl) loadingEl.style.display = 'none';
                if (btnEl)     btnEl.disabled = false;
                return;
            }
            window.location.reload();
        })
        .catch(() => {
            clearInterval(interval);
            if (loadingEl) loadingEl.style.display = 'none';
            if (emptyEl)   emptyEl.style.display   = 'flex';
            if (btnEl)     btnEl.disabled = false;
            alert('Erreur réseau. Réessaie dans quelques instants.');
        });
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>