/**
 * Email Parser Application - Client-side JavaScript
 */

// Initialize when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    initTabNavigation();
    initSubTabNavigation();
    
    // Load a simple email by default
    loadPreset('simple');
});

/**
 * Initialize tab navigation
 */
function initTabNavigation() {
    const tabs = document.querySelectorAll('.tabs:not(.sub-tabs) .tab:not([data-subtab])');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all tab content
            const tabContents = document.querySelectorAll('.tab-content:not(.sub-tab-content)');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Show the selected tab content
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId + '-tab').classList.add('active');
        });
    });
}

/**
 * Initialize sub-tab navigation (for nested tabs)
 */
function initSubTabNavigation() {
    const subTabs = document.querySelectorAll('.sub-tabs .tab, [data-subtab]');
    
    subTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Get parent tabs element
            const parentTabs = this.closest('.tabs');
            
            // Remove active class from all tabs in this group
            parentTabs.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all related tab content
            const subtabId = this.getAttribute('data-subtab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            // Find parent tab content and hide all children
            const parentTabContent = this.closest('.tab-content') || document;
            
            parentTabContent.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Show the selected tab content
            const tabContent = document.getElementById(subtabId + '-tab');
            if (tabContent) {
                tabContent.classList.add('active');
            }
        });
    });
}

/**
 * Parse email
 */
function parseEmail() {
    const emailInput = document.getElementById('emailInput').value;
    
    if (!emailInput) {
        alert('Please enter email content first.');
        return;
    }
    
    // Send to server for parsing
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'parse',
            'email_content': emailInput
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update JSON result
            const jsonResult = document.getElementById('jsonResult');
            jsonResult.innerHTML = syntaxHighlight(JSON.stringify(data.parsed_email, null, 2));
            
            // Update keyword results
            updateKeywordResults(data.extracted_keywords);
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while parsing the email.');
    });
}

/**
 * Parse email and send to webhook
 */
function parseAndSendToWebhook() {
    const emailInput = document.getElementById('emailInput').value;
    
    if (!emailInput) {
        alert('Please enter email content first.');
        return;
    }
    
    // First parse the email
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'parse',
            'email_content': emailInput
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI with parsed result
            const jsonResult = document.getElementById('jsonResult');
            jsonResult.innerHTML = syntaxHighlight(JSON.stringify(data.parsed_email, null, 2));
            
            // Update keyword results
            updateKeywordResults(data.extracted_keywords);
            
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
 * Update keyword results in the UI
 */
function updateKeywordResults(extractedKeywords) {
    const container = document.getElementById('keywordResults');
    
    if (!extractedKeywords || Object.keys(extractedKeywords).length === 0) {
        container.innerHTML = '<p>No keywords extracted from this email.</p>';
        return;
    }
    
    // Create HTML for keyword results
    let html = '<table class="keyword-results-table">';
    html += '<thead><tr><th>Rule Name</th><th>Matches</th></tr></thead>';
    html += '<tbody>';
    
    for (const ruleName in extractedKeywords) {
        const matches = extractedKeywords[ruleName];
        
        html += '<tr>';
        html += `<td>${ruleName}</td>`;
        html += '<td>';
        
        if (matches.length > 0) {
            html += '<ul class="keyword-matches">';
            matches.forEach(match => {
                html += `<li>${match}</li>`;
            });
            html += '</ul>';
        } else {
            html += 'No matches found';
        }
        
        html += '</td>';
        html += '</tr>';
    }
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

/**
 * Add a keyword rule
 */
function addKeywordRule() {
    const name = document.getElementById('ruleName').value;
    const pattern = document.getElementById('rulePattern').value;
    const isRegex = document.getElementById('isRegex').checked;
    const scope = document.getElementById('ruleScope').value;
    
    if (!name) {
        alert('Please enter a rule name.');
        return;
    }
    
    if (!pattern) {
        alert('Please enter a pattern to match.');
        return;
    }
    
    // Send to server to add rule
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'add_rule',
            'name': name,
            'pattern': pattern,
            'is_regex': isRegex,
            'scope': scope
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear form fields
            document.getElementById('ruleName').value = '';
            document.getElementById('rulePattern').value = '';
            document.getElementById('isRegex').checked = false;
            document.getElementById('ruleScope').value = 'all';
            
            // Update rules list
            updateRulesList(data.rules);
            
            // If there's a parsed email, re-parse it to apply the new rule
            const emailInput = document.getElementById('emailInput').value;
            if (emailInput) {
                parseEmail();
            }
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the rule.');
    });
}

/**
 * Remove a keyword rule
 */
