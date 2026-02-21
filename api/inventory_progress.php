<?php
// api/inventory_progress.php — Stream SSE de progression en temps réel

// Doit être AVANT tout require pour overrider php.ini
ini_set('max_execution_time', 0);
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);
set_time_limit(0);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Ferme la session immédiatement — sinon elle bloque les requêtes parallèles
// et cause des timeouts sur les connexions SSE longues
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Headers SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');

define('STEAM_API_KEY', 'F623F5D1BDD4666094D118E33CEA2ED2');
define('CACHE_HOURS', 24);

$steamId = $_GET['steam_id'] ?? null;
$force   = !empty($_GET['force']);

if (!$steamId) { sendEvent('error', ['message' => 'Steam ID requis']); exit; }

// Flush helper
function sendEvent(string $type, array $data): void {
    echo "event: {$type}\n";
    echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    if (ob_get_level() > 0) ob_flush();
    flush();
}

$db = getDB();

// Vérifie le cache
$stmt = $db->prepare('SELECT inventory_value, inventory_details, inventory_updated_at FROM users WHERE steam_id = :steam_id LIMIT 1');
$stmt->execute([':steam_id' => $steamId]);
$user = $stmt->fetch();

if (
    !$force &&
    $user &&
    $user['inventory_value'] !== null &&
    !empty($user['inventory_updated_at']) &&
    strtotime($user['inventory_updated_at']) > time() - (CACHE_HOURS * 3600)
) {
    sendEvent('done', [
        'cached'  => true,
        'total'   => (float) $user['inventory_value'],
        'details' => json_decode($user['inventory_details'], true),
        'updated' => $user['inventory_updated_at'],
    ]);
    exit;
}

// ─── cURL ────────────────────────────────────────────────────────────────────

function steamCurl(string $url): ?array {
    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING       => '',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Accept-Language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
                'Connection: keep-alive',
                'Sec-Fetch-Dest: empty',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Site: same-origin',
                'X-Requested-With: XMLHttpRequest',
            ],
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($code === 429) {
            sendEvent('progress', ['step' => "⏳ Rate-limit Steam, attente {$attempt}0s..."]);
            sleep($attempt * 10);
            continue;
        }
        if ($err || !$res) return null;
        return json_decode($res, true);
    }
    return null;
}

function getOwnedGames(string $steamId): array {
    $data = steamCurl("https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/?key=" . STEAM_API_KEY . "&steamid={$steamId}&include_appinfo=1");
    return $data['response']['games'] ?? [];
}

// Cache mémoire des résultats de sonde pour éviter les doublons
$_probeCache = [];

// Vérifie rapidement si un jeu a un inventaire accessible (1 seule requête)
function probeInventory(string $steamId, int $appId): ?int {
    global $_probeCache;
    if (isset($_probeCache[$appId])) return $_probeCache[$appId];

    foreach ([2, 1, 6] as $contextId) {
        $data = steamCurl("https://steamcommunity.com/inventory/{$steamId}/{$appId}/{$contextId}?l=english&count=1");
        if ($data && !empty($data['assets'])) {
            $_probeCache[$appId] = $contextId;
            return $contextId;
        }
        usleep(200000); // 200ms entre les tentatives
    }
    $_probeCache[$appId] = null;
    return null;
}

// Vérifie via l'API Steam Store si un jeu a des éléments échangeables (trading cards, market)
function hasSteamMarket(int $appId): bool {
    static $cache = [];
    if (isset($cache[$appId])) return $cache[$appId];

    $data = steamCurl("https://store.steampowered.com/api/appdetails?appids={$appId}&filters=categories");
    $categories = $data[$appId]['data']['categories'] ?? [];
    foreach ($categories as $cat) {
        // 29 = Trading Cards, 8 = Valve Anti-Cheat, 12 = Online Multi (souvent inventory)
        // On check surtout 29 (Trading Cards) et on inclut les jeux VAC car souvent ils ont un marché
        if (in_array($cat['id'], [29, 30])) { // 29=Trading Cards, 30=Steam Workshop
            $cache[$appId] = true;
            return true;
        }
    }
    $cache[$appId] = false;
    return false;
}

