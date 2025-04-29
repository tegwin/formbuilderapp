<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// ? Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use RobThree\Auth\TwoFactorAuth;

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "User not found.";
    exit;
}

if (empty($user['2fa_secret'])) {
    // Generate a new 2FA secret
    $tfa = new TwoFactorAuth('Form Builder');
    $secret = $tfa->createSecret();

    // Save to user record
    $stmt = $pdo->prepare("UPDATE users SET 2fa_secret = ?, 2fa_enabled = 1 WHERE id = ?");
    $stmt->execute([$secret, $user_id]);
} else {
    $secret = $user['2fa_secret'];
    $tfa = new TwoFactorAuth('Form Builder');
}

// Handle verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $code = $_POST['token'];
    if ($tfa->verifyCode($secret, $code)) {
        $_SESSION['2fa_verified'] = true;
        $redirect = $_GET['redirect'] ?? 'dashboard.php';
        header("Location: $redirect");
        exit;
    } else {
        $error = "Invalid 2FA code. Try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>2FA Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h2 class="mb-3">Two-Factor Authentication (2FA)</h2>
    <p>Scan this QR code using your preferred 2FA app (e.g., Google Authenticator):</p>
    <img src="<?= $tfa->getQRCodeImageAsDataUri($user['username'], $secret) ?>" alt="QR Code">

    <form method="POST" class="mt-4">
        <div class="mb-3">
            <label for="token" class="form-label">Enter the 6-digit code from your app:</label>
            <input type="text" name="token" id="token" class="form-control" required>
        </div>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">? Verify & Continue</button>
    </form>
</div>
</body>
</html>
