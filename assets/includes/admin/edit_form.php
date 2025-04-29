<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: forms.php');
    exit;
}

// Fetch form details
$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
$stmt->execute([$id]);
$form = $stmt->fetch();

if (!$form) {
    header('Location: forms.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_name = trim($_POST['form_name']);

    if (empty($form_name)) {
        $error = "Form name is required.";
    } else {
        $stmt = $pdo->prepare("UPDATE forms SET form_name = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$form_name, $id]);
        header('Location: forms.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Form</title>
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
    <h1 class="mb-4">Edit Form</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="bg-white p-4 rounded shadow-sm">
        <div class="mb-3">
            <label class="form-label">Form Name</label>
            <input type="text" name="form_name" class="form-control" value="<?= htmlspecialchars($form['form_name']) ?>" required>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="forms.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
