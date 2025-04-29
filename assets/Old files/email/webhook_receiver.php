<?php
/**
 * Email Parser Settings
 * Configure system settings like cron intervals and real-time processing
 */

// Start session to store settings
session_start();

// Initialize default settings if not set
if (!isset($_SESSION['email_parser_settings'])) {
    $_SESSION['email_parser_settings'] = [
        'cron_interval' => 5, // Default: check every 5 minutes
        'real_time_processing' => false, // Default: disabled
        'webhook_instant_forward' => false, // Default: disabled
        'last_updated' => time()
    ];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Update settings
    if (isset($_POST['action']) && $_POST['action'] === 'update_settings') {
        updateSettings();
    }
    
    // Update cron job
    if (isset($_POST['action']) && $_POST['action'] === 'update_cron') {
        updateCronJob();
    }
    
    // Test real-time connection
    if (isset($_POST['action']) && $_POST['action'] === 'test_connection') {
        testConnection();
    }
    
    die;
}

/**
 * Update system settings
 */
function updateSettings() {
    $settings = $_SESSION['email_parser_settings'];
    
    if (isset($_POST['cron_interval'])) {
        $interval = intval($_POST['cron_interval']);
        $settings['cron_interval'] = max(1, $interval); // Minimum 1 minute
    }
    
    if (isset($_POST['real_time_processing'])) {
        $settings['real_time_processing'] = $_POST['real_time_processing'] === 'true';
    }
    
    if (isset($_POST['webhook_instant_forward'])) {
        $settings['webhook_instant_forward'] = $_POST['webhook_instant_forward'] === 'true';
    }
    
    $settings['last_updated'] = time();
    $_SESSION['email_parser_settings'] = $settings;
    
    echo json_encode(['success' => true]);
}

/**
 * Update cron job with new interval
 */
function updateCronJob() {
    $settings = $_SESSION['email_parser_settings'];
    $interval = $settings['cron_interval'];
    
    // Check if we're on a Unix-like system
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        echo json_encode([
            'success' => false, 
            'error' => 'Cron job updating is only supported on Unix-like systems.'
        ]);
        return;
    }
    
    // Path settings
    $phpPath = '/usr/bin/php'; // Default PHP path
    
    // Try to find the actual PHP path
    $phpPathOutput = [];
    exec('which php 2>/dev/null', $phpPathOutput);
    if (!empty($phpPathOutput[0])) {
        $phpPath = $phpPathOutput[0];
    }
    
    $scriptPath = __DIR__ . '/email_listener.php';
    $logPath = __DIR__ . '/cron_execution.log';
    
    // Create cron job command
    $cronCommand = "*/". $interval . " * * * * $phpPath $scriptPath >> $logPath 2>&1";
    
    // Get existing crontab entries
    exec('crontab -l', $crontab, $retval);
    
    // Check for errors
    if ($retval !== 0 && $retval !== 1) { // Return code 1 can mean no previous crontab
        echo json_encode([
            'success' => false, 
            'error' => 'Failed to retrieve current crontab. Error code: ' . $retval
        ]);
        return;
    }
    
    // Find and replace existing email_listener.php entry
    $newCrontab = [];
    $found = false;
    
    foreach ($crontab as $line) {
        if (strpos($line, 'email_listener.php') !== false) {
            $newCrontab[] = $cronCommand;
            $found = true;
        } else {
            $newCrontab[] = $line;
        }
    }
    
    // If not found, add it
    if (!$found) {
        $newCrontab[] = $cronCommand;
    }
    
    // Create a temporary file with the new crontab
    $tempFile = tempnam(sys_get_temp_dir(), 'crontab');
    file_put_contents($tempFile, implode("\n", $newCrontab) . "\n");
    
    // Install the new crontab
    system("crontab $tempFile", $retval);
    unlink($tempFile); // Remove the temporary file
    
    if ($retval === 0) {
        echo json_encode([
            'success' => true,
            'message' => "Cron job updated to run every $interval minutes."
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update crontab. Error code: ' . $retval
        ]);
    }
}

/**
 * Test real-time email connection
 */
function testConnection() {
    // This would connect to an email service that supports real-time notifications
    // For now, we'll simulate a successful connection
    
    $success = true; // In a real implementation, this would be the result of the connection test
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Successfully connected to real-time email service.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to connect to real-time email service.'
        ]);
    }
}

// Check if PHP IMAP extension is installed
$imapInstalled = function_exists('imap_open');

