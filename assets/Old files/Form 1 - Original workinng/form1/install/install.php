<?php
// install/install.php
session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host']);
    $db_name = trim($_POST['db_name']);
    $db_user = trim($_POST['db_user']);
    $db_pass = trim($_POST['db_pass']);

    try {
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `$db_name`;");

        // Create forms table
        $pdo->exec("CREATE TABLE IF NOT EXISTS forms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            fields_json LONGTEXT NOT NULL,
            webhook_url VARCHAR(500) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );");

        // Create submissions table
        $pdo->exec("CREATE TABLE IF NOT EXISTS submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            form_id INT NOT NULL,
            submission_json LONGTEXT NOT NULL,
            webhook_status VARCHAR(50) DEFAULT 'pending',
            webhook_response TEXT,
            ip_address VARCHAR(100),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
        );");

        // Save config
        $config = [
            'db_host' => $db_host,
            'db_name' => $db_name,
            'db_user' => $db_user,
            'db_pass' => $db_pass
        ];
        file_put_contents(__DIR__ . '/../config.php', "<?php\nreturn " . var_export($config, true) . ";\n");

        header('Location: ../admin/index.php');
        exit;
    } catch (PDOException $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Install Form Builder</title>
</head>
<body>
    <h1>Form Builder Installer</h1>

    <?php if ($errors): ?>
        <div style="color: red;">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post">
        <label>Database Host:</label><br>
        <input type="text" name="db_host" value="localhost" required><br><br>

        <label>Database Name:</label><br>
        <input type="text" name="db_name" required><br><br>

        <label>Database User:</label><br>
        <input type="text" name="db_user" required><br><br>

        <label>Database Password:</label><br>
        <input type="password" name="db_pass"><br><br>

        <button type="submit">Install</button>
    </form>
</body>
</html>
S