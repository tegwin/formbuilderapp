<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Check if the email exists in the users table
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Generate a random token for password reset
        $reset_token = bin2hex(random_bytes(16)); // 32 characters long

        // Save the reset token in the database (with an expiration date)
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiration = NOW() + INTERVAL 1 HOUR WHERE email = ?");
        $stmt->execute([$reset_token, $email]);

        // Send the reset email
        $reset_link = "https://yourdomain.com/formbuilder/reset_password.php?token=$reset_token";
        $subject = "Password Reset Request";
        $body = "Click the link below to reset your password: \n\n$reset_link";

        mail($email, $subject, $body); // Simple PHP mail function, configure with SMTP if needed

        echo "Password reset email sent.";
    } else {
        echo "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Forgot Password</h1>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Send Reset Link</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
