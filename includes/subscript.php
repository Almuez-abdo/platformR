<?php
// includes/subscript.php
require_once 'config/database.php';
require_once 'classes/Subscription.php';

/**
 * دالة مساعدة للتحقق من اشتراك المستخدم
 */
function checkUserSubscription($user_id) {
    try {
        $database = new Database();
        $db = $database->connect();
        
        $subscription = new Subscription($db);
        return $subscription->checkSubscriptionStatus($user_id);
    } catch(Exception $e) {
        error_log("Check User Subscription Error: " . $e->getMessage());
        return false;
    }
}

/**
 * دالة مساعدة لإنشاء اشتراك جديد
 */
function createUserSubscription($user_id, $plan_id) {
    try {
        $database = new Database();
        $db = $database->connect();
        
        $subscription = new Subscription($db);
        return $subscription->createSubscription($user_id, $plan_id);
    } catch(Exception $e) {
        error_log("Create User Subscription Error: " . $e->getMessage());
        return false;
    }
}

/**
 * دالة مساعدة للحصول على خطة المستخدم الحالية
 */
function getUserSubscriptionPlan($user_id) {
    try {
        $database = new Database();
        $db = $database->connect();
        
        $subscription = new Subscription($db);
        return $subscription->getUserCurrentSubscription($user_id);
    } catch(Exception $e) {
        error_log("Get User Subscription Plan Error: " . $e->getMessage());
        return false;
    }
}

/**
 * دالة مساعدة للتحقق من صلاحية الاشتراك
 */
function isSubscriptionValid($user_id) {
    $subscription = checkUserSubscription($user_id);
    return ($subscription !== false && !empty($subscription));
}

/**
 * دالة مساعدة للحصول على أيام الاشتراك المتبقية
 */
function getSubscriptionRemainingDays($user_id) {
    $subscription = checkUserSubscription($user_id);
    
    if($subscription && isset($subscription['expiry_date']) && $subscription['expiry_date']) {
        $expiry = new DateTime($subscription['expiry_date']);
        $now = new DateTime();
        $diff = $now->diff($expiry);
        
        if($diff->invert == 1) {
            return 0; // منتهي
        }
        
        return $diff->days;
    }
    
    if($subscription && !$subscription['expiry_date']) {
        return -1; // مدى الحياة
    }
    
    return 0; // لا يوجد اشتراك
}
?>