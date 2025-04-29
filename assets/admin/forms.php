<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch forms
$stmt = $pdo->query("SELECT * FROM forms ORDER BY created_at DESC");
$forms = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Forms - Form Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">All Forms</h1>
        <a href="new_form.php" class="btn btn-success">➕ Create New Form</a>
    </div>

    <?php if (count($forms) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Form Name</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form): ?>
                        <tr>
                            <td><?= htmlspecialchars($form['form_name']) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($form['created_at'])) ?></td>
                            <td>
                                <a href="edit_form.php?id=<?= $form['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="clone_form.php?id=<?= $form['id'] ?>" class="btn btn-sm btn-secondary">Clone</a>
                                <a href="../forms/form.php?form_id=<?= $form['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank">View</a>
                                <a href="delete_form.php?id=<?= $form['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this form?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No forms created yet.</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
