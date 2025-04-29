<?php
// admin/edit.php
require_once __DIR__ . '/../includes/db.php';

$form_id = (int)($_GET['id'] ?? 0);

// Load form
$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) {
    die('Form not found!');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields_json = $_POST['fields_json'] ?? '[]';

    $stmt = $pdo->prepare("UPDATE forms SET fields_json = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$fields_json, $form_id]);

    header("Location: index.php");
    exit;
}

// Decode fields JSON
$fields = json_decode($form['fields_json'], true) ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Form - <?php echo htmlspecialchars($form['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .field {
            padding: 10px;
            margin: 5px 0;
            background: #f8f9fa;
            border: 1px solid #ced4da;
            cursor: move;
            position: relative;
            border-radius: 5px;
        }
        .delete-btn {
            position: absolute;
            right: 10px;
            top: 10px;
            color: red;
            cursor: pointer;
        }
    </style>
</head>
<body class="container py-5">

<h1>Edit Form: <?php echo htmlspecialchars($form['title']); ?></h1>

<a href="index.php" class="btn btn-secondary mb-4">← Back to Admin</a>

<div class="mb-3">
    <button onclick="addField('text')" class="btn btn-primary mb-1">Add Text Field</button>
    <button onclick="addField('email')" class="btn btn-primary mb-1">Add Email Field</button>
    <button onclick="addField('textarea')" class="btn btn-primary mb-1">Add Textarea</button>
    <button onclick="addField('checkbox')" class="btn btn-primary mb-1">Add Checkbox</button>
    <button onclick="addField('select')" class="btn btn-primary mb-1">Add Select</button>
    <button onclick="addField('multiselect')" class="btn btn-primary mb-1">Add Multi-Select</button>
    <button onclick="addField('date')" class="btn btn-primary mb-1">Add Date Field</button>
</div>

<form method="post" onsubmit="saveFields()">
    <div id="fields">
        <?php foreach ($fields as $field): ?>
            <div class="field" 
                data-type="<?php echo htmlspecialchars($field['type']); ?>" 
                data-label="<?php echo htmlspecialchars($field['label']); ?>" 
                data-options="<?php echo isset($field['options']) ? htmlspecialchars(implode(',', $field['options'])) : ''; ?>">
                <?php echo htmlspecialchars($field['type']); ?> - <?php echo htmlspecialchars($field['label']); ?>
                <?php if (in_array($field['type'], ['select', 'multiselect']) && isset($field['options'])): ?>
                    <small class="text-muted">(Options: <?php echo htmlspecialchars(implode(', ', $field['options'])); ?>)</small>
                <?php endif; ?>
                <span class="delete-btn" onclick="this.parentElement.remove()">❌</span>
            </div>
        <?php endforeach; ?>
    </div>

    <input type="hidden" name="fields_json" id="fields_json">

    <br>
    <button type="submit" class="btn btn-success">Save Form</button>
</form>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
let fieldsDiv = document.getElementById('fields');

new Sortable(fieldsDiv, {
    animation: 150
});

function addField(type) {
    let label = prompt("Enter label for the " + type + " field:");
    if (!label) return;

    let options = '';
    if (type === 'select' || type === 'multiselect') {
        options = prompt("Enter options separated by commas (e.g., Red, Green, Blue):");
    }

    let div = document.createElement('div');
    div.className = 'field';
    div.setAttribute('data-type', type);
    div.setAttribute('data-label', label);
    div.setAttribute('data-options', options || '');
    div.innerHTML = type + ' - ' + label;

    if (options) {
        div.innerHTML += `<small class="text-muted">(Options: ${options})</small>`;
    }

    div.innerHTML += `<span class="delete-btn" onclick="this.parentElement.remove()">❌</span>`;

    fieldsDiv.appendChild(div);
}

function saveFields() {
    let fields = [];

    document.querySelectorAll('#fields .field').forEach(div => {
        let field = {
            type: div.getAttribute('data-type'),
            label: div.getAttribute('data-label')
        };
        let options = div.getAttribute('data-options');
        if ((field.type === 'select' || field.type === 'multiselect') && options) {
            field.options = options.split(',').map(o => o.trim());
        }
        fields.push(field);
    });

    document.getElementById('fields_json').value = JSON.stringify(fields);
}
</script>

</body>
</html>
