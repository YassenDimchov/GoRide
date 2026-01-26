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
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-top">
        <div class="brand">
            <div class="brand-icon icon48">
                <img src="./assets/images/Icons/car.svg" alt="" class="icon24">
            </div>
            <div class="brand-text">
                <div class="brand-name">GoRide</div>
                <div class="brand-sub">Go anywhere</div>
            </div>
        </div>

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
                <span class="user-badge">User</span>
            </div>
        </div>
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

        <?php $settings = navItem('settings.php', $current); ?>
        <a href="<?= $settings['href'] ?>" class="<?= $settings['class'] ?>"<?= $settings['data'] ?><?= $settings['aria'] ?>>
            <img src="./assets/images/Icons/settings.svg" alt="" class="nav-ic icon20">
            <span>Settings</span>
        </a>

        <?php $support = navItem('support.php', $current); ?>
        <a href="<?= $support['href'] ?>" class="<?= $support['class'] ?>"<?= $support['data'] ?><?= $support['aria'] ?>>
            <img src="./assets/images/Icons/help.svg" alt="" class="nav-ic icon20">
            <span>Help &amp; Support</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <a href="logout.php" class="nav-item">
            <img src="./assets/images/Icons/logout.svg" alt="" class="nav-ic icon20">
            <span>Log Out</span>
        </a>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<script src="assets/js/sidebar.js"></script>
