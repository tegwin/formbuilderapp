<?php
require_once __DIR__ . '/../includes/db.php';

if (isset($_POST['delete_bulk']) && isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = $_POST['ids'];

    // Prepare and execute bulk delete query
    $stmt = $pdo->prepare("DELETE FROM submissions WHERE id IN (" . implode(',', array_map('intval', $ids)) . ")");
    $stmt->execute();

    // Redirect back to submissions page after deletion
    header('Location: submissions.php');
    exit;
} else {
    // If no submissions are selected for deletion, redirect back
    header('Location: submissions.php');
    exit;
}
?>
