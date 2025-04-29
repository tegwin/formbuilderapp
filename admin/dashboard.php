<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

// Count stats for widgets
$formCount = $pdo->query("SELECT COUNT(*) FROM forms")->fetchColumn();
$submissionCount = $pdo->query("SELECT COUNT(*) FROM submissions")->fetchColumn();
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Form Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Welcome to the Admin Dashboard</h1>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow border-0">
                <div class="card-body">
                    <h5 class="card-title">📝 Forms</h5>
                    <p class="card-text display-6"><?= $formCount ?></p>
                    <a href="forms.php" class="btn btn-outline-primary">Manage Forms</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow border-0">
                <div class="card-body">
                    <h5 class="card-title">📬 Submissions</h5>
                    <p class="card-text display-6"><?= $submissionCount ?></p>
                    <a href="submissions.php" class="btn btn-outline-success">View Submissions</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow border-0">
                <div class="card-body">
                    <h5 class="card-title">👤 Users</h5>
                    <p class="card-text display-6"><?= $userCount ?></p>
                    <a href="users.php" class="btn btn-outline-dark">Manage Users</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
