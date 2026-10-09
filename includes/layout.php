<?php
// includes/layout.php - القالب الموحد للصفحات الداخلية
function pro_header($title, $active = '') {
    $nav = [
        'index.php'      => ['الرئيسية', 'fas fa-home'],
        'lectures.php'   => ['المحاضرات', 'fas fa-video'],
        'my-courses.php' => ['دوراتي', 'fas fa-book'],
        'profile.php'    => ['حسابي', 'fas fa-user'],
    ];
    $name = htmlspecialchars($_SESSION['username'] ?? 'حسابي', ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<meta name="description" content="منصة التعليم — محاضرات ودورات تعليمية باشتراك">';
    echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . ' - منصة التعليم</title>';
    echo '<link rel="stylesheet" href="styles.css">';
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">';
    echo '</head><body>';
    echo '<nav class="navbar"><div class="container">';
    echo '<a href="index.php" class="logo"><i class="fas fa-graduation-cap"></i> منصة التعليم</a>';
    echo '<button class="nav-toggle" aria-label="القائمة"><i class="fas fa-bars"></i></button>';
    echo '<div class="nav-links" id="navLinks">';
    foreach ($nav as $file => $item) {
        $cls = ($active === $file) ? ' class="active"' : '';
        echo '<a href="' . $file . '"' . $cls . '><i class="' . $item[1] . '"></i> ' . $item[0] . '</a>';
    }
    echo '<span class="nav-user"><i class="fas fa-circle-user"></i> ' . $name . '</span>';
    echo '<a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> خروج</a>';
    echo '</div></div></nav>';
    echo '<div class="container">';
}
function pro_footer() {
    echo '</div>';
    echo '<div class="footer"><div class="container">';
    echo '<p>Create by Ezzo Service @2023</p>';
    echo '</div></div>';
    echo '<script src="script.js"></script>';
    echo '</body></html>';
}
function pro_pagehead($title, $sub = '') {
    echo '<div class="page-head"><h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
    if ($sub !== '') echo '<p>' . htmlspecialchars($sub, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</div>';
}
?>
