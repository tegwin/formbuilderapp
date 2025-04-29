<?php
/**
 * Webhook class for Form Builder
 */

class DFB_Webhook {
    
    /**
     * Send webhook with form submission data
     */
    public static function send($form, $entry_data) {
        // If no webhook URL specified, return
        if (empty($form->webhook_url)) {
            return false;
        }
        
        // Remove any internal fields
        if (isset($entry_data['has_errors'])) {
            unset($entry_data['has_errors']);
        }
        
        if (isset($entry_data['errors'])) {
            unset($entry_data['errors']);
        }
        
        // Format the data for webhook
        $webhook_data = array(
            'form' => array(
                'id' => $form->id,
                'title' => $form->title
            ),
            'submission' => $entry_data,
            'meta' => array(
                'timestamp' => date('c'),
                'ip_address' => self::get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
            )
        );
        
        // Convert to JSON
        $json_data = json_encode($webhook_data);
        
        // WordPress environment
        if (function_exists('wp_remote_post')) {
            $response = wp_remote_post($form->webhook_url, array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'X-Form-Builder' => 'Dynamic Form Builder'
                ),
                'body' => $json_data,
                'timeout' => 15
            ));
            
            if (is_wp_error($response)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Form Builder webhook error: ' . $response->get_error_message());
                }
                return false;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            return ($response_code >= 200 && $response_code < 300);
        }
        // Standalone environment
        else {
            $ch = curl_init($form->webhook_url);
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json_data),
                'X-Form-Builder: Dynamic Form Builder'
            ));
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                error_log('Form Builder webhook error: ' . curl_error($ch));
                curl_close($ch);
                return false;
            }
            
            curl_close($ch);
            return ($http_code >= 200 && $http_code < 300);
        }
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
}