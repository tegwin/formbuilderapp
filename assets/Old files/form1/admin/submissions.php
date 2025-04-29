<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Fetch submissions
$stmt = $pdo->query("SELECT * FROM submissions ORDER BY created_at DESC");
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submissions - Form Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Form Builder</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="mb-4">Form Submissions</h1>

        <?php if (count($submissions)): ?>
            <div class="table-responsive">
                <table class="table table-bordered bg-white shadow-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Form Name</th>
                            <th>Data</th>
                            <th>Submitted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><?= htmlspecialchars($submission['id']) ?></td>
                                <td><?= htmlspecialchars($submission['form_name']) ?></td>
                                <td><pre><?= htmlspecialchars($submission['submission_data']) ?></pre></td>
                                <td><?= htmlspecialchars($submission['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No submissions yet.</div>
        <?php endif; ?>
    </div>
</body>
</html>
