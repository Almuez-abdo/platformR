<?php
require_once 'includes/guard.php';
require_once 'includes/layout.php';

$q = trim($_GET['q'] ?? '');
$lectures = [];
try {
    $db = (new Database())->connect();
    if ($q !== '') {
        $st = $db->prepare("SELECT id, title, description, created_at FROM lectures WHERE title LIKE ? OR description LIKE ? ORDER BY created_at DESC LIMIT 60");
        $st->execute(['%' . $q . '%', '%' . $q . '%']);
    } else {
        $st = $db->query("SELECT id, title, description, created_at FROM lectures ORDER BY created_at DESC LIMIT 60");
    }
    $lectures = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lectures = [];
}

pro_header('المحاضرات', 'lectures.php');
pro_pagehead('المحاضرات', 'تصفح جميع المحاضرات المتاحة في اشتراكك');
?>
<form class="searchbar" method="get" action="lectures.php">
    <input type="search" name="q" placeholder="ابحث بعنوان المحاضرة..." value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>">
    <button type="submit"><i class="fas fa-search"></i> بحث</button>
</form>

<?php if (!$lectures): ?>
<div class="empty">
    <i class="fas fa-video-slash"></i>
    <h3>لا توجد محاضرات مطابقة</h3>
    <p>جرب كلمة بحث أخرى أو تصفح الكل</p>
    <a href="lectures.php" class="btn btn-primary">عرض الكل</a>
</div>
<?php else: ?>
<div class="grid">
    <?php foreach ($lectures as $l): ?>
    <div class="card">
        <div class="icon"><i class="fas fa-play"></i></div>
        <h3><?php echo htmlspecialchars($l['title']); ?></h3>
        <p><?php echo htmlspecialchars(mb_substr($l['description'] ?? '', 0, 110, 'UTF-8')); ?>...</p>
        <div class="meta"><i class="far fa-calendar"></i> <?php echo htmlspecialchars($l['created_at']); ?></div>
        <div class="go"><a href="lecture.php?id=<?php echo (int) $l['id']; ?>" class="btn btn-primary">مشاهدة المحاضرة</a></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php pro_footer(); ?>
