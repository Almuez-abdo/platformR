<?php
// session_start();
require_once '..\config/database.php';
require_once '..\includes/auth.php';

$auth = new Auth();

// التحقق من صلاحيات المشرف
if(!$auth->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// إحصائيات النظام
$db = (new Database())->connect();

// احصائيات المستخدمين
$stmt = $db->query("SELECT COUNT(*) as total_users FROM users");
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

// احصائيات الاشتراكات
$stmt = $db->query("SELECT 
    COUNT(*) as total_subs,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_subs,
    SUM(CASE WHEN plan_type = 'monthly' THEN 1 ELSE 0 END) as monthly_subs,
    SUM(CASE WHEN plan_type = 'yearly' THEN 1 ELSE 0 END) as yearly_subs
    FROM subscriptions");
$subscription_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// احصائيات المحاضرات
$stmt = $db->query("SELECT COUNT(*) as total_lectures FROM lectures");
$total_lectures = $stmt->fetch(PDO::FETCH_ASSOC)['total_lectures'];

// إيرادات الشهر الحالي
$stmt = $db->query("SELECT SUM(price) as monthly_revenue FROM subscriptions 
                   WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                   AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$monthly_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['monthly_revenue'] ?? 0;

// المستخدمين الجدد هذا الشهر
$stmt = $db->query("SELECT COUNT(*) as new_users FROM users 
                   WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                   AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$new_users = $stmt->fetch(PDO::FETCH_ASSOC)['new_users'];

// آخر 10 مستخدمين مسجلين
$stmt = $db->query("SELECT id, username, email, created_at FROM users 
                   ORDER BY created_at DESC LIMIT 10");
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - منصة التعليم</title>
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* الشريط الجانبي */
        .sidebar {
            width: 250px;
            background: linear-gradient(to bottom, var(--secondary-color), var(--primary-color));
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s;
            border-right: 4px solid transparent;
        }
        
        .menu-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-right-color: var(--warning-color);
        }
        
        .menu-item.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-right-color: var(--warning-color);
        }
        
        .menu-item i {
            margin-left: 10px;
            font-size: 18px;
        }
        
        .menu-text {
            font-size: 15px;
            font-weight: 500;
        }
        
        /* المحتوى الرئيسي */
        .main-content {
            flex: 1;
            margin-right: 250px;
            padding: 20px;
        }
        
        .top-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .page-title h1 {
            font-size: 24px;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }
        
        .page-title p {
            color: #666;
            font-size: 14px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info span {
            margin-left: 15px;
            font-weight: 500;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }
        
        /* بطاقات الإحصائيات */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .icon-users { background: var(--success-color); }
        .icon-subs { background: var(--primary-color); }
        .icon-lectures { background: var(--warning-color); }
        .icon-revenue { background: var(--danger-color); }
        
        .stat-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: var(--secondary-color);
            margin-bottom: 10px;
        }
        
        .stat-change {
            font-size: 12px;
            color: #666;
        }
        
        .change-up { color: var(--success-color); }
        .change-down { color: var(--danger-color); }
        
        /* الجداول */
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .table-title {
            font-size: 18px;
            color: var(--secondary-color);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-title a {
            font-size: 14px;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: right;
            font-weight: 600;
            color: var(--secondary-color);
            border-bottom: 1px solid #eee;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            color: #555;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .user-avatar-small {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-expired { background: #f8d7da; color: #721c24; }
        
        .btn-action {
            padding: 5px 10px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            margin-left: 5px;
        }
        
        .btn-view { background: #d1ecf1; color: #0c5460; }
        .btn-edit { background: #d4edda; color: #155724; }
        .btn-delete { background: #f8d7da; color: #721c24; }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }
            
            .main-content {
                margin-right: 70px;
            }
            
            .menu-text {
                display: none;
            }
            
            .menu-item {
                justify-content: center;
            }
            
            .menu-item i {
                margin-left: 0;
                font-size: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-info {
                margin-top: 15px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- الشريط الجانبي -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>لوحة التحكم</h2>
                <p>منصة التعليم الإلكتروني</p>
            </div>
            
            <div class="sidebar-menu">
                <a href="dashboard.php" class="menu-item active">
                    <i class="fas fa-home"></i>
                    <span class="menu-text">الرئيسية</span>
                </a>
                
                <a href="users.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span class="menu-text">المستخدمين</span>
                </a>
                
                <a href="lectures.php" class="menu-item">
                    <i class="fas fa-video"></i>
                    <span class="menu-text">المحاضرات</span>
                </a>
                
                <a href="subscriptions.php" class="menu-item">
                    <i class="fas fa-credit-card"></i>
                    <span class="menu-text">الاشتراكات</span>
                </a>
                
                <a href="categories.php" class="menu-item">
                    <i class="fas fa-folder"></i>
                    <span class="menu-text">التصنيفات</span>
                </a>
                
                <a href="reports.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span class="menu-text">التقارير</span>
                </a>
                
                <a href="settings.php" class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span class="menu-text">الإعدادات</span>
                </a>
                
                <a href="../logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="menu-text">تسجيل الخروج</span>
                </a>
            </div>
        </div>
        
        <!-- المحتوى الرئيسي -->
        <div class="main-content">
            <!-- شريط العلوي -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>لوحة التحكم</h1>
                    <p>مرحباً <?php echo $_SESSION['username']; ?>, أهلاً بك في لوحة التحكم</p>
                </div>
                
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                    <span><?php echo $_SESSION['username']; ?></span>
                </div>
            </div>
            
            <!-- بطاقات الإحصائيات -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">إجمالي المستخدمين</div>
                            <div class="stat-value"><?php echo number_format($total_users); ?></div>
                        </div>
                        <div class="stat-icon icon-users">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-change">
                        <span class="change-up">+<?php echo $new_users; ?> هذا الشهر</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">الاشتراكات النشطة</div>
                            <div class="stat-value"><?php echo number_format($subscription_stats['active_subs']); ?></div>
                        </div>
                        <div class="stat-icon icon-subs">
                            <i class="fas fa-credit-card"></i>
                        </div>
                    </div>
                    <div class="stat-change">
                        <span><?php echo $subscription_stats['monthly_subs']; ?> شهري</span> | 
                        <span><?php echo $subscription_stats['yearly_subs']; ?> سنوي</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">إجمالي المحاضرات</div>
                            <div class="stat-value"><?php echo number_format($total_lectures); ?></div>
                        </div>
                        <div class="stat-icon icon-lectures">
                            <i class="fas fa-video"></i>
                        </div>
                    </div>
                    <div class="stat-change">
                        <span>جميع التصنيفات</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">إيرادات الشهر</div>
                            <div class="stat-value">$<?php echo number_format($monthly_revenue, 2); ?></div>
                        </div>
                        <div class="stat-icon icon-revenue">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-change">
                        <span class="change-up">+15% عن الشهر الماضي</span>
                    </div>
                </div>
            </div>
            
            <!-- جدول آخر المستخدمين -->
            <div class="table-container">
                <div class="table-title">
                    <h3>آخر المستخدمين المسجلين</h3>
                    <a href="users.php">عرض الكل</a>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>المستخدم</th>
                            <th>البريد الإلكتروني</th>
                            <th>تاريخ التسجيل</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_users as $user): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center;">
                                    <div class="user-avatar-small">
                                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                    </div>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php 
                                // التحقق من اشتراك المستخدم
                                $stmt = $db->prepare("SELECT status FROM subscriptions 
                                                   WHERE user_id = ? AND status = 'active' 
                                                   AND (expiry_date IS NULL OR expiry_date > NOW())");
                                $stmt->execute([$user['id']]);
                                $has_active_sub = $stmt->rowCount() > 0;
                                ?>
                                <span class="status-badge <?php echo $has_active_sub ? 'status-active' : 'status-expired'; ?>">
                                    <?php echo $has_active_sub ? 'نشط' : 'غير مشترك'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-action btn-view">عرض</button>
                                <button class="btn-action btn-edit">تعديل</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- إحصائيات إضافية -->
            <div class="table-container">
                <div class="table-title">
                    <h3>إحصائيات الاشتراكات</h3>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                        <div style="font-size: 32px; font-weight: bold; color: var(--primary-color);">
                            <?php echo number_format($subscription_stats['total_subs']); ?>
                        </div>
                        <div style="color: #666; margin-top: 10px;">إجمالي الاشتراكات</div>
                    </div>
                    
                    <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                        <div style="font-size: 32px; font-weight: bold; color: var(--success-color);">
                            <?php echo number_format($subscription_stats['active_subs']); ?>
                        </div>
                        <div style="color: #666; margin-top: 10px;">اشتراكات نشطة</div>
                    </div>
                    
                    <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                        <div style="font-size: 32px; font-weight: bold; color: var(--warning-color);">
                            <?php echo number_format($subscription_stats['monthly_subs']); ?>
                        </div>
                        <div style="color: #666; margin-top: 10px;">اشتراكات شهرية</div>
                    </div>
                    
                    <div style="text-align: center; padding: 20px; background: #f8f9fa;