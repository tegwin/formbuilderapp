<?php
$config = require __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['name']}",
        $config['user'],
        $config['pass']
    );
    echo "✅ Connected successfully to MySQL using TCP!";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
