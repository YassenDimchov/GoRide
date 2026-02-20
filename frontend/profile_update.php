<?php
session_start();

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/roles.php';

$token = $_SESSION['token'] ?? $_COOKIE['goride_token'] ?? null;
if (!$token) {
    header('Location: login.php');
    exit;
}

$name  = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');

$vehicleMake  = trim($_POST['vehicle_make'] ?? '');
$vehicleModel = trim($_POST['vehicle_model'] ?? '');
$vehicleColor = trim($_POST['vehicle_color'] ?? '');
$licensePlate = trim($_POST['license_plate'] ?? '');

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

$didUpdate = false;
$errors = [];

$oldName  = (string)($currentUser['name'] ?? '');
$oldPhone = (string)($currentUser['phone'] ?? '');

$userChanged = !($name === $oldName && $phone === $oldPhone);

if ($userChanged) {
    $resUser = apiUpdateMe($token, [
        'name'  => $name,
        'phone' => $phone !== '' ? $phone : null,
    ]);

    if (!$resUser || !empty($resUser['_error']) || empty($resUser['user'])) {
        $errors[] = 'Could not update profile info.';
    } else {
        $_SESSION['user'] = $resUser['user'];
        $didUpdate = true;
    }
}

$isDriver = (($currentUser['role'] ?? null) === 'driver');

if ($isDriver) {
    $currentDriver = apiDriverMe($token);

    if ($currentDriver) {
        $oldMake  = (string)($currentDriver['vehicle_make'] ?? '');
        $oldModel = (string)($currentDriver['vehicle_model'] ?? '');
        $oldColor = (string)($currentDriver['vehicle_color'] ?? '');
        $oldPlate = (string)($currentDriver['license_plate'] ?? '');

        $driverChanged = !(
            $vehicleMake === $oldMake &&
            $vehicleModel === $oldModel &&
            $vehicleColor === $oldColor &&
            $licensePlate === $oldPlate
        );

        if ($driverChanged) {
            $payload = [
                'vehicle_make'  => $vehicleMake !== '' ? $vehicleMake : null,
                'vehicle_model' => $vehicleModel !== '' ? $vehicleModel : null,
                'vehicle_color' => $vehicleColor !== '' ? $vehicleColor : null,
                'license_plate' => $licensePlate !== '' ? $licensePlate : null,
            ];

            $resDriver = apiUpdateDriverMe($token, $payload);

            if (empty($resDriver['ok'])) {
                $errors[] = 'Could not update vehicle information: ' . ($resDriver['error'] ?? 'Unknown error');
            } else {
                $_SESSION['driver'] = $resDriver['driver'];
                $didUpdate = true;
            }
        }
    } else {
        $errors[] = 'Driver profile could not be loaded for updating.';
    }
}


if (!empty($errors)) {
    if (!$didUpdate) {
        $_SESSION['flash_error'] = implode(' ', $errors);
        header('Location: profile.php');
        exit;
    }

    $_SESSION['flash_success'] = 'Saved, but some changes could not be applied. ' . implode(' ', $errors);
    header('Location: profile.php');
    exit;
}

if (!$didUpdate) {
    header('Location: profile.php');
    exit;
}

$_SESSION['flash_success'] = 'Profile updated successfully.';
header('Location: profile.php');
exit;
