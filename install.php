<?php
// install.php - Fresh, fixed installer

session_start();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['db_host'] ?? 'localhost';
    $name = $_POST['db_name'] ?? '';
    $user = $_POST['db_user'] ?? '';
    $pass = $_POST['db_pass'] ?? '';
    $site = $_POST['site_name'] ?? 'Form Builder';
    $admin = $_POST['admin_user'] ?? 'admin';
    $admin_pass = password_hash($_POST['admin_pass'] ?? 'admin123', PASSWORD_DEFAULT);

    try {
        $pdo = new PDO("mysql:host=$host", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$name`");

        // SETTINGS TABLE
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            site_name VARCHAR(255),
            admin_email VARCHAR(255),
            banner_color VARCHAR(20) DEFAULT '#007bff',
            logo_path VARCHAR(255),
            email_subject VARCHAR(255),
            email_body TEXT
        )");
        $pdo->exec("INSERT INTO settings (site_name, admin_email, email_subject, email_body)
                    VALUES ('$site', '', 'Submission from {form}', 'New submission from {form}:<br><br>{fields}')");

        // USERS TABLE
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            email VARCHAR(255),
            username VARCHAR(255) UNIQUE,
            password VARCHAR(255),
            is_admin TINYINT(1) DEFAULT 0,
            is_external TINYINT(1) DEFAULT 0,
            twofa_enabled TINYINT(1) DEFAULT 0,
            twofa_secret VARCHAR(255),
            avatar_path VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$admin]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, username, password, is_admin) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute(['Admin User', 'admin@example.com', $admin, $admin_pass]);
        }

        // FORMS
        $pdo->exec("CREATE TABLE IF NOT EXISTS forms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            form_name VARCHAR(255),
            webhook_url VARCHAR(255),
            is_public TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // FIELDS
        $pdo->exec("CREATE TABLE IF NOT EXISTS form_fields (
            id INT AUTO_INCREMENT PRIMARY KEY,
            form_id INT,
            field_type VARCHAR(50),
            label VARCHAR(255),
            name VARCHAR(255),
            required TINYINT(1),
            options TEXT,
            dependency_field VARCHAR(255),
            dependency_value VARCHAR(255),
            FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
        )");

        // SUBMISSIONS
        $pdo->exec("CREATE TABLE IF NOT EXISTS submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            form_id INT,
            entry_data LONGTEXT,
            user_ip VARCHAR(100),
            user_agent TEXT,
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // FORM ASSIGNMENTS
        $pdo->exec("CREATE TABLE IF NOT EXISTS form_assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            form_id INT,
            user_id INT,
            FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $success = true;
    } catch (PDOException $e) {
        $errors[] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Installer</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container py-5">
    <h1 class="mb-4">Form Builder Installer</h1>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ Install complete! <a href="admin/login.php">Go to Login</a></div>
    <?php elseif (!empty($errors)): ?>
        <div class="alert alert-danger">❌ Install failed: <?= htmlspecialchars($errors[0]) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">DB Host</label>
                <input type="text" name="db_host" class="form-control" value="localhost" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">DB Name</label>
                <input type="text" name="db_name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">DB User</label>
                <input type="text" name="db_user" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">DB Password</label>
                <input type="password" name="db_pass" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">Site Name</label>
                <input type="text" name="site_name" class="form-control" value="Form Builder">
            </div>
            <div class="col-md-6">
                <label class="form-label">Admin Username</label>
                <input type="text" name="admin_user" class="form-control" value="admin">
            </div>
            <div class="col-md-6">
                <label class="form-label">Admin Password</label>
                <input type="text" name="admin_pass" class="form-control" value="admin123">
            </div>
        </div>
        <button class="btn btn-primary mt-4">Install Now</button>
    </form>
</div>
</body>
</html>
