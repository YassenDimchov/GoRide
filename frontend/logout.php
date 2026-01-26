<?php
session_start();
require_once __DIR__ . '/includes/auth.php';

$token = $_SESSION['token'] ?? $_COOKIE['goride_token'] ?? null;

if ($token) {
    apiLogout($token);
}

$_SESSION = [];
session_destroy();

setcookie('goride_token', '', time() - 3600, '/', '', false, true);

header('Location: login.php');
exit;
