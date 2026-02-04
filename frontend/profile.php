<?php require_once __DIR__ . '/includes/guard.php'; ?>
<?php require_once __DIR__ . '/includes/roles.php'; ?>
<?php require_once __DIR__ . '/includes/profile_stats.php'; ?>
<?php $stats = profileStats($token); ?>

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
    <link rel="stylesheet" href="assets/css/profile.css"/>
</head>
<body>
    <?php include __DIR__ . '/components/navbar.php'; ?>
    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <main class="app-shell profile-shell">
        <div class="profile-wrap">

            <!-- Header -->
            <section class="card profile-card profile-hero">
                <div class="profile-id">
                    <div class="profile-avatar avatar-btn no-hover">
                        <?= htmlspecialchars($initials) ?>
                    </div>

                    <div class="profile-meta">
                        <div class="profile-name"><?= htmlspecialchars($user['name']) ?></div>
                        <div class="mail-meta">
                            <img src="./assets/images/Icons/mail.svg" class="icon16" alt="">
                            <div class="profile-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                        </div>

                        <div class="profile-badges">
                            <span class="pill">Verified Account</span>
                            <span class="pill">Premium Member</span>

                            <?php if (isAdmin()): ?>
                                <span class="pill pill-admin">
                                    <img src="./assets/images/Icons/crown.svg" alt="">
                                    Admin
                                </span>

                            <?php elseif (isDriver()): ?>
                                <span class="pill pill-driver">
                                    <img src="./assets/images/Icons/car.svg" alt="">
                                    Driver
                                </span>

                            <?php else: ?>
                                <span class="pill pill-user">
                                    <img src="./assets/images/Icons/user.svg" alt="">
                                    User
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="profile-actions">
                    <button class="btn-light" id="editBtn" type="button">
                        <img src="./assets/images/Icons/pen.svg" class="icon16" alt="">
                        <span class="edit-profile-text">Edit Profile</span>
                    </button>

                    <button class="btn-light" id="saveBtn" type="submit" style="display:none;" form="profileForm">
                        <img src="./assets/images/Icons/save.svg" class="icon16" alt="">
                        <span>Save</span>
                    </button>

                    <button class="btn-dark" id="cancelBtn" type="button" style="display:none;">
                        <img src="./assets/images/Icons/x.svg" class="icon16" alt="">
                        <span>Cancel</span>
                    </button>
                </div>
            </section>

            <?php include __DIR__ . '/includes/flash.php'; ?>

            <!-- Stats -->
            <div class="stats-grid colorful-stats">
                <div class="stat-card stat-blue">
                    <div class="stat-ic">
                        <img src="./assets/images/Icons/car.svg" class="icon28" alt="">
                    </div>
                    <div class="stat-meta">
                        <div class="stat-number"><?= $stats['totalTrips'] ?></div>
                        <div class="stat-label">Total Trips</div>
                    </div>
                </div>

                <div class="stat-card stat-purple">
                    <div class="stat-ic">
                        <img src="./assets/images/Icons/star.svg" class="icon28" alt="">
                    </div>
                    <div class="stat-meta">
                        <div class="stat-number">
                            <?= $stats['avgRating'] === null ? '—' : htmlspecialchars((string)$stats['avgRating']) ?>
                        </div>
                        <div class="stat-label">Average Rating</div>
                    </div>
                </div>

                <div class="stat-card stat-green">
                    <div class="stat-ic">
                        <img src="./assets/images/Icons/arrow.svg" class="icon28" alt="">
                    </div>
                    <div class="stat-meta">
                        <div class="stat-number"><?= number_format($stats['totalSpent'], 2) ?> €</div>
                        <div class="stat-label">Total Spent</div>
                    </div>
                </div>
            </div>

            <div class="info-security-container">
                <!-- Personal Info -->
                <section class="card">
                    <div class="personal-info-title">
                        <img src="./assets/images/Icons/pen.svg" class="icon20" alt="">
                        <div class="card-title">Personal Information</div>
                    </div>
                    <form action="profile_update.php" class="info-form" id="profileForm" autocomplete="off" method="POST">
                        <!-- Name -->
                        <div class="field">
                            <label class="field-label" for="name">
                                <img src="./assets/images/Icons/user.svg" class="icon16" alt="">
                                <span>Full Name</span>
                            </label>
                            <input
                                class="field-input"
                                type="text"
                                name="name"
                                id="name"
                                value="<?= htmlspecialchars($user['name']) ?>"
                                disabled
                                data-original="<?= htmlspecialchars($user['name']) ?>"
                            />
                        </div>
                        <!-- Email (read-only) -->
                        <div class="field field-readonly">
                            <label class="field-label">
                                <img src="./assets/images/Icons/mail.svg" class="icon16" alt="">
                                <span>Email Address</span>
                            </label>
                            <div class="field-readonly-value">
                                <?= htmlspecialchars($user['email'] ?? '') ?>
                            </div>
                        </div>
                        <!-- Phone -->
                        <div class="field">
                            <label class="field-label" for="phone">
                                <img src="./assets/images/Icons/phone.svg" class="icon16" alt="">
                                <span>Phone Number</span>
                            </label>
                            <input
                                class="field-input"
                                type="tel"
                                name="phone"
                                id="phone"
                                value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                placeholder="Enter phone number"
                                disabled
                                data-original="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                            />
                        </div>
                    </form>
                </section>
                <!-- Security -->
                <section class="card">
                    <div class="card-title">Account Security</div>
                    <div class="security-list">
                        <div class="security-row">
                            <div>
                                <div class="sec-title">Password</div>
                                <div class="sec-sub">Change your account password</div>
                            </div>
                            <button class="btn-light" type="button">Change Password</button>
                        </div>
                        <div class="security-row">
                            <div>
                                <div class="sec-title">Two-Factor Authentication</div>
                                <div class="sec-sub">Add an extra layer of security</div>
                            </div>
                            <button class="btn-light" type="button">Enable</button>
                        </div>
                        <div class="security-row">
                            <div>
                                <div class="sec-title">Active Sessions</div>
                                <div class="sec-sub">Manage your active sessions</div>
                            </div>
                            <button class="btn-light" type="button">View All</button>
                        </div>
                    </div>
                </section>
            </div>

        </div>
    </main>

    <?php require_once __DIR__ . '/components/footer.php'; ?>

    <script src="assets/js/profile.js"></script>
</body>
</html>