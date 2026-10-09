<?php
session_start();

// التحقق من تسجيل الدخول
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
require_once 'classes/Subscription.php';

$database = new Database();
$db = $database->connect();
$subscription = new Subscription($db);

// معالجة طلب الاشتراك
$message = '';
$message_type = '';

// ✅ هنا التعديل المهم: معالجة الاشتراك
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['plan_id'])) {
    $plan_id = $_POST['plan_id'];
    $user_id = $_SESSION['user_id'];
    
    // محاولة إنشاء اشتراك للمستخدم
    $result = $subscription->createSubscription($user_id, $plan_id);
    
    if($result) {
        $message = "✅ تم تفعيل اشتراكك بنجاح! جاري تحويلك إلى لوحة التحكم...";
        $message_type = "success";
        
        // تحديث حالة الاشتراك في الجلسة
        $_SESSION['subscription_active'] = true;
        $_SESSION['subscription_plan'] = $plan_id;
        
        // تحويل المستخدم للصفحة الرئيسية بعد ثانيتين
        header("refresh:2;url=index.php");
    } else {
        $message = "❌ حدث خطأ أثناء تفعيل الاشتراك. الرجاء المحاولة مرة أخرى";
        $message_type = "error";
    }
}

// الحصول على الخطط المتاحة
$plans = $subscription->getAvailablePlans();

// إذا مافي خطط، نضيف خطط افتراضية
if(empty($plans)) {
    $plans = [
        ['id' => 1, 'name' => 'التشريح وعلم الأجنة', 'price' => 99, 'duration' => null, 'description' => 'دراسة تركيب جسم الإنسان'],
        ['id' => 2, 'name' => 'علم وظائف الأعضاء', 'price' => 499, 'duration' => null, 'description' => 'كيف تعمل أجهزة الجسم'],
        ['id' => 3, 'name' => 'الكيمياء الحيوية', 'price' => 999, 'duration' => null, 'description' => 'التفاعلات الكيميائية داخل الجسم']
    ];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختر الكورس - منصة التعليم</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Tajawal', sans-serif;
        }
        
        body {
            background: linear-gradient(145deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1 {
            text-align: center;
            color: white;
            font-size: 2.5em;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
            font-weight: 800;
        }
        
        .subtitle {
            text-align: center;
            color: rgba(255,255,255,0.9);
            margin-bottom: 50px;
            font-size: 1.2em;
        }
        
        .alert {
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            font-size: 1.1em;
            font-weight: 500;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: rgba(72, 187, 120, 0.95);
            color: white;
            box-shadow: 0 10px 30px rgba(72, 187, 120, 0.3);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .alert-error {
            background: rgba(245, 101, 101, 0.95);
            color: white;
            box-shadow: 0 10px 30px rgba(245, 101, 101, 0.3);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .subscription-plans {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }
        
        .plan-card {
            background: white;
            border-radius: 30px;
            padding: 35px 25px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-align: center;
            position: relative;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .plan-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 30px 60px rgba(0,0,0,0.15);
        }
        
        .plan-card h3 {
            font-size: 1.8em;
            color: #2d3748;
            margin-bottom: 15px;
            font-weight: 800;
        }
        
        .price {
            font-size: 2.5em;
            color: #4a6fa5;
            font-weight: 900;
            margin-bottom: 20px;
        }
        
        .price small {
            font-size: 0.4em;
            font-weight: normal;
            color: #718096;
        }
        
        .duration {
            background: #f7fafc;
            color: #4a5568;
            padding: 10px 20px;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .description {
            color: #718096;
            margin-bottom: 30px;
            line-height: 1.6;
            font-size: 1.1em;
        }
        
        .btn-subscribe {
            width: 100%;
            padding: 16px 30px;
            background: linear-gradient(to right, #4a6fa5, #2c3e50);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1.2em;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-subscribe:hover {
            background: linear-gradient(to right, #2c3e50, #1a2632);
            transform: scale(0.98);
            box-shadow: 0 10px 25px rgba(44, 62, 80, 0.3);
        }
        
        .featured {
            position: relative;
            overflow: hidden;
        }
        
        .featured::before {
            content: "⭐ الأكثر مبيعاً";
            position: absolute;
            top: 20px;
            right: -35px;
            background: #f6ad55;
            color: white;
            padding: 10px 40px;
            transform: rotate(45deg);
            font-size: 0.9em;
            font-weight: bold;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 2em;
            }
            
            .subscription-plans {
                grid-template-columns: 1fr;
            }
            
            .plan-card {
                margin-bottom: 20px;
            }
        }
        
        .user-info {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 15px 25px;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 30px;
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="user-info">
            👋 مرحباً، <?php echo htmlspecialchars($_SESSION['username'] ?? 'مستخدم'); ?>
        </div>
        
        <h1>اختر الكورس</h1>
        <div class="subtitle">
            اشترك الآن وتمتع بالوصول إلى جميع المحاضرات والاختبارات
        </div>
        
        <?php if($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="subscription-plans">
            <?php foreach($plans as $index => $plan): ?>
            <div class="plan-card <?php echo ($index == 1) ? 'featured' : ''; ?>">
                <h3><?php echo htmlspecialchars($plan['name']); ?></h3>
                <div class="price">
                    <?php echo number_format($plan['price']); ?> <small>ر.س</small>
                </div>
                <!-- <div class="duration"> -->
                    <?php
                    // if($plan['duration']) {
                    //     echo "📅 " . $plan['duration'] . " يوم";
                    // } else {
                    //     echo "♾️ مدى الحياة";
                    // }
                    ?>
                <!-- </div> -->
                <div class="description">
                    <?php echo htmlspecialchars($plan['description']); ?>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                    <button type="submit" class="btn-subscribe">
                        اشترك الآن 🚀
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 40px; color: rgba(255,255,255,0.8);">
            <a href="logout.php" style="color: white; text-decoration: none; border-bottom: 1px dashed white; padding-bottom: 3px;">
                تسجيل الخروج
            </a>
        </div>
    </div>
</body>
</html>