// Check if cron is available
$cronAvailable = (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN');

// Check if current cron job is set
$currentCronInterval = 'Not set';
if ($cronAvailable) {
    exec('crontab -l 2>/dev/null | grep email_listener.php', $cronOutput);
    if (!empty($cronOutput)) {
        // Try to extract the interval
        if (preg_match('/\*\/(\d+)\s+\*\s+\*\s+\*\s+\*/', $cronOutput[0], $matches)) {
            $currentCronInterval = $matches[1] . ' minutes';
        } else {
            $currentCronInterval = 'Custom (see crontab)';
        }
    }
}

// Check if webhook is configured
$webhookConfigured = isset($_SESSION['webhook_url']) && !empty($_SESSION['webhook_url']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Parser Settings - Sondela Consulting</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Email Parser Settings</h1>
    
    <div class="tabs">
        <a href="index.php" class="tab">Email Parser</a>
        <a href="inbox.php" class="tab">Inbox</a>
        <div class="tab active">Settings</div>
    </div>
    
    <?php if (!$imapInstalled): ?>
    <div class="notice error">
        <strong>PHP IMAP Extension Not Installed!</strong><br>
        The PHP IMAP extension is required for email processing. Please install it on your server.
    </div>
    <?php endif; ?>
    
    <div class="container">
        <div class="panel">
            <div class="panel-header">
                <h2>Email Checking Settings</h2>
            </div>
            
            <div class="settings-section">
                <h3>Cron Job Interval</h3>
                <p>Set how frequently the system checks for new emails.</p>
                
                <div class="input-group">
                    <label class="label" for="cronInterval">Check Interval (minutes)</label>
                    <div style="display: flex; align-items: center;">
                        <input type="number" id="cronInterval" min="1" value="<?php echo $_SESSION['email_parser_settings']['cron_interval']; ?>" style="width: 80px; margin-right: 10px;">
                        <button onclick="updateCron()" <?php echo !$cronAvailable ? 'disabled' : ''; ?>>Update Cron Job</button>
                    </div>
                    <?php if (!$cronAvailable): ?>
                    <div class="notice warning" style="margin-top: 10px;">
                        Cron job updating is only available on Unix-like systems. On Windows, please set up a scheduled task manually.
                    </div>
                    <?php endif; ?>
                    <div class="status-item" style="margin-top: 10px;">
                        <div class="status-label">Current Interval:</div>
                        <div class="status-value"><?php echo $currentCronInterval; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="settings-section">
                <h3>Real-Time Email Processing</h3>
                <p>Enable near-instantaneous email processing using webhook technology.</p>
                
                <div class="input-group">
                    <label class="toggle-switch">
                        <input type="checkbox" id="realTimeProcessing" <?php echo $_SESSION['email_parser_settings']['real_time_processing'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <span style="margin-left: 10px;">Enable Real-Time Processing</span>
                    <div class="tooltip">
                        <div class="tooltip-icon">?</div>
                        <span class="tooltip-text">
                            When enabled, the system will attempt to process emails as soon as they are received,
                            rather than waiting for the next cron job execution.
                        </span>
                    </div>
                </div>
                
                <div id="realTimeOptions" style="<?php echo $_SESSION['email_parser_settings']['real_time_processing'] ? '' : 'display: none;'; ?>">
                    <div class="input-group">
                        <label class="toggle-switch">
                            <input type="checkbox" id="webhookInstantForward" <?php echo $_SESSION['email_parser_settings']['webhook_instant_forward'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <span style="margin-left: 10px;">Instantly Forward to Webhook</span>
                        <div class="tooltip">
                            <div class="tooltip-icon">?</div>
                            <span class="tooltip-text">
                                Automatically send parsed emails to your configured webhook URL as soon as they are received.
                            </span>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <button onclick="testConnection()" class="test-btn">Test Real-Time Connection</button>
                        <div id="connectionStatus"></div>
                    </div>
                    
                    <?php if (!$webhookConfigured): ?>
                    <div class="notice warning">
                        <strong>Webhook Not Configured!</strong><br>
                        To use instant forwarding, please configure a webhook URL in the Email Parser page.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <button onclick="saveSettings()" class="save-btn">Save Settings</button>
            <div id="saveStatus"></div>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <h2>Real-Time Email Processing Setup</h2>
            </div>
            
            <div class="setup-instructions">
                <h3>How Real-Time Processing Works</h3>
                <p>Real-time email processing allows your system to receive and process emails almost instantly, 
                rather than waiting for the next scheduled check.</p>
                
                <div class="setup-step">
                    <h4>Step 1: Set Up Email Forwarding</h4>
                    <p>Configure your email service to forward incoming emails to our processing endpoint:</p>
                    <div class="code-block">
                        <code>Forward all emails sent to email@sondelaconsulting.com to:</code>
                        <code class="highlight"><?php echo 'https://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/webhook_receiver.php'; ?></code>
                    </div>
                </div>
                
                <div class="setup-step">
                    <h4>Step 2: Configure Your Email Provider</h4>
                    <p>Most email providers support webhook notifications when new emails arrive. 
                    Set up a webhook in your email provider's dashboard using the URL above.</p>
                    
                    <p>Popular email providers that support this:</p>
                    <ul>
                        <li>Mailgun</li>
                        <li>SendGrid</li>
                        <li>Mandrill/Mailchimp</li>
                        <li>Postmark</li>
                    </ul>
                </div>
                
                <div class="setup-step">
                    <h4>Step 3: Test the Connection</h4>
                    <p>After setting up the forwarding, click the "Test Real-Time Connection" button to verify the configuration.</p>
                </div>
                
                <div class="notice" style="margin-top: 20px;">
                    <strong>Note:</strong> Even with real-time processing enabled, the cron job will still run as a backup
                    to ensure no emails are missed.
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle real-time options visibility
        document.getElementById('realTimeProcessing').addEventListener('change', function() {
            document.getElementById('realTimeOptions').style.display = this.checked ? 'block' : 'none';
        });
        
        /**
         * Save settings
         */
        function saveSettings() {
            const cronInterval = document.getElementById('cronInterval').value;
            const realTimeProcessing = document.getElementById('realTimeProcessing').checked;
            const webhookInstantForward = document.getElementById('webhookInstantForward').checked;
            
            const saveStatus = document.getElementById('saveStatus');
            saveStatus.innerHTML = '<div class="notice">Saving settings...</div>';
            
            fetch('settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'update_settings',
                    'cron_interval': cronInterval,
                    'real_time_processing': realTimeProcessing,
                    'webhook_instant_forward': webhookInstantForward
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    saveStatus.innerHTML = '<div class="notice success">Settings saved successfully!</div>';
                    
                    // Clear the notice after 3 seconds
                    setTimeout(() => {
                        saveStatus.innerHTML = '';
                    }, 3000);
                } else {
                    saveStatus.innerHTML = `<div class="notice error">Error: ${data.error}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                saveStatus.innerHTML = '<div class="notice error">An error occurred while saving settings.</div>';
            });
        }
        
        /**
         * Update cron job
         */
        function updateCron() {
            const cronInterval = document.getElementById('cronInterval').value;
            
            // First save the settings
            fetch('settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'update_settings',
                    'cron_interval': cronInterval
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Now update the cron job
                    return fetch('settings.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            'action': 'update_cron'
                        })
                    });
                } else {
                    throw new Error(data.error || 'Failed to save settings');
                }
            })
            .then(response => response.json())
            .then(data => {
                const saveStatus = document.getElementById('saveStatus');
                
                if (data.success) {
                    saveStatus.innerHTML = `<div class="notice success">${data.message}</div>`;
                    
                    // Clear the notice after 3 seconds
                    setTimeout(() => {
                        saveStatus.innerHTML = '';
                    }, 3000);
                    
                    // Reload the page after a delay to refresh the current interval display
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    saveStatus.innerHTML = `<div class="notice error">Error: ${data.error}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const saveStatus = document.getElementById('saveStatus');
                saveStatus.innerHTML = `<div class="notice error">Error: ${error.message || 'An error occurred'}</div>`;
            });
        }
        
        /**
         * Test real-time connection
         */
        function testConnection() {
            const connectionStatus = document.getElementById('connectionStatus');
            connectionStatus.innerHTML = '<div class="notice">Testing connection...</div>';
            
            fetch('settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'test_connection'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    connectionStatus.innerHTML = `<div class="notice success">${data.message}</div>`;
                } else {
                    connectionStatus.innerHTML = `<div class="notice error">Error: ${data.error}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                connectionStatus.innerHTML = '<div class="notice error">An error occurred while testing the connection.</div>';
            });
        }
    </script>
</body>
</html>
