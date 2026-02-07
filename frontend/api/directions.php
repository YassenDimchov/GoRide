<?php
// require_once __DIR__ . '/../includes/guard.php';

header('Content-Type: application/json; charset=utf-8');

$required = ['start_lat','start_lng','end_lat','end_lng'];
foreach ($required as $k) {
  if (!isset($_GET[$k]) || $_GET[$k] === '') {
    http_response_code(400);
    echo json_encode(['message' => 'Missing ' . $k]);
    exit;
  }
}

$api =
  'http://127.0.0.1:8000/api/directions'
  . '?start_lat=' . urlencode($_GET['start_lat'])
  . '&start_lng=' . urlencode($_GET['start_lng'])
  . '&end_lat='   . urlencode($_GET['end_lat'])
  . '&end_lng='   . urlencode($_GET['end_lng']);

$ch = curl_init($api);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => [
    'Accept: application/json',
  ],
  CURLOPT_CONNECTTIMEOUT => 5,
  CURLOPT_TIMEOUT => 12,
]);

$out = curl_exec($ch);

if ($out === false) {
  $err = curl_error($ch);
  curl_close($ch);
  http_response_code(502);
  echo json_encode(['message' => 'Proxy curl failed', 'error' => $err]);
  exit;
}

$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($code ?: 200);
echo $out;
