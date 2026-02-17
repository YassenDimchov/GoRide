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
    <link rel="stylesheet" href="assets/css/review.css"/>
</head>
<body>
    <div class="app-shell">
        <?php include __DIR__ . '/components/navbar.php'; ?>
        <?php include __DIR__ . '/components/sidebar.php'; ?>
        
        <main class="trips-shell">
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
                                    $paymentMethod = $r['payment']['method'] ?? null;
                                    $tripDurationMinutes = isset($r['trip_duration_s']) ? round($r['trip_duration_s'] / 60) : null;
                                    $tripDistanceKm = isset($r['trip_distance_m']) ? round($r['trip_distance_m'] / 1000, 2) : null;
                                    $nameParts = explode(' ', $driverName);
                                    $driverInitials = strtoupper($nameParts[0][0] . (isset($nameParts[1]) ? $nameParts[1][0] : ''));
                                ?>
                                <article class="trip-card" data-ride-id="<?= $r['id'] ?>">
                                    <div class="trip-top">
                                        <div class="trip-left">
                                            <div class="trip-car-icon">
                                                <img src="./assets/images/Icons/car.svg" class="icon24" alt="">
                                            </div>
                                            <div class="car-description">
                                                <div class="car-model">
                                                    <?= htmlspecialchars(trim(($vehicleMake ?? '') . ' ' . ($vehicleModel ?? ''))) ?>
                                                </div>
                                                <div class="car-plate">
                                                    <?= $plate ? 'License plate:  ' . htmlspecialchars($plate) : '' ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="trip-right">
                                            <?= htmlspecialchars(money($price)) ?>
                                            <div class="trip-payment">
                                                <span class="payment-method"><?= htmlspecialchars($paymentMethod) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="trip-info">
                                        <div class="trip-date">
                                            <img src="./assets/images/Icons/calendar.svg" class="icon14" alt="">
                                            <span><?= htmlspecialchars(fmtDateTime($dt)) ?></span>
                                        </div>
                                        <div class="trip-core">
                                            <div class="route-row">
                                                <span class="dot dot-green"></span>
                                                <div class="trip-pickup">
                                                    <div class="pickup-text">PICKUP</div>
                                                    <span class="route-text"><?= htmlspecialchars($pickup) ?></span>
                                                </div>
                                            </div>

                                            <div class="trip-duration">
                                                <div>
                                                    <img src="./assets/images/Icons/grey-clock.svg" class="icon12" alt="" />
                                                    <?= htmlspecialchars(trim(($tripDurationMinutes ?? '') . ' min')) ?>
                                                </div> 
                                                <span>→ <?= htmlspecialchars(trim(($tripDistanceKm ?? '') . ' km')) ?></span>
                                            </div>

                                            <div class="route-row">
                                                <span class="dot dot-red"></span>
                                                <div class="trip-dropoff">
                                                    <div class="pickup-text">DROPOFF</div>
                                                    <span class="route-text"><?= htmlspecialchars($dropoff) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="driver-info">
                                        <button class="driver-profile-btn" onclick="openDriverProfileModal(<?= $r['driver']['id'] ?>)">
                                            <div class="driver-avatar"><?= htmlspecialchars($driverInitials) ?></div>
                                            <div class="driver-name"><?= htmlspecialchars($driverName) ?></div>
                                        </button>
                                        <?php if (empty($r['review'])): ?>
                                            <button class="leave-review-btn" onclick="openReviewModal(<?= $r['id'] ?>)">Leave a Review</button>
                                            <div class="trip-review-text" style="display: none;"></div>
                                        <?php else: ?>
                                            <div class="trip-review-text">
                                                <div class="stars">
                                                    <?php 
                                                        $rating = $r['review']['rating'];
                                                        for ($i = 1; $i <= 5; $i++) {
                                                            if ($i <= $rating) {
                                                                echo '<img src="./assets/images/Icons/star-filled.svg" class="star icon16" alt="Filled star" />';
                                                            } else {
                                                                echo '<img src="./assets/images/Icons/star-empty.svg" class="star icon16" alt="Empty star" />';
                                                            }
                                                        }
                                                    ?>
                                                </div>
                                                - <?= !empty($r['review']['review_text']) ? htmlspecialchars($r['review']['review_text']) : 'No description' ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
        <?php require_once __DIR__ . '/components/footer.php'; ?>
    </div>

    <script src="assets/js/review_modal.js"></script>
    <script src="assets/js/trip_history.js"></script>
</body>
</html>