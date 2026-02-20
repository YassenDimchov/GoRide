<?php
require_once __DIR__ . '/includes/guard.php';
require_once __DIR__ . '/includes/trips_helpers.php';
require_once __DIR__ . '/includes/payments_data.php';
require_once __DIR__ . '/includes/auth.php';

date_default_timezone_set('Europe/Sofia');

$statusFilter = strtolower((string)($_GET['status'] ?? 'all'));
if (!in_array($statusFilter, ['all', 'paid', 'unpaid'], true)) {
    $statusFilter = 'all';
}

$stripeNotice = '';
$stripeError = '';

if (isset($_GET['stripe_success'])) {
    $paymentId = (int)($_GET['payment_id'] ?? 0);
    $sessionId = trim((string)($_GET['session_id'] ?? ''));

    if ($paymentId > 0 && $sessionId !== '') {
        $confirm = apiConfirmStripeCheckout($token, $paymentId, $sessionId);
        if (!$confirm || !empty($confirm['_error'])) {
            $stripeError = $confirm['body']['message'] ?? 'Stripe payment could not be confirmed.';
        } else {
            $stripeNotice = $confirm['message'] ?? 'Card payment completed successfully.';
        }
    } else {
        $stripeError = 'Stripe payment callback is missing required data.';
    }
}

if (isset($_GET['stripe_cancel'])) {
    $stripeError = 'Card payment was canceled.';
}

$payments = getMyPayments($token, $statusFilter);

$userData = apiGetPreferredPaymentMethod($token);
$selected = $userData['preferred_payment'] ?? 'online';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css"/>
    <link rel="stylesheet" href="assets/css/payments.css"/>
