<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

// CHANGE THESE for your real admin:
$email = 'admin@example.com';
$password = 'changeme123';

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $db->prepare('INSERT OR IGNORE INTO admins (email, password_hash) VALUES (?, ?)');
$stmt->execute([$email, $hash]);

echo "Admin seeded: {$email} / {$password}\n";
