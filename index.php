<?php
require_once 'includes/guard.php';
require_once 'includes/layout.php';

$user_id = $_SESSION['user_id'];
$remaining_days = getSubscriptionRemainingDays($user_id);

$stats = ['lectures' => 0, 'students' => 0];
try {
    $db = (new Database())->connect();
    $stats['lectures'] = (int) $db->query("SELECT COUNT(*) FROM lectures")->fetchColumn();
    $stats['students'] = (int) $db->query("SELECT COUNT(*) FROM users WHERE role='student' AND is_active=1")->fetchColumn();
} catch (PDOException $e) {
    // تبقى القيم الصفرية عند تعذر الاتصال
}

pro_header('الصفحة الرئيسية', 'index.php');
?>

<?php if (isset($_SESSION['subscription_activated'])): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    تم تفعيل اشتراكك بنجاح! مرحبا بك في منصة التعليم.
</div>
<?php unset($_SESSION['subscription_activated']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['welcome_message'])): ?>
<div class="alert alert-info">
    <i class="fas fa-smile-wink"></i>
    <?php echo htmlspecialchars($_SESSION['welcome_message'] ?? ''); unset($_SESSION['welcome_message']); ?>
</div>
<?php endif; ?>

<div class="hero">
    <span class="badge">
        <i class="fas fa-crown"></i>
        <?php if (($remaining_days ?? 0) == -1): ?>
            اشتراك مدى الحياة
        <?php elseif (($remaining_days ?? 0) > 0): ?>
            متبق <?php echo (int) $remaining_days; ?> يوم في اشتراكك
        <?php else: ?>
            اشتراك فعال
        <?php endif; ?>
    </span>
    <h1>مرحبا <?php echo htmlspecialchars($_SESSION['username'] ?? 'زائر'); ?></h1>
    <p>اشتراكك فعال ويمكنك الوصول إلى جميع المحاضرات والدورات التعليمية</p>
    <a href="lectures.php" class="btn">ابدأ التعلم الآن <i class="fas fa-arrow-left"></i></a>
</div>

<div class="grid">
    <div class="card center">
        <div class="icon" style="margin-inline:auto"><i class="fas fa-video"></i></div>
        <h3>إجمالي المحاضرات</h3>
        <div class="stat-num"><?php echo $stats['lectures']; ?>+</div>
    </div>
    <div class="card center">
        <div class="icon" style="margin-inline:auto"><i class="fas fa-user-graduate"></i></div>
        <h3>المشتركين</h3>
        <div class="stat-num"><?php echo $stats['students']; ?>+</div>
    </div>
    <div class="card center">
        <div class="icon" style="margin-inline:auto"><i class="fas fa-certificate"></i></div>
        <h3>تعلّم معتمد</h3>
        <p>محتوى منظم ومتابعة مستمرة لتقدمك</p>
    </div>
    <div class="card center">
        <div class="icon" style="margin-inline:auto"><i class="fas fa-mobile-screen"></i></div>
        <h3>في أي وقت وأي مكان</h3>
        <p>شاهد على الكمبيوتر أو الجوال متى شئت</p>
    </div>
</div>

<h2 class="section-title">ماذا تنتظر؟ ابدأ رحلة التعلم الآن</h2>
<p class="section-sub">اختر محاضرتك الأولى وواصل التقدم يوما بعد يوم</p>
<div class="grid">
    <div class="card center">
        <div class="icon" style="margin-inline:auto"><i class="fas fa-bolt"></i></div>
        <h3>تعلم بسرعة</h3>
        <p>دروس قصيرة ومنظمة تساعدك على التعلم بشكل أسرع</p>
        <div class="go"><a href="lectures.php" class="btn btn-primary">تصفح المحاضرات</a></div>
    </div>
    <div class="card center">
        <div class="icon" style="margin-inline:auto"><i class="fas fa-bookmark"></i></div>
        <h3>تابع تقدمك</h3>
        <p>آخر ما شاهدته محفوظ في صفحة دوراتي</p>
        <div class="go"><a href="my-courses.php" class="btn btn-primary">دوراتي</a></div>
    </div>
    <div class="card center">
        <div class="icon" style="margin-inline:auto"><i class="fas fa-user-gear"></i></div>
        <h3>حسابك واشتراكك</h3>
        <p>بياناتك وحالة الاشتراك وتغيير كلمة المرور</p>
        <div class="go"><a href="profile.php" class="btn btn-primary">حسابي</a></div>
    </div>
</div>

<?php pro_footer(); ?>
