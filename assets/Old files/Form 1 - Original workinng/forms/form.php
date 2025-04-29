<?php
// forms/form.php
require_once __DIR__ . '/../includes/db.php';

// Get slug from URL
$slug = $_GET['slug'] ?? '';

if (!$slug) {
    die('Form slug not found.');
}

// Load form by slug
$stmt = $pdo->prepare("SELECT * FROM forms WHERE slug = ?");
$stmt->execute([$slug]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) {
    die('Form not found.');
}

$fields = json_decode($form['fields_json'], true) ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission = [];
    foreach ($fields as $field) {
        $name = strtolower(str_replace(' ', '_', $field['label']));

        if ($field['type'] === 'multiselect') {
            $submission[$name] = $_POST[$name] ?? [];
        } elseif ($field['type'] === 'checkbox') {
            $submission[$name] = isset($_POST[$name]) ? true : false;
        } else {
            $submission[$name] = $_POST[$name] ?? '';
        }
    }

    // Send to webhook if exists
    $webhook_status = 'success';
    $webhook_response = '';

    if (!empty($form['webhook_url'])) {
        $payload = json_encode($submission);

        $ch = curl_init($form['webhook_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignore SSL errors (for localhost)
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error || $http_code < 200 || $http_code >= 300) {
            $webhook_status = 'failed';
            $webhook_response = $curl_error ?: $response;
        } else {
            $webhook_status = 'success';
            $webhook_response = $response;
        }

        // Save webhook debug log
        $log_entry = date('Y-m-d H:i:s') . " | URL: {$form['webhook_url']} | HTTP: $http_code | Payload: $payload | Response/Error: $webhook_response" . PHP_EOL;
        file_put_contents(__DIR__ . '/../webhook_log.txt', $log_entry, FILE_APPEND);
    }

    // Save submission locally
    $stmt = $pdo->prepare("INSERT INTO submissions (form_id, submission_json, webhook_status, webhook_response, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $form['id'],
        json_encode($submission),
        $webhook_status,
        $webhook_response,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    echo "<div style='padding: 30px; text-align: center; font-family: sans-serif;'>
        <h2 class='text-success'>✅ Thank you for your submission!</h2>
        <p>We have received your message. You can now close this page.</p>
        <a href='/' class='btn btn-primary mt-3'>Back to Home</a>
    </div>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($form['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">

<h1 class="mb-4"><?php echo htmlspecialchars($form['title']); ?></h1>

<form method="post" class="needs-validation" novalidate>
    <?php foreach ($fields as $field): ?>
        <?php
            $name = strtolower(str_replace(' ', '_', $field['label']));
        ?>
        <div class="mb-3">
            <label class="form-label"><?php echo htmlspecialchars($field['label']); ?></label>

            <?php if ($field['type'] === 'text' || $field['type'] === 'email' || $field['type'] === 'date'): ?>
                <input type="<?php echo htmlspecialchars($field['type']); ?>" name="<?php echo $name; ?>" class="form-control" required>

            <?php elseif ($field['type'] === 'textarea'): ?>
                <textarea name="<?php echo $name; ?>" class="form-control" rows="4" required></textarea>

            <?php elseif ($field['type'] === 'checkbox'): ?>
                <div class="form-check">
                    <input type="checkbox" name="<?php echo $name; ?>" value="1" class="form-check-input" id="<?php echo $name; ?>">
                    <label class="form-check-label" for="<?php echo $name; ?>"><?php echo htmlspecialchars($field['label']); ?></label>
                </div>

            <?php elseif ($field['type'] === 'select' && isset($field['options'])): ?>
                <select name="<?php echo $name; ?>" class="form-select" required>
                    <option value="">Select an option</option>
                    <?php foreach ($field['options'] as $option): ?>
                        <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                    <?php endforeach; ?>
                </select>

            <?php elseif ($field['type'] === 'multiselect' && isset($field['options'])): ?>
                <select name="<?php echo $name; ?>[]" class="form-select" multiple required>
                    <?php foreach ($field['options'] as $option): ?>
                        <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                    <?php endforeach; ?>
                </select>

            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary">Submit</button>
</form>

</body>
</html>
