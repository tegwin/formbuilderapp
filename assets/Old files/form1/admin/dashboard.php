<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Form Builder</title>
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
                    <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="mb-4">Welcome to the Admin Dashboard</h1>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Manage Users</h5>
                        <p class="card-text">Add, edit, or remove admin users.</p>
                        <a href="users.php" class="btn btn-primary">Manage Users</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">View Submissions</h5>
                        <p class="card-text">View form submissions and webhook logs.</p>
                        <a href="submissions.php" class="btn btn-primary">View Submissions</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Settings</h5>
                        <p class="card-text">Manage site settings and webhooks.</p>
                        <a href="settings.php" class="btn btn-primary">Settings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
