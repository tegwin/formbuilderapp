<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

// Automatically detect app base path (no hardcoding)
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/admin');

// Load settings
$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = $_POST['site_name'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $banner_color = $_POST['banner_color'] ?? '#007bff';
    $email_subject = $_POST['email_subject'] ?? 'Submission from {form}';
    $email_body = $_POST['email_body'] ?? 'New submission from {form}:<br><br>{fields}';

    $logo_path = $settings['logo_path'] ?? null; // current logo

    // Handle logo upload
    if (!empty($_FILES['logo']['name'])) {
        $upload_dir = __DIR__ . '/../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['png', 'jpg', 'jpeg', 'gif', 'svg'];
        if (in_array($ext, $allowed_exts)) {
            $safe_name = uniqid('logo_', true) . '.' . $ext;
            $upload_path = $upload_dir . $safe_name;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                // Delete old logo if exists
                if (!empty($logo_path) && file_exists(__DIR__ . '/../' . $logo_path)) {
                    unlink(__DIR__ . '/../' . $logo_path);
                }
                $logo_path = 'uploads/' . $safe_name;
            }
        }
    }

    // Save all settings
    $stmt = $pdo->prepare("UPDATE settings SET site_name = ?, admin_email = ?, banner_color = ?, logo_path = ?, email_subject = ?, email_body = ?");
    $stmt->execute([$site_name, $admin_email, $banner_color, $logo_path, $email_subject, $email_body]);

    header("Location: settings.php?success=1");
    exit;
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

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ Settings saved successfully!</div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-4" id="settingsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="branding-tab" data-bs-toggle="tab" data-bs-target="#branding" type="button" role="tab" aria-controls="branding" aria-selected="true">Branding</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab" aria-controls="email" aria-selected="false">Email</button>
        </li>
    </ul>

    <form method="post" enctype="multipart/form-data">
        <div class="tab-content" id="settingsTabContent">
            <!-- Branding Tab -->
            <div class="tab-pane fade show active" id="branding" role="tabpanel" aria-labelledby="branding-tab">
                <div class="mb-3">
                    <label for="site_name">Site Name:</label>
                    <input type="text" id="site_name" name="site_name" class="form-control" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="banner_color">Banner Color:</label>
                    <input type="color" id="banner_color" name="banner_color" class="form-control form-control-color" value="<?= htmlspecialchars($settings['banner_color'] ?? '#007bff') ?>" title="Choose your color">
                </div>

                <div class="mb-3">
                    <label for="logo">Logo Upload:</label>
                    <input type="file" name="logo" class="form-control">
                    <?php if (!empty($settings['logo_path'])): ?>
                        <div class="mt-3">
                            <img src="<?= htmlspecialchars($base_path . '/' . $settings['logo_path']) ?>" alt="Logo" style="max-height: 80px;">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Email Tab -->
            <div class="tab-pane fade" id="email" role="tabpanel" aria-labelledby="email-tab">
                <div class="mb-3">
                    <label for="admin_email">Admin Email:</label>
                    <input type="email" id="admin_email" name="admin_email" class="form-control" value="<?= htmlspecialchars($settings['admin_email'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="email_subject">Email Subject:</label>
                    <input type="text" id="email_subject" name="email_subject" class="form-control" value="<?= htmlspecialchars($settings['email_subject'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="email_body">Email Body:</label>
                    <textarea id="email_body" name="email_body" class="form-control" rows="8"><?= htmlspecialchars($settings['email_body'] ?? '') ?></textarea>
                    <small class="form-text text-muted">Available placeholders: {form}, {fields}, {ip}, {date}</small>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-success btn-lg px-5">💾 Save Settings</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
