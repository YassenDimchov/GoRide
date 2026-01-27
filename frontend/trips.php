<?php 
require_once __DIR__ . '/includes/guard.php';
require_once __DIR__ . '/includes/trips_data.php';

date_default_timezone_set('Europe/Sofia');
$rides = getTripHistory($token);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css"/>
    <link rel="stylesheet" href="assets/css/trips.css"/>
</head>
<body>
    <?php include __DIR__ . '/components/navbar.php'; ?>
    <?php include __DIR__ . '/components/sidebar.php'; ?>
    
    <main class="app-shell trips-shell">
        <div class="trips-wrap">
            <section class="card">
                <div class="card-title">Trip Histroy</div>

                <?php if (empty($rides)): ?>
                    <div class="empty-state">No completed trips yet.</div>
                <?php else: ?>
                    <div class="trip-list">
                        <?php foreach ($rides as $r): ?>
                            <?php
                                $dt = $r['completed_at'] ?? $r['created_at'];

                                $pickup  = $r['start_address'] ?? '—';
                                $dropoff = $r['end_address'] ?? '—';
                                $price   = $r['fare'] ?? null;

                                $vehicleMake  = $r['driver']['vehicle_make'] ?? null;
                                $vehicleModel = $r['driver']['vehicle_model'] ?? null;
                                $plate        = $r['driver']['license_plate'] ?? null;

                                $driverName = $r['driver']['user']['name'] ?? null;

                                $rating = $r['review']['rating'] ?? null;
                            ?>
                            <article class="trip-card">
                                <div class="trip-left">

                                    <div class="trip-top">
                                        <div class="trip-date">
                                            <img src="./assets/images/Icons/calendar.svg" class="icon16" alt="">
                                            <span><?= htmlspecialchars(fmtDateTime($dt)) ?></span>
                                        </div>

                                        <?php if ($vehicleMake || $vehicleModel || $plate): ?>
                                            <span class="pill pill-gray">
                                                <?= htmlspecialchars(trim(($vehicleMake ?? '') . ' ' . ($vehicleModel ?? ''))) ?>
                                                <?= $plate ? ' · ' . htmlspecialchars($plate) : '' ?>
                                            </span>
                                        <?php endif; ?>

                                    </div>

                                    <div class="trip-route">
                                        <div class="route-row">
                                            <span class="dot dot-green"></span>
                                            <span class="route-text"><?= htmlspecialchars($pickup) ?></span>
                                        </div>
                                        <div class="route-row">
                                            <span class="dot dot-red"></span>
                                            <span class="route-text"><?= htmlspecialchars($dropoff) ?></span>
                                        </div>
                                    </div>

                                    <div class="trip-bottom">
                                        <?php if ($driverName): ?>
                                            <div class="trip-driver">Driver: <?= htmlspecialchars($driverName) ?></div>
                                        <?php endif; ?>

                                        <div class="trip-rating">
                                            <img src="./assets/images/Icons/star.svg" class="icon16" alt="">
                                            <span>
                                                <?= ($rating === null || !is_numeric($rating)) ? '—' : number_format((float)$rating, 1) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="trip-price">
                                    <?= htmlspecialchars(money($price)) ?>
                                </div>

                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html>