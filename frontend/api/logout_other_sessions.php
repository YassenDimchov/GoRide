<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/guard.php';

apiLogoutOtherSessions($token);

echo json_encode([
    'message' => 'Logged out from all other sessions',
], JSON_UNESCAPED_UNICODE);