function removeKeywordRule(ruleId) {
    if (!confirm('Are you sure you want to remove this rule?')) {
        return;
    }
    
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'remove_rule',
            'id': ruleId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update rules list
            updateRulesList(data.rules);
            
            // If there's a parsed email, re-parse it to apply the rule changes
            const emailInput = document.getElementById('emailInput').value;
            if (emailInput) {
                parseEmail();
            }
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while removing the rule.');
    });
}

/**
 * Update the rules list in the UI
 */
function updateRulesList(rules) {
    const rulesList = document.getElementById('keywordRulesList');
    
    if (!rules || rules.length === 0) {
        rulesList.innerHTML = '<p>No keyword rules defined yet.</p>';
        return;
    }
    
    // Create HTML for rules list
    let html = '';
    
    rules.forEach(rule => {
        html += `<div class="keyword-rule" data-id="${rule.id}">`;
        html += '<div class="rule-header">';
        html += `<span class="rule-name">${rule.name}</span>`;
        html += `<button class="danger" onclick="removeKeywordRule('${rule.id}')">Remove</button>`;
        html += '</div>';
        html += `<div class="rule-pattern">Pattern: ${rule.pattern}`;
        
        if (rule.is_regex) {
            html += '<span class="badge regex">RegEx</span>';
        }
        
        html += '</div>';
        html += `<div>Scope: ${rule.scope.charAt(0).toUpperCase() + rule.scope.slice(1)}</div>`;
        html += '</div>';
    });
    
    rulesList.innerHTML = html;
}

/**
 * Save webhook settings
 */
function saveWebhookSettings() {
    const webhookUrl = document.getElementById('webhookUrl').value;
    const webhookHeaders = document.getElementById('webhookHeaders').value;
    
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'update_webhook',
            'webhook_url': webhookUrl,
            'webhook_headers': webhookHeaders
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Webhook settings saved successfully.');
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving webhook settings.');
    });
}

/**
 * Clear webhook history
 */
function clearHistory() {
    if (!confirm('Are you sure you want to clear all webhook history?')) {
        return;
    }
    
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'clear_history'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to update history
            window.location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while clearing history.');
    });
}

/**
 * Load preset email
 */
function loadPreset(type) {
    const emailInput = document.getElementById('emailInput');
    
    switch (type) {
        case 'simple':
            emailInput.value = `From: John Doe <john.doe@example.com>
To: Jane Smith <jane.smith@example.com>
Subject: Quick question
Date: Wed, 16 Apr 2025 10:30:00 -0700

Hi Jane,

Just wanted to ask if you're available for a quick call this afternoon?

Thanks,
John`;
            break;
            
        case 'complex':
            emailInput.value = `From: Sarah Johnson <sarah.johnson@example.com>
To: Development Team <dev-team@example.com>
Cc: Alex Wilson <alex@example.com>, Maria Garcia <maria@example.com>
Bcc: manager@example.com
Subject: [URGENT] Project Status Update - Q2 Review
Date: Wed, 16 Apr 2025 15:45:22 +0200
Message-ID: <abc123@mail.example.com>
In-Reply-To: <xyz789@mail.example.com>
References: <xyz789@mail.example.com> <def456@mail.example.com>
X-Priority: 1
X-Mailer: Example Mail Client 3.0

Dear team,

I'm writing to request an urgent status update on the ongoing development project. 
We need to prepare for the Q2 review meeting scheduled for next Monday.

Please provide the following by EOD tomorrow:
1. Current status of your assigned modules
2. Any blockers or issues you're facing
3. Expected completion dates for remaining tasks

attachment: Project-Timeline.pdf
attachment: Requirements-Doc.docx
attachment: Budget-Overview.xlsx

The executive team will be reviewing our progress, so please ensure your reports are thorough and accurate.

Best regards,
Sarah Johnson
Project Manager
Example Corp
Tel: +1-555-123-4567`;
            break;
            
        case 'html':
            emailInput.value = `From: Newsletter <newsletter@example.com>
To: subscribers@example.com
Subject: Weekly Newsletter - Tech Updates
Date: Wed, 16 Apr 2025 08:00:00 -0500
Content-Type: text/html; charset=UTF-8

<html>
<head>
  <title>Weekly Tech Newsletter</title>
</head>
<body>
  <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
    <h1 style="color: #2c3e50; text-align: center;">Weekly Tech Updates</h1>
    
    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
      <h2>Featured Article</h2>
      <h3>Advancements in AI Development</h3>
      <p>
        This week we look at the latest developments in artificial intelligence
        and how they're