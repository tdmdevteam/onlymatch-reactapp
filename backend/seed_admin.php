<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$email = strtolower(trim($argv[1] ?? 'dan@onlymatch.com'));
$pass  = $argv[2] ?? '1';

$hash = password_hash($pass, PASSWORD_DEFAULT);
$st = $db->prepare('INSERT OR REPLACE INTO admins (id,email,password_hash) VALUES (1,?,?)');
$st->execute([$email, $hash]);

echo "Seeded admin:\n  email: {$email}\n  password: {$pass}\n";
