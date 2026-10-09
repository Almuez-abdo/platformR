<?php
// includes/csrf.php - حماية CSRF بسيطة
function csrf_token() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}
function csrf_check() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token']);
}
?>
