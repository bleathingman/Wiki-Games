<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

session_destroy();
header('Location: ' . APP_URL . '/login.php');
exit;
