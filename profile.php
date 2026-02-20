<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

define('STEAM_API_KEY', 'F623F5D1BDD4666094D118E33CEA2ED2');

// Récupère le profil demandé (via ?user=username ou ?id=steamid)
$db = getDB();

if (isset($_GET['id'])) {
    $stmt = $db->prepare('SELECT * FROM users WHERE steam_id = :steam_id LIMIT 1');
    $stmt->execute([':steam_id' => $_GET['id']]);
} elseif (isset($_GET['user'])) {
    $stmt = $db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $_GET['user']]);
} elseif (isLoggedIn()) {
    // Si pas de paramètre, affiche le profil de l'utilisateur connecté
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
    // Infos profil Steam
    $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key=" . STEAM_API_KEY . "&steamids={$steamId}";
    $res = @file_get_contents($url);
    if ($res) {
        $json = json_decode($res, true);
        $steamData = $json['response']['players'][0] ?? null;
    }

    // Nombre de jeux possédés
    $url = "https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/?key=" . STEAM_API_KEY . "&steamid={$steamId}&include_appinfo=1";
    $res = @file_get_contents($url);
    if ($res) {
        $gamesData = json_decode($res, true)['response'] ?? null;
    }

    // Niveau Steam
    $url = "https://api.steampowered.com/IPlayerService/GetSteamLevel/v1/?key=" . STEAM_API_KEY . "&steamid={$steamId}";
    $res = @file_get_contents($url);
    if ($res) {
        $levelData = json_decode($res, true)['response'] ?? null;
    }

    // Jeux récemment joués (pour calcul heures totales top 3)
    $url = "https://api.steampowered.com/IPlayerService/GetRecentlyPlayedGames/v1/?key=" . STEAM_API_KEY . "&steamid={$steamId}&count=3";
    $res = @file_get_contents($url);
    if ($res) {
        $recentGames = json_decode($res, true)['response'] ?? null;
    }
}

// Calculs
$totalGames    = $gamesData['game_count'] ?? 0;
$steamLevel    = $levelData['player_level'] ?? 0;
$totalMinutes  = 0;
if (!empty($gamesData['games'])) {
    foreach ($gamesData['games'] as $g) {
        $totalMinutes += $g['playtime_forever'] ?? 0;
    }
}
$totalHours = round($totalMinutes / 60);

// Statut Steam
$personaStates = [0 => 'Hors ligne', 1 => 'En ligne', 2 => 'Occupé', 3 => 'Absent', 4 => 'Endormi', 5 => 'Cherche à jouer', 6 => 'Cherche à trader'];
$onlineState   = $personaStates[$steamData['personastate'] ?? 0] ?? 'Hors ligne';
$isOnline      = ($steamData['personastate'] ?? 0) !== 0;

$isOwnProfile  = isLoggedIn() && $_SESSION['user_id'] == $profileUser['id'];
$pageTitle     = $profileUser['username'] . ' — Profil';
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="profile-page">

    <!-- Hero du profil -->
    <div class="profile-hero">
        <div class="profile-hero-bg"></div>
        <div class="profile-hero-content">

            <!-- Avatar + infos principales -->
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

    <!-- Stats principales -->
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
                        onerror="this.src='<?= APP_URL ?>/assets/no-image.png'"
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

        <!-- Profil privé -->
        <?php if ($steamId && empty($gamesData['games']) && $totalGames === 0): ?>
        <div class="profile-private-notice">
            <span class="notice-icon">🔒</span>
            <p>Ce profil Steam est privé — certaines stats ne sont pas disponibles.</p>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>