<?php
/**
 * Form Handler class for standalone form submissions
 */

class DFB_Form_Handler_Standalone {
    
    private $db;
    
    public function __construct() {
        $this->db = new DFB_DB_Standalone();
    }
    
    /**
     * Process form submission
     */
    public function process_form() {
        $form_id = intval($_POST['dfb_form_id']);
        
        // Get form data
        $form = $this->db->get_form($form_id);
        if (!$form) {
            die('Form not found');
        }
        
        // Get form fields
        $fields = $this->db->get_form_fields($form_id);
        
        // Validate form data
        $entry_data = $this->validate_form_data($fields);
        
        // If validation failed, redirect back with errors
        if (isset($entry_data['has_errors']) && $entry_data['has_errors']) {
            session_start();
            $_SESSION['dfb_errors'] = $entry_data['errors'];
            $_SESSION['dfb_form_data'] = $_POST;
            
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }
        
        // Save entry to database
        $entry_id = $this->db->store_entry($form_id, $entry_data);
        
        // Send email notification
        $this->send_email_notification($form, $fields, $entry_data);
        
        // Send webhook if configured
        if (!empty($form->webhook_url)) {
            $this->send_webhook($form, $entry_data);
        }
        
        // Store success message in session
        session_start();
        $_SESSION['dfb_success'] = $form->success_message;
        
        // Redirect back to form
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }
    
    /**
     * Validate form data
     */
    private function validate_form_data($fields) {
        $entry_data = array();
        $errors = array();
        
        foreach ($fields as $field) {
            $field_name = 'dfb_field_' . $field->id;
            $field_value = isset($_POST[$field_name]) ? $_POST[$field_name] : '';
            
            // Required field check
            if ($field->required && empty($field_value)) {
                $errors[] = "Field \"{$field->label}\" is required.";
                continue;
            }
            
            // Add to entry data
            $entry_data[$field->label] = $field_value;
        }
        
        // If there are errors, add them to entry data
        if (!empty($errors)) {
            $entry_data['has_errors'] = true;
            $entry_data['errors'] = $errors;
        }
        
        return $entry_data;
    }
    
    /**
     * Send email notification
     */
    private function send_email_notification($form, $fields, $entry_data) {
        if (empty($form->email_recipients)) {
            return;
        }
        
        $to = explode(',', $form->email_recipients);
        $subject = "New form submission: {$form->title}";
        
        // Build email content
        $message = "<h2>New submission for form: {$form->title}</h2>";
        $message .= '<table style="width: 100%; border-collapse: collapse;">';
        
        foreach ($entry_data as $label => $value) {
            // Skip internal fields
            if ($label === 'has_errors' || $label === 'errors') {
                continue;
            }
            
            $message .= '<tr>';
            $message .= '<th style="text-align: left; padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2;">' . htmlspecialchars($label) . '</th>';
            
            if (is_array($value)) {
                $message .= '<td style="padding: 8px; border: 1px solid #ddd;">' . implode(', ', array_map('htmlspecialchars', $value)) . '</td>';
            } else {
                $message .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($value) . '</td>';
            }
            
            $message .= '</tr>';
        }
        
        $message .= '</table>';
        
        // Email headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Form Builder <no-reply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
        
        // Send email
        foreach ($to as $recipient) {
            mail(trim($recipient), $subject, $message, $headers);
        }
    }
    
    /**
     * Send webhook
     */
    private function send_webhook($form, $entry_data) {
        // Implementation omitted for brevity - will use cURL to send webhook data
    }
}