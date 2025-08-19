<?php
// Serve real files (e.g. /uploads/...) directly
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
  return false;
}

require __DIR__ . '/../bootstrap.php';

/* ---------------- Helpers ---------------- */

function safe(callable $fn) {
  try { $fn(); } catch (Throwable $e) { json(['error' => $e->getMessage()], 500); }
}

/** Accepts a full OnlyFans URL or a slug like "hj123"; returns slug or null */
function onlyfansSlug(string $input): ?string {
  $input = trim($input);
  if ($input === '') return null;
  if (preg_match('#^https?://#i', $input)) {
    $p = parse_url($input);
    $slug = ltrim($p['path'] ?? '', '/');
    return $slug !== '' ? $slug : null;
  }
  return $input;
}

/** Map upstream JSON to our fields (about removed) */
function mapOnlyFansPayload(array $d): array {
  $subPrice = $d['subscribedByData']['subscribePrice'] ?? $d['subscribePrice'] ?? null;
  return [
    'username'        => $d['username'] ?? null,
    'avatar_url'      => $d['avatar'] ?? null,
    'display_name'    => $d['name'] ?? null,
    'likes'           => isset($d['favoritedCount']) ? (int)$d['favoritedCount'] : 0, // <-- pull from favoritedCount
    'subscribe_price' => is_numeric($subPrice) ? (float)$subPrice : null,
    'photos_count'    => isset($d['photosCount']) ? (int)$d['photosCount'] : 0,
    'videos_count'    => isset($d['videosCount']) ? (int)$d['videosCount'] : 0,
  ];
}


/** Call approved service (GET with X-API-KEY) and map the response */
function fetchOnlyFansSummary(string $input): array {
  $slug  = onlyfansSlug($input);
  if (!$slug) return [];

  $base = getenv('OF_API_URL') ?: '';
  $key  = getenv('OF_API_KEY') ?: '';
  if ($base === '' || $key === '') return [];

  $url = rtrim($base, '/') . '/' . rawurlencode($slug);

  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['X-API-KEY: ' . $key, 'Accept: application/json'],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_FOLLOWLOCATION => true,
  ]);
  $body = curl_exec($ch);
  $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($http !== 200 || !is_string($body)) return [];
  $raw = json_decode($body, true);
  if (!is_array($raw)) return [];
  return mapOnlyFansPayload($raw);
}

/* ---------------- Public GET routes ---------------- */

if ($path === '/api/profiles' && $_SERVER['REQUEST_METHOD'] === 'GET') {
  safe(function() use ($db) {
    $q = $db->query('SELECT id,name,avatar_url,onlyfans_url,username,likes,display_name,subscribe_price,photos_count,videos_count,created_at FROM profiles ORDER BY created_at DESC');
    json($q->fetchAll(PDO::FETCH_ASSOC));
  });
}

if (preg_match('#^/api/profiles/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
  safe(function() use ($db, $m) {
    $st = $db->prepare('SELECT id,name,avatar_url,onlyfans_url,username,likes,display_name,subscribe_price,photos_count,videos_count,created_at FROM profiles WHERE id=?');
    $st->execute([(int)$m[1]]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $row ? json($row) : json(['error' => 'Not found'], 404);
  });
}

/* ---------------- Auth ---------------- */

// GET /api/me
if ($path === '/api/me' && $_SERVER['REQUEST_METHOD'] === 'GET') {
  safe(function() {
    if (!empty($_SESSION['admin_id'])) json(['ok' => true, 'admin_id' => (int)$_SESSION['admin_id']]);
    json(['error' => 'Unauthorized'], 401);
  });
}

// POST /api/login  {email,password}
if ($path === '/api/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  safe(function() use ($db) {
    $data = body_json();
    $email = strtolower(trim($data['email'] ?? ''));
    $password = $data['password'] ?? '';
    $st = $db->prepare('SELECT id,password_hash FROM admins WHERE email=?');
    $st->execute([$email]);
    $admin = $st->fetch(PDO::FETCH_ASSOC);
    if ($admin && password_verify($password, $admin['password_hash'])) {
      $_SESSION['admin_id'] = (int)$admin['id'];
      json(['ok' => true]);
    }
    json(['error' => 'Invalid credentials'], 401);
  });
}

