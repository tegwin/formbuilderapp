<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_name = trim($_POST['form_name']);
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $form_name)));
    $created_at = date('Y-m-d H:i:s');

    if (empty($form_name)) {
        $error = "Form name is required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO forms (form_name, slug, created_at, updated_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$form_name, $slug, $created_at, $created_at]);
        header('Location: forms.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create New Form</title>
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
                <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link text-warning" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h1 class="mb-4">Create New Form</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="bg-white p-4 rounded shadow-sm">
        <div class="mb-3">
            <label class="form-label">Form Name</label>
            <input type="text" name="form_name" class="form-control" required autofocus>
            <small class="text-muted">This will also create a URL slug automatically.</small>
        </div>

        <button type="submit" class="btn btn-success">Create Form</button>
        <a href="forms.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
