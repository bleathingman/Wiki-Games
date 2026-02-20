<?php
// api/inventory_value.php
set_time_limit(600); // 10 minutes max

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

define('STEAM_API_KEY', 'F623F5D1BDD4666094D118E33CEA2ED2');
define('CACHE_HOURS', 24);

$steamId = $_GET['steam_id'] ?? null;
$debug   = !empty($_GET['debug']);
$force   = !empty($_GET['force']);

if (!$steamId) { echo json_encode(['error' => 'Steam ID requis']); exit; }
if ($debug) { header('Content-Type: text/plain'); echo "=== DEBUG INVENTORY API ===\nSteamID: $steamId\n\n"; }

$db = getDB();

// Vérifie le cache
$stmt = $db->prepare('SELECT inventory_value, inventory_details, inventory_updated_at FROM users WHERE steam_id = :steam_id LIMIT 1');
$stmt->execute([':steam_id' => $steamId]);
$user = $stmt->fetch();

if ($debug) echo "Cache: value=" . ($user['inventory_value'] ?? 'null') . " updated=" . ($user['inventory_updated_at'] ?? 'null') . "\n\n";

if (
    !$debug && !$force &&
    $user &&
    $user['inventory_value'] !== null &&
    !empty($user['inventory_updated_at']) &&
    strtotime($user['inventory_updated_at']) > time() - (CACHE_HOURS * 3600)
) {
    echo json_encode(['cached' => true, 'total' => (float) $user['inventory_value'], 'details' => json_decode($user['inventory_details'], true), 'updated' => $user['inventory_updated_at']]);
    exit;
}

// ─── cURL avec retry 429 ─────────────────────────────────────────────────────

function steamCurl(string $url): ?array {
    global $debug;
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
            $wait = $attempt * 10;
            if ($debug) echo "  429 rate-limit → attente {$wait}s\n";
            sleep($wait);
            continue;
        }
        if ($err || !$res) return null;
        return json_decode($res, true);
    }
    return null;
}

// ─── Récupère tous les jeux possédés ─────────────────────────────────────────

function getOwnedGames(string $steamId): array {
    $data = steamCurl("https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/?key=" . STEAM_API_KEY . "&steamid={$steamId}&include_appinfo=1");
    return $data['response']['games'] ?? [];
}

// ─── Récupère l'inventaire complet avec pagination ───────────────────────────

function fetchInventoryFull(string $steamId, int $appId): array {
    global $debug;
    $allAssets   = [];
    $allDesc     = [];
    $lastAssetId = null;
    $page        = 0;

    do {
        $url = "https://steamcommunity.com/inventory/{$steamId}/{$appId}/2?l=english&count=75";
        if ($lastAssetId) $url .= "&start_assetid={$lastAssetId}";

        $data = steamCurl($url);
        if (!$data || empty($data['assets'])) break;

        $allAssets = array_merge($allAssets, $data['assets']);
        foreach ($data['descriptions'] ?? [] as $d) {
            $allDesc[$d['classid'] . '_' . $d['instanceid']] = $d;
        }

        $more        = $data['more_items'] ?? 0;
        $lastAssetId = $data['last_assetid'] ?? null;
        $page++;

        if ($debug) echo "  Page {$page}: " . count($data['assets']) . " assets | more: {$more}\n";
        if ($more) sleep(2);

    } while (!empty($data['more_items']) && $lastAssetId && $page < 100);

    return ['assets' => $allAssets, 'descriptions' => $allDesc];
}

// ─── Prix du marché ──────────────────────────────────────────────────────────

