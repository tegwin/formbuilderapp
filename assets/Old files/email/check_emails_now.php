<?php
/**
 * Check Emails Now
 * This script manually executes the email listener to check for new emails
 */

header('Content-Type: application/json');

// Check if email listener script exists
if (!file_exists('email_listener.php')) {
    echo json_encode([
        'success' => false,
        'error' => 'Email listener script is not configured'
    ]);
    exit;
}

// Include the email listener script
ob_start(); // Capture output
include 'email_listener.php';
$output = ob_get_clean();

// Get the number of emails found (parse from log)
$emailsFound = 0;
if (file_exists('email_listener_log.txt')) {
    $log = file_get_contents('email_listener_log.txt');
    if (preg_match('/Found (\d+) unread email/', $log, $matches)) {
        $emailsFound = $matches[1];
    }
}

echo json_encode([
    'success' => true,
    'emails_found' => $emailsFound,
    'output' => $output
]);
