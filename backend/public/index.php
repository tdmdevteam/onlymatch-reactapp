<?php
// Let the built-in server serve real files (e.g. /uploads/..)
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
  return false;
}

require __DIR__ . '/../bootstrap.php';

function safe(callable $fn) {
  try { $fn(); } catch (Throwable $e) { json(['error' => $e->getMessage()], 500); }
}


if ($path === '/api/profiles' && $_SERVER['REQUEST_METHOD'] === 'GET') {
  safe(function() use ($db) {
    $q = $db->query('SELECT id,name,bio,avatar_url,onlyfans_url,created_at FROM profiles ORDER BY created_at DESC');
    json($q->fetchAll(PDO::FETCH_ASSOC));
  });
}

if (preg_match('#^/api/profiles/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
  safe(function() use ($db, $m) {
    $st = $db->prepare('SELECT id,name,bio,avatar_url,onlyfans_url,created_at FROM profiles WHERE id=?');
    $st->execute([(int)$m[1]]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $row ? json($row) : json(['error'=>'Not found'], 404);
  });
}


if ($path === '/api/me' && $_SERVER['REQUEST_METHOD'] === 'GET') {
  safe(function() {
    if (!empty($_SESSION['admin_id'])) json(['ok'=>true,'admin_id'=>(int)$_SESSION['admin_id']]);
    json(['error'=>'Unauthorized'], 401);
  });
}

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
      json(['ok'=>true]);
    }
    json(['error'=>'Invalid credentials'], 401);
  });
}

// POST /api/logout
if ($path === '/api/logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  session_destroy();
  json(['ok'=>true]);
}

/* ---------- Admin-only ---------- */

// POST /api/profiles  (multipart: name, bio, avatar?, onlyfans_url?)
if ($path === '/api/profiles' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  safe(function() use ($db) {
    requireAuth();

    $name = trim($_POST['name'] ?? '');
    $bio  = trim($_POST['bio'] ?? '');
    if ($name === '') json(['error'=>'name required'], 400);

    // OnlyFans URL (optional, validate)
    $onlyfans = trim($_POST['onlyfans_url'] ?? '');
    if ($onlyfans !== '') {
      if (!preg_match('#^https?://#i', $onlyfans)) {
        $onlyfans = 'https://' . $onlyfans;
      }
      if (!preg_match('#^https?://(www\.)?onlyfans\.com/[^/\s]+$#i', $onlyfans)) {
        json(['error'=>'onlyfans_url must look like onlyfans.com/username'], 400);
      }
    } else {
      $onlyfans = null;
    }

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

    // Insert
    $st = $db->prepare('INSERT INTO profiles(name,bio,avatar_url,onlyfans_url) VALUES(?,?,?,?)');
    $st->execute([$name, $bio, $avatarUrl, $onlyfans]);

    json([
      'id' => (int)$db->lastInsertId(),
      'name' => $name,
      'bio' => $bio,
      'avatar_url' => $avatarUrl,
      'onlyfans_url' => $onlyfans
    ], 201);
  });
}

// DELETE /api/profiles/{id}
if (preg_match('#^/api/profiles/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
  safe(function() use ($db, $m) {
    requireAuth();
    $id = (int)$m[1];

    $st = $db->prepare('SELECT avatar_url FROM profiles WHERE id=?');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    $del = $db->prepare('DELETE FROM profiles WHERE id=?');
    $del->execute([$id]);

    if ($row && !empty($row['avatar_url'])) {
      $f = __DIR__ . $row['avatar_url'];
      if (is_file($f)) @unlink($f);
    }
    json(['ok'=>true]);
  });
}

/* ---------- Fallback ---------- */
json(['error' => 'Not found'], 404);
