<?php
    session_start();
    require_once 'config/database.php';
    require_once 'includes/auth.php';

    $auth = new Auth();
    $error = '';
    $theMsg = '';


    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['updatePassword'])){
        $newpassword = $_POST['newPassword'];
        $confirm_password = $_POST['confirm_password'];
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

        $user_email = $auth->rePassword($email);
        $user = $user_email;

        $errors = [];

        if(strlen($newpassword) < 6) {
            $errors[] = "كلمة المرور يجب أن تكون 6 أحرف على الأقل";
        }
        
        if($newpassword !== $confirm_password) {
            $errors[] = "كلمة المرور غير متطابقة";
        }

    if(empty($errors)) {

        $auth->changePassword($email, $newpassword);

        // تنظيف السيشن
        unset($_SESSION['reset_email']);

        $theMsg= '<div class= "alert alert-success"> تم تغيير كلمة المرور بنجاح </div>';
        $auth->redirectHome($theMsg, 3, 'login.php');

        }else{
            $error = implode("<br>", $errors);
        }
      
    }

?>