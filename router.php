<?php

$request = $_SERVER['REQUEST_URI'];

// Remove subdirectory if used, adjust accordingly
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
    default:
        http_response_code(404);
        require __DIR__ . '/404.php';
        break;
}
