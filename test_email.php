<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                 // Enable verbose debug output
    $mail->isSMTP();                                       // Send using SMTP
    $mail->Host       = 'mail.smtp2go.com';                // SMTP server
    $mail->SMTPAuth   = true;                              // Enable SMTP authentication
    
    // Try with full email address as username
    $mail->Username   = 'formbuilder1'; // SMTP username
    $mail->Password   = 'HvNg5R9tZeLqtZ1A';                // SMTP password
    
    // Try with PLAIN authentication
    $mail->AuthType = 'PLAIN';
    
    // Try without explicit encryption (let PHPMailer negotiate)
    $mail->SMTPSecure = '';                                // Auto-negotiate encryption
    $mail->Port       = 2525;                              // TCP port to connect to

    // Recipients - make sure these match exactly what's in your SMTP2GO account
    $mail->setFrom('formbuilder1@mspformbuilder.com', 'Form Builder');
    $mail->addAddress('chris@christimm.com', 'Chris');     // Add a recipient

    // Content
    $mail->isHTML(true);                                   // Set email format to HTML
    $mail->Subject = 'Test Email';
    $mail->Body    = 'This is a test email sent using PHPMailer';
    $mail->AltBody = 'This is a test email sent using PHPMailer';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>