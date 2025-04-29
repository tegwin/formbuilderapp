<?php
require_once __DIR__ . '/../includes/db.php';

// Fetch form settings
$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_name = $_POST['form_name'] ?? 'Untitled Form';
    $submission_data = json_encode($_POST, JSON_PRETTY_PRINT);
    $created_at = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("INSERT INTO submissions (form_name, submission_data, created_at) VALUES (?, ?, ?)");
    if ($stmt->execute([$form_name, $submission_data, $created_at])) {
        $success = true;

        // Send webhook if URL set
        if (!empty($settings['webhook_url'])) {
            @file_get_contents($settings['webhook_url'], false, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $submission_data
                ]
            ]));
        }
    } else {
        $error = 'Failed to save submission.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Form - <?= htmlspecialchars($settings['site_name'] ?? 'Form Builder') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4"><?= htmlspecialchars($settings['site_name'] ?? 'Form') ?></h1>

        <?php if ($success): ?>
            <div class="alert alert-success">Form submitted successfully!</div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="card p-4 shadow-sm bg-white">
            <input type="hidden" name="form_name" value="Public Form">

            <div class="mb-3">
                <label class="form-label">Your Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea name="message" class="form-control" rows="4" required></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Send Message</button>
        </form>
    </div>
</body>
</html>
