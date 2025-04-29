<?php
// admin/index.php
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->query("SELECT * FROM forms ORDER BY created_at DESC");
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Form Builder - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
    function confirmDelete(formId) {
        if (confirm("Are you sure you want to delete this form? This cannot be undone!")) {
            window.location.href = 'delete_form.php?id=' + formId;
        }
    }
    </script>
</head>
<body class="container py-5">

<h1 class="mb-4">Form Builder Admin</h1>

<a href="create.php" class="btn btn-primary mb-3">+ Create New Form</a>

<h2>Forms List</h2>
<ul class="list-group">
    <?php foreach ($forms as $form): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <strong><?php echo htmlspecialchars($form['title']); ?></strong> 
                <small class="text-muted">(slug: <?php echo htmlspecialchars($form['slug']); ?>)</small>
            </div>
            <div class="btn-group" role="group">
                <a href="edit.php?id=<?php echo $form['id']; ?>" class="btn btn-sm btn-secondary">Edit Fields</a>
                <a href="settings.php?id=<?php echo $form['id']; ?>" class="btn btn-sm btn-warning">Settings</a>
                <a href="submissions.php?form_id=<?php echo $form['id']; ?>" class="btn btn-sm btn-info">Submissions</a>
                <a href="/form1/forms/<?php echo htmlspecialchars($form['slug']); ?>" target="_blank" class="btn btn-sm btn-success">View Form</a>
                <button onclick="confirmDelete(<?php echo $form['id']; ?>)" class="btn btn-sm btn-danger">Delete</button>
            </div>
        </li>
    <?php endforeach; ?>
</ul>

</body>
</html>
