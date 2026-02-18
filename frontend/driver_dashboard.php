<?php require_once __DIR__ . '/includes/guard.php'; ?>

<?php
if (($user['role'] ?? '') !== 'driver') {
    header('Location: index.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css"/>
    <link rel="stylesheet" href="assets/css/driver_dashboard.css"/>
</head>
<body>
    <?php include __DIR__ . '/components/navbar.php'; ?>
    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <div class="app-shell">
        <main class="page">
            <div class="page-head">
                <div class="page-title">Driver Dashboard</div>
            </div>

            <div class="dash-tabs" role="tablist">
                <button class="dash-tab is-active" type="button" data-period="today" role="tab" >Today</button>
                <button class="dash-tab" type="button" data-period="week" role="tab">This Week</button>
                <button class="dash-tab" type="button" data-period="all" role="tab">All Time</button>
            </div>

            <section class="dash-hero">
                <div class="dash-hero-left">
                    <div class="dash-hero-label">Total Earnings</div>
                    <div class="dash-hero-value" id="ddTotalEarnings">—</div>
                    <div class="dash-hero-sub" id="ddEarningsSub"> </div>
                </div>
                <div class="dash-hero-icon">
                    <img src="./assets/images/Icons/cash.svg" class="icon32" alt="">
                </div>
            </section>

            <section class="dash-stats">
                <div class="dash-stat">
                    <div class="dash-stat-label">Total Trips</div>
                    <div class="dash-stat-value" id="ddTotalTrips">-</div>
                </div>
                <div class="dash-stat">
                    <div class="dash-stat-label">Avg per Trip</div>
                    <div class="dash-stat-value" id="ddAvgPerTrip">-</div>
                </div>
            </section>

            <section class="dash-card">
                <div class="dash-card-head">
                    <div class="dash-card-title">Trip History</div>
                </div>

                <div id="ddTripsEmpty" class="dash-empty" style="display:none;">
                    No trips in this period.
                </div>

                <div id="ddTripsList" class="dash-trips"></div>
            </section>
        </main>

        
    </div>

    <?php require_once __DIR__ . '/components/footer.php'; ?>
    <script src="assets/js/driver_dashboard.js"></script>
</body>
</html>