<?php
// includes/guard.php - بوابة الصفحات المحمية (دخول + اشتراك ساري)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/subscript.php';

$__auth_guard = new Auth();
if (!$__auth_guard->isLoggedIn()) {
    header('Location: login.php');
    exit();
}
if (!isSubscriptionValid($_SESSION['user_id'])) {
    header('Location: subscription.php');
    exit();
}
?>
