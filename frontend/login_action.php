<?php
session_start();

$API_BASE = 'http://127.0.0.1:8000/api';

$email    = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');
$remember = isset($_POST['remember']);

$_SESSION['old'] = ['email' => $email];

$errors = [];
if ($email === '')    $errors['email'] = 'Email is required.';
if ($password === '') $errors['password'] = 'Password is required.';

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header('Location: login.php');
    exit;
}

$payload = json_encode([
    'email' => $email,
    'password' => $password,
], JSON_UNESCAPED_UNICODE);

$ch = curl_init($API_BASE . '/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json',
    ],
]);

$response = curl_exec($ch);
$status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
file_put_contents(__DIR__ . '/_debug_login.txt',
  "STATUS: $status\n\nRESPONSE:\n$response"
);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false) {
    $_SESSION['errors'] = ['general' => 'Connection error: ' . $curlErr];
    header('Location: login.php');
    exit;
}

$data = json_decode($response, true);

if (!is_array($data)) {
    $_SESSION['errors'] = ['general' => 'Server error.'];
    header('Location: login.php');
    exit;
}

if ($status === 200 && !empty($data['token'])) {
    $_SESSION['token'] = $data['token'];

    if ($remember) {
        setcookie('goride_token', $data['token'], time() + 60 * 60 * 24 * 30, '/');
    }

    header('Location: index.php');
    exit;
}

if ($status === 422 && !empty($data['errors']) && is_array($data['errors'])) {
    $mapped = [];
    foreach ($data['errors'] as $field => $messages) {
        if (!empty($messages[0])) $mapped[$field] = $messages[0];
    }
    $_SESSION['errors'] = $mapped ?: ['general' => 'Invalid input.'];
    header('Location: login.php');
    exit;
}

$_SESSION['errors'] = ['general' => $data['message'] ?? 'Login failed.'];
header('Location: login.php');
exit;
