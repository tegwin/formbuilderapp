<?php
/**
 * Email Listener for email@sondelaconsulting.com
 * This script should be set up to run periodically (via cron job)
 * to check for new emails and process them
 */

// Include required files
require_once 'EmailParser.php';
require_once 'WebhookSender.php';
require_once 'KeywordExtractor.php';

// Load configuration (store credentials safely)
$config = [
    'email_address' => 'email@sondelaconsulting.com',
    'email_password' => 'YOUR_PASSWORD_HERE', // Set your actual password here
    'mail_server' => 'mail.sondelaconsulting.com', // Update with your mail server
    'mail_port' => 993, // Standard port for IMAP with SSL
    'protocol' => 'imap',
    'use_ssl' => true,
    'mark_as_read' => true,
    'webhook_url' => '', // Optional webhook URL if you want to forward parsed emails
];

// Create log file for tracking activity
$logFile = 'email_listener_log.txt';
logMessage("Email listener started at " . date('Y-m-d H:i:s'));

try {
    // Connect to the mailbox
    logMessage("Connecting to mailbox {$config['email_address']}");
    $mailbox = connectToMailbox($config);
    
    if (!$mailbox) {
        throw new Exception("Failed to connect to mailbox");
    }
    
    // Search for unread emails
    logMessage("Searching for unread emails");
    $emails = searchEmails($mailbox, 'UNSEEN');
    $emailCount = count($emails);
    logMessage("Found $emailCount unread email(s)");
    
    if ($emailCount > 0) {
        // Create parser and keyword extractor
        $parser = new EmailParser();
        $keywordExtractor = createKeywordExtractor();
        
        // Process each email
        foreach ($emails as $emailId) {
            logMessage("Processing email ID: $emailId");
            
            // Get email content
            $rawEmail = getRawEmail($mailbox, $emailId);
            
            if (!$rawEmail) {
                logMessage("Warning: Could not retrieve email content for ID: $emailId");
                continue;
            }
            
            // Parse email
            $parsedEmail = $parser->parse($rawEmail);
            
            // Extract keywords
            $parsedEmail['extracted_keywords'] = $keywordExtractor->extract($parsedEmail);
            
            // Save parsed email
            $saved = saveProcessedEmail($parsedEmail);
            
            if ($saved) {
                logMessage("Email processed and saved successfully");
                
                // Mark as read if configured
                if ($config['mark_as_read']) {
                    imap_setflag_full($mailbox, $emailId, "\\Seen");
                    logMessage("Marked email as read");
                }
                
                // Send to webhook if configured
                if (!empty($config['webhook_url'])) {
                    sendToWebhook($config['webhook_url'], $parsedEmail);
                }
            } else {
                logMessage("Error: Failed to save processed email");
            }
        }
    }
    
    // Close mailbox connection
    imap_close($mailbox);
    logMessage("Connection closed");
    
} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage());
}

logMessage("Email listener finished at " . date('Y-m-d H:i:s'));

/**
 * Connect to mailbox
 */
function connectToMailbox($config) {
    // Build mailbox string
    $mailboxString = '{' . $config['mail_server'] . ':' . $config['mail_port'];
    
    // Add protocol
    if ($config['protocol'] === 'pop3') {
        $mailboxString .= '/pop3';
    }
    
    // Add SSL if needed
    if ($config['use_ssl']) {
        $mailboxString .= '/ssl';
    }
    
    // Add NOVALIDATE-CERT to avoid certificate validation issues
    $mailboxString .= '/novalidate-cert';
    
    // Close mailbox string
    $mailboxString .= '}INBOX';
    
    // Connect to the mailbox
    $connection = @imap_open(
        $mailboxString, 
        $config['email_address'], 
        $config['email_password']
    );
    
    if (!$connection) {
        logMessage("Connection error: " . imap_last_error());
        return false;
    }
    
    return $connection;
}

/**
 * Search emails matching criteria
 */
function searchEmails($mailbox, $criteria = 'ALL') {
    $emails = imap_search($mailbox, $criteria);
    return is_array($emails) ? $emails : [];
}

/**
 * Get raw email content
 */
function getRawEmail($mailbox, $emailId) {
    // Get email headers
    $headers = imap_fetchheader($mailbox, $emailId);
    
    // Get email body
    $body = imap_body($mailbox, $emailId);
    
    if (!$headers || !$body) {
        return false;
    }
    
    // Combine headers and body
    return $headers . "\r\n" . $body;
}

/**
 * Create keyword extractor with rules
 */
function createKeywordExtractor() {
    $extractor = new KeywordExtractor();
    
    // Add your keyword extraction rules here
    // Example:
    $extractor->addRule('invoice_number', 'INV-\d+', true, 'all');
    $extractor->addRule('order_id', 'Order #\d+', true, 'all');
    $extractor->addRule('customer_id', 'Customer ID: \d+', true, 'all');
    
    return $extractor;
}

/**
 * Save processed email
 */
function saveProcessedEmail($parsedEmail) {
    // Create a unique filename based on date and subject
    $sanitizedSubject = preg_replace('/[^a-zA-Z0-9]/', '_', $parsedEmail['subject']);
    $filename = 'processed_emails/' . date('Y-m-d_H-i-s') . '_' . substr($sanitizedSubject, 0, 30) . '.json';
    
    // Create directory if it doesn't exist
    if (!file_exists('processed_emails')) {
        mkdir('processed_emails', 0755, true);
    }
    
    // Save as JSON
    return file_put_contents($filename, json_encode($parsedEmail, JSON_PRETTY_PRINT));
}

/**
 * Send to webhook
 */
function sendToWebhook($webhookUrl, $parsedEmail) {
    try {
        $webhookSender = new WebhookSender();
        $result = $webhookSender->send($webhookUrl, $parsedEmail);
        
        if ($result['success']) {
            logMessage("Successfully sent to webhook: " . $webhookUrl);
            return true;
        } else {
            logMessage("Error sending to webhook: HTTP status " . $result['status']);
            return false;
        }
    } catch (Exception $e) {
        logMessage("Webhook error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log message to file
 */
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}
