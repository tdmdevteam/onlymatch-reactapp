<?php
// Router for PHP built-in server: serve static files first (e.g. /uploads/foo.jpg)
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
  return false; // let the built-in server serve the file
}

require __DIR__ . '/../bootstrap.php';

// Helper to return JSON errors instead of HTML fatals
function safe(callable $fn) {
  try { $fn(); } catch (Throwable $e) { json(['error' => $e->getMessage()], 500); }
}

// ---------- Public ----------
if ($path === '/api/profiles' && $_SERVER['REQUEST_METHOD'] === 'GET') {
  safe(function() use ($db) {
    $q = $db->query('SELECT id,name,bio,avatar_url,created_at FROM profiles ORDER BY created_at DESC');
    json($q->fetchAll(PDO::FETCH_ASSOC));
  });
}

if ($path === '/api/me' && $_SERVER['REQUEST_METHOD'] === 'GET') {
  safe(function() {
    if (!empty($_SESSION['admin_id'])) {
      json(['ok' => true, 'admin_id' => (int)$_SESSION['admin_id']]);
    }
    json(['error' => 'Unauthorized'], 401);
  });
}


if (preg_match('#^/api/profiles/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
  safe(function() use ($db, $m) {
    $st = $db->prepare('SELECT id,name,bio,avatar_url,created_at FROM profiles WHERE id=?');
    $st->execute([(int)$m[1]]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $row ? json($row) : json(['error' => 'Not found'], 404);
  });
}

// ---------- Admin auth ----------
if ($path === '/api/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  safe(function() use ($db) {
    $data = body_json();
    $email = $data['email'] ?? '';
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

if ($path === '/api/logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  session_destroy();
  json(['ok' => true]);
}

// ---------- Admin protected ----------
if ($path === '/api/profiles' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  safe(function() use ($db) {
    requireAuth();
    $name = $_POST['name'] ?? '';
    $bio  = $_POST['bio'] ?? '';
    if (!$name) json(['error' => 'name required'], 400);

    $avatarUrl = null;
    if (!empty($_FILES['avatar']['tmp_name'])) {
      $uploads = __DIR__ . '/uploads';
      if (!is_dir($uploads)) mkdir($uploads, 0777, true);
      $ext = strtolower(pathinfo($_FILES['avatar']['name'] ?? '', PATHINFO_EXTENSION) ?: 'jpg');
      $fname = uniqid('avatar_', true) . '.' . $ext;
      if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $uploads . '/' . $fname)) {
        json(['error' => 'upload failed'], 500);
      }
      $avatarUrl = '/uploads/' . $fname;
    }

    $st = $db->prepare('INSERT INTO profiles(name,bio,avatar_url) VALUES(?,?,?)');
    $st->execute([$name,$bio,$avatarUrl]);
    json(['id'=>(int)$db->lastInsertId(), 'name'=>$name, 'bio'=>$bio, 'avatar_url'=>$avatarUrl], 201);
  });
}

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
    json(['ok' => true]);
  });
}

// Fallback
json(['error' => 'Not found'], 404);
