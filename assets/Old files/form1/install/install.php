<?php
session_start();

// Installation steps
$steps = [
    1 => 'Welcome',
    2 => 'Requirements Check',
    3 => 'Database Setup',
    4 => 'Admin Account Setup',
    5 => 'Configuration',
    6 => 'Installation',
    7 => 'Complete'
];

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Required PHP extensions
$required_extensions = ['pdo', 'pdo_mysql', 'session', 'json'];

// Helper: Test DB connection
function test_db_connection($host, $name, $user, $pass) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$name", $user, $pass);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Helper: Create tables
function create_tables($pdo) {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'admin',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    
    CREATE TABLE IF NOT EXISTS submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        form_name VARCHAR(255) NOT NULL,
        submission_data TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    
    CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_name VARCHAR(255),
        webhook_url VARCHAR(500),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    ");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['db_setup'])) {
        $_SESSION['db_host'] = $_POST['db_host'];
        $_SESSION['db_name'] = $_POST['db_name'];
        $_SESSION['db_user'] = $_POST['db_user'];
        $_SESSION['db_pass'] = $_POST['db_pass'];

        if (test_db_connection($_SESSION['db_host'], $_SESSION['db_name'], $_SESSION['db_user'], $_SESSION['db_pass'])) {
            header('Location: install.php?step=4');
            exit;
        } else {
            $db_error = "Database connection failed.";
        }
    }

    if (isset($_POST['admin_setup'])) {
        $_SESSION['admin_username'] = $_POST['admin_username'];
        $_SESSION['admin_email'] = $_POST['admin_email'];
        $_SESSION['admin_password'] = password_hash($_POST['admin_password'], PASSWORD_BCRYPT);
        header('Location: install.php?step=5');
        exit;
    }

    if (isset($_POST['site_setup'])) {
        $_SESSION['site_name'] = $_POST['site_name'];
        header('Location: install.php?step=6');
        exit;
    }

    if (isset($_POST['install_now'])) {
        try {
            $pdo = new PDO(
                "mysql:host={$_SESSION['db_host']};dbname={$_SESSION['db_name']};charset=utf8mb4",
                $_SESSION['db_user'],
                $_SESSION['db_pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );

            create_tables($pdo);

            // Insert admin
            $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$_SESSION['admin_username'], $_SESSION['admin_email'], $_SESSION['admin_password']]);

            // Insert settings
            $stmt = $pdo->prepare("INSERT INTO settings (site_name) VALUES (?)");
            $stmt->execute([$_SESSION['site_name']]);

            // Write config file
            $config = "<?php return [\n" .
                      "    'db_host' => '{$_SESSION['db_host']}',\n" .
                      "    'db_name' => '{$_SESSION['db_name']}',\n" .
                      "    'db_user' => '{$_SESSION['db_user']}',\n" .
                      "    'db_password' => '{$_SESSION['db_pass']}',\n" .
                      "    'site_name' => '{$_SESSION['site_name']}',\n" .
                      "    'admin_email' => '{$_SESSION['admin_email']}',\n" .
                      "    'upload_dir' => 'uploads/',\n" .
                      "    'max_file_size' => 5242880,\n" .
                      "    'allowed_file_types' => 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip',\n" .
                      "    'session_timeout' => 1800\n" .
                      "]; ?>";
            file_put_contents(__DIR__ . '/../config.php', $config);

            header('Location: install.php?step=7');
            exit;
        } catch (PDOException $e) {
            $install_error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Installer - Form Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .steps { margin-bottom: 20px; }
        .step { display: inline-block; padding: 10px 15px; margin-right: 5px; background: #eee; border-radius: 5px; }
        .step.active { background: #0d6efd; color: white; }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="mb-4">Form Builder Installer</h1>

    <div class="steps">
        <?php foreach ($steps as $num => $name): ?>
            <div class="step <?= $step == $num ? 'active' : '' ?>"><?= $num ?>. <?= htmlspecialchars($name) ?></div>
        <?php endforeach; ?>
    </div>

    <div class="card shadow p-4 bg-white">
        <?php if ($step == 1): ?>
            <h4>Welcome</h4>
            <p>This wizard will install the Form Builder system step-by-step.</p>
            <a href="?step=2" class="btn btn-primary">Start Installation</a>

        <?php elseif ($step == 2): ?>
            <h4>Requirements Check</h4>
            <ul>
                <?php foreach ($required_extensions as $ext): ?>
                    <li><?= $ext ?>: <?= extension_loaded($ext) ? '✅ Installed' : '❌ Missing' ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="?step=3" class="btn btn-primary mt-3">Continue</a>

        <?php elseif ($step == 3): ?>
            <h4>Database Setup</h4>
            <?php if (isset($db_error)) echo '<div class="alert alert-danger">' . htmlspecialchars($db_error) . '</div>'; ?>
            <form method="post">
                <div class="mb-3">
                    <label>DB Host</label>
                    <input type="text" name="db_host" class="form-control" required value="localhost">
                </div>
                <div class="mb-3">
                    <label>DB Name</label>
                    <input type="text" name="db_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>DB User</label>
                    <input type="text" name="db_user" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>DB Password</label>
                    <input type="password" name="db_pass" class="form-control">
                </div>
                <button type="submit" name="db_setup" class="btn btn-primary">Save and Continue</button>
            </form>

        <?php elseif ($step == 4): ?>
            <h4>Admin Account Setup</h4>
            <form method="post">
                <div class="mb-3">
                    <label>Admin Username</label>
                    <input type="text" name="admin_username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Admin Email</label>
                    <input type="email" name="admin_email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="admin_password" class="form-control" required>
                </div>
                <button type="submit" name="admin_setup" class="btn btn-primary">Save and Continue</button>
            </form>

        <?php elseif ($step == 5): ?>
            <h4>Site Configuration</h4>
            <form method="post">
                <div class="mb-3">
                    <label>Site Name</label>
                    <input type="text" name="site_name" class="form-control" required>
                </div>
                <button type="submit" name="site_setup" class="btn btn-primary">Save and Continue</button>
            </form>

        <?php elseif ($step == 6): ?>
            <h4>Final Installation</h4>
            <?php if (isset($install_error)) echo '<div class="alert alert-danger">' . htmlspecialchars($install_error) . '</div>'; ?>
            <form method="post">
                <button type="submit" name="install_now" class="btn btn-success">Install Now</button>
            </form>

        <?php elseif ($step == 7): ?>
            <h4>Installation Complete!</h4>
            <p>Form Builder has been successfully installed.</p>
            <a href="../admin/login.php" class="btn btn-success">Go to Admin Login</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
