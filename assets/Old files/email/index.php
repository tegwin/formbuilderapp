<?php
/**
 * Email Parser Application with Keyword Extraction & Webhook
 * Main Entry Point
 */

// Start session to store settings and history
session_start();

// Initialize settings if not exists
if (!isset($_SESSION['webhook_url'])) {
    $_SESSION['webhook_url'] = '';
}
if (!isset($_SESSION['webhook_headers'])) {
    $_SESSION['webhook_headers'] = '';
}
if (!isset($_SESSION['webhook_history'])) {
    $_SESSION['webhook_history'] = [];
}
if (!isset($_SESSION['keyword_rules'])) {
    $_SESSION['keyword_rules'] = [];
}

// Include helper classes
require_once 'EmailParser.php';
require_once 'WebhookSender.php';
require_once 'KeywordExtractor.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Parse email
    if (isset($_POST['action']) && $_POST['action'] === 'parse') {
        parseEmailAction();
    }
    
    // Send to webhook
    if (isset($_POST['action']) && $_POST['action'] === 'send_webhook') {
        sendWebhookAction();
    }
    
    // Add keyword rule
    if (isset($_POST['action']) && $_POST['action'] === 'add_rule') {
        addKeywordRuleAction();
    }
    
    // Remove keyword rule
    if (isset($_POST['action']) && $_POST['action'] === 'remove_rule') {
        removeKeywordRuleAction();
    }
    
    // Update webhook settings
    if (isset($_POST['action']) && $_POST['action'] === 'update_webhook') {
        updateWebhookSettingsAction();
    }
    
    // Clear history
    if (isset($_POST['action']) && $_POST['action'] === 'clear_history') {
        clearHistoryAction();
    }
    
    die;
}

/**
 * Parse Email Action
 */
