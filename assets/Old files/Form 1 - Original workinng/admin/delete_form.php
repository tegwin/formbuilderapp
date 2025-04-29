<?php
// admin/delete_form.php
require_once __DIR__ . '/../includes/db.php';

$form_id = (int)($_GET['id'] ?? 0);

if (!$form_id) {
    die('Form ID missing.');
}

// Delete form submissions first
$stmt = $pdo->prepare("DELETE FROM submissions WHERE form_id = ?");
$stmt->execute([$form_id]);

// Delete the form
$stmt = $pdo->prepare("DELETE FROM forms WHERE id = ?");
$stmt->execute([$form_id]);

header('Location: index.php');
exit;
