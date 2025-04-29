<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

// Fetch all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY id ASC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
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
                <li class="nav-item"><a class="nav-link text-warning" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Manage Users</h1>
        <a href="new_user.php" class="btn btn-success">+ Add New User</a>
    </div>

    <?php if (count($users) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= $user['is_admin'] ? 'Admin' : 'User' ?></td>
                            <td>
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="delete_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure to delete this user?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No users found. Add some!</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
