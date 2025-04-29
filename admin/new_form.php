<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_name = $_POST['form_name'] ?? '';
    $webhook_url = $_POST['webhook_url'] ?? '';
    $is_public = isset($_POST['is_public']) ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO forms (form_name, webhook_url, is_public, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$form_name, $webhook_url, $is_public]);

    $form_id = $pdo->lastInsertId();

    if (isset($_POST['new_fields'])) {
        foreach ($_POST['new_fields'] as $field) {
            $stmt = $pdo->prepare("INSERT INTO form_fields (form_id, field_type, label, name, required, options) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $form_id,
                $field['type'] ?? '',
                $field['label'] ?? '',
                $field['name'] ?? '',
                isset($field['required']) ? 1 : 0,
                $field['options'] ?? ''
            ]);
        }
    }

    header('Location: forms.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create New Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Create New Form</h1>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Form Name</label>
            <input type="text" name="form_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Webhook URL (optional)</label>
            <input type="url" name="webhook_url" class="form-control">
        </div>
        <div class="form-check mb-4">
            <input type="checkbox" name="is_public" class="form-check-input" id="is_public" checked>
            <label class="form-check-label" for="is_public">Make this form public (no login required)</label>
        </div>

        <h4 class="mt-5">Form Fields</h4>
        <div id="new-fields"></div>

        <button type="button" class="btn btn-outline-primary mt-3" onclick="addNewField()">➕ Add Field</button>

        <div class="text-end mt-5">
            <button type="submit" class="btn btn-success btn-lg">Save Form</button>
        </div>
    </form>
</div>

<script>
let fieldIndex = 0;
function addNewField() {
    const wrapper = document.getElementById('new-fields');
    const index = fieldIndex++;
    const template = `
    <div class="card p-3 mb-3">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label>Type</label>
                <select name="new_fields[${index}][type]" class="form-select" required>
                    <option value="text">Text</option>
                    <option value="email">Email</option>
                    <option value="password">Password</option>
                    <option value="select">Select</option>
                    <option value="multiselect">Multi Select</option>
                    <option value="checkbox">Checkbox</option>
                    <option value="date">Date</option>
                    <option value="textarea">Textarea</option>
                    <option value="upload">File Upload</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label>Label</label>
                <input type="text" name="new_fields[${index}][label]" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
                <label>Name</label>
                <input type="text" name="new_fields[${index}][name]" class="form-control" required>
            </div>
            <div class="col-md-2 mb-3">
                <label>Options (comma separated)</label>
                <input type="text" name="new_fields[${index}][options]" class="form-control">
            </div>
            <div class="col-md-1 text-center">
                <label>Required</label><br>
                <input type="checkbox" name="new_fields[${index}][required]" class="form-check-input mt-2">
            </div>
        </div>
    </div>
    `;
    wrapper.insertAdjacentHTML('beforeend', template);
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
