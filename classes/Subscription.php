<?php
class Subscription {
    private $db;
    private $table = 'user_subscriptions';
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * التحقق من حالة اشتراك المستخدم
     */
    public function checkSubscriptionStatus($user_id) {
        try {
            $query = "SELECT us.*, sp.name as plan_name, sp.duration as plan_duration 
                      FROM " . $this->table . " us
                      LEFT JOIN subscription_plans sp ON us.plan_id = sp.id
                      WHERE us.user_id = :user_id 
                      AND us.status = 'active' 
                      AND (us.expiry_date IS NULL OR us.expiry_date > NOW())
                      ORDER BY us.created_at DESC 
                      LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($subscription) {
                // إذا كان الاشتراك منتهي، نحدث حالته
                if($subscription['expiry_date'] && strtotime($subscription['expiry_date']) < time()) {
                    $this->expireSubscription($subscription['id']);
                    return false;
                }
            }
            
            return $subscription;
            
        } catch(PDOException $e) {
            error_log("Check Subscription Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * إنشاء اشتراك جديد للمستخدم
     */
    public function createSubscription($user_id, $plan_id) {
        try {
            // بدأ المعاملة (Transaction)
            $this->db->beginTransaction();
            
            // جلب تفاصيل الخطة
            $plan = $this->getPlanDetails($plan_id);
            if(!$plan) {
                throw new Exception("❌ الخطة غير موجودة");
            }
            
            // إلغاء أي اشتراكات سابقة نشطة
            $this->deactivatePreviousSubscriptions($user_id);
            
            // إنشاء رقم اشتراك فريد
            $subscription_number = 'SUB-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // حساب تاريخ الانتهاء
            $expiry_date = null;
            if($plan['duration'] && $plan['duration'] > 0) {
                $expiry_date = date('Y-m-d H:i:s', strtotime('+' . $plan['duration'] . ' days'));
            }
            
            // إنشاء اشتراك جديد
            $query = "INSERT INTO " . $this->table . " 
                      (user_id, plan_id, subscription_number, start_date, expiry_date, status, created_at) 
                      VALUES 
                      (:user_id, :plan_id, :subscription_number, NOW(), :expiry_date, 'active', NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':plan_id', $plan_id, PDO::PARAM_INT);
            $stmt->bindParam(':subscription_number', $subscription_number);
            $stmt->bindParam(':expiry_date', $expiry_date);
            
            if(!$stmt->execute()) {
                throw new Exception("❌ فشل إنشاء الاشتراك");
            }
            
            $subscription_id = $this->db->lastInsertId();
            
            // تسجيل العملية في سجل الاشتراكات
            $this->logSubscriptionActivity($user_id, $subscription_id, $plan_id, 'activated');
            
            // تأكيد المعاملة
            $this->db->commit();
            
            return $subscription_id;
            
        } catch(Exception $e) {
            // تراجع عن المعاملة في حالة الخطأ
            $this->db->rollBack();
            error_log("Create Subscription Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * الحصول على الخطط المتاحة
     */
    public function getAvailablePlans() {
        try {
            $query = "SELECT * FROM subscription_plans 
                      WHERE is_active = 1 
                      ORDER BY sort_order ASC, price ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // إذا مافي خطط في قاعدة البيانات، نرجع خطط افتراضية
            if(empty($plans)) {
                return [
                    ['id' => 1, 'name' => 'اشتراك شهري', 'price' => 99.00, 'duration' => 30, 'description' => 'اشتراك لمدة شهر كامل', 'is_featured' => 0],
                    ['id' => 2, 'name' => 'اشتراك سنوي', 'price' => 499.00, 'duration' => 365, 'description' => 'اشتراك لمدة سنة كاملة - وفر 50%', 'is_featured' => 1],
                    ['id' => 3, 'name' => 'اشتراك مدى الحياة', 'price' => 999.00, 'duration' => null, 'description' => 'اشتراك بدون حدود - مدى الحياة', 'is_featured' => 0]
                ];
            }
            
            return $plans;
            
        } catch(PDOException $e) {
            error_log("Get Plans Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * جلب تفاصيل خطة معينة
     */
    public function getPlanDetails($plan_id) {
        try {
            $query = "SELECT * FROM subscription_plans 
                      WHERE id = :id AND is_active = 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $plan_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log("Get Plan Details Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * إلغاء الاشتراكات السابقة
     */
    private function deactivatePreviousSubscriptions($user_id) {
        try {
            $query = "UPDATE " . $this->table . " 
                      SET status = 'cancelled', 
                          updated_at = NOW() 
                      WHERE user_id = :user_id 
                      AND status = 'active'";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            return $stmt->execute();
            
        } catch(PDOException $e) {
            error_log("Deactivate Subscriptions Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * إنهاء اشتراك (انتهاء الصلاحية)
     */
    public function expireSubscription($subscription_id) {
        try {
            $query = "UPDATE " . $this->table . " 
                      SET status = 'expired', 
                          updated_at = NOW() 
                      WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $subscription_id, PDO::PARAM_INT);
            
            if($stmt->execute()) {
                // جلب user_id لتسجيل النشاط
                $query = "SELECT user_id FROM " . $this->table . " WHERE id = :id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':id', $subscription_id, PDO::PARAM_INT);
                $stmt->execute();
                $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if($subscription) {
                    $this->logSubscriptionActivity($subscription['user_id'], $subscription_id, null, 'expired');
                }
                
                return true;
            }
            
            return false;
            
        } catch(PDOException $e) {
            error_log("Expire Subscription Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تسجيل نشاط الاشتراك
     */
    private function logSubscriptionActivity($user_id, $subscription_id, $plan_id, $action) {
        try {
            $query = "INSERT INTO subscription_logs 
                      (user_id, subscription_id, plan_id, action, ip_address, user_agent, created_at) 
                      VALUES 
                      (:user_id, :subscription_id, :plan_id, :action, :ip, :user_agent, NOW())";
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '127.0.0.1';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':subscription_id', $subscription_id, PDO::PARAM_INT);
            $stmt->bindParam(':plan_id', $plan_id, PDO::PARAM_INT);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':ip', $ip);
            $stmt->bindParam(':user_agent', $user_agent);
            
            return $stmt->execute();
            
        } catch(PDOException $e) {
            error_log("Log Subscription Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * التحقق من صلاحية الاشتراكات المنتهية (للكرون جوب)
     */
    public function checkExpiredSubscriptions() {
        try {
            $query = "SELECT id, user_id FROM " . $this->table . " 
                      WHERE status = 'active' 
                      AND expiry_date IS NOT NULL 
                      AND expiry_date <= NOW()";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $expired = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($expired as $subscription) {
                $this->expireSubscription($subscription['id']);
            }
            
            return count($expired);
            
        } catch(PDOException $e) {
            error_log("Check Expired Subscriptions Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * الحصول على اشتراك المستخدم الحالي
     */
    public function getUserCurrentSubscription($user_id) {
        try {
            $query = "SELECT us.*, sp.name as plan_name, sp.duration as plan_duration 
                      FROM " . $this->table . " us
                      LEFT JOIN subscription_plans sp ON us.plan_id = sp.id
                      WHERE us.user_id = :user_id 
                      AND us.status = 'active'
                      ORDER BY us.created_at DESC 
                      LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log("Get User Subscription Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تجديد الاشتراك
     */
    public function renewSubscription($user_id, $plan_id, $payment_method = null) {
        try {
            // استخدام الإجراء المخزن إذا كان موجوداً
            $stmt = $this->db->prepare("CALL renew_subscription(:user_id, :plan_id, :payment_method)");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':plan_id', $plan_id, PDO::PARAM_INT);
            $stmt->bindParam(':payment_method', $payment_method);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            return $result;
            
        } catch(PDOException $e) {
            // إذا الإجراء المخزن مش موجود، نستخدم الطريقة العادية
            error_log("Renew Subscription via Procedure Error: " . $e->getMessage());
            return $this->createSubscription($user_id, $plan_id);
        }
    }
}
?>