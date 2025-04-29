<?php
// Start session and display errors
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Form Builder Test</h1>";

// Define constants
define('DFB_VERSION', '1.0');
define('DFB_STANDALONE', true);
define('DFB_ROOT_DIR', dirname(__FILE__) . '/');
define('DFB_ASSETS_URL', 'assets/');

// Include required files
require_once 'includes/database/class-dfb-db-standalone.php';
require_once 'includes/admin/class-dfb-admin-standalone.php';
require_once 'includes/frontend/class-dfb-form-display.php';

// Try to display content
if (isset($_GET['dfb_admin']) && $_GET['dfb_admin'] == 1) {
    echo "<p>Admin mode requested</p>";
    $admin = new DFB_Admin_Standalone();
    $admin->display();
} else if (isset($_GET['form_id'])) {
    echo "<p>Form display requested</p>";
    $form_display = new DFB_Form_Display();
    $form_display->render_form($_GET['form_id']);
} else {
    echo "<p>Form list requested</p>";
    $form_display = new DFB_Form_Display();
    $form_display->list_forms();
}

echo "<p>End of script</p>";