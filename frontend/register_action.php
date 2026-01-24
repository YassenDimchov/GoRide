<?php
session_start();

$API_BASE = 'http://127.0.0.1:8000/api';

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['password_confirm'] ?? '';
$terms    = isset($_POST['terms']);

$_SESSION['old'] = [
    'name'  => $name,
    'email' => $email,
    'phone' => $phone,
    'terms' => $terms ? 1 : 0,
];

$errors = [];

if ($name === '') $errors['name'] = 'Full name is required.';
if ($email === '') $errors['email'] = 'Email is required.';
if ($password === '') $errors['password'] = 'Password is required.';
if ($password !== $confirm) $errors['password_confirm'] = 'Passwords do not match.';
if (!$terms) $errors['terms'] = 'You must accept the terms.';
if (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters.';
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header('Location: register.php');
    exit;
}

$payload = json_encode([
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'password' => $password,
    'password_confirmation' => $confirm,
]);


$ch = curl_init($API_BASE . '/register');
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
$status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($status === 201 && isset($data['token'])) {
    $_SESSION['token'] = $data['token'];
    header('Location: index.php');
    exit;
}

if ($status === 422 && isset($data['errors'])) {
    $_SESSION['errors'] = array_map(fn($v) => $v[0], $data['errors']);
    header('Location: register.php');
    exit;
}

$_SESSION['errors'] = [
    'general' => $data['message'] ?? 'Registration failed.',
];
header('Location: register.php');
exit;
