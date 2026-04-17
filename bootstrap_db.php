<?php
$env = parse_ini_file(__DIR__ . '/.env');
if ($env === false) {
    fwrite(STDERR, "Kon .env niet lezen.\n");
    exit(1);
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $env['DB_HOST'] ?? '127.0.0.1',
    $env['DB_PORT'] ?? '3306',
    $env['DB_DATABASE'] ?? ''
);

$pdo = new PDO($dsn, $env['DB_USERNAME'] ?? '', $env['DB_PASSWORD'] ?? '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$sql = file_get_contents(__DIR__ . '/schema.sql');
if ($sql === false) {
    fwrite(STDERR, "Kon schema.sql niet lezen.\n");
    exit(1);
}

$pdo->exec($sql);
echo "Database bootstrap voltooid.\n";
