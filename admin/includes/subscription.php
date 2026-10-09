<?php
class Subscription {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
    // أنواع الاشتراكات
    const PLANS = [
        'monthly' => [
            'price' => 100,
            'duration' => 30 // أيام
        ],
        'yearly' => [
            'price' => 1000,
            'duration' => 365 // أيام
        ],
        'lifetime' => [
            'price' => 5000,
            'duration' => NULL // مدى الحياة
        ]
    ];
    
    public function createSubscription($user_id, $plan_type, $payment_method = 'stripe') {
        $plan = self::PLANS[$plan_type];
        $expiry_date = $plan['duration'] ? date('Y-m-d', strtotime("+{$plan['duration']} days")) : NULL;
        
        $query = "INSERT INTO subscriptions 
                  (user_id, plan_type, price, expiry_date, status, created_at) 
                  VALUES (:user_id, :plan_type, :price, :expiry_date, 'active', NOW())";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':plan_type', $plan_type);
        $stmt->bindParam(':price', $plan['price']);
        $stmt->bindParam(':expiry_date', $expiry_date);
        
        return $stmt->execute();
    }
    
    public function checkSubscriptionStatus($user_id) {
        $query = "SELECT * FROM subscriptions 
                  WHERE user_id = :user_id 
                  AND status = 'active' 
                  AND (expiry_date IS NULL OR expiry_date > NOW())
                  ORDER BY created_at DESC 
                  LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0 ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    }
    
    public function cancelSubscription($subscription_id) {
        $query = "UPDATE subscriptions SET status = 'cancelled' WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $subscription_id);
        return $stmt->execute();
    }
    
    public function getAvailablePlans() {
        return self::PLANS;
    }
}
?>