<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/lib/TwoFactorAuth.php';

session_start();

if (!isset($_SESSION['2fa_user_id'])) {
    header('Location: login.php');
    exit;
}

$tfa = new \RobThree\Auth\TwoFactorAuth('Form Builder');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    $user_id = $_SESSION['2fa_user_id'];

    // Fetch user's 2FA secret
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user && $user['twofa_secret'] && $tfa->verifyCode($user['twofa_secret'], $code)) {
        // Complete login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['role'] = $user['role'];
        unset($_SESSION['2fa_user_id']);
        header("Location: dashboard.php");
        exit;
    } else {
        $error = 'Invalid code. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>2FA Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2>Two-Factor Verification</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="code">Enter the 6-digit code from your app:</label>
            <input type="text" name="code" class="form-control" required maxlength="6">
        </div>
        <button class="btn btn-primary">Verify</button>
    </form>
</div>
</body>
</html>
