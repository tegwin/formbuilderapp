<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Fetch settings
$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = trim($_POST['site_name'] ?? '');
    $webhook_url = trim($_POST['webhook_url'] ?? '');

    if ($settings) {
        // Update existing
        $stmt = $pdo->prepare("UPDATE settings SET site_name = ?, webhook_url = ? WHERE id = ?");
        $result = $stmt->execute([$site_name, $webhook_url, $settings['id']]);
    } else {
        // Insert new
        $stmt = $pdo->prepare("INSERT INTO settings (site_name, webhook_url) VALUES (?, ?)");
        $result = $stmt->execute([$site_name, $webhook_url]);
    }

    if ($result) {
        $success = 'Settings updated successfully!';
        header("Refresh:0");
    } else {
        $error = 'Failed to update settings.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - Form Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Form Builder</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="submissions.php">Submissions</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="mb-4">Site Settings</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label for="site_name" class="form-label">Site Name</label>
                <input type="text" class="form-control" name="site_name" id="site_name" required
                    value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="webhook_url" class="form-label">Webhook URL</label>
                <input type="text" class="form-control" name="webhook_url" id="webhook_url"
                    value="<?= htmlspecialchars($settings['webhook_url'] ?? '') ?>">
                <small class="text-muted">Optional. Form submissions will POST to this URL as JSON if set.</small>
            </div>

            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
</body>
</html>
