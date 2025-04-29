<?php
// admin/submissions.php
require_once __DIR__ . '/../includes/db.php';

$form_id = (int)($_GET['form_id'] ?? 0);

if (!$form_id) {
    die('Form ID is missing.');
}

// Load form
$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) {
    die('Form not found.');
}

// Load submissions
$stmt = $pdo->prepare("SELECT * FROM submissions WHERE form_id = ? ORDER BY created_at DESC");
$stmt->execute([$form_id]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submissions - <?php echo htmlspecialchars($form['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">

<h1 class="mb-4">Submissions for: <?php echo htmlspecialchars($form['title']); ?></h1>

<a href="index.php" class="btn btn-secondary mb-4">← Back to Admin</a>

<?php if (empty($submissions)): ?>
    <div class="alert alert-warning">No submissions yet for this form.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Submitted Data</th>
                    <th>IP Address</th>
                    <th>Browser Info</th>
                    <th>Webhook Status</th>
                    <th>Submitted At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $index => $submission): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <pre><?php echo htmlspecialchars(json_encode(json_decode($submission['submission_json']), JSON_PRETTY_PRINT)); ?></pre>
                        </td>
                        <td><?php echo htmlspecialchars($submission['ip_address']); ?></td>
                        <td><small><?php echo htmlspecialchars($submission['user_agent']); ?></small></td>
                        <td><?php echo htmlspecialchars($submission['webhook_status']); ?></td>
                        <td><?php echo htmlspecialchars($submission['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

</body>
</html>
