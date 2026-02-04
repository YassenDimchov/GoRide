<?php


$driver = null;

try {
    $stmt = $pdo->prepare("SELECT id, vehicle_make, vehicle_model, license_plate, status
                           FROM drivers
                           WHERE user_id = ?
                           LIMIT 1");
    $stmt->execute([$user['id']]);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {
    $driver = null;
}
