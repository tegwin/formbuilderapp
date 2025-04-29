<?php
/**
 * Email Receiver Class
 * Handles receiving emails via POP3 or IMAP and processes them with the parser
 */
class EmailReceiver {
    private $server;
    private $port;
    private $username;
    private $password;
    private $protocol;
    private $options;
    private $connection;
    
    /**
     * Constructor
     * 
     * @param string $server Mail server address
     * @param int $port Server port
     * @param string $username Email username
     * @param string $password Email password
     * @param string $protocol Connection protocol (imap or pop3)
     * @param array $options Additional options
     */
    public function __construct($server, $port, $username, $password, $protocol = 'imap', $options = []) {
        $this->server = $server;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->protocol = strtolower($protocol);
        $this->options = $options;
        $this->connection = null;
    }
    
    /**
     * Connect to mail server
     * 
     * @return bool True if connected successfully
     * @throws Exception If connection fails
     */
    public function connect() {
        if ($this->connection) {
            return true; // Already connected
        }
        
        // Check if IMAP extension is installed
        if (!function_exists('imap_open')) {
            throw new Exception('IMAP extension is not installed on this server');
        }
        
        // Build mailbox string
        $mailbox = $this->buildMailboxString();
        
        // Set connection options
        $options = 0;
        if (isset($this->options['novalidate-cert']) && $this->options['novalidate-cert']) {
            $options |= OP_NOVALIDATE_CERT;
        }
        
        // Connect to server
        $this->connection = @imap_open($mailbox, $this->username, $this->password, $options);
        
        if (!$this->connection) {
            throw new Exception('Failed to connect to mail server: ' . imap_last_error());
        }
        
        return true;
    }
    
    /**
     * Disconnect from mail server
     */
    public function disconnect() {
        if ($this->connection) {
            imap_close($this->connection);
            $this->connection = null;
        }
    }
    
    /**
     * Build mailbox connection string
     * 
     * @return string Mailbox string
     */
    private function buildMailboxString() {
        $mailbox = '{' . $this->server . ':' . $this->port;
        
        // Add protocol
        if ($this->protocol === 'pop3') {
            $mailbox .= '/pop3';
        }
        
        // Add SSL/TLS if needed
        if (isset($this->options['ssl']) && $this->options['ssl']) {
            $mailbox .= '/ssl';
        } else if (isset($this->options['tls']) && $this->options['tls']) {
            $mailbox .= '/tls';
        }
        
        // Close mailbox string
        $mailbox .= '}INBOX';
        
        return $mailbox;
    }
    
    /**
     * Get new emails
     * 
     * @param bool $markAsRead Whether to mark emails as read
     * @param int $limit Maximum number of emails to fetch
     * @return array Array of raw email content
     * @throws Exception If connection fails
     */
    public function getNewEmails($markAsRead = true, $limit = 10) {
        // Connect if not already connected
        if (!$this->connection) {
            $this->connect();
        }
        
        $emails = [];
        
        // Search for unread messages
        $searchCriteria = 'UNSEEN';
        $emailIds = imap_search($this->connection, $searchCriteria);
        
        if (!$emailIds) {
            return $emails; // No new emails
        }
        
        // Sort emails by date (newest first)
        rsort($emailIds);
        
        // Limit the number of emails to fetch
        if ($limit > 0 && count($emailIds) > $limit) {
            $emailIds = array_slice($emailIds, 0, $limit);
        }
        
        // Fetch each email
        foreach ($emailIds as $emailId) {
            $headers = imap_headerinfo($this->connection, $emailId);
            $rawBody = imap_body($this->connection, $emailId);
            
            // Rebuild the raw email with headers and body
            $rawEmail = $this->rebuildRawEmail($headers, $rawBody);
            
            $emails[] = [
                'id' => $emailId,
                'raw_content' => $rawEmail,
                'headers' => $headers
            ];
            
            // Mark as read if requested
            if ($markAsRead) {
                imap_setflag_full($this->connection, $emailId, "\\Seen");
            }
        }
        
        return $emails;
    }
    
    /**
     * Rebuild raw email content from headers and body
     * 
     * @param object $headers Email headers
     * @param string $body Email body
     * @return string Raw email content
     */
    private function rebuildRawEmail($headers, $body) {
        $rawEmail = '';
        
        // Add From header
        $from = isset($headers->fromaddress) ? $headers->fromaddress : '';
        $rawEmail .= "From: $from\r\n";
        
        // Add To header
        $to = isset($headers->toaddress) ? $headers->toaddress : '';
        $rawEmail .= "To: $to\r\n";
        
        // Add Subject header
        $subject = isset($headers->subject) ? $headers->subject : '';
        $rawEmail .= "Subject: $subject\r\n";
        
        // Add Date header
        $date = isset($headers->date) ? $headers->date : '';
        $rawEmail .= "Date: $date\r\n";
        
        // Add Message-ID header
        $messageId = isset($headers->message_id) ? $headers->message_id : '';
        $rawEmail .= "Message-ID: $messageId\r\n";
        
        // Add empty line to separate headers and body
        $rawEmail .= "\r\n";
        
        // Add body
        $rawEmail .= $body;
        
        return $rawEmail;
    }
    
    /**
     * Process new emails with parser
     * 
     * @param EmailParser $parser Parser instance
     * @param bool $markAsRead Whether to mark emails as read
     * @param int $limit Maximum number of emails to process
     * @param KeywordExtractor $keywordExtractor Optional keyword extractor
     * @return array Processed emails
     */
    public function receiveAndProcess($parser, $markAsRead = true, $limit = 10, $keywordExtractor = null) {
        $results = [];
        
        try {
            // Get new emails
            $emails = $this->getNewEmails($markAsRead, $limit);
            
            foreach ($emails as $email) {
                // Parse the email
                $parsedEmail = $parser->parse($email['raw_content']);
                
                // Extract keywords if extractor is provided
                if ($keywordExtractor) {
                    $parsedEmail['extracted_keywords'] = $keywordExtractor->extract($parsedEmail);
                }
                
                $results[] = [
                    'email_id' => $email['id'],
                    'parsed_email' => $parsedEmail
                ];
            }
        } catch (Exception $e) {
            // Log error
            error_log('Error processing emails: ' . $e->getMessage());
            throw $e;
        } finally {
            // Always disconnect
            $this->disconnect();
        }
        
        return $results;
    }
    
    /**
     * Delete an email
     * 
     * @param int $emailId Email ID to delete
     * @return bool True if deleted successfully
     */
    public function deleteEmail($emailId) {
        if (!$this->connection) {
            $this->connect();
        }
        
        $result = imap_delete($this->connection, $emailId, FT_UID);
        imap_expunge($this->connection);
        
        return $result;
    }
}
