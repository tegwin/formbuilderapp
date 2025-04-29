<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_name = trim($_POST['form_name']);
    $webhook_url = trim($_POST['webhook_url']);
    $fields = $_POST['fields'] ?? [];

    if ($form_name && count($fields) > 0) {
        $stmt = $pdo->prepare("INSERT INTO forms (form_name, webhook_url) VALUES (?, ?)");
        $stmt->execute([$form_name, $webhook_url]);
        $form_id = $pdo->lastInsertId();

        $stmt_field = $pdo->prepare("INSERT INTO form_fields (form_id, field_label, field_name, field_type, field_required) VALUES (?, ?, ?, ?, ?)");

        foreach ($fields as $field) {
            $label = trim($field['label']);
            $name = strtolower(str_replace(' ', '_', $label));
            $type = trim($field['type']);
            $required = !empty($field['required']) ? 1 : 0;

            $stmt_field->execute([$form_id, $label, $name, $type, $required]);
        }

        header('Location: forms.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create New Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Form Builder Admin</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="forms.php">Forms</a></li>
                <li class="nav-item"><a class="nav-link" href="submissions.php">Submissions</a></li>
                <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h1>Create New Form</h1>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Form Name</label>
            <input type="text" name="form_name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Webhook URL (Optional)</label>
            <input type="text" name="webhook_url" class="form-control">
        </div>

        <h3>Fields</h3>

        <div id="fields">
            <div class="card p-3 mb-3">
                <div class="mb-2">
                    <label>Field Label</label>
                    <input type="text" name="fields[0][label]" class="form-control" required>
                </div>

                <div class="mb-2">
                    <label>Field Type</label>
                    <select name="fields[0][type]" class="form-control" required>
                        <option value="text">Text</option>
                        <option value="email">Email</option>
                        <option value="textarea">Textarea</option>
                        <option value="select">Select Dropdown</option>
                        <option value="password">Password</option>
                        <option value="date">Date</option>
                    </select>
                </div>

                <div class="form-check mt-2">
                    <input type="checkbox" name="fields[0][required]" class="form-check-input">
                    <label class="form-check-label">Required</label>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-secondary mb-4" onclick="addField()">➕ Add Another Field</button>

        <br>
        <button type="submit" class="btn btn-primary">Save Form</button>
    </form>
</div>

<script>
let fieldCount = 1;
function addField() {
    const container = document.getElementById('fields');
    const html = `
        <div class="card p-3 mb-3">
            <div class="mb-2">
                <label>Field Label</label>
                <input type="text" name="fields[${fieldCount}][label]" class="form-control" required>
            </div>

            <div class="mb-2">
                <label>Field Type</label>
                <select name="fields[${fieldCount}][type]" class="form-control" required>
                    <option value="text">Text</option>
                    <option value="email">Email</option>
                    <option value="textarea">Textarea</option>
                    <option value="select">Select Dropdown</option>
                    <option value="password">Password</option>
                    <option value="date">Date</option>
                </select>
            </div>

            <div class="form-check mt-2">
                <input type="checkbox" name="fields[${fieldCount}][required]" class="form-check-input">
                <label class="form-check-label">Required</label>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    fieldCount++;
}
</script>
</body>
</html>
