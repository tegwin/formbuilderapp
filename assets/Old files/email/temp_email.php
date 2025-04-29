<?php
/**
 * Temporary Email Receiver and Parser
 * This page manages temporary email addresses and automatically parses received emails
 */

// Start session to store settings
session_start();

// Include required files
require_once 'EmailParser.php';
require_once 'WebhookSender.php';
require_once 'KeywordExtractor.php';
require_once 'EmailReceiver.php';

// Initialize settings if not exists
if (!isset($_SESSION['temp_email_settings'])) {
    $_SESSION['temp_email_settings'] = [
        'enabled' => false,
        'email_address' => '',
        'email_password' => '',
        'server' => '',
        'port' => 993,
        'protocol' => 'imap',
        'ssl' => true,
        'check_interval' => 5, // minutes
        'last_check' => 0,
        'auto_webhook' => false
    ];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Update temp email settings
    if (isset($_POST['action']) && $_POST['action'] === 'update_settings') {
        updateTempEmailSettings();
    }
    
    // Generate a temporary email address
    if (isset($_POST['action']) && $_POST['action'] === 'generate_email') {
        generateTempEmail();
    }
    
    // Check for new emails manually
    if (isset($_POST['action']) && $_POST['action'] === 'check_now') {
        $result = checkForNewEmails();
        echo json_encode($result);
    }
    
    // Delete received email
    if (isset($_POST['action']) && $_POST['action'] === 'delete_email') {
        deleteReceivedEmail();
    }
    
    die;
}

/**
 * Update temporary email settings
 */
function updateTempEmailSettings() {
    $settings = $_SESSION['temp_email_settings'];
    
    if (isset($_POST['enabled'])) {
        $settings['enabled'] = $_POST['enabled'] === 'true';
    }
    
    if (isset($_POST['email_address'])) {
        $settings['email_address'] = $_POST['email_address'];
    }
    
    if (isset($_POST['email_password'])) {
        $settings['email_password'] = $_POST['email_password'];
    }
    
    if (isset($_POST['server'])) {
        $settings['server'] = $_POST['server'];
    }
    
    if (isset($_POST['port'])) {
        $settings['port'] = intval($_POST['port']);
    }
    
    if (isset($_POST['protocol'])) {
        $settings['protocol'] = $_POST['protocol'];
    }
    
    if (isset($_POST['ssl'])) {
        $settings['ssl'] = $_POST['ssl'] === 'true';
    }
    
    if (isset($_POST['check_interval'])) {
        $settings['check_interval'] = max(1, intval($_POST['check_interval']));
    }
    
    if (isset($_POST['auto_webhook'])) {
        $settings['auto_webhook'] = $_POST['auto_webhook'] === 'true';
    }
    
    $_SESSION['temp_email_settings'] = $settings;
    
    echo json_encode(['success' => true]);
}

/**
 * Generate a temporary email address (placeholder for actual implementation)
 * In a real implementation, this would connect to a service to create a temp email
 */
function generateTempEmail() {
    // This is a simple implementation that just generates a random string
    // In production, you would use a real temporary email service API
    
    $username = 'temp_' . substr(md5(uniqid(rand(), true)), 0, 8);
    $domain = 'example.com'; // In production, this would be your actual temp email domain
    
    $email = $username . '@' . $domain;
    $password = substr(md5(uniqid(rand(), true)), 0, 12);
    
    // Update settings
    $_SESSION['temp_email_settings']['email_address'] = $email;
    $_SESSION['temp_email_settings']['email_password'] = $password;
    
    // In production, you would actually create this email account
    
    echo json_encode([
        'success' => true,
        'email' => $email,
        'password' => $password,
        'message' => 'Note: This is a demo. In production, you would use a real temporary email service.'
    ]);
}

/**
 * Check for new emails
 */
