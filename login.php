<?php
session_start(); 
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/subscript.php'; // 👈 أضفنا ملف الاشتراكات
require_once 'includes/csrf.php';

$auth = new Auth();
$error = '';
$success = '';

// إذا كان المستخدم مسجل الدخول بالفعل
if($auth->isLoggedIn()) {
    // توجيه المستخدم حسب دوره وحالة الاشتراك
    if(($_SESSION['user_role'] ?? '') === 'admin') {
        header("Location: admin/dashboard.php");
        exit();
    } else {
        // التحقق من وجود اشتراك نشط للمستخدم العادي
        if(isSubscriptionValid($_SESSION['user_id'])) {
            header("Location: index.php");
            exit();
        } else {
            header("Location: subscription.php");
            exit();
        }
    }
}

// معالجة تسجيل الدخول
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    if(!csrf_check()) {
        $error = "انتهت صلاحية الجلسة — أعد تحميل الصفحة وحاول مجددا";
    } else {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if($auth->login($email, $password)) {
        // تسجيل الدخول ناجح — توجيه المستخدم حسب دوره
        if(($_SESSION['user_role'] ?? '') === 'admin') {
            header("Location: admin/dashboard.php");
            exit();
        } else {
            // التحقق من الاشتراك للمستخدم العادي
            if(isSubscriptionValid($_SESSION['user_id'])) {
                header("Location: index.php");
                exit();
            } else {
                // رسالة ترحيب مع التوجيه لصفحة الاشتراك
                $_SESSION['welcome_message'] = "مرحباً بك! يرجى اختيار باقة اشتراك للبدء في التعلم";
                header("Location: subscription.php");
                exit();
            }
        }
    } else {
        $error = "البريد الإلكتروني أو كلمة المرور غير صحيحة";
    }
    }
}

