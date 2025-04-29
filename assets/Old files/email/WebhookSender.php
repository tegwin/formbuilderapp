<?php
/**
 * WebhookSender Class
 * Handles sending data to webhook endpoints
 */
class WebhookSender {
    /**
     * Send data to a webhook URL
     * 
     * @param string $url Webhook URL
     * @param array $data Data to send
     * @param array $headers Optional HTTP headers
     * @return array Response data
     * @throws Exception If request fails
     */
    public function send($url, $data, $headers = []) {
        if (empty($url)) {
            throw new Exception('Webhook URL is required');
        }
        
        // Prepare cURL options
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => []
        ];
        
        // Add Content-Type header if not provided
        $contentTypeSet = false;
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'content-type') {
                $contentTypeSet = true;
            }
            $options[CURLOPT_HTTPHEADER][] = "$key: $value";
        }
        
        if (!$contentTypeSet) {
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        }
        
        // Initialize cURL
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        
        // Execute request
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        // Close cURL
        curl_close($ch);
        
        // Handle errors
        if ($error) {
            throw new Exception("Webhook request failed: $error");
        }
        
        return [
            'status' => $statusCode,
            'response' => $response,
            'success' => $statusCode >= 200 && $statusCode < 300
        ];
    }
    
    /**
     * Test a webhook connection
     * 
     * @param string $url Webhook URL
     * @param array $headers Optional HTTP headers
     * @return array Test results
     */
    public function test($url, $headers = []) {
        try {
            // Send a simple test payload
            $testData = [
                'test' => true,
                'timestamp' => date('c'),
                'message' => 'This is a test webhook from Email Parser application'
            ];
            
            $response = $this->send($url, $testData, $headers);
            
            return [
                'success' => true,
                'status' => $response['status'],
                'response' => $response['response']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
