<?php
require_once __DIR__ . '/../includes/db.php';

session_start();
require_once __DIR__ . '/../includes/auth.php';
require_login();

// Fetch submissions along with the associated form name
$stmt = $pdo->query("SELECT submissions.*, forms.form_name FROM submissions JOIN forms ON submissions.form_id = forms.id");
$submissions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Submissions - Form Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Manage Submissions</h1>

    <!-- Export buttons -->
    <div class="mb-3">
        <a href="../export_excel.php" class="btn btn-success">Export All to Excel</a>
        <a href="../export_csv.php" class="btn btn-info">Export All to CSV</a>
    </div>

    <!-- Bulk delete form -->
   <form action="/formbuilder/bulk_delete.php" method="POST">
        <?php if (count($submissions) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox" id="select_all"></th>
                            <th>Form Name</th>
                            <th>Submission Data</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><input type="checkbox" class="delete_checkbox" name="ids[]" value="<?= $submission['id'] ?>"></td>
                                <td><?= htmlspecialchars($submission['form_name']) ?></td>
                                <td><?= htmlspecialchars($submission['entry_data']) ?></td>
                                <td>
                                    <a href="delete_submission.php?id=<?= $submission['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this submission?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Bulk delete button -->
                <button type="submit" class="btn btn-danger">Delete Selected</button>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No submissions yet.</div>
        <?php endif; ?>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Select/Deselect all checkboxes
    document.getElementById('select_all').addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('.delete_checkbox');
        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
    });
</script>

</body>
</html>
