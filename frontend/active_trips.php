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
            
        </div>
    </main>

    <?php require_once __DIR__ . '/components/footer.php'; ?>

    <script src="assets/js/active_trips.js"></script>
</body>
</html>