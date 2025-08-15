<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

$isCli = (PHP_SAPI === 'cli');

// Only send headers / handle CORS / sessions for web requests
if (!$isCli) {
  header('Content-Type: application/json');
  // In dev we might run behind nginx (same-origin) or Vite (5173).
  // Mirror Origin when present so credentials work; fallback to Vite origin.
  $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
  if ($origin !== '') {
    header('Access-Control-Allow-Origin: ' . $origin);
  } else {
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
}

// ---- SQLite setup ----
$dbFile = __DIR__ . '/db.sqlite';
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ensure tables exist
$schemaPath = __DIR__ . '/schema.sql';
if (is_readable($schemaPath)) {
  $db->exec(file_get_contents($schemaPath));
}

// --- simple migration: add onlyfans_url if missing ---
$cols = $db->query("PRAGMA table_info(profiles)")->fetchAll(PDO::FETCH_ASSOC);
$hasOnlyfans = false;
foreach ($cols as $c) { if (($c['name'] ?? '') === 'onlyfans_url') { $hasOnlyfans = true; break; } }
if (!$hasOnlyfans) {
  $db->exec("ALTER TABLE profiles ADD COLUMN onlyfans_url TEXT");
}


function json($data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data);
  exit;
}

function requireAuth(): void {
  if (empty($_SESSION['admin_id'])) json(['error' => 'Unauthorized'], 401);
}

function body_json(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}
