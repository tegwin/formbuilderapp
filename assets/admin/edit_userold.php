<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$user_id = $_GET['id'] ?? null;

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die('❌ User not found.');
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $is_external = isset($_POST['is_external']) ? 1 : 0;

    // Optional: Password reset
    $password_update = '';
    $params = [$username, $email, $is_admin, $is_external, $user_id];

    if (!empty($_POST['new_password'])) {
        $password_update = ", password = ?";
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $params = [$username, $email, $is_admin, $is_external, $new_password, $user_id];
    }

    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, is_admin = ?, is_external = ? $password_update WHERE id = ?");
    $stmt->execute($params);

    header('Location: users.php');
    exit;
}
?>

<?php include '../includes/navbar.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Edit User</h1>

    <form method="post" class="card card-body">
        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars((string)($user['username'] ?? '')) ?>" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars((string)($user['email'] ?? '')) ?>">
        </div>

        <div class="form-check mb-2">
            <input type="checkbox" class="form-check-input" name="is_admin" id="isAdmin" <?= !empty($user['is_admin']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="isAdmin">Admin</label>
        </div>

        <div class="form-check mb-4">
            <input type="checkbox" class="form-check-input" name="is_external" id="isExternal" <?= !empty($user['is_external']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="isExternal">External User</label>
        </div>

        <hr class="my-4">

        <h5>Reset Password (optional)</h5>
        <div class="mb-3">
            <label>New Password</label>
            <input type="text" name="new_password" class="form-control" placeholder="Leave blank to keep current password">
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-success btn-lg">💾 Save Changes</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
