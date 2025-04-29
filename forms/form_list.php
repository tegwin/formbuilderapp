<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user_id = get_user_id();
$role = get_user_role();

// Fetch private forms assigned to this user
$stmt = $pdo->prepare("
    SELECT f.*
    FROM forms f
    JOIN form_assignments fa ON fa.form_id = f.id
    WHERE fa.user_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$user_id]);
$assigned_forms = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Forms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Forms Shared with You</h1>

    <?php if (count($assigned_forms) === 0): ?>
        <div class="alert alert-info">No private forms have been assigned to you yet.</div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($assigned_forms as $form): ?>
                <a href="form.php?form_id=<?= $form['id'] ?>" class="list-group-item list-group-item-action">
                    <?= htmlspecialchars((string)$form['form_name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
