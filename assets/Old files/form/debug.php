<?php
// Force display of errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start session
session_start();

// Define standalone constants
define('DFB_VERSION', '1.0');
define('DFB_STANDALONE', true);
define('DFB_ROOT_DIR', dirname(__FILE__) . '/');
define('DFB_ASSETS_URL', 'assets/');

echo "<h1>Debug Test</h1>";

// Step 1: Test config
echo "<h2>Step 1: Loading Config</h2>";
try {
    $config = require_once('config.php');
    echo "✓ Config loaded successfully<br>";
} catch (Throwable $e) {
    echo "✗ Config error: " . $e->getMessage() . "<br>";
    exit;
}

// Step 2: Test database class
echo "<h2>Step 2: Loading Database Class</h2>";
try {
    require_once 'includes/database/class-dfb-db-standalone.php';
    echo "✓ Database class loaded<br>";
} catch (Throwable $e) {
    echo "✗ Database class error: " . $e->getMessage() . "<br>";
    exit;
}

// Step 3: Test database connection
echo "<h2>Step 3: Database Connection</h2>";
try {
    $db = new DFB_DB_Standalone();
    echo "✓ Database connection established<br>";
} catch (Throwable $e) {
    echo "✗ Database connection error: " . $e->getMessage() . "<br>";
    exit;
}

// Step 4: Test admin class
echo "<h2>Step 4: Loading Admin Class</h2>";
try {
    require_once 'includes/admin/class-dfb-admin-standalone.php';
    echo "✓ Admin class loaded<br>";
} catch (Throwable $e) {
    echo "✗ Admin class error: " . $e->getMessage() . "<br>";
    exit;
}

// Step 5: Test form display class
echo "<h2>Step 5: Loading Form Display Class</h2>";
try {
    require_once 'includes/frontend/class-dfb-form-display.php';
    echo "✓ Form display class loaded<br>";
} catch (Throwable $e) {
    echo "✗ Form display class error: " . $e->getMessage() . "<br>";
    exit;
}

// Step 6: Test form handler class
echo "<h2>Step 6: Loading Form Handler Class</h2>";
try {
    require_once 'includes/frontend/class-dfb-form-handler-standalone.php';
    echo "✓ Form handler class loaded<br>";
} catch (Throwable $e) {
    echo "✗ Form handler class error: " . $e->getMessage() . "<br>";
    exit;
}

// Step 7: Test webhook class
echo "<h2>Step 7: Loading Webhook Class</h2>";
try {
    require_once 'includes/api/class-dfb-webhook.php';
    echo "✓ Webhook class loaded<br>";
} catch (Throwable $e) {
    echo "✗ Webhook class error: " . $e->getMessage() . "<br>";
    exit;
}

// Step 8: Test creating admin instance
echo "<h2>Step 8: Creating Admin Instance</h2>";
try {
    $admin = new DFB_Admin_Standalone();
    echo "✓ Admin instance created<br>";
} catch (Throwable $e) {
    echo "✗ Admin instance error: " . $e->getMessage() . "<br>";
    exit;
}

// Step 9: Test creating form display instance
echo "<h2>Step 9: Creating Form Display Instance</h2>";
try {
    $form_display = new DFB_Form_Display();
    echo "✓ Form display instance created<br>";
} catch (Throwable $e) {
    echo "✗ Form display instance error: " . $e->getMessage() . "<br>";
    exit;
}

// Step 10: Test templates
echo "<h2>Step 10: Checking Templates</h2>";
try {
    if (file_exists('templates/forms-list-frontend.php')) {
        echo "✓ Forms list template exists<br>";
    } else {
        echo "✗ Forms list template is missing<br>";
    }
    
    if (file_exists('templates/form-display.php')) {
        echo "✓ Form display template exists<br>";
    } else {
        echo "✗ Form display template is missing<br>";
    }
    
    if (file_exists('templates/header.php')) {
        echo "✓ Header template exists<br>";
    } else {
        echo "✗ Header template is missing<br>";
    }
    
    if (file_exists('templates/footer.php')) {
        echo "✓ Footer template exists<br>";
    } else {
        echo "✗ Footer template is missing<br>";
    }
} catch (Throwable $e) {
    echo "✗ Template check error: " . $e->getMessage() . "<br>";
}

echo "<h2>Summary</h2>";
echo "All critical components loaded successfully. If you're still seeing a blank page, the issue might be with the template rendering or CSS/JS files.<br>";
echo "<a href='index.php?dfb_admin=1'>Try accessing admin interface directly</a>";