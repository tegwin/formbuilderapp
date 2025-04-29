<?php
/**
 * Email Inbox Page
 * Shows all emails received at email@sondelaconsulting.com
 */

// Start session to store settings
session_start();

// Include required files
require_once 'EmailParser.php';
require_once 'WebhookSender.php';
require_once 'KeywordExtractor.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // View email details
    if (isset($_POST['action']) && $_POST['action'] === 'view_email') {
        if (!isset($_POST['filename']) || empty($_POST['filename'])) {
            echo json_encode(['success' => false, 'error' => 'Filename is required']);
            exit;
        }
        
        $filename = 'processed_emails/' . basename($_POST['filename']);
        
        if (!file_exists($filename)) {
            echo json_encode(['success' => false, 'error' => 'Email file not found']);
            exit;
        }
        
        $email = json_decode(file_get_contents($filename), true);
        echo json_encode(['success' => true, 'email' => $email]);
        exit;
    }
    
    // Send to webhook
    if (isset($_POST['action']) && $_POST['action'] === 'send_to_webhook') {
        if (!isset($_POST['filename']) || empty($_POST['filename'])) {
            echo json_encode(['success' => false, 'error' => 'Filename is required']);
            exit;
        }
        
        if (!isset($_SESSION['webhook_url']) || empty($_SESSION['webhook_url'])) {
            echo json_encode(['success' => false, 'error' => 'Webhook URL is not configured']);
            exit;
        }
        
        $filename = 'processed_emails/' . basename($_POST['filename']);
        
        if (!file_exists($filename)) {
            echo json_encode(['success' => false, 'error' => 'Email file not found']);
            exit;
        }
        
        $email = json_decode(file_get_contents($filename), true);
        
        // Send to webhook
        $webhookSender = new WebhookSender();
        
        try {
            $response = $webhookSender->send($_SESSION['webhook_url'], $email);
            
            // Record in history
            $historyItem = [
                'timestamp' => date('Y-m-d H:i:s'),
                'url' => $_SESSION['webhook_url'],
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
            
            echo json_encode(['success' => true, 'response' => $response]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        
        exit;
    }
    
    // Delete email
    if (isset($_POST['action']) && $_POST['action'] === 'delete_email') {
        if (!isset($_POST['filename']) || empty($_POST['filename'])) {
            echo json_encode(['success' => false, 'error' => 'Filename is required']);
            exit;
        }
        
        $filename = 'processed_emails/' . basename($_POST['filename']);
        
        if (!file_exists($filename)) {
            echo json_encode(['success' => false, 'error' => 'Email file not found']);
            exit;
        }
        
        $result = unlink($filename);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete email file']);
        }
        
        exit;
    }
}

// Get all processed emails
$emails = [];
$processingActive = false;

if (file_exists('processed_emails') && is_dir('processed_emails')) {
    $files = scandir('processed_emails');
    $processingActive = true;
    
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $filePath = 'processed_emails/' . $file;
            $content = file_get_contents($filePath);
            $email = json_decode($content, true);
            
            // Check if the file contains valid email data
            if (isset($email['subject']) && isset($email['from'])) {
                $emails[] = [
                    'filename' => $file,
                    'timestamp' => filemtime($filePath),
                    'subject' => $email['subject'],
                    'from' => $email['from'],
                    'to' => $email['to'],
                    'date' => $email['date']
                ];
            }
        }
    }
    
    // Sort by timestamp (newest first)
    usort($emails, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
} else {
    // Directory doesn't exist, email processing not active
    $processingActive = false;
}

// Check if the email listener is active
$listenerActive = false;
$cronSetup = false;

// Check if email_listener.php exists
if (file_exists('email_listener.php')) {
    $listenerActive = true;
    
    // Check if cron is set up (basic check)
    exec('crontab -l 2>/dev/null | grep email_listener.php', $output, $returnVal);
    $cronSetup = ($returnVal === 0 && !empty($output));
}

