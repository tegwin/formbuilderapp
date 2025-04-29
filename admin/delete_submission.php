<?php
require_once __DIR__ . '/../includes/db.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Delete the submission from the database
    $stmt = $pdo->prepare("DELETE FROM submissions WHERE id = ?");
    $stmt->execute([$id]);

    // Redirect back to the submissions page after deletion
    header('Location: submissions.php');
    exit;
} else {
    // If no ID is passed, redirect to submissions page
    header('Location: submissions.php');
    exit;
}
?>
