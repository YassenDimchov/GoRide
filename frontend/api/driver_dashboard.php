<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/auth.php';

$token = $_SESSION['token'] ?? $_COOKIE['goride_token'] ?? null;
if (!$token) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthenticated']);
    exit;
}

$period = (string)($_GET['period'] ?? 'today');
$period = in_array($period, ['today','week','all'], true) ? $period : 'today';

date_default_timezone_set('Europe/Sofia');

$from = null;
$to = null;

$today = new DateTimeImmutable('today');
if ($period === 'today') {
    $from = $today->format('Y-m-d');
    $to = $today->format('Y-m-d');
} elseif ($period === 'week') {
    $monday = $today->modify('monday this week');
    $sunday = $monday->modify('+6 days');
    $from = $monday->format('Y-m-d');
    $to = $sunday->format('Y-m-d');
}

$params = [
    'with_payment' => '1',
    'with_review'  => '1',
    'status' => 'completed',
];

if ($from) $params['from'] = $from;
if ($to) $params['to'] = $to;

$qs = http_build_query($params);
$res = apiRequest('GET', '/rides/driver?with_user=true&' . $qs, [], $token);

if (!$res || !empty($res['_error'])) {
    $msg = $res['_error'] ? ($res['body']['message'] ?? 'Dashboard fetch failed') : 'Dashboard fetch failed';
    http_response_code((int)($res['status'] ?? 500));
    echo json_encode(['message' => $msg, 'debug' => $res]);
    exit;
}

$items = $res['data'] ?? [];
if (!is_array($items)) $items = [];

$totalTrips = 0;
$totalEarnings = 0.0;

foreach ($items as $ride) {
    $totalTrips++;

    $payAmount = null;
    if (isset($ride['payment']) && is_array($ride['payment'])) {
        $payAmount = $ride['payment']['amount'] ?? null;
    }

    $fare = $ride['fare'] ?? null;

    $val = null;
    if ($payAmount !== null && is_numeric($payAmount)) $val = (float)$payAmount;
    else if ($fare !== null && is_numeric($fare)) $val = (float)$fare;

    if ($val !== null) $totalEarnings += $val;
}

$avg = $totalTrips > 0 ? ($totalEarnings / $totalTrips) : 0.0;

$recent = array_slice($items, 0, 20);

echo json_encode([
    'period' => $period,
    'from' => $from,
    'to' => $to,
    'stats' => [
        'total_trips' => $totalTrips,
        'total_earnings' => round($totalEarnings, 2),
        'avg_per_trip' => round($avg, 2),
    ],
    'trips' => $recent,
], JSON_UNESCAPED_UNICODE);