// Get the most recent log entries
$logEntries = [];
if (file_exists('email_listener_log.txt')) {
    $log = file_get_contents('email_listener_log.txt');
    $lines = explode("\n", $log);
    $logEntries = array_slice(array_filter($lines), -20); // Get last 20 non-empty lines
    $logEntries = array_reverse($logEntries); // Newest first
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Inbox - Sondela Consulting</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Email Inbox for email@sondelaconsulting.com</h1>
    
    <div class="tabs">
        <a href="index.php" class="tab">Email Parser</a>
        <a href="temp_email.php" class="tab">Temp Email</a>
        <div class="tab active">Inbox</div>
    </div>
    
    <?php if (!$listenerActive): ?>
    <div class="notice error">
        <strong>Email listener is not configured!</strong><br>
        The email_listener.php file is not found. Please make sure it's properly set up to receive emails.
    </div>
    <?php elseif (!$cronSetup): ?>
    <div class="notice warning">
        <strong>Cron job not detected!</strong><br>
        The email listener script exists, but doesn't appear to be set up as a cron job. Please run setup_cron.php to configure it.
    </div>
    <?php elseif (!$processingActive): ?>
    <div class="notice warning">
        <strong>No processed emails directory!</strong><br>
        The processed_emails directory doesn't exist. It will be created when the first email is received and processed.
    </div>
    <?php endif; ?>
    
    <div class="container">
        <div class="panel">
            <div class="panel-header">
                <h2>Received Emails</h2>
                <button onclick="checkEmailsNow()" class="refresh-btn">Check Now</button>
            </div>
            
            <div id="emailsList">
                <?php if (empty($emails)): ?>
                <p>No emails received yet at email@sondelaconsulting.com</p>
                <?php else: ?>
                    <?php foreach ($emails as $email): ?>
                    <div class="email-item" data-filename="<?php echo htmlspecialchars($email['filename']); ?>">
                        <div class="email-header">
                            <div class="email-subject"><?php echo htmlspecialchars($email['subject']); ?></div>
                            <div class="email-actions">
                                <button class="small-btn" onclick="viewEmail('<?php echo htmlspecialchars($email['filename']); ?>')">View</button>
                                <button class="small-btn danger" onclick="deleteEmail('<?php echo htmlspecialchars($email['filename']); ?>')">Delete</button>
                            </div>
                        </div>
                        <div class="email-meta">
                            <div>From: <?php echo htmlspecialchars($email['from']['name'] . ' <' . $email['from']['email'] . '>'); ?></div>
                            <div>Received: <?php echo date('Y-m-d H:i:s', $email['timestamp']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <h2>System Status</h2>
            </div>
            
            <div class="status-indicators">
                <div class="status-item">
                    <div class="status-label">Email Listener:</div>
                    <div class="status-value <?php echo $listenerActive ? 'active' : 'inactive'; ?>">
                        <?php echo $listenerActive ? 'Active' : 'Not Configured'; ?>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="status-label">Cron Job:</div>
                    <div class="status-value <?php echo $cronSetup ? 'active' : 'inactive'; ?>">
                        <?php echo $cronSetup ? 'Running' : 'Not Set Up'; ?>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="status-label">Listening On:</div>
                    <div class="status-value">email@sondelaconsulting.com</div>
                </div>
                
                <?php if (file_exists('email_listener_log.txt')): ?>
                <div class="status-item">
                    <div class="status-label">Last Check:</div>
                    <div class="status-value">
                        <?php 
                            $logStat = stat('email_listener_log.txt');
                            echo date('Y-m-d H:i:s', $logStat['mtime']);
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="log-viewer">
                <h3>Recent Activity</h3>
                <pre class="log-content">
<?php foreach ($logEntries as $entry): ?>
<?php echo htmlspecialchars($entry); ?>
<?php endforeach; ?>
                </pre>
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
                    <div class="tab" data-modal-tab="keywords">Extracted Keywords</div>
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
                    <button id="sendToWebhookBtn" onclick="sendEmailToWebhook()">Send to Webhook</button>
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
         * View email details
         */
        function viewEmail(filename) {
            fetch('inbox.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'view_email',
                    'filename': filename
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const email = data.email;
                    
                    // Fill modal with email data
                    document.getElementById('modalEmailSubject').textContent = email.subject;
                    
                    const fromDisplay = email.from.name 
                        ? `${email.from.name} <${email.from.email}>` 
                        : email.from.email;
                    document.getElementById('modalEmailFrom').textContent = fromDisplay;
                    
                    // Format "To" addresses
                    let toDisplay = '';
                    if (email.to && email.to.length > 0) {
                        toDisplay = email.to.map(to => 
                            to.name ? `${to.name} <${to.email}>` : to.email
                        ).join(', ');
                    }
                    document.getElementById('modalEmailTo').textContent = toDisplay;
                    
                    // Format date
                    document.getElementById('modalEmailDate').textContent = email.date;
                    
                    // Email body content - prefer HTML if available
                    if (email.html) {
                        document.getElementById('modalEmailBody').innerHTML = email.html;
                    } else if (email.text_as_html) {
                        document.getElementById('modalEmailBody').innerHTML = email.text_as_html;
                    } else {
                        document.getElementById('modalEmailBody').innerHTML = '<pre>' + email.text + '</pre>';
                    }
                    
                    // JSON tab
                    document.getElementById('modalEmailJson').textContent = JSON.stringify(email, null, 2);
                    
                    // Extracted keywords tab
                    const keywordsContainer = document.getElementById('modalEmailKeywords');
                    keywordsContainer.innerHTML = '';
                    
                    if (email.extracted_keywords && Object.keys(email.extracted_keywords).length > 0) {
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
                        
                        for (const ruleName in email.extracted_keywords) {
                            const matches = email.extracted_keywords[ruleName];
                            
                            const row = document.createElement('tr');
                            
                            const tdRule = document.createElement('td');
                            tdRule.textContent = ruleName;
                            row.appendChild(tdRule);
                            
                            const tdMatches = document.createElement('td');
                            if (matches.length > 0) {
                                const matchList = document.createElement('ul');
                                matchList.className = 'keywor
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
                    
                    // Store the filename for webhook sending
                    document.getElementById('sendToWebhookBtn').setAttribute('data-filename', filename);
                    
                    // Show the modal
                    document.getElementById('emailViewerModal').style.display = 'block';
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while loading the email.');
            });
        }
        
        /**
         * Send the selected email to webhook
         */
        function sendEmailToWebhook() {
            const filename = document.getElementById('sendToWebhookBtn').getAttribute('data-filename');
            
            if (!filename) {
                alert('No email selected');
                return;
            }
            
            fetch('inbox.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'send_to_webhook',
                    'filename': filename
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Email successfully sent to webhook!');
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while sending to webhook.');
            });
        }
        
        /**
         * Delete an email
         */
        function deleteEmail(filename) {
            if (!confirm('Are you sure you want to delete this email?')) {
                return;
            }
            
            fetch('inbox.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'delete_email',
                    'filename': filename
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove email from the list
                    const emailElement = document.querySelector(`.email-item[data-filename="${filename}"]`);
                    if (emailElement) {
                        emailElement.remove();
                    }
                    
                    // If the modal is open and showing this email, close it
                    const modal = document.getElementById('emailViewerModal');
                    if (modal.style.display === 'block' && 
                        document.getElementById('sendToWebhookBtn').getAttribute('data-filename') === filename) {
                        modal.style.display = 'none';
                    }
                    
                    // If no more emails, show the "no emails" message
                    const emailsList = document.getElementById('emailsList');
                    if (!emailsList.querySelector('.email-item')) {
                        emailsList.innerHTML = '<p>No emails received yet at email@sondelaconsulting.com</p>';
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
         * Check for new emails now (manually trigger the email listener script)
         */
        function checkEmailsNow() {
            const button = document.querySelector('.refresh-btn');
            button.disabled = true;
            button.textContent = 'Checking...';
            
            // Execute the email_listener.php script
            fetch('check_emails_now.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Check completed. ${data.emails_found} new email(s) found.`);
                    // Reload the page to show new emails
                    window.location.reload();
                } else {
                    alert('Error: ' + data.error);
                    button.disabled = false;
                    button.textContent = 'Check Now';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while checking for emails.');
                button.disabled = false;
                button.textContent = 'Check Now';
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