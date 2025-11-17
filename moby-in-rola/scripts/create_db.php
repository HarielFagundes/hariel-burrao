<?php
// Cria o banco configurado em config/config.php
$config = require __DIR__ . '/../config/config.php';
$host = $config['db']['host'] ?? '127.0.0.1';
$user = $config['db']['user'] ?? 'root';
$pass = $config['db']['pass'] ?? '';
$dbname = $config['db']['dbname'] ?? 'cafeteria';
try {
    // Conectamos ao servidor sem selecionar um DB
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "Database '$dbname' created or already exists.\n";
    exit(0);
} catch (PDOException $e) {
    fwrite(STDERR, "Erro ao criar o banco: " . $e->getMessage() . "\n");
    exit(1);
}
