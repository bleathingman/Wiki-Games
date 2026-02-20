<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

define('STEAM_API_KEY', 'F623F5D1BDD4666094D118E33CEA2ED2');

// PHP convertit les points en underscores dans $_GET
// On doit parser la query string manuellement pour garder les points
function getOpenIDParams() {
    $params = [];
    $queryString = $_SERVER['QUERY_STRING'];
    $pairs = explode('&', $queryString);
    foreach ($pairs as $pair) {
        $parts = explode('=', $pair, 2);
        if (count($parts) === 2) {
            $key = urldecode($parts[0]);
            $value = urldecode($parts[1]);
            $params[$key] = $value;
        }
    }
    return $params;
}

// Vérifie la validité de la réponse Steam via OpenID
function validateSteamLogin($params) {
    $params['openid.mode'] = 'check_authentication';

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://steamcommunity.com/openid/login');
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($curl);
    curl_close($curl);

    return strpos($response, 'is_valid:true') !== false;
}

$params = getOpenIDParams();

// Vérification de la réponse Steam
if (!isset($params['openid.claimed_id']) || !validateSteamLogin($params)) {
    header('Location: ' . APP_URL . '/login.php?error=steam_invalid');
    exit;
}

// Extrait le SteamID64
preg_match('/(\d{17})/', $params['openid.claimed_id'], $matches);
$steamId = $matches[1] ?? null;

if (!$steamId) {
    header('Location: ' . APP_URL . '/login.php?error=steam_id');
    exit;
}

// Récupère les infos du profil Steam
$url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key=" . STEAM_API_KEY . "&steamids={$steamId}";
$data = json_decode(file_get_contents($url), true);
$player = $data['response']['players'][0] ?? null;

if (!$player) {
    header('Location: ' . APP_URL . '/login.php?error=steam_profile');
    exit;
}

$steamUsername = $player['personaname'];
$steamAvatar   = $player['avatarfull'];

// Vérifie si cet utilisateur Steam existe déjà en base
$db   = getDB();
$stmt = $db->prepare('SELECT * FROM users WHERE steam_id = :steam_id LIMIT 1');
$stmt->execute([':steam_id' => $steamId]);
$user = $stmt->fetch();

if ($user) {
    // Utilisateur Steam déjà connu → connexion directe
    session_regenerate_id(true);
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['role']         = $user['role'];
    $_SESSION['steam_id']     = $steamId;
    $_SESSION['steam_avatar'] = $steamAvatar;

    header('Location: ' . APP_URL . '/index.php');
    exit;
} else {
    // Nouvel utilisateur Steam → on crée un compte automatiquement
    $uniqueUsername = $steamUsername; // ex: steam_123456

    // Vérifie que le username n'existe pas déjà
    $stmt = $db->prepare('SELECT id FROM users WHERE username = :username');
    $stmt->execute([':username' => $uniqueUsername]);
    if ($stmt->fetch()) {
        $uniqueUsername .= '_' . rand(10, 99);
    }

    $stmt = $db->prepare('
        INSERT INTO users (username, email, password, steam_id, steam_avatar, role, created_at)
        VALUES (:username, :email, :password, :steam_id, :steam_avatar, :role, NOW())
    ');
    $stmt->execute([
        ':username'     => $uniqueUsername,
        ':email'        => $steamId . '@steam.local', // placeholder
        ':password'     => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
        ':steam_id'     => $steamId,
        ':steam_avatar' => $steamAvatar,
        ':role'         => 'user',
    ]);

    $newId = $db->lastInsertId();

    session_regenerate_id(true);
    $_SESSION['user_id']      = $newId;
    $_SESSION['username']     = $uniqueUsername;
    $_SESSION['role']         = 'user';
    $_SESSION['steam_id']     = $steamId;
    $_SESSION['steam_avatar'] = $steamAvatar;

    header('Location: ' . APP_URL . '/index.php');
    exit;
}