function fetchMarketPrice(int $appId, string $marketHashName): float {
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

// ─── Calcul principal ────────────────────────────────────────────────────────

// Récupère la liste de tous les jeux possédés
if ($debug) echo "=== Récupération des jeux possédés ===\n";
$ownedGames = getOwnedGames($steamId);
if ($debug) echo count($ownedGames) . " jeux trouvés\n\n";

// Jeux connus pour avoir des inventaires (contextid 2)
// On essaie d'abord les jeux connus, puis les autres
$knownInventoryGames = [440, 730, 570, 252490, 304930, 322330, 578080, 433850, 230410, 221100, 346110, 4000, 8930, 255710];

$results = [];
$total   = 0.0;
$checked = 0;

foreach ($ownedGames as $game) {
    $appId    = (int) $game['appid'];
    $gameName = $game['name'] ?? "App {$appId}";

    // Saute les jeux sans items market connus
    // On essaie uniquement les jeux qui ont un workshop/market (heuristique : has_community_visible_stats)
    // Pour limiter le temps, on ne tente que les jeux connus + ceux avec beaucoup d'heures
    $playtime = $game['playtime_forever'] ?? 0;
    $isKnown  = in_array($appId, $knownInventoryGames);
    $hasTime  = $playtime > 60; // Plus d'1h de jeu

    if (!$isKnown && !$hasTime) continue;

    if ($debug) echo "=== {$gameName} (appid {$appId}) ===\n";

    sleep(1); // 1 seconde entre chaque jeu

    $inv = fetchInventoryFull($steamId, $appId);

    if (empty($inv['assets'])) {
        if ($debug) echo "  → Pas d'inventaire\n\n";
        continue;
    }

    // Regroupe les assets identiques
    $itemCounts = [];
    foreach ($inv['assets'] as $asset) {
        $key = $asset['classid'] . '_' . $asset['instanceid'];
        $itemCounts[$key] = ($itemCounts[$key] ?? 0) + 1;
    }

    $gameTotal  = 0.0;
    $itemCount  = 0;
    $topItems   = [];
    $marketable = 0;

    foreach ($itemCounts as $key => $count) {
        $desc = $inv['descriptions'][$key] ?? null;
        if (!$desc || empty($desc['marketable'])) continue;
        $marketable++;

        $marketHashName = $desc['market_hash_name'] ?? null;
        if (!$marketHashName) continue;

        usleep(300000); // 300ms entre chaque prix

        $unitPrice  = fetchMarketPrice($appId, $marketHashName);
        $lineTotal  = round($unitPrice * $count, 2);
        $gameTotal += $lineTotal;
        $itemCount += $count;

        if ($unitPrice > 0) {
            $topItems[] = [
                'name'       => $marketHashName,
                'count'      => $count,
                'unit_price' => round($unitPrice, 2),
                'total'      => $lineTotal,
            ];
        }
    }

    if ($debug) echo "  Vendables: {$marketable} | Total: {$gameTotal}€\n\n";

    // N'ajoute le jeu que s'il a de la valeur
    if ($gameTotal <= 0) continue;

    usort($topItems, fn($a, $b) => $b['total'] <=> $a['total']);

    $results[$gameName] = [
        'appid'     => $appId,
        'icon'      => $game['img_icon_url'] ?? '',
        'items'     => $itemCount,
        'total'     => round($gameTotal, 2),
        'top_items' => array_slice($topItems, 0, 5),
    ];

    $total += $gameTotal;
    $checked++;
}

$total = round($total, 2);
$now   = date('Y-m-d H:i:s');

// Sauvegarde cache
try {
    $stmt = $db->prepare('UPDATE users SET inventory_value = :value, inventory_details = :details, inventory_updated_at = :updated WHERE steam_id = :steam_id');
    $stmt->execute([':value' => $total, ':details' => json_encode($results), ':updated' => $now, ':steam_id' => $steamId]);
} catch (Exception $e) {
    error_log('Inventory cache error: ' . $e->getMessage());
}

if (!$debug) {
    echo json_encode(['cached' => false, 'total' => $total, 'details' => $results, 'updated' => $now]);
} else {
    echo "\n=== RÉSULTAT FINAL ===\n";
    echo "Total: {$total}€\n";
    echo "Jeux avec valeur: " . count($results) . "\n";
    echo json_encode(['cached' => false, 'total' => $total, 'details' => $results, 'updated' => $now]);
}