<?php
// admin/settings.php
require_once __DIR__ . '/../includes/db.php';

$form_id = (int)($_GET['id'] ?? 0);

// Load form
$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) {
    die('Form not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $webhook_url = trim($_POST['webhook_url']);

    $stmt = $pdo->prepare("UPDATE forms SET title = ?, webhook_url = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$title, $webhook_url, $form_id]);

    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Form Settings - <?php echo htmlspecialchars($form['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">

<h1>Edit Form Settings</h1>

<form method="post" class="mt-4">
    <div class="mb-3">
        <label class="form-label">Form Title:</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($form['title']); ?>" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Webhook URL (optional):</label>
        <input type="text" name="webhook_url" value="<?php echo htmlspecialchars($form['webhook_url']); ?>" class="form-control">
    </div>

    <button type="submit" class="btn btn-primary">Save Settings</button>
    <a href="index.php" class="btn btn-secondary">Back to Admin</a>
</form>

</body>
</html>