</head>
<body>
    <?php include __DIR__ . '/components/navbar.php'; ?>
    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <main class="app-shell payments-shell">
        <div class="payments-wrap">
            <section class="card pay-card">

                <div class="pay-banner">
                    <div class="pay-banner-icon">
                        <img src="./assets/images/Icons/shield.svg" alt="" class="icon20">
                    </div>
                    <div class="pay-banner-text">
                        <div class="pay-banner-title">Secure Payment</div>
                        <div class="pay-banner-sub">
                            Choose between online payment via Stripe or pay with cash.
                        </div>
                    </div>
                </div>

                <div class="pay-section-title">Select Payment Method</div>

                <div class="pay-method-grid">
                    <div class="pay-method <?= $selected === 'online' ? 'is-active is-blue' : '' ?>" id="stripe-btn" aria-label="Online Payment (Stripe)">
                        <div class="pay-method-top">
                            <div class="pay-method-top-left">
                                <div class="pay-method-icon pay-icon-blue">
                                    <img src="./assets/images/Icons/card-white.svg" class="icon24" alt="">
                                </div>
                                <div class="pay-method-text">
                                    <div class="pay-method-name">Online Payment</div>
                                    <div class="pay-method-sub">Pay with card via Stripe</div>
                                </div>
                            </div>

                            <?php if ($selected === 'online'): ?>
                                <div class="pay-method-check">
                                    <img src="./assets/images/Icons/check.svg" class="icon16" alt="">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="pay-method-badges">
                            <span class="badge">Most Popular</span>
                        </div>
                    </div>

                    <div class="pay-method <?= $selected === 'cash' ? 'is-active is-green' : '' ?>" id="cash-btn" aria-label="Cash">
                        <div class="pay-method-top">
                            <div class="pay-method-top-left">
                                <div class="pay-method-icon pay-icon-green">
                                    <img src="./assets/images/Icons/cash.svg" class="icon24" alt="">
                                </div>
                                <div class="pay-method-text">
                                    <div class="pay-method-name">Cash</div>
                                    <div class="pay-method-sub">Pay driver with cash</div>
                                </div>
                            </div>

                            <?php if ($selected === 'cash'): ?>
                                <div class="pay-method-check">
                                    <img src="./assets/images/Icons/check.svg" class="icon16" alt="">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="pay-method-badges">
                            <span class="badge">Pay after Ride</span>
                        </div>
                    </div>
                </div>

                <?php if ($selected === 'cash'): ?>
                    <div class="pay-info">

                        <img src="./assets/images/Icons/cash-green.svg" alt="" class="icon20">

                        <div class="pay-info-right">
                            <div class="pay-info-title">
                                Cash Payment Selected
                            </div>
                            <ul class="pay-info-list">
                                <li>Please have exact change ready</li>
                                <li>Pay the driver at the end of your trip</li>
                                <li>Request a receipt if needed</li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="pay-current">
                    <div class="pay-current-left">
                        <div class="pay-current-title">Current Payment Method</div>
                        <div class="pay-current-value">
                            <?php if ($selected === 'cash'): ?>
                                <img src="./assets/images/Icons/cash-black.svg" alt="" class="icon16">
                                Cash
                            <?php else: ?>
                                <img src="./assets/images/Icons/card.svg" alt="" class="icon16">
                                Online Payment (Stripe)
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="pill pill-success">Active</span>
                </div>
            </section>

            <section class="card pay-card">
                <div class="pay-section-title">Transactions</div>

                <div class="pay-filters">
                    <a class="pay-filter <?= $statusFilter === 'all' ? 'is-active' : '' ?>" href="payment.php?status=all">All</a>
                    <a class="pay-filter <?= $statusFilter === 'paid' ? 'is-active' : '' ?>" href="payment.php?status=paid">Paid</a>
                    <a class="pay-filter <?= $statusFilter === 'unpaid' ? 'is-active' : '' ?>" href="payment.php?status=unpaid">Unpaid</a>
                </div>

                <?php if ($stripeNotice !== ''): ?>
                    <div class="pay-alert pay-alert-success"><?= htmlspecialchars($stripeNotice) ?></div>
                <?php endif; ?>
                <?php if ($stripeError !== ''): ?>
                    <div class="pay-alert pay-alert-error"><?= htmlspecialchars($stripeError) ?></div>
                <?php endif; ?>

                <?php if (empty($payments)): ?>
                    <div class="empty-state">No transactions found.</div>
                <?php else: ?>
                    <div class="pay-list">
                        <?php foreach ($payments as $p): ?>
                            <?php
                                $amount = $p['amount'] ?? null;
                                $status = strtolower((string)($p['status'] ?? 'pending'));
                                $method = strtolower((string)($p['method'] ?? 'cash'));

                                $to = $p['ride']['end_address'] ?? null;
                                $dt = $p['ride']['completed_at'] ?? $p['paid_at'] ?? $p['created_at'] ?? null;

                                $statusText = $status === 'paid' ? 'Paid' : ($status === 'failed' ? 'Failed' : 'Pending');
                                $isCardMethod = in_array($method, ['online', 'card', 'stripe'], true);
                            ?>
                            <div class="pay-row">
                                <div class="pay-row-left">
                                    <div class="pay-row-title">
                                        <?= $to ? 'Trip to ' . htmlspecialchars($to) : 'Trip payment' ?>
                                    </div>
                                    <div class="pay-row-meta">
                                        <span class="meta-pill"><?= htmlspecialchars(ucfirst($method)) ?></span>
                                        <span class="meta-dot">&bull;</span>
                                        <span class="meta-date"><?= htmlspecialchars(fmtDateTime($dt)) ?></span>
                                    </div>
                                </div>

                                <div class="pay-row-right">
                                    <div class="pay-row-amount"><?= htmlspecialchars(number_format((float)$amount, 2) . ' EUR') ?></div>
                                    <span class="status-pill <?= $status === 'paid' ? 'is-paid' : ($status === 'failed' ? 'is-failed' : 'is-pending') ?>">
                                        <?= htmlspecialchars($statusText) ?>
                                    </span>
                                    <?php if ($isCardMethod && $status === 'pending'): ?>
                                        <button
                                            type="button"
                                            class="pay-now-btn"
                                            data-payment-id="<?= (int)($p['id'] ?? 0) ?>"
                                        >
                                            Pay With Card
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <?php require_once __DIR__ . '/components/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/payment-method.js"></script>
</body>
</html>
