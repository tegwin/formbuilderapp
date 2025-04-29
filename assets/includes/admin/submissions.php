<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

// Fetch all submissions
$stmt = $pdo->query("SELECT * FROM submissions ORDER BY submitted_at DESC");
$submissions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Submissions</title>
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
                <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link text-warning" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h1 class="mb-4">Submissions</h1>

    <?php if (count($submissions) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Form ID</th>
                        <th>Data</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?= htmlspecialchars($submission['form_id']) ?></td>
                            <td><pre><?= htmlspecialchars($submission['data']) ?></pre></td>
                            <td><?= date('Y-m-d H:i', strtotime($submission['submitted_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No submissions yet.</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
