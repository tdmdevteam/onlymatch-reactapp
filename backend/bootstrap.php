<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

$isCli = (PHP_SAPI === 'cli');

// Only send headers / handle CORS / sessions for web requests
if (!$isCli) {
  require __DIR__ . '/http_bootstrap.php';
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
