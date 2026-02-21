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
    <title>Manage Users</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css"/>
    <link rel="stylesheet" href="assets/css/manage_users.css"/>
</head>
<body>
    <?php include __DIR__ . '/components/navbar.php'; ?>
    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <main class="admin-shell">
        <section class="admin-wrap">
            <div class="admin-title">Manage Users</div>

            <div class="admin-stats">
                <article class="admin-stat">
                    <div class="admin-stat-label">Total Users</div>
                    <div class="admin-stat-value" id="muTotalUsers">0</div>
                </article>
                <article class="admin-stat">
                    <div class="admin-stat-label">Active Users</div>
                    <div class="admin-stat-value" id="muActiveUsers">0</div>
                </article>
                <article class="admin-stat">
                    <div class="admin-stat-label">Drivers</div>
                    <div class="admin-stat-value" id="muDrivers">0</div>
                </article>
                <article class="admin-stat">
                    <div class="admin-stat-label">Suspended</div>
                    <div class="admin-stat-value" id="muSuspended">0</div>
                </article>
            </div>

            <div class="admin-tools">
                <input type="text" id="muSearch" placeholder="Search by name or email..." autocomplete="off">
            </div>

            <div class="admin-table-card">
                <div class="admin-table-head">
                    <div>User</div>
                    <div>Contact</div>
                    <div>Role</div>
                    <div>Trips</div>
                    <div>Joined</div>
                    <div>Status</div>
                    <div>Actions</div>
                </div>
                <div id="muRows"></div>
                <div class="admin-empty" id="muEmpty" style="display:none;">No users found.</div>
            </div>
        </section>
    </main>

    <?php require_once __DIR__ . '/components/footer.php'; ?>
    <script src="assets/js/manage_users.js"></script>
</body>
</html>