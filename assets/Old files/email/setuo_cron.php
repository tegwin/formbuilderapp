<?php
/**
 * Cron Job Setup for Email Listener
 * Run this script once to set up the cron job for regular email checking
 */

// Configuration
$phpPath = '/usr/bin/php'; // Path to PHP executable (may vary depending on your server)
$scriptPath = __DIR__ . '/email_listener.php'; // Full path to the email_listener.php script
$logPath = __DIR__ . '/cron_execution.log'; // Log file for cron job output
$interval = 5; // Check interval in minutes

// Check if PHP path is valid
if (!file_exists($phpPath)) {
    echo "Error: PHP executable not found at $phpPath\n";
    echo "Please update the \$phpPath variable in this script with the correct path.\n";
    echo "You can find the path by running 'which php' on your server.\n";
    exit(1);
}

// Check if the email listener script exists
if (!file_exists($scriptPath)) {
    echo "Error: Email listener script not found at $scriptPath\n";
    echo "Please make sure email_listener.php is in the same directory as this script.\n";
    exit(1);
}

// Create cron job command
$cronCommand = "*/". $interval . " * * * * $phpPath $scriptPath >> $logPath 2>&1";

// Check if we're on a Unix-like system where crontab is available
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    echo "This script is designed for Unix-like systems with crontab.\n";
    echo "For Windows, you'll need to set up a Scheduled Task manually.\n";
    echo "Command to run: $phpPath $scriptPath\n";
    echo "Recommended interval: Every $interval minutes\n";
    exit;
}

// Get existing crontab entries
exec('crontab -l', $crontab, $retval);

// Check for errors
if ($retval !== 0 && $retval !== 1) { // Return code 1 can mean no previous crontab
    echo "Error: Failed to retrieve current crontab. Error code: $retval\n";
    exit(1);
}

// Check if the cron job already exists
$jobExists = false;
foreach ($crontab as $line) {
    if (strpos($line, $scriptPath) !== false) {
        $jobExists = true;
        break;
    }
}

if ($jobExists) {
    echo "The cron job for email listener is already set up.\n";
    echo "Current job: $cronCommand\n";
    echo "If you need to modify it, please edit your crontab manually (crontab -e).\n";
} else {
    // Add our new cron job
    $crontab[] = $cronCommand;
    
    // Create a temporary file with the new crontab
    $tempFile = tempnam(sys_get_temp_dir(), 'crontab');
    file_put_contents($tempFile, implode("\n", $crontab) . "\n");
    
    // Install the new crontab
    system("crontab $tempFile", $retval);
    unlink($tempFile); // Remove the temporary file
    
    if ($retval === 0) {
        echo "Success! Cron job has been set up to check for new emails every $interval minutes.\n";
        echo "Command: $cronCommand\n";
        echo "Logs will be written to: $logPath\n";
    } else {
        echo "Error: Failed to install crontab. Error code: $retval\n";
        exit(1);
    }
}

echo "\nEmail listener setup instructions:\n";
echo "1. Make sure the email_listener.php has the correct email credentials for email@sondelaconsulting.com\n";
echo "2. Ensure your server has the PHP IMAP extension installed\n";
echo "3. The script will check for new emails every $interval minutes and process them\n";
echo "4. Check $logPath for cron execution logs\n";
echo "5. Processed emails will be saved in the 'processed_emails' directory\n";
