<?php
/**
 * Form Display class for standalone mode
 */

class DFB_Form_Display {
    
    private $db;
    
    public function __construct() {
        $this->db = new DFB_DB_Standalone();
    }
    
    /**
     * List available forms
     */
    public function list_forms() {
        $forms = $this->db->get_forms();
        
        include_once 'templates/forms-list-frontend.php';
    }
    
    /**
     * Render a form
     */
    public function render_form($form_id) {
        $form = $this->db->get_form($form_id);
        
        if (!$form) {
            echo '<p class="error">Form not found</p>';
            return;
        }
        
        $fields = $this->db->get_form_fields($form_id);
        
        if (empty($fields)) {
            echo '<p class="error">This form has no fields</p>';
            return;
        }
        
        // Check for success message
        $success_message = '';
        if (isset($_SESSION['dfb_success'])) {
            $success_message = '<div class="dfb-success-message">' . htmlspecialchars($_SESSION['dfb_success']) . '</div>';
            unset($_SESSION['dfb_success']);
        }
        
        // Check for errors
        $error_messages = '';
        if (isset($_SESSION['dfb_errors']) && is_array($_SESSION['dfb_errors'])) {
            $error_messages = '<div class="dfb-error-messages">';
            $error_messages .= '<ul>';
            
            foreach ($_SESSION['dfb_errors'] as $error) {
                $error_messages .= '<li>' . htmlspecialchars($error) . '</li>';
            }
            
            $error_messages .= '</ul>';
            $error_messages .= '</div>';
            
            // Clear errors from session
            unset($_SESSION['dfb_errors']);
        }
        
        include_once 'templates/form-display.php';
    }
}