<?php require_once __DIR__ . '/includes/guard.php'; ?>

<?php
if (($user['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Trips</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css"/>
    <link rel="stylesheet" href="assets/css/all_trips.css"/>
</head>
<body>
    <?php include __DIR__ . '/components/navbar.php'; ?>
    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <main class="at-shell">
        <section class="at-wrap">
            <div class="at-title">All Trips</div>

            <div class="at-stats">
                <article class="at-stat at-stat-blue">
                    <div class="at-stat-top">
                        <div class="at-stat-label">Total Revenue</div>
                        <img src="./assets/images/icons/cash.svg" alt="" class="">
                    </div>
                    <div class="at-stat-value" id="atTotalRevenue">0.00 EUR</div>
                </article>

                <article class="at-stat at-stat-green">
                    <div class="at-stat-top">
                        <div class="at-stat-label">Total Trips</div>
                        <img src="./assets/images/icons/trips.svg" alt="" class="at-stat-icon-invert">
                    </div>
                    <div class="at-stat-value" id="atTotalTrips">0</div>
                </article>

                <article class="at-stat at-stat-purple">
                    <div class="at-stat-top">
                        <div class="at-stat-label">Active Users</div>
                        <img src="./assets/images/icons/users.svg" alt="" class="at-stat-icon-invert">
                    </div>
                    <div class="at-stat-value" id="atActiveUsers">0</div>
                </article>

                <article class="at-stat at-stat-orange">
                    <div class="at-stat-top">
                        <div class="at-stat-label">Avg Trip Value</div>
                        <img src="./assets/images/icons/arrow.svg" alt="" class="at-stat-icon-invert">
                    </div>
                    <div class="at-stat-value" id="atAvgTripValue">0.00 EUR</div>
                </article>
            </div>

            <div class="at-tools">
                <input
                    type="text"
                    id="atSearch"
                    placeholder="Search by ride ID, passenger, or driver..."
                    autocomplete="off"
                >
            </div>

            <div class="at-list" id="atRows"></div>
            <div class="at-empty" id="atEmpty" style="display:none;">No trips found.</div>

            <div class="at-pagination" id="atPager" style="display:none;">
                <button type="button" class="at-page-btn" id="atPrevBtn">Previous</button>
                <div class="at-page-numbers" id="atPageNumbers"></div>
                <button type="button" class="at-page-btn" id="atNextBtn">Next</button>
            </div>
        </section>
    </main>

    <?php require_once __DIR__ . '/components/footer.php'; ?>
    <script src="assets/js/all_trips.js"></script>
</body>
</html>
