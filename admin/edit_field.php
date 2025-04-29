<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$field_id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM form_fields WHERE id = ?");
$stmt->execute([$field_id]);
$field = $stmt->fetch();

if (!$field) {
    die("Field not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $label = $_POST['label'] ?? '';
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? 'text';
    $options = $_POST['options'] ?? '';
    $required = isset($_POST['required']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE form_fields SET label = ?, name = ?, field_type = ?, options = ?, required = ? WHERE id = ?");
    $stmt->execute([$label, $name, $type, $options, $required, $field_id]);

    header("Location: edit_form.php?id=" . $field['form_id']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Field</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="container py-5">
    <h1 class="mb-4">Edit Field</h1>

    <form method="post">
        <div class="mb-3"><label>Label</label><input type="text" name="label" class="form-control" value="<?= htmlspecialchars((string)$field['label']) ?>" required></div>
        <div class="mb-3"><label>Name</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars((string)$field['name']) ?>" required></div>
        <div class="mb-3">
            <label>Type</label>
            <select name="type" class="form-select">
                <?php
                $types = ['text', 'email', 'password', 'select', 'multiselect', 'checkbox', 'date', 'textarea', 'upload'];
                foreach ($types as $t):
                ?>
                    <option value="<?= $t ?>" <?= $field['field_type'] === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Options (comma-separated)</label>
            <input type="text" name="options" class="form-control" value="<?= htmlspecialchars((string)$field['options']) ?>">
        </div>
        <div class="form-check mb-4">
            <input type="checkbox" name="required" class="form-check-input" id="requiredCheck" <?= $field['required'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="requiredCheck">Required</label>
        </div>
        <button class="btn btn-primary">Save Field</button>
    </form>
</div>
</body>
</html>
