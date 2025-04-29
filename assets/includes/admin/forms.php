<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

// Optional: Fetch stats (count forms and submissions)
$form_count = $pdo->query("SELECT COUNT(*) FROM forms")->fetchColumn();
$submission_count = $pdo->query("SELECT COUNT(*) FROM submissions")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Form Builder</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
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
    <h1 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm p-4 text-center">
                <h3><?= $form_count ?></h3>
                <p class="text-muted">Total Forms</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm p-4 text-center">
                <h3><?= $submission_count ?></h3>
                <p class="text-muted">Total Submissions</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
