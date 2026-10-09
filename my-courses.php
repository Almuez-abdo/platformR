<?php
require_once 'includes/guard.php';
require_once 'includes/layout.php';

$recent = $_SESSION['recent'] ?? [];
$items = [];
if ($recent) {
    try {
        $db = (new Database())->connect();
        $ph = implode(',', array_fill(0, count($recent), '?'));
        $st = $db->prepare("SELECT id, title, description, created_at FROM lectures WHERE id IN ($ph)");
        $st->execute($recent);
        $byId = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) $byId[$row['id']] = $row;
        foreach ($recent as $rid) {
            if (isset($byId[$rid])) $items[] = $byId[$rid];
        }
    } catch (PDOException $e) {
        $items = [];
    }
}

pro_header('دوراتي', 'my-courses.php');
pro_pagehead('دوراتي', 'استكمل من حيث توقفت — آخر ما شاهدته');
?>

<?php if (!$items): ?>
<div class="empty">
    <i class="fas fa-bookmark"></i>
    <h3>لم تشاهد أي محاضرة بعد</h3>
    <p>ابدأ من صفحة المحاضرات وستظهر هنا آخر مشاهداتك</p>
    <a href="lectures.php" class="btn btn-primary">تصفح المحاضرات</a>
</div>
<?php else: ?>
<div class="grid">
    <?php foreach ($items as $l): ?>
    <div class="card">
        <div class="icon"><i class="fas fa-history"></i></div>
        <h3><?php echo htmlspecialchars($l['title']); ?></h3>
        <div class="meta"><i class="far fa-calendar"></i> <?php echo htmlspecialchars($l['created_at']); ?></div>
        <div class="go"><a href="lecture.php?id=<?php echo (int) $l['id']; ?>" class="btn btn-primary">استكمال المشاهدة</a></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php pro_footer(); ?>