function fetchInventoryFull(string $steamId, int $appId, int $contextId): array {
    $allAssets = []; $allDesc = []; $lastAssetId = null; $page = 0;
    do {
        $url  = "https://steamcommunity.com/inventory/{$steamId}/{$appId}/{$contextId}?l=english&count=75";
        if ($lastAssetId) $url .= "&start_assetid={$lastAssetId}";
        $data = steamCurl($url);
        if (!$data || empty($data['assets'])) break;
        $allAssets = array_merge($allAssets, $data['assets']);
        foreach ($data['descriptions'] ?? [] as $d) {
            $allDesc[$d['classid'] . '_' . $d['instanceid']] = $d;
        }
        $more = $data['more_items'] ?? 0;
        $lastAssetId = $data['last_assetid'] ?? null;
        $page++;
        if ($more) sleep(2);
    } while ($more && $lastAssetId && $page < 100);
    return ['assets' => $allAssets, 'descriptions' => $allDesc];
}

// Cache en mémoire des prix par appid
$_priceCache = [];

// Charge TOUS les prix d'un jeu en une seule requête Steam Economy API
function loadBulkPrices(int $appId): bool {
    global $_priceCache;
    if (isset($_priceCache[$appId])) return true;

    $url  = "https://api.steampowered.com/ISteamEconomy/GetAssetPrices/v1/?appid={$appId}&currency=3&language=fr&key=" . STEAM_API_KEY;
    $data = steamCurl($url);
    $items = $data['result']['assets'] ?? null;

    if ($items === null) return false;

    $_priceCache[$appId] = [];
    foreach ($items as $item) {
        $name  = $item['name'] ?? null;
        // Prix en centimes → euros
        $price = isset($item['prices']['EUR']) ? (float)$item['prices']['EUR'] / 100 : 0.0;
        if ($name && $price > 0) {
            $_priceCache[$appId][$name] = $price;
        }
    }
    return true;
}

// Fallback : prix unitaire via Steam Market (pour les jeux sans bulk API)
function fetchMarketPriceSingle(int $appId, string $marketHashName): float {
    $url  = "https://steamcommunity.com/market/priceoverview/?appid={$appId}&currency=3&market_hash_name=" . urlencode($marketHashName);
    $data = steamCurl($url);
    if (!$data || empty($data['success'])) return 0.0;
    $raw = $data['lowest_price'] ?? $data['median_price'] ?? '';
    if (empty($raw)) return 0.0;
    $cleaned = preg_replace('/[^\d,\.]/', '', $raw);
    if (strpos($cleaned, '.') !== false && strpos($cleaned, ',') !== false) {
        $cleaned = str_replace('.', '', $cleaned);
        $cleaned = str_replace(',', '.', $cleaned);
    } elseif (strpos($cleaned, ',') !== false) {
        $cleaned = str_replace(',', '.', $cleaned);
    }
    return (float) $cleaned;
}

// Récupère le prix : bulk d'abord, fallback unitaire sinon
function fetchMarketPrice(int $appId, string $marketHashName): float {
    global $_priceCache;

    // TF2 (440) et CS2 (730) : on utilise le bulk pricing
    if (in_array($appId, [440, 730])) {
        if (!isset($_priceCache[$appId])) loadBulkPrices($appId);
        $price = $_priceCache[$appId][$marketHashName] ?? 0.0;
        if ($price > 0) return $price;
        // Si pas trouvé dans le bulk, fallback unitaire (items récents non indexés)
        usleep(300000);
        return fetchMarketPriceSingle($appId, $marketHashName);
    }

    // Autres jeux : fallback unitaire avec délai
    usleep(300000);
    return fetchMarketPriceSingle($appId, $marketHashName);
}

