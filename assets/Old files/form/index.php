<?php
/**
 * Dynamic Form Builder
 * Description: Create and publish custom forms with various field types. Form submissions are sent via email and webhook.
 * Version: 1.0
 * Author: Form Builder
 */

// Start session
session_start();

// Error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Define constants for standalone mode
define('DFB_VERSION', '1.0');
define('DFB_STANDALONE', true);
define('DFB_ROOT_DIR', dirname(__FILE__) . '/');
define('DFB_ASSETS_URL', 'assets/');

// Include required files
require_once DFB_ROOT_DIR . 'includes/database/class-dfb-db-standalone.php';
require_once DFB_ROOT_DIR . 'includes/admin/class-dfb-admin-standalone.php';
require_once DFB_ROOT_DIR . 'includes/frontend/class-dfb-form-display.php';
require_once DFB_ROOT_DIR . 'includes/frontend/class-dfb-form-handler-standalone.php';
require_once DFB_ROOT_DIR . 'includes/api/class-dfb-webhook.php';

// Initialize database
$db = new DFB_DB_Standalone();

// Check if tables exist
if (!$db->tables_exist()) {
    $db->create_tables();
}

// Handle form submission if present
if (isset($_POST['dfb_form_id'])) {
    $form_handler = new DFB_Form_Handler_Standalone();
    $form_handler->process_form();
}

// Display admin or form based on URL parameters
if (isset($_GET['dfb_admin']) && $_GET['dfb_admin'] == 1) {
    $admin = new DFB_Admin_Standalone();
    $admin->display();
} else if (isset($_GET['form_id'])) {
    $form_display = new DFB_Form_Display();
    $form_display->render_form($_GET['form_id']);
} else {
    // Display form list
    $form_display = new DFB_Form_Display();
    $form_display->list_forms();
}