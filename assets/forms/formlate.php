<?php
require_once __DIR__ . '/../includes/db.php';
$smtp = require_once __DIR__ . '/../includes/smtp_config.php';
require_once 'C:/vendor/phpmailer/PHPMailer.php';
require_once 'C:/vendor/phpmailer/SMTP.php';
require_once 'C:/vendor/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$form_id = $_GET['form_id'] ?? 0;

// Fetch form
$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch();

// Fetch fields
$stmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY id ASC");
$stmt->execute([$form_id]);
$fields = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entry_data = json_encode($_POST);

    $stmt = $pdo->prepare("INSERT INTO submissions (form_id, entry_data, user_ip, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $form_id,
        $entry_data,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    // Send webhook
    if (!empty($form['webhook_url'])) {
        $payload = json_encode($_POST);
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

    // Send email
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch();
    $admin_email = $settings['admin_email'] ?? '';
    $email_subject = $settings['email_subject'] ?? 'Submission from {form}';
    $email_body_template = $settings['email_body'] ?? 'New submission from {form}:<br><br>{fields}';

    $field_rows = '';
    foreach ($_POST as $key => $value) {
        $label = ucwords(str_replace('_', ' ', $key));
        if (is_array($value)) {
            $value = implode(', ', $value);
        }
        $field_rows .= "<tr><td><strong>$label</strong></td><td>" . nl2br(htmlspecialchars((string)$value)) . "</td></tr>";
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

    if ($admin_email && filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
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
            $mail->addAddress($admin_email);
            $mail->isHTML(true);
            $mail->Subject = $final_subject;
            $mail->Body    = $final_body;
            $mail->send();
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
        }
    }

    echo "<div class='container py-4'><div class='alert alert-success'>✅ Thank you for your submission!</div>";
    echo "<a href='../admin/forms.php' class='btn btn-primary'>Back to Forms</a></div>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars((string)($form['form_name'] ?? '')) ?> - Public Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h1 class="mb-4"><?= htmlspecialchars((string)($form['form_name'] ?? '')) ?></h1>

    <form method="post" enctype="multipart/form-data" id="dynamic-form">
        <?php foreach ($fields as $field): ?>
            <?php
            $isRequired = !empty($field['required']) ? 'required' : '';
            $name = htmlspecialchars((string)$field['name']);
            $label = htmlspecialchars((string)$field['label']);
            ?>

            <div class="mb-3">
                <label class="form-label"><?= $label ?></label>

                <?php if (in_array($field['field_type'], ['text', 'email', 'password', 'date'])): ?>
                    <input type="<?= $field['field_type'] ?>" name="<?= $name ?>" class="form-control" <?= $isRequired ?>>

                <?php elseif ($field['field_type'] === 'textarea'): ?>
                    <textarea name="<?= $name ?>" class="form-control" <?= $isRequired ?>></textarea>

                <?php elseif ($field['field_type'] === 'select'): ?>
                    <select name="<?= $name ?>" class="form-select" <?= $isRequired ?>>
                        <?php foreach (explode(',', $field['options']) as $opt): ?>
                            <option value="<?= trim($opt) ?>"><?= trim($opt) ?></option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ($field['field_type'] === 'multiselect'): ?>
                    <select name="<?= $name ?>[]" class="form-select" multiple <?= $isRequired ?>>
                        <?php foreach (explode(',', $field['options']) as $opt): ?>
                            <option value="<?= trim($opt) ?>"><?= trim($opt) ?></option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ($field['field_type'] === 'checkbox'): ?>
                    <?php foreach (explode(',', $field['options']) as $opt): ?>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="<?= $name ?>[]" value="<?= trim($opt) ?>" id="<?= $name . '_' . trim($opt) ?>">
                            <label class="form-check-label" for="<?= $name . '_' . trim($opt) ?>"><?= trim($opt) ?></label>
                        </div>
                    <?php endforeach; ?>

                <?php elseif ($field['field_type'] === 'upload'): ?>
                    <input type="file" name="<?= $name ?>" class="form-control" <?= $isRequired ?>>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-success">Submit</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
