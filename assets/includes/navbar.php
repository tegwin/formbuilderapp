<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

// Load settings
$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Automatically detect app base path
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/admin');
?>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: <?= htmlspecialchars($settings['banner_color'] ?? '#007bff') ?>;">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= $base_path ?>/admin/dashboard.php">
            <?php if (!empty($settings['logo_path']) && file_exists(__DIR__ . '/../' . $settings['logo_path'])): ?>
                <img src="<?= htmlspecialchars($base_path . '/' . $settings['logo_path']) ?>" alt="Logo" style="height: 50px;">
            <?php else: ?>
                <span class="fs-4"><?= htmlspecialchars($settings['site_name'] ?? 'Form Builder') ?></span>
            <?php endif; ?>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarNavAltMarkup">
            <div class="navbar-nav me-auto">
                <a class="nav-link" href="<?= $base_path ?>/admin/dashboard.php">Dashboard</a>
                <a class="nav-link" href="<?= $base_path ?>/admin/forms.php">Forms</a>
                <a class="nav-link" href="<?= $base_path ?>/admin/submissions.php">Submissions</a>
                <a class="nav-link" href="<?= $base_path ?>/admin/users.php">Users</a>
                <a class="nav-link" href="<?= $base_path ?>/admin/settings.php">Settings</a>
            </div>

            <ul class="navbar-nav align-items-center">
                <?php if (isset($_SESSION['username'])): ?>
                    <li class="nav-item me-3">
                        <span class="text-white small">
                            Welcome, <?= htmlspecialchars($_SESSION['username']) ?>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <span class="badge bg-warning text-dark ms-2">Admin</span>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="btn btn-outline-light btn-sm" href="<?= $base_path ?>/admin/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
