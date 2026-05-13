<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php");
    exit;
} else {
    header("Location: pages/login.php");
    exit;
}