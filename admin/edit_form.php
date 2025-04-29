<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

// Logging function
function logError($message) {
    $log_path = __DIR__ . '/form_edit_error_log.txt';
    file_put_contents($log_path, date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
}

try {
    $form_id = $_GET['id'] ?? null;

    if (!$form_id) {
        throw new Exception("No form ID provided");
    }

    // Fetch form details
    $stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
    $stmt->execute([$form_id]);
    $form = $stmt->fetch();

    if (!$form) {
        throw new Exception("Form not found");
    }

    // Fetch form fields
    $stmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY id");
    $stmt->execute([$form_id]);
    $fields = $stmt->fetchAll();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Log POST data for debugging
        logError("POST Data: " . print_r($_POST, true));

        // Validate form name
        $form_name = $_POST['form_name'] ?? '';
        if (empty($form_name)) {
            throw new Exception("Form name cannot be empty");
        }

        // Start transaction
        $pdo->beginTransaction();

        // Update form details
        $stmt = $pdo->prepare("UPDATE forms SET 
            form_name = ?, 
            webhook_url = ?, 
            is_public = ?, 
            updated_at = NOW() 
            WHERE id = ?");
        $stmt->execute([
            $form_name,
            $_POST['webhook_url'] ?? '',
            isset($_POST['is_public']) ? 1 : 0,
            $form_id
        ]);

        // Handle existing field updates
        if (isset($_POST['fields'])) {
            foreach ($_POST['fields'] as $field_id => $field_data) {
                $stmt = $pdo->prepare("UPDATE form_fields SET 
                    label = ?, 
                    name = ?, 
                    field_type = ?, 
                    required = ?, 
                    options = ? 
                    WHERE id = ?");
                $stmt->execute([
                    $field_data['label'],
                    $field_data['name'],
                    $field_data['type'],
                    isset($field_data['required']) ? 1 : 0,
                    $field_data['options'] ?? '',
                    $field_id
                ]);
            }
        }

        // Handle new fields
        if (isset($_POST['new_fields'])) {
            foreach ($_POST['new_fields'] as $new_field) {
                $stmt = $pdo->prepare("INSERT INTO form_fields 
                    (form_id, field_type, label, name, required, options) 
                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $form_id,
                    $new_field['type'],
                    $new_field['label'],
                    $new_field['name'],
                    isset($new_field['required']) ? 1 : 0,
                    $new_field['options'] ?? ''
                ]);
            }
        }

        // Commit transaction
        $pdo->commit();

        // Redirect with success message
        header("Location: forms.php?updated=1");
        exit;
    }
} catch (Exception $e) {
    // Roll back transaction if active
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log error
    logError("Error: " . $e->getMessage());

    // Display error
    die("An error occurred: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Edit Form: <?= htmlspecialchars($form['form_name']) ?></h1>

    <form method="post">
        <div class="card shadow-sm mb-4">
            <div class="card-header">Form Details</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Form Name</label>
                    <input type="text" name="form_name" class="form-control" 
                        value="<?= htmlspecialchars($form['form_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Webhook URL</label>
                    <input type="text" name="webhook_url" class="form-control" 
                        value="<?= htmlspecialchars($form['webhook_url'] ?? '') ?>">
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="is_public" class="form-check-input" id="is_public"
                        <?= $form['is_public'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_public">
                        Make this form public
                    </label>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header">Current Fields</div>
            <div class="card-body">
                <?php foreach ($fields as $field): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <input type="hidden" name="fields[<?= $field['id'] ?>][id]" 
                                value="<?= $field['id'] ?>">
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <label>Label</label>
                                    <input type="text" name="fields[<?= $field['id'] ?>][label]" 
                                        class="form-control" 
                                        value="<?= htmlspecialchars($field['label']) ?>" required>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label>Name</label>
                                    <input type="text" name="fields[<?= $field['id'] ?>][name]" 
                                        class="form-control" 
                                        value="<?= htmlspecialchars($field['name']) ?>" required>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label>Type</label>
                                    <select name="fields[<?= $field['id'] ?>][type]" class="form-select">
                                        <?php 
                                        $types = ['text', 'email', 'password', 'textarea', 'select', 'multiselect', 'checkbox', 'date', 'upload'];
                                        foreach ($types as $type): 
                                        ?>
                                            <option value="<?= $type ?>" 
                                                <?= $field['field_type'] === $type ? 'selected' : '' ?>>
                                                <?= ucfirst($type) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label>Options</label>
                                    <input type="text" name="fields[<?= $field['id'] ?>][options]" 
                                        class="form-control" 
                                        value="<?= htmlspecialchars($field['options'] ?? '') ?>" 
                                        placeholder="Comma separated">
                                </div>
                                <div class="col-md-1 text-center">
                                    <label>Required</label><br>
                                    <input type="checkbox" 
                                        name="fields[<?= $field['id'] ?>][required]" 
                                        class="form-check-input mt-2" 
                                        <?= $field['required'] ? 'checked' : '' ?>>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header">Add New Fields</div>
            <div class="card-body" id="new-fields-container">
                <!-- New fields will be dynamically added here -->
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-outline-primary" onclick="addNewField()">
                    ➕ Add Field
                </button>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <button type="submit" class="btn btn-success">💾 Save Changes</button>
            <a href="forms.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
let newFieldIndex = 0;

function addNewField() {
    const container = document.getElementById('new-fields-container');
    const fieldTypes = [
        'text', 'email', 'password', 'textarea', 
        'select', 'multiselect', 'checkbox', 'date', 'upload'
    ];

    const fieldHtml = `
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label>Label</label>
                        <input type="text" name="new_fields[${newFieldIndex}][label]" 
                            class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label>Name</label>
                        <input type="text" name="new_fields[${newFieldIndex}][name]" 
                            class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label>Type</label>
                        <select name="new_fields[${newFieldIndex}][type]" class="form-select">
                            ${fieldTypes.map(type => 
                                `<option value="${type}">${type.charAt(0).toUpperCase() + type.slice(1)}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Options</label>
                        <input type="text" name="new_fields[${newFieldIndex}][options]" 
                            class="form-control" placeholder="Comma separated">
                    </div>
                    <div class="col-md-1 text-center">
                        <label>Required</label><br>
                        <input type="checkbox" name="new_fields[${newFieldIndex}][required]" 
                            class="form-check-input mt-2">
                    </div>
                </div>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', fieldHtml);
    newFieldIndex++;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>