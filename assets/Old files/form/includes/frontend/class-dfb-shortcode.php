<?php
/**
 * Shortcode class for displaying forms
 */

class DFB_Shortcode {
    
    public function __construct() {
        // Register shortcode
        add_shortcode('dynamic_form', array($this, 'render_form'));
        
        // Add scripts and styles to frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Styles
        wp_enqueue_style('dfb-frontend-css', DFB_ASSETS_URL . 'css/frontend.css', array(), DFB_VERSION);
        
        // Scripts
        wp_enqueue_script('dfb-frontend-js', DFB_ASSETS_URL . 'js/frontend.js', array('jquery'), DFB_VERSION, true);
        
        // Pass data to script
        wp_localize_script('dfb-frontend-js', 'dfb_vars', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }
    
    /**
     * Render form shortcode
     */
    public function render_form($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'title' => true,
            'description' => true
        ), $atts, 'dynamic_form');
        
        $form_id = intval($atts['id']);
        
        if ($form_id <= 0) {
            return '<p class="dfb-error">' . __('Invalid form ID', 'dynamic-form-builder') . '</p>';
        }
        
        $db = new DFB_DB();
        $form = $db->get_form($form_id);
        
        if (!$form) {
            return '<p class="dfb-error">' . __('Form not found', 'dynamic-form-builder') . '</p>';
        }
        
        $fields = $db->get_form_fields($form_id);
        
        if (empty($fields)) {
            return '<p class="dfb-error">' . __('This form has no fields', 'dynamic-form-builder') . '</p>';
        }
        
        // Check for success message
        $success_message = '';
        if (isset($_GET['dfb_success']) && isset($_GET['dfb_form_id']) && intval($_GET['dfb_form_id']) === $form_id) {
            $success_message = '<div class="dfb-success-message">' . esc_html($form->success_message) . '</div>';
        }
        
        // Check for errors
        $error_messages = '';
        if (isset($_GET['dfb_error']) && isset($_GET['dfb_form_id']) && intval($_GET['dfb_form_id']) === $form_id) {
            if (!session_id()) {
                session_start();
            }
            
            if (isset($_SESSION['dfb_errors']) && is_array($_SESSION['dfb_errors'])) {
                $error_messages = '<div class="dfb-error-messages">';
                $error_messages .= '<ul>';
                
                foreach ($_SESSION['dfb_errors'] as $error) {
                    $error_messages .= '<li>' . esc_html($error) . '</li>';
                }
                
                $error_messages .= '</ul>';
                $error_messages .= '</div>';
                
                // Clear errors from session
                unset($_SESSION['dfb_errors']);
            }
        }
        
        // Start output buffer
        ob_start();
        
        // Display form
        ?>
        <div class="dynamic-form-container" id="dfb-form-<?php echo $form_id; ?>">
            <?php echo $success_message; ?>
            <?php echo $error_messages; ?>
            
            <?php if ($atts['title'] && !empty($form->title)): ?>
                <h3 class="dfb-form-title"><?php echo esc_html($form->title); ?></h3>
            <?php endif; ?>
            
            <?php if ($atts['description'] && !empty($form->description)): ?>
                <div class="dfb-form-description"><?php echo wp_kses_post($form->description); ?></div>
            <?php endif; ?>
            
            <form method="post" class="dfb-form" enctype="multipart/form-data">
                <?php wp_nonce_field('dfb_form_submit', 'dfb_nonce'); ?>
                <input type="hidden" name="dfb_form_id" value="<?php echo $form_id; ?>">
                <input type="hidden" name="dfb_submit" value="1">
                
                <?php foreach ($fields as $field): ?>
                    <div class="dfb-field-container dfb-field-type-<?php echo esc_attr($field->field_type); ?> <?php echo esc_attr($field->field_class); ?>">
                        <?php if ($field->field_type !== 'hidden' && $field->field_type !== 'html'): ?>
                            <label for="dfb-field-<?php echo $field->id; ?>" class="dfb-field-label">
                                <?php echo esc_html($field->label); ?>
                                <?php if ($field->required): ?>
                                    <span class="dfb-required">*</span>
                                <?php endif; ?>
                            </label>
                        <?php endif; ?>
                        
                        <?php echo $this->render_field($field); ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="dfb-submit-container">
                    <button type="submit" class="dfb-submit-button"><?php _e('Submit', 'dynamic-form-builder'); ?></button>
                </div>
            </form>
        </div>
        <?php
        
