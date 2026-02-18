<?php
    $current = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)); 
    if ($current === '' || $current === '/') $current = 'index.php';

    function navItem(string $href, string $current): array {
        $isActive = ($href === $current);

        return [
            'class' => $isActive ? 'nav-item active' : 'nav-item',
            'href'  => $isActive ? '#' : $href,
            'data'  => $isActive ? ' data-close-sidebar="1"' : '',
            'aria'  => $isActive ? ' aria-current="page"' : ''
        ];
    }

    $isDriver = (($user['role'] ?? '') === 'driver');
    $isAdmin = (($user['role'] ?? '') === 'admin');
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-top">
        <div class="brand">
            <div class="brand-icon icon48">
                <img src="./assets/images/Icons/car.svg" alt="" class="icon24">
            </div>
            <div class="brand-text">
                <div class="brand-name">GoRide<span class="logo-dot">.</span></div>
                <div class="brand-sub">Go anywhere</div>
            </div>
        </div>

        <a href="profile.php" class="user-card-link">
            <div class="user-card">
                <div class="user-avatar icon48 avatar-btn">
                    <?= htmlspecialchars($initials) ?>
                </div>
                <div class="user-info">
                    <div class="user-name">
                        <?= htmlspecialchars($user['name']) ?>
                    </div>
                    <div class="user-email">
                        <?= htmlspecialchars($user['email'] ?? '') ?>
                    </div>
                    <span class="user-badge <?= $isDriver ? 'badge-driver' : 'badge-user' ?>">
                        <?= $isDriver ? 'Driver' : 'User' ?>
                    </span>
                </div>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <?php $home = navItem('index.php', $current); ?>
        <a href="<?= $home['href'] ?>" class="<?= $home['class'] ?>"<?= $home['data'] ?><?= $home['aria'] ?>>
            <img src="./assets/images/Icons/home.svg" alt="" class="nav-ic icon20">
            <span>Home</span>
        </a>

        <?php $profile = navItem('profile.php', $current); ?>
        <a href="<?= $profile['href'] ?>" class="<?= $profile['class'] ?>"<?= $profile['data'] ?><?= $profile['aria'] ?>>
            <img src="./assets/images/Icons/user.svg" alt="" class="nav-ic icon20">
            <span>Profile</span>
        </a>

        <?php if ($isDriver): ?>
            <?php $activeTrips = navItem('active_trips.php', $current); ?>
            <a href="<?= $activeTrips['href'] ?>" class="<?= $activeTrips['class'] ?>"<?= $activeTrips['data'] ?><?= $activeTrips['aria'] ?>>
                <img src="./assets/images/Icons/location.svg" alt="" class="nav-ic icon20">
                <span>Active Trips</span>
            </a>

            <?php $dashboard = navItem('driver_dashboard.php', $current); ?>
            <a href="<?= $dashboard['href'] ?>" class="<?= $dashboard['class'] ?>"<?= $dashboard['data'] ?><?= $dashboard['aria'] ?>>
                <img src="./assets/images/Icons/chart.svg" alt="" class="nav-ic icon20">
                <span>Driver Dashboard</span>
            </a>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <?php $manageUsers = navItem('manage_users.php', $current); ?>
            <a href="<?= $manageUsers['href'] ?>" class="<?= $manageUsers['class'] ?>"<?= $manageUsers['data'] ?><?= $manageUsers['aria'] ?>>
                <img src="./assets/images/Icons/users.svg" alt="" class="nav-ic icon20">
                <span>Manage Users</span>
            </a>

            <?php $manageDrivers = navItem('manage_drivers.php', $current); ?>
            <a href="<?= $manageDrivers['href'] ?>" class="<?= $manageDrivers['class'] ?>"<?= $manageDrivers['data'] ?><?= $manageDrivers['aria'] ?>>
                <img src="./assets/images/Icons/car.svg" alt="" class="nav-ic icon20">
                <span>Manage Drivers</span>
            </a>

            <?php $analytics = navItem('analytics.php', $current); ?>
            <a href="<?= $analytics['href'] ?>" class="<?= $analytics['class'] ?>"<?= $analytics['data'] ?><?= $analytics['aria'] ?>>
                <img src="./assets/images/Icons/chart.svg" alt="" class="nav-ic icon20">
                <span>Analytics</span>
            </a>

            <?php $allTrips = navItem('all_trips.php', $current); ?>
            <a href="<?= $allTrips['href'] ?>" class="<?= $allTrips['class'] ?>"<?= $allTrips['data'] ?><?= $allTrips['aria'] ?>>
                <img src="./assets/images/Icons/all-trips.svg" alt="" class="nav-ic icon20">
                <span>All trips</span>
            </a>
        <?php endif; ?>

        <?php $trips = navItem('trips.php', $current); ?>
        <a href="<?= $trips['href'] ?>" class="<?= $trips['class'] ?>"<?= $trips['data'] ?><?= $trips['aria'] ?>>
            <img src="./assets/images/Icons/trips.svg" alt="" class="nav-ic icon20">
            <span>Trip History</span>
        </a>

        <?php $payment = navItem('payment.php', $current); ?>
        <a href="<?= $payment['href'] ?>" class="<?= $payment['class'] ?>"<?= $payment['data'] ?><?= $payment['aria'] ?>>
            <img src="./assets/images/Icons/card.svg" alt="" class="nav-ic icon20">
            <span>Payment</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <a href="logout.php" class="nav-item logout">
            <img src="./assets/images/Icons/logout.svg" alt="" class="nav-ic icon20">
            <span>Log Out</span>
        </a>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<script src="assets/js/sidebar.js"></script>