function checkForNewEmails() {
    $settings = $_SESSION['temp_email_settings'];
    
    if (!$settings['enabled']) {
        return ['success' => false, 'error' => 'Temporary email receiving is not enabled'];
    }
    
    if (empty($settings['email_address']) || empty($settings['server'])) {
        return ['success' => false, 'error' => 'Email settings are incomplete'];
    }
    
    try {
        // Create email receiver
        $receiver = new EmailReceiver(
            $settings['server'],
            $settings['port'],
            $settings['email_address'],
            $settings['email_password'],
            $settings['protocol'],
            ['ssl' => $settings['ssl'], 'novalidate-cert' => true]
        );
        
        // Create parser
        $parser = new EmailParser();
        
        // Create keyword extractor
        $keywordExtractor = new KeywordExtractor();
        if (isset($_SESSION['keyword_rules']) && !empty($_SESSION['keyword_rules'])) {
            foreach ($_SESSION['keyword_rules'] as $rule) {
                $keywordExtractor->addRule($rule['name'], $rule['pattern'], $rule['is_regex'], $rule['scope']);
            }
        }
        
        // Process new emails
        $processedEmails = $receiver->receiveAndProcess($parser, true, 10, $keywordExtractor);
        
        // Store processed emails in session
        if (!isset($_SESSION['received_emails'])) {
            $_SESSION['received_emails'] = [];
        }
        
        foreach ($processedEmails as $processedEmail) {
            $emailId = uniqid('email_');
            $timestamp = time();
            
            $_SESSION['received_emails'][$emailId] = [
                'id' => $emailId,
                'timestamp' => $timestamp,
                'parsed_email' => $processedEmail['parsed_email'],
                'email_id' => $processedEmail['email_id']
            ];
            
            // Send to webhook if auto-webhook is enabled
            if ($settings['auto_webhook'] && isset($_SESSION['webhook_url']) && !empty($_SESSION['webhook_url'])) {
                sendToWebhook($processedEmail['parsed_email']);
            }
        }
        
        // Update last check time
        $_SESSION['temp_email_settings']['last_check'] = time();
        
        return [
            'success' => true,
            'emails_found' => count($processedEmails),
            'emails' => $processedEmails
        ];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Send parsed email to webhook
 */
function sendToWebhook($parsedEmail) {
    if (!isset($_SESSION['webhook_url']) || empty($_SESSION['webhook_url'])) {
        return false;
    }
    
    $webhookUrl = $_SESSION['webhook_url'];
    
    $headers = [];
    if (!empty($_SESSION['webhook_headers'])) {
        $headerLines = explode("\n", $_SESSION['webhook_headers']);
        foreach ($headerLines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $headers[trim($parts[0])] = trim($parts[1]);
            }
        }
    }
    
    // Add default headers if not set
    if (!isset($headers['Content-Type'])) {
        $headers['Content-Type'] = 'application/json';
    }
    
    // Send to webhook
    $webhookSender = new WebhookSender();
    
    try {
        $response = $webhookSender->send($webhookUrl, $parsedEmail, $headers);
        
        // Record in history
        $historyItem = [
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $webhookUrl,
            'status' => $response['status'],
            'response' => $response['response'],
            'success' => $response['success']
        ];
        
        if (!isset($_SESSION['webhook_history'])) {
            $_SESSION['webhook_history'] = [];
        }
        
        $_SESSION['webhook_history'][] = $historyItem;
        // Keep only the latest 20 items
        if (count($_SESSION['webhook_history']) > 20) {
            $_SESSION['webhook_history'] = array_slice($_SESSION['webhook_history'], -20);
        }
        
        return true;
    } catch (Exception $e) {
        error_log('Webhook error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete a received email
 */
function deleteReceivedEmail() {
    if (!isset($_POST['email_id']) || empty($_POST['email_id'])) {
        echo json_encode(['success' => false, 'error' => 'Email ID is required']);
        return;
    }
    
    $emailId = $_POST['email_id'];
    
    if (!isset($_SESSION['received_emails']) || !isset($_SESSION['received_emails'][$emailId])) {
        echo json_encode(['success' => false, 'error' => 'Email not found']);
        return;
    }
    
    // Remove from session
    unset($_SESSION['received_emails'][$emailId]);
    
    echo json_encode(['success' => true]);
}

/**
 * Check if we should automatically check for new emails based on the interval
 */
function shouldCheckEmails() {
    $settings = $_SESSION['temp_email_settings'];
    
    if (!$settings['enabled']) {
        return false;
    }
    
    $now = time();
    $lastCheck = $settings['last_check'];
    $interval = $settings['check_interval'] * 60; // Convert to seconds
    
    return ($now - $lastCheck >= $interval);
}

// Perform automatic check if needed
if (shouldCheckEmails()) {
    checkForNewEmails();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temporary Email Receiver - Email Parser</title>
    <link rel="stylesheet" href="css/styles.css">
    <!-- Add meta refresh to periodically check for new emails -->
    <?php if ($_SESSION['temp_email_settings']['enabled']): ?>
    <meta http-equiv="refresh" content="<?php echo $_SESSION['temp_email_settings']['check_interval'] * 60; ?>">
    <?php endif; ?>
</head>
<body>
    <h1>Temporary Email Receiver</h1>
    
    <div class="tabs">
        <a href="index.php" class="tab">Email Parser</a>
        <div class="tab active">Temp Email Receiver</div>
    </div>
    
    <div class="container">
        <div class="panel">
            <div class="panel-header">
                <h2>Temporary Email Settings</h2>
            </div>
            
            <div class="input-group">
                <label class="toggle-switch">
                    <input type="checkbox" id="emailEnabled" <?php echo $_SESSION['temp_email_settings']['enabled'] ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
                <span style="margin-left: 10px;">Enable Temporary Email</span>
            </div>
            
            <div class="input-group">
                <label class="label" for="emailAddress">Email Address</label>
                <div style="display: flex;">
                    <input type="text" id="emailAddress" value="<?php echo htmlspecialchars($_SESSION['temp_email_settings']['email_address']); ?>" style="flex: 1; margin-right: 10px;">
                    <button onclick="generateTempEmail()">Generate</button>
                </div>
                <div id="generationNotice" class="notice" style="display: none; margin-top: 10px;"></div>
            </div>
            
            <div class="input-group">
                <label class="label" for="emailPassword">Email Password</label>
                <input type="password" id="emailPassword" value="<?php echo htmlspecialchars($_SESSION['temp_email_settings']['email_password']); ?>">
            </div>
            
            <div class="input-group">
                <label class="label" for="emailServer">Mail Server</label>
                <input type="text" id="emailServer" placeholder="mail.example.com" value="<?php echo htmlspecialchars($_SESSION['temp_email_settings']['server']); ?>">
            </div>
            
            <div class="input-group">
                <label class="label" for="emailPort">Server Port</label>
                <input type="number" id="emailPort" value="<?php echo $_SESSION['temp_email_settings']['port']; ?>">
            </div>
            
            <div class="input-group">
                <label class="label" for="emailProtocol">Protocol</label>
                <select id="emailProtocol">
                    <option value="imap" <?php echo $_SESSION['temp_email_settings']['protocol'] === 'imap' ? 'selected' : ''; ?>>IMAP</option>
                    <option value="pop3" <?php echo $_SESSION['temp_email_settings']['protocol'] === 'pop3' ? 'selected' : ''; ?>>POP3</option>
                </select>
            </div>
            
            <div class="input-group">
                <label class="toggle-switch">
                    <input type="checkbox" id="emailSSL" <?php echo $_SESSION['temp_email_settings']['ssl'] ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
                <span style="margin-left: 10px;">Use SSL/TLS</span>
            </div>
            
            <div class="input-group">
                <label class="label" for="checkInterval">Check Interval (minutes)</label>
                <input type="number" id="checkInterval" min="1" value="<?php echo $_SESSION['temp_email_settings']['check_interval']; ?>">
            </div>
            
            <div class="input-group">
                <label class="toggle-switch">
                    <input type="checkbox" id="autoWebhook" <?php echo $_SESSION['temp_email_settings']['auto_webhook'] ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
                <span style="margin-left: 10px;">Automatically Send to Webhook</span>
            </div>
            
            <button onclick="saveEmailSettings()">Save Settings</button>
            <button onclick="checkEmailsNow()" style="margin-left: 10px;">Check Emails Now</button>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <h2>Received Emails</h2>
            </div>
            
            <div id="receivedEmailsList">
                <?php if (empty($_SESSION['received_emails'])): ?>
                <p>No emails received yet.</p>
                <?php else: ?>
                    <?php foreach (array_reverse($_SESSION['received_emails']) as $email): ?>
                    <div class="email-item" data-id="<?php echo htmlspecialchars($email['id']); ?>">
                        <div class="email-header">
                            <div class="email-subject"><?php echo htmlspecialchars($email['parsed_email']['subject']); ?></div>
                            <div class="email-actions">
                                <button class="small-btn" onclick="viewEmail('<?php echo htmlspecialchars($email['id']); ?>')">View</button>
                                <button class="small-btn danger" onclick="deleteEmail('<?php echo htmlspecialchars($email['id']); ?>')">Delete</button>
                            </div>
                        </div>
                        <div class="email-meta">
                            <div>From: <?php echo htmlspecialchars($email['parsed_email']['from']['name'] . ' <' . $email['parsed_email']['from']['email'] . '>'); ?></div>
                            <div>Received: <?php echo date('Y-m-d H:i:s', $email['timestamp']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Email Viewer Modal -->
    <div id="emailViewerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalEmailSubject"></h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="tabs">
                    <div class="tab active" data-modal-tab="parsed">Parsed Email</div>
                    <div class="tab" data-modal-tab="json">JSON Output</div>
                    <?php if (!empty($_SESSION['keyword_rules'])): ?>
                    <div class="tab" data-modal-tab="keywords">Extracted Keywords</div>
                    <?php endif; ?>
                </div>
                
                <div class="modal-tab-content active" id="parsed-tab">
                    <div class="email-details">
                        <div><strong>From:</strong> <span id="modalEmailFrom"></span></div>
                        <div><strong>To:</strong> <span id="modalEmailTo"></span></div>
                        <div><strong>Date:</strong> <span id="modalEmailDate"></span></div>
                    </div>
                    <div class="email-body-content" id="modalEmailBody"></div>
                </div>
                
                <div class="modal-tab-content" id="json-tab">
                    <pre id="modalEmailJson"></pre>
                </div>
                
                <div class="modal-tab-content" id="keywords-tab">
                    <div id="modalEmailKeywords"></div>
                </div>
                
                <div class="modal-actions">
                    <button id="sendToWebhookBtn" onclick="sendSelectedEmailToWebhook()">Send to Webhook</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize modal tabs
        document.addEventListener('DOMContentLoaded', function() {
            // Modal tabs
            const modalTabs = document.querySelectorAll('.modal-header .tab, .modal-body .tab');
            modalTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabGroup = this.closest('.tabs');
                    tabGroup.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    const modalTabId = this.getAttribute('data-modal-tab');
                    document.querySelectorAll('.modal-tab-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    document.getElementById(modalTabId + '-tab').classList.add('active');
                });
            });
            
            // Close modal when clicking the X
            const closeModalBtn = document.querySelector('.close-modal');
            if (closeModalBtn) {
                closeModalBtn.addEventListener('click', function() {
                    document.getElementById('emailViewerModal').style.display = 'none';
                });
            }
            
            // Close modal when clicking outside of it
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('emailViewerModal');
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
        
        /**
         * Save email settings
         */
        function saveEmailSettings() {
            const enabled = document.getElementById('emailEnabled').checked;
            const emailAddress = document.getElementById('emailAddress').value;
            const emailPassword = document.getElementById('emailPassword').value;
            const server = document.getElementById('emailServer').value;
            const port = document.getElementById('emailPort').value;
            const protocol = document.getElementById('emailProtocol').value;
            const ssl = document.getElementById('emailSSL').checked;
            const checkInterval = document.getElementById('checkInterval').value;
            const autoWebhook = document.getElementById('autoWebhook').checked;
            
            // Validate inputs
            if (enabled && (!emailAddress || !server || !port)) {
                alert('Please fill in all required fields (Email Address, Server, Port)');
                return;
            }
            
            fetch('temp_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'update_settings',
                    'enabled': enabled,
                    'email_address': emailAddress,
                    'email_password': emailPassword,
                    'server': server,
                    'port': port,
                    'protocol': protocol,
                    'ssl': ssl,
                    'check_interval': checkInterval,
                    'auto_webhook': autoWebhook
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Settings saved successfully.');
                    
                    // Reload the page to update the auto-refresh
                    if (enabled) {
                        window.location.reload();
                    }
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving settings.');
            });
        }
        
        /**
         * Generate a temporary email address
         */
        function generateTempEmail() {
            fetch('temp_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'generate_email'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('emailAddress').value = data.email;
                    document.getElementById('emailPassword').value = data.password;
                    
                    // Show notice
                    const notice = document.getElementById('generationNotice');
                    notice.style.display = 'block';
                    notice.className = 'notice';
                    notice.innerHTML = data.message;
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while generating email.');
            });
        }
        
        /**
         * Check for new emails now
         */
        function checkEmailsNow() {
            const emailAddress = document.getElementById('emailAddress').value;
            const server = document.getElementById('emailServer').value;
            
            if (!emailAddress || !server) {
                alert('Please fill in all required fields (Email Address, Server)');
                return;
            }
            
            fetch('temp_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'check_now'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Check completed. ${data.emails_found} new email(s) found.`);
                    // Reload the page to show new emails
                    window.location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while checking for emails.');
            });
        }
        
        /**
         * View email details
         */
        function viewEmail(emailId) {
            // Find the email in the session (we'll get it from the page data)
            const emails = <?php echo json_encode(isset($_SESSION['received_emails']) ? $_SESSION['received_emails'] : []); ?>;
            const email = emails[emailId];
            
            if (!email) {
                alert('Email not found');
                return;
            }
            
            const parsedEmail = email.parsed_email;
            
            // Fill modal with email data
            document.getElementById('modalEmailSubject').textContent = parsedEmail.subject;
            
            const fromDisplay = parsedEmail.from.name 
                ? `${parsedEmail.from.name} <${parsedEmail.from.email}>` 
                : parsedEmail.from.email;
            document.getElementById('modalEmailFrom').textContent = fromDisplay;
            
            // Format "To" addresses
            let toDisplay = '';
            if (parsedEmail.to && parsedEmail.to.length > 0) {
                toDisplay = parsedEmail.to.map(to => 
                    to.name ? `${to.name} <${to.email}>` : to.email
                ).join(', ');
            }
            document.getElementById('modalEmailTo').textContent = toDisplay;
            
            // Format date
            document.getElementById('modalEmailDate').textContent = parsedEmail.date;
            
            // Email body content - prefer HTML if available
            if (parsedEmail.html) {
                document.getElementById('modalEmailBody').innerHTML = parsedEmail.html;
            } else if (parsedEmail.text_as_html) {
                document.getElementById('modalEmailBody').innerHTML = parsedEmail.text_as_html;
            } else {
                document.getElementById('modalEmailBody').innerHTML = '<pre>' + parsedEmail.text + '</pre>';
            }
            
            // JSON tab
            document.getElementById('modalEmailJson').textContent = JSON.stringify(parsedEmail, null, 2);
            
            // Extracted keywords tab
            if (document.getElementById('keywords-tab')) {
                const keywordsContainer = document.getElementById('modalEmailKeywords');
                keywordsContainer.innerHTML = '';
                
                if (parsedEmail.extracted_keywords && Object.keys(parsedEmail.extracted_keywords).length > 0) {
                    const table = document.createElement('table');
                    table.className = 'keyword-results-table';
                    
                    // Create table header
                    const thead = document.createElement('thead');
                    const headerRow = document.createElement('tr');
                    
                    const thRule = document.createElement('th');
                    thRule.textContent = 'Rule Name';
                    headerRow.appendChild(thRule);
                    
                    const thMatches = document.createElement('th');
                    thMatches.textContent = 'Matches';
                    headerRow.appendChild(thMatches);
                    
                    thead.appendChild(headerRow);
                    table.appendChild(thead);
                    
                    // Create table body
                    const tbody = document.createElement('tbody');
                    
                    for (const ruleName in parsedEmail.extracted_keywords) {
                        const matches = parsedEmail.extracted_keywords[ruleName];
                        
                        const row = document.createElement('tr');
                        
                        const tdRule = document.createElement('td');
                        tdRule.textContent = ruleName;
                        row.appendChild(tdRule);
                        
                        const tdMatches = document.createElement('td');
                        if (matches.length > 0) {
                            const matchList = document.createElement('ul');
                            matchList.className = 'keyword-matches';
                            
                            matches.forEach(match => {
                                const matchItem = document.createElement('li');
                                matchItem.textContent = match;
                                matchList.appendChild(matchItem);
                            });
                            
                            tdMatches.appendChild(matchList);
                        } else {
                            tdMatches.textContent = 'No matches found';
                        }
                        row.appendChild(tdMatches);
                        
                        tbody.appendChild(row);
                    }
                    
                    table.appendChild(tbody);
                    keywordsContainer.appendChild(table);
                } else {
                    keywordsContainer.innerHTML = '<p>No keywords extracted from this email.</p>';
                }
            }
            
            // Store the email ID for webhook sending
            document.getElementById('sendToWebhookBtn').setAttribute('data-email-id', emailId);
            
            // Show the modal
            document.getElementById('emailViewerModal').style.display = 'block';
        }
        
        /**
         * Send the selected email to webhook
         */
        function sendSelectedEmailToWebhook() {
            const emailId = document.getElementById('sendToWebhookBtn').getAttribute('data-email-id');
            
            // Find the email in the session
            const emails = <?php echo json_encode(isset($_SESSION['received_emails']) ? $_SESSION['received_emails'] : []); ?>;
            const email = emails[emailId];
            
            if (!email) {
                alert('Email not found');
                return;
            }
            
            // Make a POST request to index.php to send to webhook
            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'parse',
                    'email_content': JSON.stringify(email.parsed_email)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Now send to webhook
                    return fetch('index.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            'action': 'send_webhook'
                        })
                    });
                } else {
                    throw new Error('Error parsing email: ' + data.error);
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Email successfully sent to webhook!');
                } else {
                    alert('Error sending to webhook: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'An error occurred while processing the request.');
            });
        }
        
        /**
         * Delete a received email
         */
        function deleteEmail(emailId) {
            if (!confirm('Are you sure you want to delete this email?')) {
                return;
            }
            
            fetch('temp_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'delete_email',

body: new URLSearchParams({
                    'action': 'delete_email',
                    'email_id': emailId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove email from the list
                    const emailElement = document.querySelector(`.email-item[data-id="${emailId}"]`);
                    if (emailElement) {
                        emailElement.remove();
                    }
                    
                    // If the modal is open and showing this email, close it
                    const modal = document.getElementById('emailViewerModal');
                    if (modal.style.display === 'block' && 
                        document.getElementById('sendToWebhookBtn').getAttribute('data-email-id') === emailId) {
                        modal.style.display = 'none';
                    }
                    
                    // If no more emails, show the "no emails" message
                    const emailsList = document.getElementById('receivedEmailsList');
                    if (!emailsList.querySelector('.email-item')) {
                        emailsList.innerHTML = '<p>No emails received yet.</p>';
                    }
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the email.');
            });
        }
        
        /**
         * Syntax highlight for JSON display
         */
        function syntaxHighlight(json) {
            json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                let cls = 'json-number';
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        cls = 'json-key';
                    } else {
                        cls = 'json-string';
                    }
                } else if (/true|false/.test(match)) {
                    cls = 'json-boolean';
                } else if (/null/.test(match)) {
                    cls = 'json-null';
                }
                return '<span class="' + cls + '">' + match + '</span>';
            });
        }
    </script>
</body>
</html>