function processInventory(array $inv, int $appId): array {
    $itemCounts = [];
    foreach ($inv['assets'] as $asset) {
        $key = $asset['classid'] . '_' . $asset['instanceid'];
        $itemCounts[$key] = ($itemCounts[$key] ?? 0) + 1;
    }
    $gameTotal = 0.0; $itemCount = 0; $topItems = [];
    foreach ($itemCounts as $key => $count) {
        $desc = $inv['descriptions'][$key] ?? null;
        if (!$desc || empty($desc['marketable'])) continue;
        $marketHashName = $desc['market_hash_name'] ?? null;
        if (!$marketHashName) continue;
        // Pas de usleep pour TF2/CS2 car les prix sont en cache bulk
        $unitPrice = fetchMarketPrice($appId, $marketHashName);
        $lineTotal = round($unitPrice * $count, 2);
        $gameTotal += $lineTotal;
        $itemCount += $count;
        if ($unitPrice > 0) {
            $topItems[] = ['name' => $marketHashName, 'count' => $count, 'unit_price' => round($unitPrice, 2), 'total' => $lineTotal];
        }
    }
    usort($topItems, fn($a, $b) => $b['total'] <=> $a['total']);
    return ['gameTotal' => round($gameTotal, 2), 'itemCount' => $itemCount, 'topItems' => array_slice($topItems, 0, 5)];
}

// ─── Calcul ──────────────────────────────────────────────────────────────────

$results   = [];
$total     = 0.0;
$startTime = time();

// Étape 1 : Cartes Steam
sendEvent('progress', ['step' => '🃏 Récupération des cartes Steam...', 'game' => 'Steam', 'elapsed' => 0]);
sleep(1);

$allSteamAssets = []; $allSteamDesc = []; $lastAssetId = null; $page = 0;
do {
    $url  = "https://steamcommunity.com/inventory/{$steamId}/753/6?l=english&count=75";
    if ($lastAssetId) $url .= "&start_assetid={$lastAssetId}";
    $data = steamCurl($url);
    if (!$data || empty($data['assets'])) break;
    $allSteamAssets = array_merge($allSteamAssets, $data['assets']);
    foreach ($data['descriptions'] ?? [] as $d) {
        $allSteamDesc[$d['classid'] . '_' . $d['instanceid']] = $d;
    }
    $more = $data['more_items'] ?? 0; $lastAssetId = $data['last_assetid'] ?? null; $page++;
    sendEvent('progress', ['step' => "🃏 Cartes Steam — page {$page} (" . count($allSteamAssets) . " items)...", 'game' => 'Steam', 'elapsed' => time() - $startTime]);
    if ($more) sleep(2);
} while ($more && $lastAssetId && $page < 100);

if (!empty($allSteamAssets)) {
    $totalCards = count($allSteamAssets);
    sendEvent('progress', ['step' => "🃏 Calcul des prix Steam ({$totalCards} items)...", 'game' => 'Steam', 'elapsed' => time() - $startTime]);
    $res = processInventory(['assets' => $allSteamAssets, 'descriptions' => $allSteamDesc], 753);
    if ($res['gameTotal'] > 0) {
        $results['Steam (Cartes & Items)'] = ['appid' => 753, 'icon' => '', 'steam_icon' => '🃏', 'items' => $res['itemCount'], 'total' => $res['gameTotal'], 'top_items' => $res['topItems']];
        $total += $res['gameTotal'];
        sendEvent('progress', ['step' => "✅ Steam : {$res['gameTotal']}€", 'game' => 'Steam', 'found' => true, 'value' => $res['gameTotal'], 'elapsed' => time() - $startTime]);
    }
}

// Étape 2 : Jeux possédés
sendEvent('progress', ['step' => '📋 Récupération de la liste des jeux...', 'elapsed' => time() - $startTime]);
$ownedGames = getOwnedGames($steamId);
$totalGames = count($ownedGames);

// Map appid => nom
$gameNameMap = [];
foreach ($ownedGames as $g) $gameNameMap[(string)$g['appid']] = $g['name'] ?? null;

// Nettoie les noms des cartes
if (isset($results['Steam (Cartes & Items)'])) {
    foreach ($results['Steam (Cartes & Items)']['top_items'] as &$item) {
        if (preg_match('/^(\d+)-(.+)$/', $item['name'], $m)) {
            $gName = $gameNameMap[$m[1]] ?? null;
            $item['name'] = $gName ? "{$m[2]} ({$gName})" : $m[2];
        }
    }
    unset($item);
}

sendEvent('progress', ['step' => "📋 {$totalGames} jeux trouvés, analyse des inventaires...", 'elapsed' => time() - $startTime]);

$knownInventoryGames = [440, 730, 570, 252490, 304930, 322330, 578080, 433850, 230410, 221100, 346110, 4000, 8930, 255710, 431960, 236390, 1172620, 548430, 881100, 431240, 1091500, 945360, 1182900, 1097150, 1599340, 271590, 252950, 1222730, 1604030, 1085660, 39210, 374320, 1203220, 1290080];

