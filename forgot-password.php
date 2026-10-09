<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth();
$error = '';
$success = '';

// إذا كان المستخدم مسجل دخول بالفعل
if($auth->isLoggedIn()) {
    header("Location: index.php");
    exit();
}
 

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset ($_POST['rePassword'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    $user_email = $auth->rePassword($email);
    $user = $user_email;

    if($user){
        $_SESSION['reset_email'] = $email;


    } else {
        $error = "البريد الإلكتروني غير موجود في النظام.";
    }
}


        
        // هنا يمكنك توليد رمز إعادة تعيين وإرساله عبر البريد الإلكتروني
    //     $reset_code = rand(100000, 999999);
    //     $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

    //     $update_stmt = $auth->db->prepare("UPDATE users SET reset_code = :reset_code, reset_expiry = :expiry WHERE email = :email");
    //     $update_stmt->bindParam(':reset_code', $reset_code);
    //     $update_stmt->bindParam(':expiry', $expiry);
    //     $update_stmt->bindParam(':email', $email);
    //     $update_stmt->execute();


    //     $success = "تم إرسال رمز إعادة التعيين إلى بريدك الإلكتروني.";
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اعادة تعيين كلمة المرور</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* نفس الـ CSS بتاع صفحة تسجيل الدخول مع بعض التعديلات */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Tajawal', sans-serif;
        }
        
        body {
            background: linear-gradient(145deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
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
        
        .register-header {
            background: linear-gradient(145deg, #2c3e50, #1e2a3a);
            color: white;
            padding: 30px 30px;
            text-align: center;
        }
        
        .register-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 800;
        }
        
        .register-form {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2d3748;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
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
            padding: 15px;
            background: linear-gradient(to left, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
        
        .error-message {
            background: #fed7d7;
            color: #c53030;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            border: 1px solid #fc8181;
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px solid #edf2f7;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 700;
        }
        
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 480px) {
            .row {
                grid-template-columns: 1fr;
            }
            
            .register-form {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
<?php if(!isset($_SESSION['reset_email'])): ?>
<form method="POST" action="" id="reset-password">
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
        <button type="submit" name="rePassword" class="btn">
        <i class="fas fa-user-plus"></i>
        تعيين
    </button>
</form>

<?php else :?>

    <div class="register-container">
        <div class="register-header">
            <h1>✨ إعادة تعيين كلمة المرور</h1>
            <p>أدخل كلمة المرور الجديدة</p>
        </div>
        
        <div class="register-form">
            <?php if($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="reset-password.php">
                        
                <div class="row">
                    <input type="hidden" name="email" value=" <?php echo $_SESSION['reset_email']; ?>">
                    <div class="form-group">
                        <label>🔒 كلمة المرور</label>
                        <input type="password" name="newPassword" class="form-control" required 
                               placeholder="أقل من 6 أحرف">
                    </div>
                    
                    <div class="form-group">
                        <label>🔒 تأكيد كلمة المرور</label>
                        <input type="password" name="confirm_password" class="form-control" required 
                               placeholder="أعد إدخال كلمة المرور">
                    </div>
                </div>
                
                <button type="submit" name="updatePassword" class="btn">
                    <i class="fas fa-user-plus"></i>
                    تعيين
                </button>
            </form>
        </div>
    </div>


<?php
endif;
?>
</body>
</html>
