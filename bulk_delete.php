<?php
// Start the session (for user authentication)
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection (corrected path)
require_once __DIR__ . '/includes/db.php';  // Adjusted path for db.php

// Check if the user is logged in
require_once __DIR__ . '/includes/auth.php';
require_login(); // This checks for a logged-in user

// Check if there are IDs selected for deletion
if (isset($_POST['ids']) && !empty($_POST['ids'])) {
    try {
        // Get the selected IDs
        $ids = $_POST['ids'];
        
        // Prepare the delete query to delete multiple submissions
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM submissions WHERE id IN ($placeholders)");
        
        // Execute the delete query
        $stmt->execute($ids);

        // Redirect back to the submissions page with a success message
        header('Location: submissions.php?deleted=1');
        exit;
    } catch (PDOException $e) {
        // If there is a database error, display the error message
        echo "Error: " . $e->getMessage();
        exit;
    }
} else {
    // If no submissions were selected, redirect with an error message
    header('Location: submissions.php?error=1');
    exit;
}