$checked = 0;
$gamesWithInventory = 0;

foreach ($ownedGames as $idx => $game) {
    $appId    = (int) $game['appid'];
    $gameName = $game['name'] ?? "App {$appId}";
    $playtime = $game['playtime_forever'] ?? 0;
    $isKnown  = in_array($appId, $knownInventoryGames);
    $hasTime  = $playtime > 60;

    if (!$isKnown && !$hasTime) continue;

    $checked++;
    $elapsed = time() - $startTime;

    sendEvent('progress', [
        'step'    => "🔍 [{$checked}] {$gameName}...",
        'game'    => $gameName,
        'elapsed' => $elapsed,
        'current' => $checked,
    ]);

    sleep(1);

    // Étape 1 : Vérifie via le Store Steam si le jeu a des trading cards / workshop
    // (évite les requêtes inutiles pour les jeux sans marché)
    $isKnownGame = in_array($appId, $knownInventoryGames);
    if (!$isKnownGame && !hasSteamMarket($appId)) {
        sendEvent('progress', ['step' => "⏭ [{$checked}] {$gameName} — pas de marché Steam", 'game' => $gameName, 'elapsed' => time() - $startTime]);
        continue;
    }

    // Étape 2 : Sonde rapide (1 item) pour voir si l'inventaire existe
    $contextId = probeInventory($steamId, $appId);
    if ($contextId === null) {
        sendEvent('progress', ['step' => "⏭ [{$checked}] {$gameName} — inventaire vide/privé", 'game' => $gameName, 'elapsed' => time() - $startTime]);
        continue;
    }

    // Étape 3 : Précharge les prix en bulk pour TF2/CS2 (1 requête = tous les prix)
    if (in_array($appId, [440, 730]) && !isset($GLOBALS['_priceCache'][$appId])) {
        sendEvent('progress', ['step' => "📦 Chargement des prix {$gameName} en masse...", 'game' => $gameName, 'elapsed' => time() - $startTime]);
        loadBulkPrices($appId);
        $loaded = count($GLOBALS['_priceCache'][$appId] ?? []);
        sendEvent('progress', ['step' => "📦 {$loaded} prix {$gameName} chargés en 1 requête ✅", 'game' => $gameName, 'elapsed' => time() - $startTime]);
    }

    // Étape 4 : Fetch complet seulement si la sonde a réussi
    $inv = fetchInventoryFull($steamId, $appId, $contextId);

    if (empty($inv['assets'])) continue;

    $assetCount = count($inv['assets']);
    sendEvent('progress', [
        'step'    => "💰 [{$checked}] {$gameName} — {$assetCount} items, calcul des prix...",
        'game'    => $gameName,
        'elapsed' => time() - $startTime,
        'current' => $checked,
    ]);

    $res = processInventory($inv, $appId);

    if ($res['gameTotal'] <= 0) continue;

    $gamesWithInventory++;
    $total += $res['gameTotal'];

    $results[$gameName] = ['appid' => $appId, 'icon' => $game['img_icon_url'] ?? '', 'items' => $res['itemCount'], 'total' => $res['gameTotal'], 'top_items' => $res['topItems']];

    sendEvent('progress', [
        'step'    => "✅ [{$checked}] {$gameName} : {$res['gameTotal']}€",
        'game'    => $gameName,
        'found'   => true,
        'value'   => $res['gameTotal'],
        'running_total' => round($total, 2),
        'elapsed' => time() - $startTime,
    ]);
}

$total = round($total, 2);
$now   = date('Y-m-d H:i:s');

// Sauvegarde
try {
    $stmt = $db->prepare('UPDATE users SET inventory_value = :value, inventory_details = :details, inventory_updated_at = :updated WHERE steam_id = :steam_id');
    $stmt->execute([':value' => $total, ':details' => json_encode($results), ':updated' => $now, ':steam_id' => $steamId]);
} catch (Exception $e) {
    error_log($e->getMessage());
}

sendEvent('done', [
    'cached'  => false,
    'total'   => $total,
    'details' => $results,
    'updated' => $now,
    'elapsed' => time() - $startTime,
]);