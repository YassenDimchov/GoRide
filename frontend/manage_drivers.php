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
    <title>Manage Drivers</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css"/>
    <link rel="stylesheet" href="assets/css/manage_drivers.css"/>
    <link rel="stylesheet" href="assets/css/driver_profile.css"/>
</head>
<body>
    <?php include __DIR__ . '/components/navbar.php'; ?>
    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <main class="md-shell">
        <section class="md-wrap">
            <div class="md-title">Manage Drivers</div>

            <div class="md-stats">
                <article class="md-stat">
                    <div class="md-stat-label">Total Drivers</div>
                    <div class="md-stat-value" id="mdTotalDrivers">0</div>
                </article>
                <article class="md-stat md-stat-highlight">
                    <div class="md-stat-label">Online Now</div>
                    <div class="md-stat-value" id="mdOnlineNow">0</div>
                </article>
                <article class="md-stat">
                    <div class="md-stat-label">Avg Rating</div>
                    <div class="md-stat-value" id="mdAvgRating">0.0</div>
                </article>
                <article class="md-stat">
                    <div class="md-stat-label">Total Trips Today</div>
                    <div class="md-stat-value" id="mdTripsToday">0</div>
                </article>
            </div>

            <div class="md-tools">
                <input type="text" id="mdSearch" placeholder="Search drivers by name or vehicle..." autocomplete="off">
            </div>

            <div class="md-list" id="mdCards"></div>
            <div class="md-empty" id="mdEmpty" style="display:none;">No drivers found.</div>
        </section>
    </main>

    <div id="driverProfileModal" class="modal">
        <div class="modal-content">
            <div class="driver-top">
                <span class="drive-modal-title">Driver Profile</span>
                <span class="close-btn" id="mdCloseDriverProfileModal">&times;</span>
            </div>
            <div class="driver-info-modal">
                <div class="driver-avatar-modal">
                    <div id="mdDriverProfileInitials">DR</div>
                </div>
                <div class="detailed-driver-info">
                    <div id="mdDriverProfileName"></div>
                    <div class="rating-and-trips">
                        <img src="./assets/images/Icons/star-filled.svg" class="star icon16" alt="Filled star" />
                        <div id="mdDriverProfileAverageRating"></div>
                        <div id="mdDriverProfileTotalTripsInfo"></div>
                    </div>
                </div>
            </div>
            <div class="driver-stats">
                <div class="driver-profile-stat-card">
                    <div class="stat-text">Total Trips</div>
                    <div id="mdDriverProfileTotalTrips"></div>
                </div>
                <div class="driver-profile-stat-card">
                    <div class="stat-text">Time as a Driver</div>
                    <div id="mdDriverProfileYearsActive"></div>
                </div>
                <div class="driver-profile-stat-card">
                    <div class="stat-text">Response Time</div>
                    <div id="mdDriverProfileResponseTime"></div>
                </div>
            </div>
            <div id="mdDriverProfileRatingBreakdown">
                <h3>Rating Breakdown:</h3>
                <div class="rating-item">
                    <span class="rating-label">5 ★</span>
                    <div class="rating-bar"><div id="mdDriverProfileRatingBar5" class="rating-fill"></div></div>
                    <span id="mdDriverProfileRatingCount5" class="rating-count">0%</span>
                </div>
                <div class="rating-item">
                    <span class="rating-label">4 ★</span>
                    <div class="rating-bar"><div id="mdDriverProfileRatingBar4" class="rating-fill"></div></div>
                    <span id="mdDriverProfileRatingCount4" class="rating-count">0%</span>
                </div>
                <div class="rating-item">
                    <span class="rating-label">3 ★</span>
                    <div class="rating-bar"><div id="mdDriverProfileRatingBar3" class="rating-fill"></div></div>
                    <span id="mdDriverProfileRatingCount3" class="rating-count">0%</span>
                </div>
                <div class="rating-item">
                    <span class="rating-label">2 ★</span>
                    <div class="rating-bar"><div id="mdDriverProfileRatingBar2" class="rating-fill"></div></div>
                    <span id="mdDriverProfileRatingCount2" class="rating-count">0%</span>
                </div>
                <div class="rating-item">
                    <span class="rating-label">1 ★</span>
                    <div class="rating-bar"><div id="mdDriverProfileRatingBar1" class="rating-fill"></div></div>
                    <span id="mdDriverProfileRatingCount1" class="rating-count">0%</span>
                </div>
            </div>
            <a class="btn-outline" id="mdDriverProfileCallBtn" href="tel:">
                <span><img src="./assets/images/Icons/phone.svg" class="icon16" alt="" /></span> Call Driver
            </a>
        </div>
    </div>

    <?php require_once __DIR__ . '/components/footer.php'; ?>
    <script src="assets/js/manage_drivers.js"></script>
</body>
</html>