        // Return output buffer
        return ob_get_clean();
    }
    
    /**
     * Render field HTML
     */
    private function render_field($field) {
        $field_name = 'dfb_field_' . $field->id;
        $field_id = !empty($field->field_id) ? $field->field_id : 'dfb-field-' . $field->id;
        
        // Get field value (if any)
        $field_value = '';
        if (isset($_SESSION['dfb_form_data']) && isset($_SESSION['dfb_form_data'][$field_name])) {
            $field_value = $_SESSION['dfb_form_data'][$field_name];
        }
        
        switch ($field->field_type) {
            case 'text':
            case 'email':
            case 'url':
            case 'tel':
            case 'number':
            case 'date':
            case 'time':
            case 'password':
                return $this->render_text_field($field, $field_name, $field_id, $field_value);
                
            case 'textarea':
                return $this->render_textarea_field($field, $field_name, $field_id, $field_value);
                
            case 'select':
                return $this->render_select_field($field, $field_name, $field_id, $field_value);
                
            case 'radio':
                return $this->render_radio_field($field, $field_name, $field_id, $field_value);
                
            case 'checkbox':
                return $this->render_checkbox_field($field, $field_name, $field_id, $field_value);
                
            case 'file':
                return $this->render_file_field($field, $field_name, $field_id);
                
            case 'hidden':
                return $this->render_hidden_field($field, $field_name, $field_id, $field_value);
                
            case 'html':
                return $this->render_html_field($field);
                
            default:
                return '';
        }
    }
    
    /**
     * Render text field
     */
    private function render_text_field($field, $field_name, $field_id, $field_value) {
        $required = $field->required ? 'required' : '';
        $placeholder = !empty($field->placeholder) ? 'placeholder="' . esc_attr($field->placeholder) . '"' : '';
        
        return '<input type="' . esc_attr($field->field_type) . '" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($field_value) . '" ' . $placeholder . ' ' . $required . '>';
    }
    
    /**
     * Render textarea field
     */
    private function render_textarea_field($field, $field_name, $field_id, $field_value) {
        $required = $field->required ? 'required' : '';
        $placeholder = !empty($field->placeholder) ? 'placeholder="' . esc_attr($field->placeholder) . '"' : '';
        
        return '<textarea id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" ' . $placeholder . ' ' . $required . '>' . esc_textarea($field_value) . '</textarea>';
    }
    
    /**
     * Render select field
     */
    private function render_select_field($field, $field_name, $field_id, $field_value) {
        $required = $field->required ? 'required' : '';
        $options = !empty($field->options) ? explode("\n", $field->options) : array();
        
        $html = '<select id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" ' . $required . '>';
        
        if (!empty($field->placeholder)) {
            $html .= '<option value="" disabled' . (empty($field_value) ? ' selected' : '') . '>' . esc_html($field->placeholder) . '</option>';
        }
        
        foreach ($options as $option) {
            $option = trim($option);
            if (empty($option)) continue;
            
            // Check if option has a value and label (value|label)
            if (strpos($option, '|') !== false) {
                list($option_value, $option_label) = explode('|', $option, 2);
                $option_value = trim($option_value);
                $option_label = trim($option_label);
            } else {
                $option_value = $option_label = $option;
            }
            
            $selected = ($field_value == $option_value) ? 'selected' : '';
            $html .= '<option value="' . esc_attr($option_value) . '" ' . $selected . '>' . esc_html($option_label) . '</option>';
        }
        
        $html .= '</select>';
        
        return $html;
    }
    
    /**
     * Render radio field
     */
    private function render_radio_field($field, $field_name, $field_id, $field_value) {
        $required = $field->required ? 'required' : '';
        $options = !empty($field->options) ? explode("\n", $field->options) : array();
        
        $html = '<div class="dfb-radio-options">';
        
        foreach ($options as $i => $option) {
            $option = trim($option);
            if (empty($option)) continue;
            
            // Check if option has a value and label (value|label)
            if (strpos($option, '|') !== false) {
                list($option_value, $option_label) = explode('|', $option, 2);
                $option_value = trim($option_value);
                $option_label = trim($option_label);
            } else {
                $option_value = $option_label = $option;
            }
            
            $checked = ($field_value == $option_value) ? 'checked' : '';
            $option_id = $field_id . '-' . $i;
            
            $html .= '<div class="dfb-radio-option">';
            $html .= '<input type="radio" id="' . esc_attr($option_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($option_value) . '" ' . $checked . ' ' . $required . '>';
            $html .= '<label for="' . esc_attr($option_id) . '">' . esc_html($option_label) . '</label>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render checkbox field
     */
    private function render_checkbox_field($field, $field_name, $field_id, $field_value) {
        $required = $field->required ? 'required' : '';
        $options = !empty($field->options) ? explode("\n", $field->options) : array();
        
        // If no options, treat as single checkbox
        if (empty($options)) {
            $checked = !empty($field_value) ? 'checked' : '';
            
            $html = '<div class="dfb-checkbox-option">';
            $html .= '<input type="checkbox" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="1" ' . $checked . ' ' . $required . '>';
            $html .= '<label for="' . esc_attr($field_id) . '">' . esc_html($field->placeholder) . '</label>';
            $html .= '</div>';
            
            return $html;
        }
        
        // Multiple checkboxes
        $html = '<div class="dfb-checkbox-options">';
        
        foreach ($options as $i => $option) {
            $option = trim($option);
            if (empty($option)) continue;
            
            // Check if option has a value and label (value|label)
            if (strpos($option, '|') !== false) {
                list($option_value, $option_label) = explode('|', $option, 2);
                $option_value = trim($option_value);
                $option_label = trim($option_label);
            } else {
                $option_value = $option_label = $option;
            }
            
            $option_id = $field_id . '-' . $i;
            $option_name = $field_name . '[]';
            
            // Check if value is in the array of selected values
            $checked = '';
            if (is_array($field_value) && in_array($option_value, $field_value)) {
                $checked = 'checked';
            }
            
            $html .= '<div class="dfb-checkbox-option">';
            $html .= '<input type="checkbox" id="' . esc_attr($option_id) . '" name="' . esc_attr($option_name) . '" value="' . esc_attr($option_value) . '" ' . $checked . '>';
            $html .= '<label for="' . esc_attr($option_id) . '">' . esc_html($option_label) . '</label>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render file field
     */
    private function render_file_field($field, $field_name, $field_id) {
        $required = $field->required ? 'required' : '';
        
        return '<input type="file" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" ' . $required . '>';
    }
    
    /**
     * Render hidden field
     */
    private function render_hidden_field($field, $field_name, $field_id, $field_value) {
        return '<input type="hidden" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($field_value) . '">';
    }
    
    /**
     * Render HTML field
     */
    private function render_html_field($field) {
        return '<div class="dfb-html-content">' . wp_kses_post($field->placeholder) . '</div>';
    }
}

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