<?php
declare(strict_types=1);

/**
 * Basic bootstrap: CORS, sessions, DB, helpers
 */

header('Content-Type: application/json');

// Allow your Vite dev server
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// Sessions (cookie-based auth for admin)
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_name('OMSESSID');
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// SQLite DB
$dbFile = __DIR__ . '/db.sqlite';
$initSchema = !file_exists($dbFile);
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if ($initSchema) {
  $db->exec(file_get_contents(__DIR__ . '/schema.sql'));
}

function json($data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data);
  exit;
}

function requireAuth(): void {
  if (empty($_SESSION['admin_id'])) {
    json(['error' => 'Unauthorized'], 401);
  }
}

function body_json(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}
