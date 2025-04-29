<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Correct path to Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';  // Use the correct relative path to vendor/autoload.php

// Correct path to db.php
require_once __DIR__ . '/includes/db.php';  // Adjusted for root location

// Fetch settings from the database
$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch();

// Fetch email subject and body from the settings table
$email_subject = $settings['email_subject'] ?? 'Submission from {form}';
$email_body_template = $settings['email_body'] ?? 'New submission from {form}:<br><br>{fields}';

// Sample data for testing
$form_data = [
    'form_name' => 'Test Form',
    'fields' => 'Name: John Doe, Email: john@example.com, Message: Hello!',
];

// Build email body with submission data
$field_rows = "<tr><td><strong>Name</strong></td><td>John Doe</td></tr>";
$field_rows .= "<tr><td><strong>Email</strong></td><td>john@example.com</td></tr>";
$field_rows .= "<tr><td><strong>Message</strong></td><td>Hello!</td></tr>";

$field_table = "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; font-family: Arial, sans-serif;'>";
$field_table .= "<thead><tr style='background-color: #f2f2f2;'><th>Field</th><th>Value</th></tr></thead><tbody>";
$field_table .= $field_rows . "</tbody></table>";

$final_subject = str_replace('{form}', htmlspecialchars((string)$form_data['form_name']), $email_subject);
$final_body = str_replace(
    ['{form}', '{fields}', '{ip}', '{date}'],
    [htmlspecialchars((string)$form_data['form_name']), $field_table, $_SERVER['REMOTE_ADDR'], date('Y-m-d H:i:s')],
    $email_body_template
);

// Debugging: Output the SMTP settings used
echo "<pre>";
echo "SMTP Host: " . $settings['smtp_host'] . "<br>";
echo "SMTP Username: " . $settings['smtp_username'] . "<br>";
echo "SMTP Password: " . $settings['smtp_password'] . "<br>";
echo "SMTP Secure: " . $settings['smtp_secure'] . "<br>"; // Should be empty or null for no encryption
echo "SMTP Port: " . $settings['smtp_port'] . "<br>";
echo "From Email: " . $settings['from_email'] . "<br>";
echo "To Email: " . $settings['email_to'] . "<br>";
echo "</pre>";

// Send email using PHPMailer with SMTP details from settings
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = $settings['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $settings['smtp_username'];
    $mail->Password   = $settings['smtp_password'];

    // Make sure SMTP Secure is empty for no encryption (auto-negotiate encryption)
    $mail->SMTPSecure = '';  // This ensures no encryption (auto-negotiated)
    $mail->Port       = $settings['smtp_port'];

    $mail->setFrom($settings['from_email'], 'Form Builder');
    $mail->addAddress($settings['email_to']);  // Send to the email_to field from settings
    $mail->isHTML(true);
    $mail->Subject = $final_subject;
    $mail->Body    = $final_body;

    // Send the email
    $mail->send();
    echo 'Test email sent successfully to ' . htmlspecialchars($settings['email_to']);
} catch (Exception $e) {
    echo 'Failed to send test email: ' . $mail->ErrorInfo;
}
?>
