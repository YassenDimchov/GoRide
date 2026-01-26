<?php
    if (session_status() === PHP_SESSION_NONE) session_start();

    require_once __DIR__ . '/auth.php';

    $token = $_SESSION['token'] ?? $_COOKIE['goride_token'] ?? null;

    if (!$token) {
        header('Location: login.php');
        exit;
    }

    $user = apiMe($token);

    if (!$user) {
        session_destroy();
        setcookie('goride_token', '', time() - 3600, '/');
        header('Location: login.php');
        exit;
    }

    $initials = userInitials($user['name']);
?>