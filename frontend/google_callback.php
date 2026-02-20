<?php
session_start();

$token = trim((string)($_GET['token'] ?? ''));
$error = trim((string)($_GET['error'] ?? ''));

if ($token !== '') {
    $_SESSION['token'] = $token;
    setcookie('goride_token', $token, time() + 60 * 60 * 24 * 30, '/');
    header('Location: index.php');
    exit;
}

if ($error !== '') {
    $_SESSION['errors'] = ['general' => $error];
}

header('Location: login.php');
exit;

