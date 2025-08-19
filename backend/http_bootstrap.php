<?php
// HTTP runtime bootstrap: headers, CORS, sessions
header('Content-Type: application/json');

// Allow any origin if set; otherwise, allow the site origin (e.g., SPA served via nginx on same host)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
  header('Access-Control-Allow-Origin: ' . $origin);
} else {
  // Fallback for dev; safe for prod since browser same-origin won't send Origin
  header('Access-Control-Allow-Origin: http://localhost:5173');
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  http_response_code(204);
  exit;
}

session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_name('OMSESSID');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
