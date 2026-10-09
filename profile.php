<?php
require_once 'includes/guard.php';
require_once 'includes/layout.php';
require_once 'includes/csrf.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
$remaining = getSubscriptionRemainingDays($_SESSION['user_id']);
$plan = getUserSubscriptionPlan($_SESSION['user_id']);

$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!csrf_check()) {
        $err = 'انتهت صلاحية الجلسة — أعد تحميل الصفحة وحاول مجددا';
    } else {
        $cur = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (strlen($new) < 6) {
            $err = 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل';
        } elseif ($new !== $confirm) {
            $err = 'تأكيد كلمة المرور غير متطابق';
        } else {
            try {
                $db = (new Database())->connect();
                $st = $db->prepare("SELECT password FROM users WHERE id = ?");
                $st->execute([$_SESSION['user_id']]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if (!$row || !password_verify($cur, $row['password'])) {
                    $err = 'كلمة المرور الحالية غير صحيحة';
                } elseif ($auth->changePassword($user['email'], $new)) {
                    $msg = 'تم تغيير كلمة المرور بنجاح';
                } else {
                    $err = 'تعذر تغيير كلمة المرور — حاول لاحقا';
                }
            } catch (PDOException $e) {
                $err = 'تعذر تغيير كلمة المرور — حاول لاحقا';
            }
        }
    }
}

pro_header('حسابي', 'profile.php');
pro_pagehead('حسابي', 'بياناتك وحالة اشتراكك');
?>

<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<div class="grid">
    <div class="card">
        <div class="icon"><i class="fas fa-user"></i></div>
        <h3>البيانات الأساسية</h3>
        <div class="kv"><b>اسم المستخدم</b><span><?php echo htmlspecialchars($user['username'] ?? ''); ?></span></div>
        <div class="kv"><b>الاسم الكامل</b><span><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></span></div>
        <div class="kv"><b>البريد</b><span><?php echo htmlspecialchars($user['email'] ?? ''); ?></span></div>
        <div class="kv"><b>الهاتف</b><span><?php echo htmlspecialchars($user['phone'] ?? '—'); ?></span></div>
    </div>
    <div class="card">
        <div class="icon"><i class="fas fa-crown"></i></div>
        <h3>الاشتراك</h3>
        <div class="kv"><b>الخطة</b><span><?php echo htmlspecialchars(is_array($plan) ? ($plan['name'] ?? '—') : ($plan ?? '—')); ?></span></div>
        <div class="kv"><b>المتبقي</b><span><?php echo ($remaining == -1) ? 'مدى الحياة' : ((int) $remaining . ' يوم'); ?></span></div>
        <div class="go"><a href="subscription.php" class="btn btn-primary">إدارة الاشتراك</a></div>
    </div>
</div>

<div class="form-card">
    <h3 style="margin-bottom:18px"><i class="fas fa-key"></i> تغيير كلمة المرور</h3>
    <form method="post">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label for="cur">كلمة المرور الحالية</label>
            <input id="cur" type="password" name="current_password" class="form-control" required autocomplete="current-password">
        </div>
        <div class="form-group">
            <label for="new">كلمة المرور الجديدة</label>
            <input id="new" type="password" name="new_password" class="form-control" minlength="6" required autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="cfm">تأكيد الجديدة</label>
            <input id="cfm" type="password" name="confirm_password" class="form-control" minlength="6" required autocomplete="new-password">
        </div>
        <button type="submit" name="change_password" class="btn btn-primary">حفظ كلمة المرور</button>
    </form>
</div>

<?php pro_footer(); ?>