function parseEmailAction() {
    if (!isset($_POST['email_content']) || empty($_POST['email_content'])) {
        echo json_encode(['success' => false, 'error' => 'Email content is required']);
        return;
    }
    
    $emailContent = $_POST['email_content'];
    
    // Create parser
    $parser = new EmailParser();
    
    // Add keyword rules
    $keywordExtractor = new KeywordExtractor();
    if (isset($_SESSION['keyword_rules']) && !empty($_SESSION['keyword_rules'])) {
        foreach ($_SESSION['keyword_rules'] as $rule) {
            $keywordExtractor->addRule($rule['name'], $rule['pattern'], $rule['is_regex'], $rule['scope']);
        }
    }
    
    try {
        // Parse email
        $parsedEmail = $parser->parse($emailContent);
        
        // Extract keywords
        $extractedKeywords = $keywordExtractor->extract($parsedEmail);
        
        // Add extracted keywords to the parsed email
        $parsedEmail['extracted_keywords'] = $extractedKeywords;
        
        // Store in session for later use
        $_SESSION['last_parsed_email'] = $parsedEmail;
        
        echo json_encode([
            'success' => true, 
            'parsed_email' => $parsedEmail,
            'extracted_keywords' => $extractedKeywords
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Send to Webhook Action
 */
function sendWebhookAction() {
    if (!isset($_SESSION['webhook_url']) || empty($_SESSION['webhook_url'])) {
        echo json_encode(['success' => false, 'error' => 'Webhook URL is not configured']);
        return;
    }
    
    if (!isset($_SESSION['last_parsed_email']) || empty($_SESSION['last_parsed_email'])) {
        echo json_encode(['success' => false, 'error' => 'No parsed email data available']);
        return;
    }
    
    $webhookUrl = $_SESSION['webhook_url'];
    $data = $_SESSION['last_parsed_email'];
    
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
        $response = $webhookSender->send($webhookUrl, $data, $headers);
        
        // Record in history
        $historyItem = [
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $webhookUrl,
            'status' => $response['status'],
            'response' => $response['response'],
            'success' => $response['success']
        ];
        
        $_SESSION['webhook_history'][] = $historyItem;
        // Keep only the latest 20 items
        if (count($_SESSION['webhook_history']) > 20) {
            $_SESSION['webhook_history'] = array_slice($_SESSION['webhook_history'], -20);
        }
        
        echo json_encode(['success' => true, 'response' => $response]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Add Keyword Rule Action
 */
function addKeywordRuleAction() {
    if (!isset($_POST['name']) || empty($_POST['name'])) {
        echo json_encode(['success' => false, 'error' => 'Rule name is required']);
        return;
    }
    
    if (!isset($_POST['pattern']) || empty($_POST['pattern'])) {
        echo json_encode(['success' => false, 'error' => 'Pattern is required']);
        return;
    }
    
    $name = $_POST['name'];
    $pattern = $_POST['pattern'];
    $isRegex = isset($_POST['is_regex']) && $_POST['is_regex'] === 'true';
    $scope = isset($_POST['scope']) ? $_POST['scope'] : 'all';
    
    // Check if rule with the same name already exists
    foreach ($_SESSION['keyword_rules'] as $rule) {
        if ($rule['name'] === $name) {
            echo json_encode(['success' => false, 'error' => 'A rule with this name already exists']);
            return;
        }
    }
    
    // Validate regex if applicable
    if ($isRegex) {
        try {
            $testRegex = @preg_match("/$pattern/", "test");
            if ($testRegex === false) {
                echo json_encode(['success' => false, 'error' => 'Invalid regular expression']);
                return;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Invalid regular expression: ' . $e->getMessage()]);
            return;
        }
    }
    
    // Add rule
    $rule = [
        'id' => uniqid(),
        'name' => $name,
        'pattern' => $pattern,
        'is_regex' => $isRegex,
        'scope' => $scope
    ];
    
    $_SESSION['keyword_rules'][] = $rule;
    
    echo json_encode(['success' => true, 'rule' => $rule, 'rules' => $_SESSION['keyword_rules']]);
}

/**
 * Remove Keyword Rule Action
 */
function removeKeywordRuleAction() {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        echo json_encode(['success' => false, 'error' => 'Rule ID is required']);
        return;
    }
    
    $ruleId = $_POST['id'];
    $found = false;
    
    foreach ($_SESSION['keyword_rules'] as $key => $rule) {
        if ($rule['id'] === $ruleId) {
            unset($_SESSION['keyword_rules'][$key]);
            $found = true;
            break;
        }
    }
    
    $_SESSION['keyword_rules'] = array_values($_SESSION['keyword_rules']); // Re-index array
    
    if (!$found) {
        echo json_encode(['success' => false, 'error' => 'Rule not found']);
        return;
    }
    
    echo json_encode(['success' => true, 'rules' => $_SESSION['keyword_rules']]);
}

/**
 * Update Webhook Settings Action
 */
function updateWebhookSettingsAction() {
    if (isset($_POST['webhook_url'])) {
        $_SESSION['webhook_url'] = $_POST['webhook_url'];
    }
    
    if (isset($_POST['webhook_headers'])) {
        $_SESSION['webhook_headers'] = $_POST['webhook_headers'];
    }
    
    echo json_encode(['success' => true]);
}

/**
 * Clear History Action
 */
function clearHistoryAction() {
    $_SESSION['webhook_history'] = [];
    echo json_encode(['success' => true]);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Parser with Keyword Extraction & Webhook</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Email Parser with Keyword Extraction & Webhook</h1>
    
    <div class="tabs">
        <div class="tab active" data-tab="parser">Email Parser</div>
        <div class="tab" data-tab="keyword-rules">Keyword Rules</div>
        <div class="tab" data-tab="webhook">Webhook Settings</div>
        <div class="tab" data-tab="history">History</div>
    </div>
    
    <!-- Email Parser Tab -->
    <div class="tab-content active" id="parser-tab">
        <div class="preset-buttons">
            <button class="preset-btn" onclick="loadPreset('simple')">Simple Email</button>
            <button class="preset-btn" onclick="loadPreset('complex')">Complex Email</button>
            <button class="preset-btn" onclick="loadPreset('html')">HTML Email</button>
            <button class="preset-btn" onclick="loadPreset('keywords')">Email with Keywords</button>
        </div>
        
        <div class="container">
            <div class="panel">
                <div class="panel-header">
                    <h2>Raw Email Input</h2>
                </div>
                <textarea id="emailInput" placeholder="Paste raw email content here..."></textarea>
                <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                    <button onclick="parseEmail()">Parse Email</button>
                    <button class="success" onclick="parseAndSendToWebhook()" id="sendWebhookBtn">Parse & Send to Webhook</button>
                </div>
            </div>
            
            <div class="panel">
                <div class="panel-header">
                    <h2>Parsed Result</h2>
                    <button class="copy-btn" onclick="copyToClipboard('jsonResult')">Copy JSON</button>
                </div>
                
                <div class="tabs sub-tabs">
                    <div class="tab active" data-subtab="full-json">Full JSON</div>
                    <div class="tab" data-subtab="extracted-keywords">Extracted Keywords</div>
                </div>
                
                <div class="tab-content active" id="full-json-tab">
                    <pre id="jsonResult"></pre>
                </div>
                
                <div class="tab-content" id="extracted-keywords-tab">
                    <div id="keywordResults">
                        <p>No keywords extracted yet.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Keyword Rules Tab -->
    <div class="tab-content" id="keyword-rules-tab">
        <div class="container">
            <div class="panel">
                <div class="panel-header">
                    <h2>Add Keyword Rule</h2>
                </div>
                
                <div class="input-group">
                    <label class="label" for="ruleName">Rule Name</label>
                    <input type="text" id="ruleName" placeholder="e.g., projectId, orderNumber, customerName">
                </div>
                
                <div class="input-group">
                    <label class="label" for="rulePattern">Pattern to Match</label>
                    <input type="text" id="rulePattern" placeholder="Enter text or regex pattern">
                    
                    <div style="display: flex; align-items: center; margin-top: 8px;">
                        <label class="toggle-switch">
                            <input type="checkbox" id="isRegex">
                            <span class="toggle-slider"></span>
                        </label>
                        <span style="margin-left: 10px;">Use Regular Expression</span>
                        <div class="tooltip">
                            <div class="tooltip-icon">?</div>
                            <span class="tooltip-text">
                                For RegEx: use patterns like \d+ for numbers, \w+ for words.
                                Example: Order #\d+ will match "Order #12345"
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="input-group">
                    <label class="label" for="ruleScope">Search Scope</label>
                    <select id="ruleScope">
                        <option value="all">All Content</option>
                        <option value="subject">Subject Only</option>
                        <option value="body">Body Only</option>
                        <option value="from">From Address Only</option>
                        <option value="to">To Address Only</option>
                    </select>
                </div>
                
                <button onclick="addKeywordRule()">Add Rule</button>
            </div>
            
            <div class="panel">
                <div class="panel-header">
                    <h2>Existing Rules</h2>
                </div>
                
                <div id="keywordRulesList">
                    <?php if (empty($_SESSION['keyword_rules'])): ?>
                        <p>No keyword rules defined yet.</p>
                    <?php else: ?>
                        <?php foreach ($_SESSION['keyword_rules'] as $rule): ?>
                            <div class="keyword-rule" data-id="<?php echo htmlspecialchars($rule['id']); ?>">
                                <div class="rule-header">
                                    <span class="rule-name"><?php echo htmlspecialchars($rule['name']); ?></span>
                                    <button class="danger" onclick="removeKeywordRule('<?php echo htmlspecialchars($rule['id']); ?>')">Remove</button>
                                </div>
                                <div class="rule-pattern">
                                    Pattern: <?php echo htmlspecialchars($rule['pattern']); ?>
                                    <?php if ($rule['is_regex']): ?>
                                        <span class="badge regex">RegEx</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    Scope: <?php echo htmlspecialchars(ucfirst($rule['scope'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Webhook Settings Tab -->
    <div class="tab-content" id="webhook-tab">
        <div class="container">
            <div class="panel full-width">
                <div class="panel-header">
                    <h2>Webhook Configuration</h2>
                </div>
                
                <div class="input-group">
                    <label class="label" for="webhookUrl">Webhook URL</label>
                    <input type="url" id="webhookUrl" placeholder="https://example.com/webhook" value="<?php echo htmlspecialchars($_SESSION['webhook_url']); ?>">
                </div>
                
                <div class="input-group">
                    <label class="label" for="webhookHeaders">
                        Custom Headers (one per line, format: "Header-Name: Value")
                    </label>
                    <textarea id="webhookHeaders" placeholder="Content-Type: application/json
Authorization: Bearer YourApiToken"><?php echo htmlspecialchars($_SESSION['webhook_headers']); ?></textarea>
                </div>
                
                <button onclick="saveWebhookSettings()">Save Webhook Settings</button>
                
                <div class="input-group" style="margin-top: 20px;">
                    <label class="label">Test Webhook</label>
                    <p>You can test your webhook by parsing an email and clicking the "Parse & Send to Webhook" button.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- History Tab -->
    <div class="tab-content" id="history-tab">
        <div class="container">
            <div class="panel full-width">
                <div class="panel-header">
                    <h2>Webhook History</h2>
                    <button class="danger" onclick="clearHistory()">Clear History</button>
                </div>
                
                <div class="webhook-history">
                    <?php if (empty($_SESSION['webhook_history'])): ?>
                        <p>No webhook history yet.</p>
                    <?php else: ?>
                        <?php foreach (array_reverse($_SESSION['webhook_history']) as $historyItem): ?>
                            <div class="webhook-item">
                                <div>
                                    <span class="webhook-status <?php echo $historyItem['success'] ? 'success' : 'error'; ?>"></span>
                                    <strong><?php echo htmlspecialchars($historyItem['url']); ?></strong>
                                    <span class="webhook-timestamp"><?php echo htmlspecialchars($historyItem['timestamp']); ?></span>
                                </div>
                                <div>
                                    Status: <?php echo htmlspecialchars($historyItem['status']); ?>
                                </div>
                                <pre><?php echo htmlspecialchars(json_encode(json_decode($historyItem['response']), JSON_PRETTY_PRINT)); ?></pre>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="js/script.js"></script>
</body>
</html>
