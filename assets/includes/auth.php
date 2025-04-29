<?php
session_start();

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /formbuilder/admin/login.php');
        exit;
    }
}

function require_admin() {
    if (!is_logged_in() || empty($_SESSION['is_admin'])) {
        header('Location: /formbuilder/admin/login.php');
        exit;
    }
}
?>
