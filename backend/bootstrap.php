<?php
declare(strict_types=1);

// --- .env loader ---
function loadDotEnv(string $file): void {
  if (!is_readable($file)) return;
  foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
    [$k, $v] = array_map('trim', explode('=', $line, 2));
    // strip quotes if present
    if ($v !== '' && ($v[0] === '"' || $v[0] === "'")) $v = trim($v, "\"'");
    putenv("$k=$v"); $_ENV[$k] = $v; $_SERVER[$k] = $v;
  }
}
loadDotEnv(__DIR__ . '/.env');


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

$cols = $db->query("PRAGMA table_info(profiles)")->fetchAll(PDO::FETCH_ASSOC);
$have = array_column($cols, 'name');
$add = function($col, $ddl) use ($have, $db){ if(!in_array($col,$have,true)) $db->exec("ALTER TABLE profiles ADD COLUMN $ddl"); };

$add('username',        'username TEXT');
$add('display_name',    'display_name TEXT');
$add('subscribe_price', 'subscribe_price REAL');
$add('about_html',      'about_html TEXT');
$add('photos_count',    'photos_count INTEGER DEFAULT 0');
$add('videos_count',    'videos_count INTEGER DEFAULT 0');
$add('likes',           'likes INTEGER DEFAULT 0'); // if not already present
