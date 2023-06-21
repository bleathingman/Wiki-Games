<?php

$request = $_SERVER['REQUEST_URI'];

$request = str_replace('/wiki-games', '', $request);

switch ($request) {
    case '':
        require __DIR__ . '/index.php';
        break;
    case '/index.php':
        require __DIR__ . '/index.php';
        break;
    case '/tags':
        require __DIR__ . '/pages/page_tags.php';
        break;
    case '/options':
        require __DIR__ . '/pages/page_options.php';
        break;
    case '/add_games':
        require __DIR__ . '/formulaires/add_games.php';
        break;
    case '/admin':
        require __DIR__ . '/admin/index.php';
        break;
    case '/admin/login':
        require __DIR__ . '/formulaires/login.php';
        break;
    case '/admin/register':
        require __DIR__ . '/formulaires/register.php';
        break;
    default:
        http_response_code(404);
        require __DIR__ . '/404.php';
        break;
}