// رسالة تسجيل الخروج
if(isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $success = "تم تسجيل الخروج بنجاح";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - منصة التعليم</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root{
            --primary: #667eea;
            --primary-dark: #764ba2;
            --ink: #2d3748;
            --muted: #718096;
            --line: #e2e8f0;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Tajawal', 'Segoe UI', Tahoma, Arial, sans-serif;
        }
        
        body {
            background: linear-gradient(145deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }
        
        /* تأثير الخلفية المتحركة */
        .wave {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" fill-opacity="1" d="M0,192L48,197.3C96,203,192,213,288,208C384,203,480,181,576,186.7C672,192,768,224,864,234.7C960,245,1056,235,1152,208C1248,181,1344,139,1392,117.3L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-repeat: no-repeat;
            background-size: cover;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            position: relative;
            z-index: 10;
            border: 1px solid rgba(255,255,255,0.3);
            animation: slideUp 0.6s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            background: linear-gradient(145deg, #2c3e50, #1e2a3a);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .logo {
            font-size: 32px;
            font-weight: 900;
            color: white;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            position: relative;
            letter-spacing: 1px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .login-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: 800;
            position: relative;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 16px;
            position: relative;
        }
        
        .login-form {
            padding: 50px 40px;
        }
        
        .form-group {
            margin-bottom: 30px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #2d3748;
            font-weight: 600;
            font-size: 15px;
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 18px;
        }
        
        .form-control {
            width: 100%;
            padding: 16px 50px 16px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            font-size: 16px;
            transition: all 0.3s;
            background: #f8fafc;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 16px 30px;
            background: linear-gradient(to left, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
        
        .error-message {
            background: linear-gradient(145deg, #fed7d7, #feb2b2);
            color: #c53030;
            padding: 16px 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            border: 1px solid #fc8181;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            animation: shake 0.5s ease;
        }
        
        .success-message {
            background: linear-gradient(145deg, #c6f6d5, #9ae6b4);
            color: #22543d;
            padding: 16px 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            border: 1px solid #68d391;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4a5568;
            cursor: pointer;
        }
        
        .remember input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }
        
        .forgot-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .forgot-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .links {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #edf2f7;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 15px;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
        }
        
        .links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(to left, #667eea, #764ba2);
            transition: width 0.3s;
        }
        
        .links a:hover::after {
            width: 100%;
        }
        
        .social-login {
            margin-top: 25px;
            text-align: center;
        }
        
        .social-text {
            color: #718096;
            margin-bottom: 15px;
            position: relative;
        }
        
        .social-text::before,
        .social-text::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 30%;
            height: 1px;
            background: #e2e8f0;
        }
        
        .social-text::before {
            right: 0;
        }
        
        .social-text::after {
            left: 0;
        }
        
        @media (max-width: 480px) {
            .login-container {
                border-radius: 20px;
            }
            
            .login-form {
                padding: 30px 20px;
            }
            
            .login-header {
                padding: 30px 20px;
            }
            
            .login-header h1 {
                font-size: 26px;
            }
            
            .logo {
                font-size: 26px;
            }
        }
    </style>
    <!-- Font Awesome 6 للأيقونات -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="wave"></div>
    
    <div class="login-container">
        <div class="login-header">
            <a href="index.php" class="logo">
                <i class="fas fa-graduation-cap" style="margin-left: 10px;"></i>
                منصة التعليم
            </a>
            <h1>تسجيل الدخول</h1>
            <p>مرحباً بك مرة أخرى في منصتنا التعليمية</p>
        </div>
        
        <div class="login-form">
            <?php if($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope" style="margin-left: 8px; color: #667eea;"></i>
                        البريد الإلكتروني
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               required 
                               placeholder="example@domain.com"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               autocomplete="email">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock" style="margin-left: 8px; color: #667eea;"></i>
                        كلمة المرور
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               required 
                               placeholder="أدخل كلمة المرور"
                               autocomplete="current-password">
                    </div>
                </div>
                
                <div class="remember-forgot" style="justify-content:flex-end">
                    <a href="forgot-password.php" class="forgot-link">
                        <i class="fas fa-question-circle"></i>
                        نسيت كلمة المرور؟
                    </a>
                </div>
                
                <button type="submit" name="login" class="btn">
                    <i class="fas fa-sign-in-alt"></i>
                    تسجيل الدخول
                </button>
            </form>
            
            <div class="links">
                <a href="register.php">
                    <i class="fas fa-user-plus"></i>
                    إنشاء حساب جديد
                </a>
            </div>
            
            <div class="social-login">
                <div class="social-text">أو</div>
                <p style="color: #718096; margin-top: 15px;">
                    ليس لديك حساب؟ 
                    <a href="register.php" style="color: #667eea; font-weight: bold; text-decoration: none;">
                        سجل الآن مجاناً
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // تحسين تجربة المستخدم
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const inputs = document.querySelectorAll('.form-control');
            
            // تأثيرات عند التركيز على الحقول
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
            
            // التحقق من المدخلات قبل الإرسال
            form.addEventListener('submit', function(e) {
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                
                if(!email || !password) {
                    e.preventDefault();
                    alert('الرجاء إدخال البريد الإلكتروني وكلمة المرور');
                }
            });
            
            // رسالة ترحيب للمستخدم الجديد
            const urlParams = new URLSearchParams(window.location.search);
            if(urlParams.has('registered')) {
                const successMsg = document.createElement('div');
                successMsg.className = 'success-message';
                successMsg.innerHTML = '<i class="fas fa-check-circle"></i> تم إنشاء الحساب بنجاح! يمكنك تسجيل الدخول الآن';
                document.querySelector('.login-form').insertBefore(successMsg, document.querySelector('.error-message')?.nextSibling || document.querySelector('form'));
                
                // إخفاء الرسالة بعد 5 ثواني
                setTimeout(() => {
                    successMsg.style.opacity = '0';
                    successMsg.style.transition = 'opacity 0.5s';
                    setTimeout(() => successMsg.remove(), 500);
                }, 5000);
            }
        });
    </script>
</body>
</html>