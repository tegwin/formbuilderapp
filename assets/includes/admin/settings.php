<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host']);
    $db_name = trim($_POST['db_name']);
    $db_user = trim($_POST['db_user']);
    $db_pass = trim($_POST['db_pass']);
    $admin_email = trim($_POST['admin_email']);

    // Update settings in your config (OPTIONAL - if you store them dynamically)
    $success = "Settings saved successfully!";
}

// Fetch default values (example placeholders)
$current_settings = [
    'db_host' => 'localhost',
    'db_name' => 'your_database',
    'db_user' => 'root',
    'db_pass' => '',
    'admin_email' => 'admin@example.com',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Form Builder</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="forms.php">Manage Forms</a></li>
                <li class="nav-item"><a class="nav-link" href="submissions.php">View Submissions</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link text-warning" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h1 class="mb-4">Settings</h1>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="bg-white p-4 rounded shadow-sm">
        <h4>Database Settings</h4>

        <div class="mb-3">
            <label class="form-label">DB Host</label>
            <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($current_settings['db_host']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">DB Name</label>
            <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($current_settings['db_name']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">DB User</label>
            <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($current_settings['db_user']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">DB Password</label>
            <input type="password" name="db_pass" class="form-control" value="<?= htmlspecialchars($current_settings['db_pass']) ?>" required>
        </div>

        <h4 class="mt-4">Admin Settings</h4>

        <div class="mb-3">
            <label class="form-label">Admin Email</label>
            <input type="email" name="admin_email" class="form-control" value="<?= htmlspecialchars($current_settings['admin_email']) ?>" required>
        </div>

        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
