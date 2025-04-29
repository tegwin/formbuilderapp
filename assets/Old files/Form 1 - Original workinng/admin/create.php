<?php
// admin/create.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $webhook_url = trim($_POST['webhook_url']);
    $slug = generate_slug($title);

    $stmt = $pdo->prepare("INSERT INTO forms (title, slug, fields_json, webhook_url) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $slug, '[]', $webhook_url]);

    header("Location: edit.php?id=" . $pdo->lastInsertId());
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create New Form</title>
</head>
<body>
    <h1>Create New Form</h1>

    <form method="post">
        <label>Form Title:</label><br>
        <input type="text" name="title" required><br><br>

        <label>Webhook URL (optional):</label><br>
        <input type="text" name="webhook_url"><br><br>

        <button type="submit">Create Form</button>
    </form>

    <br>
    <a href="index.php">Back to Admin</a>
</body>
</html>
