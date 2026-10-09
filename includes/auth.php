<?php
require_once 'config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
    public function register($username, $email, $password, $full_name, $phone = null) {
        try {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $query = "INSERT INTO users (username, email, password, full_name, phone, role, is_active, created_at) 
                      VALUES (:username, :email, :password, :full_name, :phone, 'student', 1, NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':phone', $phone);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Register Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function login($email, $password) {
        try {
            $query = "SELECT * FROM users WHERE email = :email AND is_active = 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if(password_verify($password, $user['password'])) {
                    // تحديث آخر تسجيل دخول
                    $this->updateLastLogin($user['id']);

                    // تخزين معلومات المستخدم في الجلسة
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_role'] = $user['role'];
                    session_regenerate_id(true);

                    return true;
                }
            }
            return false;
        } catch(PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            return false;
        }
    }

    public function rePassword($email) {
        {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
        
            $stmt->execute();
            return $stmt->fetch();
        }
    }
    
    /**
     * تحديث تاريخ آخر تسجيل دخول
     */
    private function updateLastLogin($user_id) {
        try {
            $query = "UPDATE users SET last_login = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $user_id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Update Last Login Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * التحقق من حالة تسجيل الدخول
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * تسجيل الخروج
     */
    public function logout() {
        // مسح جميع متغيرات الجلسة
        $_SESSION = array();
        
        // إتلاف الجلسة
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        header("Location: login.php");
        exit();
    }
    
    /**
     * الحصول على معلومات المستخدم الحالي
     */
    public function getCurrentUser() {
        if($this->isLoggedIn()) {
            try {
                $query = "SELECT id, username, email, full_name, phone, avatar, role, created_at 
                          FROM users WHERE id = :id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':id', $_SESSION['user_id']);
                $stmt->execute();
                
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                error_log("Get Current User Error: " . $e->getMessage());
                return null;
            }
        }
        return null;
    }
    
    /**
     * التحقق من صلاحية المستخدم (أدمن ولا لا)
     */
    public function isAdmin() {
        return ($this->isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
    }
    
    /**
     * التحقق من صلاحية المستخدم (مدرس ولا لا)
     */
    public function isInstructor() {
        return ($this->isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'instructor');
    }
    
    /**
     * تغيير كلمة المرور
     */
    public function changePassword($email, $new_password) {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $query = "UPDATE users SET password = :password WHERE email = :email";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':email', $email);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Change Password Error: " . $e->getMessage());
            return false;
        }
    }


    public function redirectHome($theMsg, $sec = 3, $url= NULL){
        if($url === NULL){
            $url = 'index.php';
            $link = 'الصفحة الرئيسية';
        }elseif($url === 'back'){
            if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] !== ''){
                $url = $_SERVER['HTTP_REFERER'];
                $link = 'الصفحة السابقة';
            }
        }elseif($url == $url){
            $url = $url;
            $link = 'الصفحة الرئيسية '; 
        }
     
    

        echo '<div class= "container text-end">';
            echo $theMsg ;
            echo '<div class= "alert alert-info">سوف يتم تحويلك الي  ' .  $link . ' بعد ' . $sec . ' ثانية </div>';
        echo '</div>';
        header("refresh:$sec;URL= $url");
        exit();
    }

}
?>