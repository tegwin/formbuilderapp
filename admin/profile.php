<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password && $password === $confirm_password) {
        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $stmt->execute([password_hash($password, PASSWORD_BCRYPT), $_SESSION['admin_id']]);
        $success = 'Password updated successfully.';
    } else {
        $error = 'Passwords do not match.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Form Builder</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="forms.php">Forms</a></li>
                <li class="nav-item"><a class="nav-link" href="submissions.php">Submissions</a></li>
                <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <h1 class="mb-4">Profile Settings</h1>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label>New Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Change Password</button>
    </form>
</div>
</body>
</html>
