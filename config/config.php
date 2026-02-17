<?php
// config/config.php

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
if (!defined('APP_URL'))   define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/wiki-games');
if (!defined('APP_SECRET'))define('APP_SECRET', $_ENV['APP_SECRET'] ?? 'fallback_secret_change_me');
if (!defined('UPLOAD_DIR'))define('UPLOAD_DIR', ROOT_PATH . '/uploads/');
if (!defined('UPLOAD_URL'))define('UPLOAD_URL', APP_URL . '/uploads/');
if (!defined('GAMES_PER_PAGE'))   define('GAMES_PER_PAGE', 12);
if (!defined('ALLOWED_IMAGE_TYPES')) define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
if (!defined('MAX_FILE_SIZE'))    define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// Session security settings (only before session starts)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}
