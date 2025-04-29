<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/db.php';
$smtp = require_once __DIR__ . '/../includes/smtp_config.php';
require_once __DIR__ . '/../vendor/autoload.php'; // ✅ Correct Composer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $smtp['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp['username'];
    $mail->Password   = $smtp['password'];
    $mail->SMTPSecure = $smtp['secure'];
    $mail->Port       = $smtp['port'];

    $mail->setFrom($smtp['username'], 'Form Builder Test');
    $mail->addAddress($smtp['username']); // Send to yourself for testing
    $mail->isHTML(true);
    $mail->Subject = '✅ Test Email from Form Builder';
    $mail->Body = '
        <html>
        <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #4CAF50;">✅ SMTP Test Successful!</h1>
            <p>If you see this email, your SMTP configuration is working correctly.</p>
            <hr>
            <h3>Configuration Details:</h3>
            <ul>
                <li><strong>SMTP Host:</strong> ' . htmlspecialchars($smtp['host']) . '</li>
                <li><strong>SMTP Port:</strong> ' . htmlspecialchars($smtp['port']) . '</li>
                <li><strong>Secure Method:</strong> ' . htmlspecialchars($smtp['secure']) . '</li>
                <li><strong>Sent at:</strong> ' . date('Y-m-d H:i:s') . '</li>
            </ul>
        </body>
        </html>
    ';
    $mail->AltBody = 'SMTP Test Successful! Check your email configuration.';

    $mail->send();

    echo "
    <div style='max-width: 600px; margin: 20px auto; padding: 20px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px;'>
        <h2>✅ Test Email Sent Successfully!</h2>
        <p>An email has been sent to: " . htmlspecialchars($smtp['username']) . "</p>
        <p>Please check your inbox (and spam folder) to confirm.</p>
    </div>
    <div class='text-center'>
        <a href='../admin/dashboard.php' class='btn btn-primary'>Back to Dashboard</a>
    </div>
    ";
} catch (Exception $e) {
    error_log("Email Test Failed: " . $mail->ErrorInfo);

    echo "
    <div style='max-width: 600px; margin: 20px auto; padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px;'>
        <h2>❌ Failed to Send Test Email</h2>
        <p><strong>Error Details:</strong> " . htmlspecialchars($mail->ErrorInfo) . "</p>
        <p>Please check your SMTP configuration and try again.</p>
    </div>
    <div class='text-center'>
        <a href='../admin/dashboard.php' class='btn btn-danger'>Back to Dashboard</a>
    </div>
    ";
}
?>
