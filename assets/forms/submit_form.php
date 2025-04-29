<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/db.php';

$form_id = $_GET['form_id'] ?? 0;

// Fetch form
$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch();

// Fetch settings
$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch();
$admin_email = $settings['admin_email'] ?? '';
$email_subject = $settings['email_subject'] ?? 'Submission from {form}';
$email_body_template = $settings['email_body'] ?? 'New submission from {form}:<br><br>{fields}';

// Prepare uploads
$uploads_dir = __DIR__ . '/../uploads/';
if (!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0755, true);
}

// Handle form fields
$final_data = [];

foreach ($_POST as $key => $value) {
    if (is_array($value)) {
        $value = implode(', ', $value);
    }
    $final_data[$key] = $value;
}

foreach ($_FILES as $key => $file) {
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $safe_name = uniqid('', true) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploads_dir . $safe_name)) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                $final_data[$key] = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2) . '/uploads/' . $safe_name;
            } else {
                $final_data[$key] = 'Upload failed';
            }
        } else {
            $final_data[$key] = 'Invalid file type';
        }
    } else {
        $final_data[$key] = '';
    }
}

// Save to submissions table
$entry_data = json_encode($final_data);
$stmt = $pdo->prepare("INSERT INTO submissions (form_id, entry_data, user_ip, user_agent) VALUES (?, ?, ?, ?)");
$stmt->execute([
    $form_id,
    $entry_data,
    $_SERVER['REMOTE_ADDR'] ?? '',
    $_SERVER['HTTP_USER_AGENT'] ?? ''
]);

// Send webhook (KEEPING YOUR WEBHOOK)
if (!empty($form['webhook_url'])) {
    $payload = json_encode($final_data);
    $ch = curl_init($form['webhook_url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_exec($ch);
    curl_close($ch);
}

// Build email content
$field_rows = '';
foreach ($final_data as $key => $value) {
    $label = ucwords(str_replace('_', ' ', $key));
    $field_rows .= "<tr><td><strong>" . htmlspecialchars($label) . "</strong></td><td>" . nl2br(htmlspecialchars((string)$value)) . "</td></tr>";
}

$field_table = "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; font-family: Arial, sans-serif;'>";
$field_table .= "<thead><tr style='background-color: #f2f2f2;'><th>Field</th><th>Value</th></tr></thead><tbody>";
$field_table .= $field_rows . "</tbody></table>";

$final_subject = str_replace('{form}', htmlspecialchars((string)$form['form_name']), $email_subject);
$final_body = str_replace(
    ['{form}', '{fields}', '{ip}', '{date}'],
    [htmlspecialchars((string)$form['form_name']), $field_table, $_SERVER['REMOTE_ADDR'], date('Y-m-d H:i:s')],
    $email_body_template
);

// Send email using simple PHP mail()
if ($admin_email && filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: " . $admin_email . "\r\n";

    @mail($admin_email, $final_subject, $final_body, $headers);
}

// Thank you message with Bootstrap styling
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Thank You</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <div class="alert alert-success text-center">
        ✅ Thank you for your submission!
    </div>
    <div class="text-center">
        <a href="../admin/forms.php" class="btn btn-primary">Back to Forms</a>
    </div>
</div>
</body>
</html>
