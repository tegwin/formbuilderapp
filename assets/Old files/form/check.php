<?php
// Display errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Form Builder Diagnostics</h1>";

// Check config file
echo "<h2>Config File</h2>";
if (file_exists('config.php')) {
    echo "Config file exists<br>";
    try {
        $config = require_once('config.php');
        echo "Config loaded successfully<br>";
        echo "Database: {$config['db_host']}/{$config['db_name']}<br>";
    } catch (Throwable $e) {
        echo "Error loading config: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Config file missing<br>";
}

// Check directories
echo "<h2>Directories</h2>";
$dirs = [
    'includes',
    'includes/admin',
    'includes/frontend',
    'includes/api',
    'includes/database',
    'assets',
    'assets/css',
    'assets/js',
    'templates',
    'uploads'
];

foreach ($dirs as $dir) {
    echo "$dir: " . (is_dir($dir) ? "✓ Exists" : "✗ Missing") . "<br>";
}

// Check key files
echo "<h2>Required Files</h2>";
$files = [
    'includes/admin/class-dfb-admin-standalone.php',
    'includes/frontend/class-dfb-form-display.php',
    'includes/frontend/class-dfb-form-handler-standalone.php',
    'includes/api/class-dfb-webhook.php',
    'includes/database/class-dfb-db-standalone.php'
];

foreach ($files as $file) {
    echo "$file: " . (file_exists($file) ? "✓ Exists" : "✗ Missing") . "<br>";
}

// Test database connection
echo "<h2>Database Test</h2>";
try {
    if (isset($config)) {
        $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']}";
        $db = new PDO($dsn, $config['db_user'], $config['db_password']);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "Database connection successful<br>";
        
        // Test if tables exist
        $tables = ['dfb_forms', 'dfb_form_fields', 'dfb_form_entries'];
        foreach ($tables as $table) {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            echo "$table: " . ($stmt->rowCount() > 0 ? "✓ Exists" : "✗ Missing") . "<br>";
        }
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

echo "<h2>PHP Information</h2>";
echo "PHP Version: " . phpversion() . "<br>";

// Check extensions
$extensions = ['pdo', 'pdo_mysql', 'mysqli', 'json', 'session'];
foreach ($extensions as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? "✓ Enabled" : "✗ Disabled") . "<br>";
}