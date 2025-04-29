<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? 'external';
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $password = $_POST['password'] ?? '';
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (name, username, email, role, is_admin, password, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$name, $username, $email, $role, $is_admin, $hashed]);

    header("Location: users.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Add New User</h1>

    <form method="post">
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control">
        </div>

        <div class="mb-3">
            <label>Role</label>
            <select name="role" class="form-select">
                <option value="internal">Internal</option>
                <option value="external">External</option>
            </select>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="is_admin" class="form-check-input" id="adminCheck">
            <label class="form-check-label" for="adminCheck">Is Admin</label>
        </div>

        <div class="mb-3">
            <label>Temporary Password</label>
            <input type="text" name="password" class="form-control" required placeholder="Temporary password to be emailed or changed">
        </div>

        <button class="btn btn-success">Create User</button>
        <a href="users.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
