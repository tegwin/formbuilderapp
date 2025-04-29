<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch submissions
$stmt = $pdo->query("
    SELECT submissions.*, forms.form_name 
    FROM submissions 
    JOIN forms ON submissions.form_id = forms.id 
    ORDER BY submissions.submitted_at DESC
");

$submissions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submissions - Form Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Form Submissions</h1>

    <?php if (count($submissions) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Form</th>
                        <th>Data</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?= htmlspecialchars($submission['form_name']) ?></td>
                            <td>
                                <?php
                                $data = json_decode($submission['entry_data'], true);
                                if (is_array($data)) {
                                    echo '<ul>';
                                    foreach ($data as $key => $value) {
                                        if (is_array($value)) {
                                            echo "<li><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars(implode(', ', $value)) . "</li>";
                                        } else {
                                            echo "<li><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value) . "</li>";
                                        }
                                    }
                                    echo '</ul>';
                                } else {
                                    echo htmlspecialchars($submission['entry_data']);
                                }
                                ?>
                            </td>
                            <td><?= date('Y-m-d H:i', strtotime($submission['submitted_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No submissions yet.</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
