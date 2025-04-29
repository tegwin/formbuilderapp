<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session and require auth

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

// Load current settings
$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Debug: Output settings to verify data
//echo '<pre>'; var_dump($settings); echo '</pre>'; // Uncomment for debugging

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    // Handle logo upload
    $logo_path = $settings['logo_path']; // Default to current logo path in the database
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        // Handle logo file upload
        $uploads_dir = __DIR__ . '/../uploads/';
        if (!is_dir($uploads_dir)) {
            mkdir($uploads_dir, 0755, true);
        }
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed_extensions)) {
            $logo_name = uniqid('logo_') . '.' . $ext;
            move_uploaded_file($_FILES['logo']['tmp_name'], $uploads_dir . $logo_name);
            $logo_path = 'uploads/' . $logo_name; // Save relative path to logo
        } else {
            echo '<div class="alert alert-danger">Invalid logo file type. Please upload JPG, JPEG, PNG, or GIF.</div>';
        }
    }

    // Update settings in the database
    try {
        $stmt = $pdo->prepare("UPDATE settings SET 
            site_name = ?, 
            admin_email = ?, 
            banner_color = ?, 
            email_subject = ?, 
            email_body = ?, 
            logo_path = ?, 
            smtp_host = ?, 
            smtp_port = ?, 
            smtp_username = ?, 
            smtp_password = ?, 
            smtp_secure = ?, 
            email_to = ?, 
            from_email = ? 
            WHERE id = 1");

        $stmt->execute([
            $_POST['site_name'],
            $_POST['admin_email'],
            $_POST['banner_color'],
            $_POST['email_subject'],
            $_POST['email_body'],
            $logo_path,
            $_POST['smtp_host'],
            $_POST['smtp_port'],
            $_POST['smtp_username'],
            $_POST['smtp_password'],
            $_POST['smtp_secure'],
            $_POST['email_to'],  // Save the "email_to" field
            $_POST['from_email'],  // Save the "from_email" field
        ]);
        echo '<div class="alert alert-success">Settings updated successfully!</div>';
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Error updating settings: ' . $e->getMessage() . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - Form Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Settings</h1>

    <form method="POST" enctype="multipart/form-data">
        <ul class="nav nav-tabs mb-3" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="site-tab" data-bs-toggle="tab" data-bs-target="#site" type="button">Site Settings</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button">Email Settings</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="smtp-tab" data-bs-toggle="tab" data-bs-target="#smtp" type="button">SMTP Settings</button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Site Settings Tab -->
            <div class="tab-pane fade show active" id="site">
                <div class="mb-3">
                    <label class="form-label">Site Name</label>
                    <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Admin Email</label>
                    <input type="email" name="admin_email" class="form-control" value="<?= htmlspecialchars($settings['admin_email'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Banner Color</label>
                    <input type="color" name="banner_color" class="form-control form-control-color" value="<?= htmlspecialchars($settings['banner_color'] ?? '#007bff') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Logo</label>
                    <?php if ($settings['logo_path']): ?>
                        <img src="../<?= htmlspecialchars($settings['logo_path']) ?>" alt="Logo" style="max-width: 100px; max-height: 50px;">
                    <?php endif; ?>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                </div>
            </div>

            <!-- Email Settings Tab -->
            <div class="tab-pane fade" id="email">
                <div class="mb-3">
                    <label class="form-label">Email Subject</label>
                    <input type="text" name="email_subject" class="form-control" value="<?= htmlspecialchars($settings['email_subject'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Body Template</label>
                    <textarea name="email_body" class="form-control" rows="6" required><?= htmlspecialchars($settings['email_body'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Send To Email</label>
                    <input type="email" name="email_to" class="form-control" value="<?= htmlspecialchars($settings['email_to'] ?? '') ?>" required>
                </div>
            </div>

            <!-- SMTP Settings Tab -->
            <div class="tab-pane fade" id="smtp">
                <div class="mb-3">
                    <label class="form-label">SMTP Host</label>
                    <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">SMTP Port</label>
                    <input type="number" name="smtp_port" class="form-control" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">SMTP Username</label>
                    <input type="text" name="smtp_username" class="form-control" value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">SMTP Password</label>
                    <input type="password" name="smtp_password" class="form-control" value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
    <label class="form-label">SMTP Secure</label>
    <input type="text" name="smtp_secure" class="form-control" value="<?= htmlspecialchars($settings['smtp_secure'] ?? '') ?>">
</div>
                <div class="mb-3">
                    <label class="form-label">From Email</label>
                    <input type="email" name="from_email" class="form-control" value="<?= htmlspecialchars($settings['from_email'] ?? '') ?>" required>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" name="save_settings" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
