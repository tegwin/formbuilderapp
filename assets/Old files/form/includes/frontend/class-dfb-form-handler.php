<?php
/**
 * Form Handler class for processing form submissions
 */

class DFB_Form_Handler {
    
    public function __construct() {
        // Add action to handle form submission
        add_action('wp', array($this, 'process_form_submission'));
    }
    
    /**
     * Process form submission
     */
    public function process_form_submission() {
        if (!isset($_POST['dfb_form_id']) || !isset($_POST['dfb_submit'])) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['dfb_nonce']) || !wp_verify_nonce($_POST['dfb_nonce'], 'dfb_form_submit')) {
            wp_die('Security check failed');
        }
        
        $form_id = intval($_POST['dfb_form_id']);
        $db = new DFB_DB();
        
        // Get form data
        $form = $db->get_form($form_id);
        if (!$form) {
            wp_die('Form not found');
        }
        
        // Get form fields
        $fields = $db->get_form_fields($form_id);
        
        // Validate form data
        $entry_data = $this->validate_form_data($fields);
        
        // If validation failed, redirect back with errors
        if (isset($entry_data['has_errors']) && $entry_data['has_errors']) {
            $redirect_url = add_query_arg(array(
                'dfb_error' => 1,
                'dfb_form_id' => $form_id
            ), wp_get_referer());
            
            wp_safe_redirect($redirect_url);
            exit;
        }
        
        // Save entry to database
        $entry_id = $db->store_entry($form_id, $entry_data);
        
        // Send email notification
        $this->send_email_notification($form, $fields, $entry_data);
        
        // Send webhook if configured
        if (!empty($form->webhook_url)) {
            $this->send_webhook($form, $entry_data);
        }
        
        // Redirect to success page or same page with success message
        $redirect_url = add_query_arg(array(
            'dfb_success' => 1,
            'dfb_form_id' => $form_id
        ), wp_get_referer());
        
        wp_safe_redirect($redirect_url);
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
                $errors[] = sprintf(__('Field "%s" is required.', 'dynamic-form-builder'), $field->label);
                continue;
            }
            
            // Validate based on field type
            switch ($field->field_type) {
                case 'email':
                    if (!empty($field_value) && !is_email($field_value)) {
                        $errors[] = sprintf(__('Please enter a valid email address for "%s".', 'dynamic-form-builder'), $field->label);
                    }
                    break;
                    
                case 'url':
                    if (!empty($field_value) && !filter_var($field_value, FILTER_VALIDATE_URL)) {
                        $errors[] = sprintf(__('Please enter a valid URL for "%s".', 'dynamic-form-builder'), $field->label);
                    }
                    break;
                    
                case 'number':
                    if (!empty($field_value) && !is_numeric($field_value)) {
                        $errors[] = sprintf(__('Please enter a valid number for "%s".', 'dynamic-form-builder'), $field->label);
                    }
                    break;
                    
                case 'tel':
                    // Simple phone validation
                    if (!empty($field_value) && !preg_match('/^[0-9+\-\(\)\s]+$/', $field_value)) {
                        $errors[] = sprintf(__('Please enter a valid phone number for "%s".', 'dynamic-form-builder'), $field->label);
                    }
                    break;
                    
                case 'file':
                    // File upload handling
                    if (!empty($_FILES[$field_name]['name'])) {
                        $uploaded_file = $_FILES[$field_name];
                        
                        // Check for upload errors
                        if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
                            $errors[] = sprintf(__('File upload failed for "%s".', 'dynamic-form-builder'), $field->label);
                        } else {
                            $upload_dir = wp_upload_dir();
                            $target_dir = $upload_dir['basedir'] . '/form-builder/';
                            $file_name = sanitize_file_name($uploaded_file['name']);
                            $target_file = $target_dir . time() . '_' . $file_name;
                            
                            if (move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
                                $field_value = $target_file;
                            } else {
                                $errors[] = sprintf(__('Failed to save uploaded file for "%s".', 'dynamic-form-builder'), $field->label);
                            }
                        }
                    }
                    break;
            }
            
            // Custom validation rules
            if (!empty($field->validation_rules) && !empty($field_value)) {
                $rules = explode('|', $field->validation_rules);
                
                foreach ($rules as $rule) {
                    if ($rule === 'email' && !is_email($field_value)) {
                        $errors[] = sprintf(__('Please enter a valid email address for "%s".', 'dynamic-form-builder'), $field->label);
                    } else if ($rule === 'url' && !filter_var($field_value, FILTER_VALIDATE_URL)) {
                        $errors[] = sprintf(__('Please enter a valid URL for "%s".', 'dynamic-form-builder'), $field->label);
                    } else if ($rule === 'numeric' && !is_numeric($field_value)) {
                        $errors[] = sprintf(__('Please enter a valid number for "%s".', 'dynamic-form-builder'), $field->label);
                    } else if (strpos($rule, 'min:') === 0) {
                        $min = intval(substr($rule, 4));
                        if (strlen($field_value) < $min) {
                            $errors[] = sprintf(__('"%s" must be at least %d characters.', 'dynamic-form-builder'), $field->label, $min);
                        }
                    } else if (strpos($rule, 'max:') === 0) {
                        $max = intval(substr($rule, 4));
                        if (strlen($field_value) > $max) {
                            $errors[] = sprintf(__('"%s" must not exceed %d characters.', 'dynamic-form-builder'), $field->label, $max);
                        }
                    }
                }
            }
            
            // Sanitize field value
            $sanitized_value = $this->sanitize_field_value($field_value, $field->field_type);
            
            // Add to entry data
            $entry_data[$field->label] = $sanitized_value;
        }
        
        // If there are errors, add them to entry data
        if (!empty($errors)) {
            $entry_data['has_errors'] = true;
            $entry_data['errors'] = $errors;
            
            // Store errors in session
            if (!session_id()) {
                session_start();
            }
            $_SESSION['dfb_errors'] = $errors;
        }
        
        return $entry_data;
    }
    
    /**
     * Sanitize field value
     */
    private function sanitize_field_value($value, $type) {
        switch ($type) {
            case 'text':
            case 'password':
            case 'tel':
            case 'date':
            case 'time':
            case 'hidden':
                return sanitize_text_field($value);
                
            case 'textarea':
            case 'html':
                return wp_kses_post($value);
                
            case 'email':
                return sanitize_email($value);
                
            case 'url':
                return esc_url_raw($value);
                
            case 'number':
                return is_numeric($value) ? $value : '';
                
            case 'select':
            case 'radio':
                return sanitize_text_field($value);
                
            case 'checkbox':
                if (is_array($value)) {
                    return array_map('sanitize_text_field', $value);
                } else {
                    return sanitize_text_field($value);
                }
                
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Send email notification
     */
    private function send_email_notification($form, $fields, $entry_data) {
        if (empty($form->email_recipients)) {
            return;
        }
        
        $to = explode(',', $form->email_recipients);
        $subject = sprintf(__('New form submission: %s', 'dynamic-form-builder'), $form->title);
        
        // Build email content
        $message = '<h2>' . sprintf(__('New submission for form: %s', 'dynamic-form-builder'), $form->title) . '</h2>';
        $message .= '<table style="width: 100%; border-collapse: collapse;">';
        
        foreach ($entry_data as $label => $value) {
            // Skip internal fields
            if ($label === 'has_errors' || $label === 'errors') {
                continue;
            }
            
            $message .= '<tr>';
            $message .= '<th style="text-align: left; padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2;">' . esc_html($label) . '</th>';
            
            if (is_array($value)) {
                $message .= '<td style="padding: 8px; border: 1px solid #ddd;">' . implode(', ', array_map('esc_html', $value)) . '</td>';
            } else {
                $message .= '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($value) . '</td>';
            }
            
            $message .= '</tr>';
        }
        
        $message .= '</table>';
        
        // Add submission date
        $message .= '<p style="margin-top: 20px; font-style: italic;">';
        $message .= sprintf(__('Submitted on: %s', 'dynamic-form-builder'), date_i18n(get_option('date_format') . ' ' . get_option('time_format')));
        $message .= '</p>';
        
        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>'
        );
        
        // Send email
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send webhook
     */
    private function send_webhook($form, $entry_data) {
        // Remove internal fields
        unset($entry_data['has_errors']);
        unset($entry_data['errors']);
        
        // Prepare data
        $webhook_data = array(
            'form_id' => $form->id,
            'form_title' => $form->title,
            'submission_date' => date('Y-m-d H:i:s'),
            'entry_data' => $entry_data
        );
        
        // Send webhook
        $response = wp_remote_post($form->webhook_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($webhook_data),
            'timeout' => 15
        ));
        
        // Log webhook errors
        if (is_wp_error($response)) {
            error_log('Form Builder Webhook Error: ' . $response->get_error_message());
        }
    }
}

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
            
            // Validate based on field type
            switch ($field->field_type) {
                case 'email':
                    if (!empty($field_value) && !filter_var($field_value, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Please enter a valid email address for \"{$field->label}\".";
                    }
                    break;
                    
                case 'url':
                    if (!empty($field_value) && !filter_var($field_value, FILTER_VALIDATE_URL)) {
                        $errors[] = "Please enter a valid URL for \"{$field->label}\".";
                    }
                    break;
                    
                case 'number':
                    if (!empty($field_value) && !is_numeric($field_value)) {
                        $errors[] = "Please enter a valid number for \"{$field->label}\".";
                    }
                    break;
                    
                case 'tel':
                    // Simple phone validation
                    if (!empty($field_value) && !preg_match('/^[0-9+\-\(\)\s]+$/', $field_value)) {
                        $errors[] = "Please enter a valid phone number for \"{$field->label}\".";
                    }
                    break;
                    
                case 'file':
                    // File upload handling
                    if (!empty($_FILES[$field_name]['name'])) {
                        $uploaded_file = $_FILES[$field_name];
                        
                        // Check for upload errors
                        if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
                            $errors[] = "File upload failed for \"{$field->label}\".";
                        } else {
                            $target_dir = 'uploads/';
                            if (!file_exists($target_dir)) {
                                mkdir($target_dir, 0777, true);
                            }
                            
                            $file_name = basename($uploaded_file['name']);
                            $target_file = $target_dir . time() . '_' . $file_name;
                            
                            if (move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
                                $field_value = $target_file;
                            } else {
                                $errors[] = "Failed to save uploaded file for \"{$field->label}\".";
                            }
                        }
                    }
                    break;
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
        
        // Add submission date
        $message .= '<p style="margin-top: 20px; font-style: italic;">';
        $message .= 'Submitted on: ' . date('Y-m-d H:i:s');
        $message .= '</p>';
        
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
        // Remove internal fields
        unset($entry_data['has_errors']);
        unset($entry_data['errors']);
        
        // Prepare data
        $webhook_data = array(
            'form_id' => $form->id,
            'form_title' => $form->title,
            'submission_date' => date('Y-m-d H:i:s'),
            'entry_data' => $entry_data
        );
        
        // Initialize cURL
        $ch = curl_init($form->webhook_url);
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhook_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($webhook_data))
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        // Execute cURL request
        $response = curl_exec($ch);
        
        // Check for errors
        if (curl_errno($ch)) {
            error_log('Form Builder Webhook Error: ' . curl_error($ch));
        }
        
        // Close cURL connection
        curl_close($ch);
    }
}