<?php require_once __DIR__ . '/includes/guard.php'; ?>
<?php require_once __DIR__ . '/includes/auth.php'; ?>

<?php
if (($user['role'] ?? '') !== 'driver') {
    header('Location: index.php');
    exit;
}

$token = $_SESSION['token'] ?? $_COOKIE['goride_token'] ?? null;
$driver = $token ? apiDriverMe($token) : null;
$currentStatus = $driver['status'] ?? 'offline';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Trips</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css"/>
    <link rel="stylesheet" href="assets/css/active_trips.css"/>
</head>
<body>
    <?php include __DIR__ . '/components/navbar.php'; ?>
    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <main class="page">
        <div class="active-trips-wrap">
            <div class="page-head">
                <div class="page-title">My Trips</div>
                <section class="status-bar" >
                    <div class="status-left">
                        <img src="./assets/images/Icons/power.svg" class="icon20" alt="">
                        <div class="status-bar-title">
                            Driver Status
                        </div>
                    </div>

                    <div class="status-pills" data-current-status="<?= htmlspecialchars($currentStatus) ?>">
                        <button class="pill" type="button" data-status="available">
                            <span class="dot"></span> Online
                        </button>

                        <button class="pill" type="button" data-status="busy" disabled
                                title="Busy is set automatically when you accept a ride">
                            <span class="dot"></span> Busy
                        </button>

                        <button class="pill" type="button" data-status="offline">
                            <span class="dot"></span> Offline
                        </button>
                    </div>
                    
                </section>
            </div>
            <div class="status-msg" id="statusMsg" aria-live="polite"></div>

            <section class="trips-tabs">
                <button class="tab is-active" type="button" data-tab="pending">
                    Pending Requests <span class="badge" id="pendingCount">0</span>
                </button>
                <button class="tab" type="button" data-tab="ongoing">
                    Ongoing Rides <span class="badge" id="ongoingCount">0</span>
                </button>
            </section>

            <section class="pending" id="pendingSection">
                <div class="pending-list" id="pendingList"></div>
                <div class="pending-empty" id="pendingEmpty" style="display: none;">
                    No pending rides right now.
                </div>
            </section>

            <section class="ongoing" id="ongoingSection" style="display:none;">
                <div id="ongoingList"></div>
                <div class="pending-empty" id="ongoingEmpty" style="display:none;">No ongoing rides right now.</div>
            </section>

            <section id="rideDetailsWrap" class="ride-details-wrap" style="display: none;">
                <div class="ride-details">
                    <div class="ride-header">
                        <h3>Ride Accepted</h3>
                    </div>
                    <div class="ride-info" id="rideInfo">
                    <!-- Ride info will be dynamically populated here -->
                    </div>
                    <button id="startRideBtn" class="start-ride-btn">Start Trip</button>
                </div>
            </section>
        </div>
    </main>

    <?php require_once __DIR__ . '/components/footer.php'; ?>

    <script src="assets/js/active_trips.js"></script>
</body>
</html>
