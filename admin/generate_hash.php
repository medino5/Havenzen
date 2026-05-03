<?php
// Usage: php admin/generate_hash.php "your-temporary-password"
$password = $argv[1] ?? '';

if ($password === '') {
    die("Usage: php admin/generate_hash.php \"your-temporary-password\"\n");
}

$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Hashed password: " . $hash . PHP_EOL;
?>
