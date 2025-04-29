<?php
require_once __DIR__ . '/../includes/db.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store token
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expires]);

        // Send email
        require_once __DIR__ . '/../includes/smtp_config.php';
        require_once 'C:/vendor/phpmailer/PHPMailer.php';
        require_once 'C:/vendor/phpmailer/SMTP.php';
        require_once 'C:/vendor/phpmailer/Exception.php';

        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;

        $resetLink = "http://localhost/admin/reset_password.php?token=$token";

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $smtp['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtp['username'];
            $mail->Password = $smtp['password'];
            $mail->SMTPSecure = $smtp['secure'];
            $mail->Port = $smtp['port'];

            $mail->setFrom($smtp['username'], 'Form Builder');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset';
            $mail->Body = "Hi,<br><br>You requested a password reset. <a href=\"$resetLink\">Click here to reset your password</a><br><br>This link expires in 1 hour.";

            $mail->send();
            $success = true;
        } catch (Exception $e) {
            $error = 'Mailer Error: ' . $mail->ErrorInfo;
        }
    } else {
        $error = 'No account found with that email address.';
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
<body class="bg-light">
<div class="container py-5">
    <h2>Forgot Password</h2>

    <?php if ($success): ?>
        <div class="alert alert-success">Check your email for a reset link.</div>
    <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label>Email address</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <button class="btn btn-primary">Send Reset Link</button>
    </form>
</div>
</body>
</html>
