<?php
/**
 * Admin class for standalone Form Builder
 */

class DFB_Admin_Standalone {
    
    private $db;
    
    public function __construct() {
        $this->db = new DFB_DB_Standalone();
    }
    
    /**
     * Display admin interface
     */
    public function display() {
        // Handle form submissions
        $this->handle_actions();
        
        // Load header
        include_once 'templates/header.php';
        
        // Determine which page to display
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        
        switch ($action) {
            case 'new':
                $this->display_form_builder();
                break;
            case 'edit':
                $this->display_form_builder();
                break;
            case 'entries':
                $this->display_form_entries();
                break;
            default:
                $this->display_forms_list();
                break;
        }
        
        // Load footer
        include_once 'templates/footer.php';
    }
    
    /**
     * Handle admin actions
     */
    private function handle_actions() {
        // Save form
        if (isset($_POST['save_form'])) {
            $this->save_form();
        }
        
        // Save field
        if (isset($_POST['save_field'])) {
            $this->save_field();
        }
        
        // Delete field
        if (isset($_GET['delete_field'])) {
            $this->delete_field();
        }
        
        // Delete form
        if (isset($_GET['delete_form'])) {
            $this->delete_form();
        }
    }
    
    /**
     * Display forms list
     */
    private function display_forms_list() {
        $forms = $this->db->get_forms();
        include_once 'templates/forms-list.php';
    }
    
    /**
     * Display form builder
     */
    private function display_form_builder() {
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        
        if ($form_id > 0) {
            $form = $this->db->get_form($form_id);
            $fields = $this->db->get_form_fields($form_id);
        } else {
            $form = new stdClass();
            $form->title = '';
            $form->description = '';
            $form->email_recipients = '';
            $form->webhook_url = '';
            $form->success_message = 'Form submitted successfully!';
            $fields = array();
        }
        
        // Get field types
        $field_types = $this->get_field_types();
        
        include_once 'templates/form-builder.php';
    }
    
    /**
     * Display form entries
     */
    private function display_form_entries() {
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        
        $form = $this->db->get_form($form_id);
        $entries = $this->db->get_form_entries($form_id);
        $fields = $this->db->get_form_fields($form_id);
        
        include_once 'templates/form-entries.php';
    }
    
    /**
     * Save form
     */
    private function save_form() {
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        $form_data = array(
            'title' => htmlspecialchars($_POST['title']),
            'description' => htmlspecialchars($_POST['description']),
            'email_recipients' => htmlspecialchars($_POST['email_recipients']),
            'webhook_url' => filter_var($_POST['webhook_url'], FILTER_SANITIZE_URL),
            'success_message' => htmlspecialchars($_POST['success_message'])
        );
        
        if ($form_id > 0) {
            $this->db->update_form($form_id, $form_data);
            $message = 'Form updated successfully!';
        } else {
            $form_id = $this->db->create_form($form_data);
            $message = 'Form created successfully!';
        }
        
        // Redirect to edit page
        header("Location: index.php?dfb_admin=1&action=edit&form_id={$form_id}&message=" . urlencode($message));
        exit;
    }
    
    /**
     * Save field
     */
    private function save_field() {
        $field_id = isset($_POST['field_id']) ? intval($_POST['field_id']) : 0;
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        $field_data = array(
            'form_id' => $form_id,
            'field_type' => htmlspecialchars($_POST['field_type']),
            'label' => htmlspecialchars($_POST['label']),
            'placeholder' => htmlspecialchars($_POST['placeholder']),
            'options' => isset($_POST['options']) ? htmlspecialchars($_POST['options']) : '',
            'required' => isset($_POST['required']) ? 1 : 0,
            'field_order' => intval($_POST['field_order']),
            'field_class' => htmlspecialchars($_POST['field_class']),
            'field_id' => htmlspecialchars($_POST['field_id']),
            'validation_rules' => isset($_POST['validation_rules']) ? htmlspecialchars($_POST['validation_rules']) : ''
        );
        
        if ($field_id > 0) {
            $this->db->update_form_field($field_id, $field_data);
            $message = 'Field updated successfully!';
        } else {
            $this->db->add_form_field($field_data);
            $message = 'Field added successfully!';
        }
        
        // Redirect to edit page
        header("Location: index.php?dfb_admin=1&action=edit&form_id={$form_id}&message=" . urlencode($message));
        exit;
    }
    
    /**
     * Delete field
     */
    private function delete_field() {
        $field_id = isset($_GET['field_id']) ? intval($_GET['field_id']) : 0;
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        
        if ($field_id > 0) {
            $this->db->delete_form_field($field_id);
            $message = 'Field deleted successfully!';
        } else {
            $message = 'Invalid field ID';
        }
        
        // Redirect to edit page
        header("Location: index.php?dfb_admin=1&action=edit&form_id={$form_id}&message=" . urlencode($message));
        exit;
    }
    
    /**
     * Delete form
     */
    private function delete_form() {
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        
        if ($form_id > 0) {
            $this->db->delete_form($form_id);
            $message = 'Form deleted successfully!';
        } else {
            $message = 'Invalid form ID';
        }
        
        // Redirect to list page
        header("Location: index.php?dfb_admin=1&message=" . urlencode($message));
        exit;
    }
    
    /**
     * Get available field types
     */
    public function get_field_types() {
        return array(
            'text' => 'Text',
            'email' => 'Email',
            'url' => 'URL',
            'password' => 'Password',
            'tel' => 'Telephone',
            'number' => 'Number',
            'textarea' => 'Textarea',
            'select' => 'Dropdown',
            'radio' => 'Radio Buttons',
            'checkbox' => 'Checkboxes',
            'date' => 'Date',
            'time' => 'Time',
            'file' => 'File Upload',
            'hidden' => 'Hidden Field',
            'html' => 'HTML Content'
        );
    }
}