// POST /api/logout
if ($path === '/api/logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  session_destroy();
  json(['ok' => true]);
}


if ($path === '/api/profiles' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  safe(function() use ($db) {
    requireAuth();

    $input = trim($_POST['onlyfans_url'] ?? '');
    if ($input === '') json(['error' => 'onlyfans_url required'], 400);

    $slug = onlyfansSlug($input);
    if (!$slug) json(['error' => 'invalid OnlyFans URL/username'], 400);

    $name = trim($_POST['name'] ?? '');
    $bio  = null; // bio removed

    $s = fetchOnlyFansSummary($input);

<<<<<<< HEAD
    $username        = $s['username']        ?? $slug;
    $avatar_url      = $s['avatar_url']      ?? null;
    $display_name    = $s['display_name']    ?? null;
    $likes           = $s['likes']           ?? 0;
    $subscribe_price = $s['subscribe_price'] ?? null;
    $photos_count    = $s['photos_count']    ?? 0;
    $videos_count    = $s['videos_count']    ?? 0;
=======
    // Image upload (optional)
    $avatarUrl = null;
    if (!empty($_FILES['avatar']['tmp_name'])) {
    $uploads = __DIR__ . '/uploads';
      if (!is_dir($uploads)) mkdir($uploads, 0777, true);
      $ext = strtolower(pathinfo($_FILES['avatar']['name'] ?? '', PATHINFO_EXTENSION) ?: 'jpg');
      $fname = uniqid('avatar_', true) . '.' . $ext;
      if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $uploads . '/' . $fname)) {
        json(['error'=>'upload failed'], 500);
      }
      $avatarUrl = '/uploads/' . $fname;
    }
>>>>>>> 9fa2085257edffbec3262d52376897fa1bddd0a3

    $profile_name = $name !== '' ? $name : ($display_name ?: $username);
    $onlyfans_url = 'https://onlyfans.com/' . $username;

    $st = $db->prepare('
      INSERT INTO profiles
      (name,bio,avatar_url,onlyfans_url,username,likes,display_name,subscribe_price,photos_count,videos_count)
      VALUES (?,?,?,?,?,?,?,?,?,?)
    ');
    $st->execute([
      $profile_name, $bio, $avatar_url, $onlyfans_url, $username, $likes,
      $display_name, $subscribe_price, $photos_count, $videos_count
    ]);

    json([
      'id'              => (int)$db->lastInsertId(),
      'name'            => $profile_name,
      'avatar_url'      => $avatar_url,
      'onlyfans_url'    => $onlyfans_url,
      'username'        => $username,
      'likes'           => $likes,
      'display_name'    => $display_name,
      'subscribe_price' => $subscribe_price,
      'photos_count'    => $photos_count,
      'videos_count'    => $videos_count
    ], 201);
  });
}

// POST /api/profiles/{id}/resync  -> refresh avatar/likes/etc (about removed)
if (preg_match('#^/api/profiles/(\d+)/resync$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
  safe(function() use ($db, $m) {
    requireAuth();
    $id = (int)$m[1];

    $st = $db->prepare('SELECT username FROM profiles WHERE id=?');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['username'])) json(['error' => 'Profile not found'], 404);

    $s = fetchOnlyFansSummary($row['username']);
    if (!$s) json(['error' => 'Upstream fetch failed'], 502);

    $up = $db->prepare('
      UPDATE profiles
      SET avatar_url=?, likes=?, display_name=?, subscribe_price=?, photos_count=?, videos_count=?
      WHERE id=?
    ');
    $up->execute([
      $s['avatar_url'] ?? null,
      $s['likes'] ?? 0,
      $s['display_name'] ?? null,
      $s['subscribe_price'] ?? null,
      $s['photos_count'] ?? 0,
      $s['videos_count'] ?? 0,
      $id
    ]);

    json(['ok' => true] + $s);
  });
}

// DELETE /api/profiles/{id}
if (preg_match('#^/api/profiles/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
  safe(function() use ($db, $m) {
    requireAuth();
    $id = (int)$m[1];
    $db->prepare('DELETE FROM profiles WHERE id=?')->execute([$id]);
    json(['ok' => true]);
  });
}

/* ---------------- Fallback ---------------- */
json(['error' => 'Not found'], 404);
