<?php
// api/inventory_value.php
set_time_limit(300);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

define('STEAM_API_KEY', 'F623F5D1BDD4666094D118E33CEA2ED2');
define('CACHE_HOURS', 24);

const SUPPORTED_GAMES = [
    730 => ['name' => 'CS2',    'icon' => '🔫', 'context' => 2],
    440 => ['name' => 'TF2',    'icon' => '🎩', 'context' => 2],
    570 => ['name' => 'Dota 2', 'icon' => '⚔️', 'context' => 2],
];

$steamId = $_GET['steam_id'] ?? null;
$debug   = !empty($_GET['debug']);

if (!$steamId) { echo json_encode(['error' => 'Steam ID requis']); exit; }

if ($debug) {
    header('Content-Type: text/plain');
    echo "=== DEBUG INVENTORY API ===\n";
    echo "SteamID: $steamId\n";
    echo "Force: " . (!empty($_GET['force']) ? 'oui' : 'non') . "\n\n";
}

$db = getDB();

// Vérifie le cache
$stmt = $db->prepare('SELECT inventory_value, inventory_details, inventory_updated_at FROM users WHERE steam_id = :steam_id LIMIT 1');
$stmt->execute([':steam_id' => $steamId]);
$user = $stmt->fetch();

if ($debug) echo "Cache DB: value=" . ($user['inventory_value'] ?? 'null') . " updated=" . ($user['inventory_updated_at'] ?? 'null') . "\n\n";

if (
    !$debug &&
    $user &&
    $user['inventory_value'] !== null &&
    !empty($user['inventory_updated_at']) &&
    strtotime($user['inventory_updated_at']) > time() - (CACHE_HOURS * 3600) &&
    empty($_GET['force'])
) {
    echo json_encode([
        'cached'  => true,
        'total'   => (float) $user['inventory_value'],
        'details' => json_decode($user['inventory_details'], true),
        'updated' => $user['inventory_updated_at'],
    ]);
    exit;
}

// ─── cURL avec headers navigateur ───────────────────────────────────────────

function steamCurl(string $url): ?array {
    global $debug;

    $maxRetries = 3;
    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
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
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($debug) {
            echo "  [Tentative $attempt] HTTP $code | " . strlen($res ?: '') . " octets | " . ($err ?: 'OK') . "\n";
            echo "  Début: " . substr($res ?: 'VIDE', 0, 100) . "\n";
        }

        // 429 = rate-limit → attend et réessaie
        if ($code === 429) {
            $wait = $attempt * 10; // 10s, 20s, 30s
            if ($debug) echo "  Rate-limit 429 → attente {$wait}s...\n";
            sleep($wait);
            continue;
        }

        if ($err || !$res) return null;
        $decoded = json_decode($res, true);
        if ($debug && $decoded === null) echo "  JSON error: " . json_last_error_msg() . "\n";
        return $decoded;
    }

    if ($debug) echo "  Échec après $maxRetries tentatives\n";
    return null;
}

function fetchInventoryFull(string $steamId, int $appId, int $contextId): array {
    global $debug;
    $allAssets = [];
    $allDesc   = [];
    $lastAssetId = null;
    $page = 0;

    do {
        $url  = "https://steamcommunity.com/inventory/{$steamId}/{$appId}/{$contextId}?l=english&count=75";
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

        if ($debug) echo "  Page $page: " . count($data['assets']) . " assets | more: $more\n";

        if ($more) sleep(2); // 2 secondes entre les pages pour éviter le rate-limit

    } while (!empty($data['more_items']) && $lastAssetId && $page < 50);

    return ['assets' => $allAssets, 'descriptions' => $allDesc];
}

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

// ─── Calcul ──────────────────────────────────────────────────────────────────

$results = [];
$total   = 0.0;

foreach (SUPPORTED_GAMES as $appId => $gameInfo) {
    if ($debug) echo "=== {$gameInfo['name']} (appid $appId) ===\n";

    // Pause entre chaque jeu pour éviter le rate-limit Steam
    if (!empty($results)) sleep(5);

    $inv = fetchInventoryFull($steamId, $appId, $gameInfo['context']);

    if ($debug) echo "Assets: " . count($inv['assets']) . " | Descriptions: " . count($inv['descriptions']) . "\n";

    if (empty($inv['assets'])) {
        if ($debug) echo "→ Inventaire vide ou inaccessible\n\n";
        continue;
    }

    // Regroupe les assets identiques
    $itemCounts = [];
    foreach ($inv['assets'] as $asset) {
        $key = $asset['classid'] . '_' . $asset['instanceid'];
        $itemCounts[$key] = ($itemCounts[$key] ?? 0) + 1;
    }

    if ($debug) echo "Items uniques: " . count($itemCounts) . "\n";

    $gameTotal     = 0.0;
    $itemCount     = 0;
    $topItems      = [];
    $marketable    = 0;
    $nonMarketable = 0;

    foreach ($itemCounts as $key => $count) {
        $desc = $inv['descriptions'][$key] ?? null;
        if (!$desc) continue;
        if (empty($desc['marketable'])) { $nonMarketable++; continue; }
        $marketable++;

        $marketHashName = $desc['market_hash_name'] ?? null;
        if (!$marketHashName) continue;

        usleep(300000);

        $unitPrice  = fetchMarketPrice($appId, $marketHashName);
        $lineTotal  = round($unitPrice * $count, 2);
        $gameTotal += $lineTotal;
        $itemCount += $count;

        if ($debug && $marketable <= 3) echo "  Item: $marketHashName | Prix: {$unitPrice}€ x{$count} = {$lineTotal}€\n";

        if ($unitPrice > 0) {
            $topItems[] = [
                'name'       => $marketHashName,
                'count'      => $count,
                'unit_price' => round($unitPrice, 2),
                'total'      => $lineTotal,
            ];
        }
    }

    if ($debug) echo "Vendables: $marketable | Non-vendables: $nonMarketable | Total: {$gameTotal}€\n\n";

    usort($topItems, fn($a, $b) => $b['total'] <=> $a['total']);

    $results[$gameInfo['name']] = [
        'icon'      => $gameInfo['icon'],
        'items'     => $itemCount,
        'total'     => round($gameTotal, 2),
        'top_items' => array_slice($topItems, 0, 5),
    ];

    $total += $gameTotal;
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

echo json_encode(['cached' => false, 'total' => $total, 'details' => $results, 'updated' => $now]);