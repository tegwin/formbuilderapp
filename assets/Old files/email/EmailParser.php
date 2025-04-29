<?php
/**
 * Email Parser Class
 * Parses raw email content into structured data
 */
class EmailParser {
    private $parsedEmail;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->resetParsedEmail();
    }
    
    /**
     * Reset parsed email structure
     */
    private function resetParsedEmail() {
        $this->parsedEmail = [
            'headers' => [],
            'subject' => '',
            'from' => [
                'name' => '',
                'email' => ''
            ],
            'to' => [],
            'cc' => [],
            'bcc' => [],
            'date' => '',
            'message_id' => '',
            'in_reply_to' => '',
            'references' => [],
            'priority' => '',
            'attachments' => [],
            'html' => '',
            'text' => '',
            'text_as_html' => ''
        ];
    }
    
    /**
     * Parse a raw email string into structured data
     * 
     * @param string $rawEmail Raw email content
     * @return array Structured email data
     * @throws Exception If email is invalid
     */
    public function parse($rawEmail) {
        if (empty($rawEmail) || !is_string($rawEmail)) {
            throw new Exception('Invalid email: Email must be a non-empty string');
        }
        
        // Reset the parsed email
        $this->resetParsedEmail();
        
        // Split email into headers and body
        $parts = preg_split('/\r?\n\r?\n/', $rawEmail, 2);
        if (count($parts) < 2) {
            throw new Exception('Invalid email format: Could not separate headers and body');
        }
        
        $headers = $parts[0];
        $body = $parts[1];
        
        // Parse headers
        $this->parseHeaders($headers);
        
        // Parse body
        $this->parseBody($body);
        
        return $this->parsedEmail;
    }
    
    /**
     * Parse email headers
     * 
     * @param string $headerString Raw header string
     */
    private function parseHeaders($headerString) {
        // Split into lines
        $headerLines = preg_split('/\r?\n/', $headerString);
        $currentHeader = '';
        $currentValue = '';
        
        // Process each line
        foreach ($headerLines as $line) {
            // Check if line is a continuation of the previous header
            if (preg_match('/^\s+/', $line)) {
                $currentValue .= ' ' . trim($line);
            } else {
                // Save the previous header if it exists
                if (!empty($currentHeader)) {
                    $this->processHeader($currentHeader, $currentValue);
                }
                
                // Parse the new header
                if (preg_match('/^([^:]+):\s*(.*)$/', $line, $matches)) {
                    $currentHeader = strtolower(trim($matches[1]));
                    $currentValue = trim($matches[2]);
                }
            }
        }
        
        // Save the last header
        if (!empty($currentHeader)) {
            $this->processHeader($currentHeader, $currentValue);
        }
    }
    
    /**
     * Process a single header
     * 
     * @param string $name Header name
     * @param string $value Header value
     */
    private function processHeader($name, $value) {
        // Store all headers
        $this->parsedEmail['headers'][$name] = $value;
        
        // Process specific headers
        switch ($name) {
            case 'subject':
                $this->parsedEmail['subject'] = $value;
                break;
                
            case 'from':
                $fromParts = $this->parseAddressList($value);
                if (!empty($fromParts)) {
                    $this->parsedEmail['from'] = $fromParts[0];
                }
                break;
                
            case 'to':
                $this->parsedEmail['to'] = $this->parseAddressList($value);
                break;
                
            case 'cc':
                $this->parsedEmail['cc'] = $this->parseAddressList($value);
                break;
                
            case 'bcc':
                $this->parsedEmail['bcc'] = $this->parseAddressList($value);
                break;
                
            case 'date':
                try {
                    $date = new DateTime($value);
                    $this->parsedEmail['date'] = $date->format('c'); // ISO 8601 format
                } catch (Exception $e) {
                    // If date parsing fails, store original
                    $this->parsedEmail['date'] = $value;
                }
                break;
                
            case 'message-id':
                $this->parsedEmail['message_id'] = trim($value, '<>');
                break;
                
            case 'in-reply-to':
                $this->parsedEmail['in_reply_to'] = trim($value, '<>');
                break;
                
            case 'references':
                $references = [];
                preg_match_all('/<([^>]+)>/', $value, $matches);
                if (!empty($matches[1])) {
                    $references = $matches[1];
                }
                $this->parsedEmail['references'] = $references;
                break;
                
            case 'importance':
            case 'x-priority':
            case 'x-msmail-priority':
                $this->parsedEmail['priority'] = strtolower($value);
                break;
        }
    }
    
    /**
     * Parse email address list
     * 
     * @param string $addressString Address string (e.g. "John Doe <john@example.com>")
     * @return array Array of address objects
     */
    private function parseAddressList($addressString) {
        if (empty($addressString)) {
            return [];
        }
        
        $addresses = [];
        
        // Split by commas, but not within quotes
        $regex = '/,(?=(?:[^"]*"[^"]*")*[^"]*$)/';
        $parts = preg_split($regex, $addressString);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;
            
            // Try to match "Name <email@example.com>" format
            if (preg_match('/^"?([^"<]+)"?\s*<([^>]+)>$/', $part, $matches)) {
                $addresses[] = [
                    'name' => trim($matches[1]),
                    'email' => trim($matches[2])
                ];
            } elseif (strpos($part, '@') !== false) {
                // Just an email address
                $addresses[] = [
                    'name' => '',
                    'email' => trim($part)
                ];
            }
        }
        
        return $addresses;
    }
    
    /**
     * Parse email body
     * 
     * @param string $body Email body
     */
    private function parseBody($body) {
        // For simplicity, assume plain text by default
        $this->parsedEmail['text'] = trim($body);
        
        // Check for HTML content
        if (preg_match('/<html|<body|<div/i', $body)) {
            $this->parsedEmail['html'] = trim($body);
            $this->parsedEmail['text'] = $this->htmlToText($body);
        } else {
            // Generate HTML version from plain text
            $this->parsedEmail['text_as_html'] = $this->textToHtml($body);
        }
        
        // Basic attachment detection
        // This is a simplified approach - real implementation would use MIME parsing
        if (preg_match_all('/attachment:\s*([^\n]+)/i', $body, $matches)) {
            foreach ($matches[1] as $attachment) {
                $filename = trim($attachment);
                $this->parsedEmail['attachments'][] = [
                    'filename' => $filename,
                    'content_type' => $this->guessContentType($filename),
                    'size' => 0 // Would need real file access to get size
                ];
            }
        }
    }
    
    /**
     * Convert HTML to plain text
     * 
     * @param string $html HTML content
     * @return string Plain text
     */
    private function htmlToText($html) {
        // Remove style and script tags
        $text = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        $text = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $text);
        
        // Replace common HTML entities
        $text = str_replace('&nbsp;', ' ', $text);
        $text = str_replace('&amp;', '&', $text);
        $text = str_replace('&lt;', '<', $text);
        $text = str_replace('&gt;', '>', $text);
        
        // Remove HTML tags
        $text = strip_tags($text);
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Convert plain text to HTML
     * 
     * @param string $text Plain text
     * @return string HTML version
     */
    private function textToHtml($text) {
        // Escape HTML special chars
        $html = htmlspecialchars($text, ENT_QUOTES);
        
        // Convert line breaks to <br> tags
        $html = nl2br($html);
        
        // Make URLs clickable
        $html = preg_replace('/(https?:\/\/[^\s<]+)/', '<a href="$1">$1</a>', $html);
        
        return '<div>' . $html . '</div>';
    }
    
    /**
     * Guess content type from filename
     * 
     * @param string $filename Filename
     * @return string MIME type
     */
    private function guessContentType($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'txt' => 'text/plain',
            'zip' => 'application/zip'
        ];
        
        return isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';
    }
}
