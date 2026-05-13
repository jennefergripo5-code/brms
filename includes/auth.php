<?php
// includes/auth.php - Session + role check (Business Logic Layer)
if (session_status() === PHP_SESSION_NONE) session_start();

function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: /brms/login.php');
        exit;
    }
}
function require_admin() {
    require_login();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: /brms/user/dashboard.php');
        exit;
    }
}
function current_user() {
    return [
        'id'   => $_SESSION['user_id']   ?? null,
        'name' => $_SESSION['full_name'] ?? '',
        'role' => $_SESSION['role']      ?? '',
    ];
}
