<?php
session_start();

require_once __DIR__ . '/includes/auth.php';

$token = $_SESSION['token'] ?? $_COOKIE['goride_token'] ?? null;
if (!$token) {
    header('Location: login.php');
    exit;
}

$name  = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if ($name === '') {
    $_SESSION['flash_error'] = 'Name is required.';
    header('Location: profile.php');
    exit;
}

$currentUser = apiMe($token);

if (!$currentUser) {
    session_destroy();
    setcookie('goride_token', '', time() - 3600, '/');
    header('Location: login.php');
    exit;
}

$oldName  = (string)($currentUser['name'] ?? '');
$oldPhone = (string)($currentUser['phone'] ?? '');

if ($name === $oldName && $phone === $oldPhone) {
    header('Location: profile.php');
    exit;
}

$res = apiUpdateMe($token, [
    'name'  => $name,
    'phone' => $phone !== '' ? $phone : null,
]);

if (!$res || empty($res['user'])) {
    $_SESSION['flash_error'] = 'Could not update profile. Please try again.';
    header('Location: profile.php');
    exit;
}

$_SESSION['user'] = $res['user'];

$_SESSION['flash_success'] = 'Profile updated successfully.';
header('Location: profile.php');
exit;
