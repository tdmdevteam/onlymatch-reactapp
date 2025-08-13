<?php
require __DIR__ . '/../bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ---------- Public Endpoints ----------

// GET /api/profiles  → list profiles
if ($path === '/api/profiles' && $_SERVER['REQUEST_METHOD'] === 'GET') {
  $q = $db->query('SELECT id, name, bio, avatar_url, created_at FROM profiles ORDER BY created_at DESC');
  json($q->fetchAll(PDO::FETCH_ASSOC));
}

// GET /api/profiles/{id} → one profile
if (preg_match('#^/api/profiles/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
  $stmt = $db->prepare('SELECT id, name, bio, avatar_url, created_at FROM profiles WHERE id = ?');
  $stmt->execute([$m[1]]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $row ? json($row) : json(['error' => 'Not found'], 404);
}

// ---------- Admin Auth ----------

// POST /api/login { email, password }
if ($path === '/api/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = body_json();
  $email = $data['email'] ?? '';
  $password = $data['password'] ?? '';

  $stmt = $db->prepare('SELECT id, password_hash FROM admins WHERE email = ?');
  $stmt->execute([$email]);
  $admin = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($admin && password_verify($password, $admin['password_hash'])) {
    $_SESSION['admin_id'] = (int)$admin['id'];
    json(['ok' => true]);
  }
  json(['error' => 'Invalid credentials'], 401);
}

// POST /api/logout
if ($path === '/api/logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  session_destroy();
  json(['ok' => true]);
}

// ---------- Admin (protected) ----------

// POST /api/profiles  (multipart/form-data: name, bio, avatar)
if ($path === '/api/profiles' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  requireAuth();

  $name = $_POST['name'] ?? '';
  $bio  = $_POST['bio'] ?? '';
  if (!$name) json(['error' => 'name required'], 400);

  // Handle image upload (optional)
  $avatarUrl = null;
  if (!empty($_FILES['avatar']['tmp_name'])) {
    $uploadsDir = __DIR__ . '/uploads';
    if (!is_dir($uploadsDir)) {
      mkdir($uploadsDir, 0777, true);
    }
    $ext = strtolower(pathinfo($_FILES['avatar']['name'] ?? '', PATHINFO_EXTENSION) ?: 'jpg');
    $fname = uniqid('avatar_', true) . '.' . $ext;
    $dest = $uploadsDir . '/' . $fname;

    if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
      json(['error' => 'upload failed'], 500);
    }
    // URL path exposed by PHP server
    $avatarUrl = '/uploads/' . $fname;
  }

  $stmt = $db->prepare('INSERT INTO profiles (name, bio, avatar_url) VALUES (?, ?, ?)');
  $stmt->execute([$name, $bio, $avatarUrl]);

  json([
    'id' => (int)$db->lastInsertId(),
    'name' => $name,
    'bio' => $bio,
    'avatar_url' => $avatarUrl
  ], 201);
}

// DELETE /api/profiles/{id}
if (preg_match('#^/api/profiles/(\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
  requireAuth();
  $id = (int)$m[1];

  // optionally remove the file too
  $stmt = $db->prepare('SELECT avatar_url FROM profiles WHERE id = ?');
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  $del = $db->prepare('DELETE FROM profiles WHERE id = ?');
  $del->execute([$id]);

  if ($row && !empty($row['avatar_url'])) {
    $file = __DIR__ . $row['avatar_url']; // '/uploads/xyz.jpg'
    if (is_file($file)) @unlink($file);
  }

  json(['ok' => true]);
}

// Fallback
json(['error' => 'Not found'], 404);
