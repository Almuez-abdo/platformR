<?php
require_once 'includes/guard.php';
require_once 'includes/layout.php';

$id = (int) ($_GET['id'] ?? 0);
$lecture = null;
$related = [];
try {
    $db = (new Database())->connect();
    $st = $db->prepare("SELECT id, title, description, created_at FROM lectures WHERE id = ?");
    $st->execute([$id]);
    $lecture = $st->fetch(PDO::FETCH_ASSOC);
    if ($lecture) {
        $st = $db->prepare("SELECT id, title FROM lectures WHERE id != ? ORDER BY created_at DESC LIMIT 4");
        $st->execute([$id]);
        $related = $st->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $lecture = null;
}

if (!$lecture) {
    pro_header('المحاضرة', 'lectures.php');
    echo '<div class="empty"><i class="fas fa-triangle-exclamation"></i><h3>المحاضرة غير موجودة</h3><a href="lectures.php" class="btn btn-primary">عودة للمحاضرات</a></div>';
    pro_footer();
    exit();
}

// سجل المشاهدات الأخيرة
if (!isset($_SESSION['recent'])) $_SESSION['recent'] = [];
$_SESSION['recent'] = array_values(array_unique(array_merge([$id], $_SESSION['recent'])));
$_SESSION['recent'] = array_slice($_SESSION['recent'], 0, 6);

pro_header($lecture['title'], 'lectures.php');
?>
<div class="detail">
    <h1><?php echo htmlspecialchars($lecture['title']); ?></h1>
    <div class="meta"><i class="far fa-calendar"></i> <?php echo htmlspecialchars($lecture['created_at']); ?></div>
    <div class="body"><?php echo nl2br(htmlspecialchars($lecture['description'] ?? 'لا يوجد وصف لهذه المحاضرة بعد.')); ?></div>
    <div class="alert alert-info" style="margin-top:20px">
        <i class="fas fa-circle-info"></i>
        ملفات الفيديو تُدار من لوحة الإدارة — تواصل مع إدارة المنصة عند تعذر التشغيل.
    </div>
    <a href="lectures.php" class="btn btn-primary">عودة للمحاضرات</a>
</div>

<?php if ($related): ?>
<h2 class="section-title">محاضرات ذات صلة</h2>
<div class="grid">
    <?php foreach ($related as $r): ?>
    <div class="card">
        <div class="icon"><i class="fas fa-play"></i></div>
        <h3><?php echo htmlspecialchars($r['title']); ?></h3>
        <div class="go"><a href="lecture.php?id=<?php echo (int) $r['id']; ?>" class="btn btn-primary">مشاهدة</a></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php pro_footer(); ?>
