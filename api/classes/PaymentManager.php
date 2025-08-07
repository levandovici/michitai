<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/NotificationManager.php';

/**
 * Payment Management Class
 * Handles PayPal subscriptions, Paynet fallback, and MAIB bank transfers
 * Supports global Visa/Mastercard payments with payouts to MAIB card (SWIFT: AGRNMD2X)
 */
class PaymentManager {
    private $db;
    private $auth;
    private $notificationManager;
    private $paypalMode;
    private $paypalClientId;
    private $paypalClientSecret;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
        $this->notificationManager = new NotificationManager();
        
        // Load PayPal configuration
        $this->paypalMode = $_ENV['PAYPAL_MODE'] ?? 'sandbox';
        $this->paypalClientId = $_ENV['PAYPAL_CLIENT_ID'] ?? '';
        $this->paypalClientSecret = $_ENV['PAYPAL_CLIENT_SECRET'] ?? '';
    }
    
    /**
     * Get PayPal API base URL
     */
    private function getPayPalBaseUrl() {
        return $this->paypalMode === 'live' 
            ? 'https://api.paypal.com' 
            : 'https://api.sandbox.paypal.com';
    }
    
    /**
     * Get PayPal access token
     */
    private function getPayPalAccessToken() {
        $url = $this->getPayPalBaseUrl() . '/v1/oauth2/token';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->paypalClientId . ':' . $this->paypalClientSecret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Failed to get PayPal access token");
        }
        
        $data = json_decode($response, true);
        return $data['access_token'];
    }
    
    /**
     * Create PayPal subscription plan
     */
    private function createPayPalPlan($planType) {
        $planLimits = Config::get('PLAN_LIMITS')[$planType];
        $price = $planLimits['price'];
        
        $accessToken = $this->getPayPalAccessToken();
        $url = $this->getPayPalBaseUrl() . '/v1/billing/plans';
        
        $planData = [
            'product_id' => 'MULTIPLAYER_API_' . strtoupper($planType),
            'name' => "Multiplayer API $planType Plan",
            'description' => "Monthly subscription for $planType plan with {$planLimits['memory_mb']}MB storage and {$planLimits['max_api_calls_per_day']} API calls per day",
            'status' => 'ACTIVE',
            'billing_cycles' => [
                [
                    'frequency' => [
                        'interval_unit' => 'MONTH',
                        'interval_count' => 1
                    ],
                    'tenure_type' => 'REGULAR',
                    'sequence' => 1,
                    'total_cycles' => 0, // Infinite
                    'pricing_scheme' => [
                        'fixed_price' => [
                            'value' => $price,
                            'currency_code' => 'USD'
                        ]
                    ]
                ]
            ],
            'payment_preferences' => [
                'auto_bill_outstanding' => true,
                'setup_fee' => [
                    'value' => '0',
                    'currency_code' => 'USD'
                ],
                'setup_fee_failure_action' => 'CONTINUE',
                'payment_failure_threshold' => 3
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json',
            'PayPal-Request-Id: ' . uniqid()
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($planData));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 201) {
            throw new Exception("Failed to create PayPal plan: " . $response);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Create subscription
     */
    public function createSubscription($userId, $planType) {
        if (!in_array($planType, ['Standard', 'Pro'])) {
            throw new Exception("Invalid plan type");
        }
        
        // Check if user already has an active subscription
        $existingSubscription = $this->db->fetchOne(
            "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active'",
            [$userId]
        );
        
        if ($existingSubscription) {
            throw new Exception("User already has an active subscription");
        }
        
        try {
            // Create PayPal plan if needed
            $plan = $this->createPayPalPlan($planType);
            $planId = $plan['id'];
            
            // Create subscription request
            $accessToken = $this->getPayPalAccessToken();
            $url = $this->getPayPalBaseUrl() . '/v1/billing/subscriptions';
            
            $subscriptionData = [
                'plan_id' => $planId,
                'start_time' => date('c', strtotime('+1 minute')),
                'quantity' => '1',
                'shipping_amount' => [
                    'currency_code' => 'USD',
                    'value' => '0.00'
                ],
                'subscriber' => [
                    'name' => [
                        'given_name' => 'User',
                        'surname' => $userId
                    ],
                    'email_address' => $this->getUserEmail($userId)
                ],
                'application_context' => [
                    'brand_name' => 'Multiplayer API Constructor',
                    'locale' => 'en-US',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'SUBSCRIBE_NOW',
                    'payment_method' => [
                        'payer_selected' => 'PAYPAL',
                        'payee_preferred' => 'IMMEDIATE_PAYMENT_REQUIRED'
                    ],
                    'return_url' => $_ENV['PAYPAL_RETURN_URL'] ?? 'https://yourdomain.com/payment/success',
                    'cancel_url' => $_ENV['PAYPAL_CANCEL_URL'] ?? 'https://yourdomain.com/payment/cancel'
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
                'PayPal-Request-Id: ' . uniqid()
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($subscriptionData));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 201) {
                // Fallback to Paynet
                return $this->createPaynetSubscription($userId, $planType);
            }
            
            $subscription = json_decode($response, true);
            
            // Store subscription in database
            $this->db->execute(
                "INSERT INTO subscriptions (user_id, paypal_subscription_id, plan_type, status, amount) VALUES (?, ?, ?, 'pending', ?)",
                [$userId, $subscription['id'], $planType, Config::get('PLAN_LIMITS')[$planType]['price']]
            );
            
            // Get approval URL
            $approvalUrl = '';
            foreach ($subscription['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approvalUrl = $link['href'];
                    break;
                }
            }
            
            return [
                'subscription_id' => $subscription['id'],
                'plan_type' => $planType,
                'status' => 'pending',
                'approval_url' => $approvalUrl,
                'payment_method' => 'paypal'
            ];
            
        } catch (Exception $e) {
            // Fallback to Paynet
            return $this->createPaynetSubscription($userId, $planType);
        }
    }
    
    /**
     * Create Paynet subscription (Moldova fallback)
     */
    private function createPaynetSubscription($userId, $planType) {
        $planLimits = Config::get('PLAN_LIMITS')[$planType];
        $price = $planLimits['price'];
        
        // Generate unique order ID
        $orderId = 'PAYNET_' . $userId . '_' . time();
        
        // Store subscription in database
        $this->db->execute(
            "INSERT INTO subscriptions (user_id, paypal_subscription_id, plan_type, status, amount) VALUES (?, ?, ?, 'pending', ?)",
            [$userId, $orderId, $planType, $price]
        );
        
        // Paynet payment URL (Moldova-specific)
        $paynetUrl = 'https://paynet.md/payment';
        $paymentData = [
            'amount' => $price,
            'currency' => 'USD',
            'order_id' => $orderId,
            'description' => "Multiplayer API $planType Plan - Monthly Subscription",
            'return_url' => $_ENV['PAYNET_RETURN_URL'] ?? 'https://yourdomain.com/payment/success',
            'cancel_url' => $_ENV['PAYNET_CANCEL_URL'] ?? 'https://yourdomain.com/payment/cancel'
        ];
        
        $paymentUrl = $paynetUrl . '?' . http_build_query($paymentData);
        
        return [
            'subscription_id' => $orderId,
            'plan_type' => $planType,
            'status' => 'pending',
            'approval_url' => $paymentUrl,
            'payment_method' => 'paynet'
        ];
    }
    
    /**
     * Update subscription
     */
    public function updateSubscription($userId, $newPlanType) {
        $currentSubscription = $this->db->fetchOne(
            "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1",
            [$userId]
        );
        
        if (!$currentSubscription) {
            throw new Exception("No active subscription found");
        }
        
        // Cancel current subscription
        $this->cancelSubscription($userId);
        
        // Create new subscription
        return $this->createSubscription($userId, $newPlanType);
    }
    
    /**
     * Cancel subscription
     */
    public function cancelSubscription($userId) {
        $subscription = $this->db->fetchOne(
            "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1",
            [$userId]
        );
        
        if (!$subscription) {
            throw new Exception("No active subscription found");
        }
        
        try {
            // Cancel PayPal subscription
            if (strpos($subscription['paypal_subscription_id'], 'PAYNET_') !== 0) {
                $accessToken = $this->getPayPalAccessToken();
                $url = $this->getPayPalBaseUrl() . '/v1/billing/subscriptions/' . $subscription['paypal_subscription_id'] . '/cancel';
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken,
                    'Accept: application/json'
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'reason' => 'User requested cancellation'
                ]));
                
                curl_exec($ch);
                curl_close($ch);
            }
        } catch (Exception $e) {
            // Log error but continue with local cancellation
            error_log("Failed to cancel PayPal subscription: " . $e->getMessage());
        }
        
        // Update local subscription status
        $this->db->execute(
            "UPDATE subscriptions SET status = 'canceled' WHERE subscription_id = ?",
            [$subscription['subscription_id']]
        );
        
        // Downgrade user to Free plan
        $freeLimits = Config::get('PLAN_LIMITS')['Free'];
        $this->db->execute(
            "UPDATE users SET plan_type = 'Free', memory_limit_mb = ? WHERE user_id = ?",
            [$freeLimits['memory_mb'], $userId]
        );
        
        // Send notification
        $this->notificationManager->queueNotification(
            $userId,
            'Subscription Canceled',
            'Your subscription has been canceled and your account has been downgraded to the Free plan.',
            'subscription_canceled'
        );
        
        return ['success' => true, 'message' => 'Subscription canceled successfully'];
    }
    
    /**
     * Get subscription status
     */
    public function getSubscriptionStatus($userId) {
        $subscription = $this->db->fetchOne(
            "SELECT s.*, u.plan_type FROM subscriptions s JOIN users u ON s.user_id = u.user_id WHERE s.user_id = ? ORDER BY s.created_at DESC LIMIT 1",
            [$userId]
        );
        
        if (!$subscription) {
            return [
                'status' => 'none',
                'plan_type' => 'Free',
                'message' => 'No subscription found'
            ];
        }
        
        return [
            'subscription_id' => $subscription['subscription_id'],
            'paypal_subscription_id' => $subscription['paypal_subscription_id'],
            'plan_type' => $subscription['plan_type'],
            'status' => $subscription['status'],
            'amount' => $subscription['amount'],
            'created_at' => $subscription['created_at'],
            'current_plan' => $subscription['plan_type']
        ];
    }
    
    /**
     * Handle PayPal webhook
     */
    public function handlePayPalWebhook($webhookData) {
        $eventType = $webhookData['event_type'] ?? '';
        $resource = $webhookData['resource'] ?? [];
        
        switch ($eventType) {
            case 'BILLING.SUBSCRIPTION.ACTIVATED':
                $this->handleSubscriptionActivated($resource);
                break;
            case 'PAYMENT.SALE.COMPLETED':
                $this->handlePaymentCompleted($resource);
                break;
            case 'BILLING.SUBSCRIPTION.CANCELLED':
                $this->handleSubscriptionCancelled($resource);
                break;
            case 'BILLING.SUBSCRIPTION.SUSPENDED':
                $this->handleSubscriptionSuspended($resource);
                break;
            default:
                error_log("Unhandled PayPal webhook event: $eventType");
        }
    }
    
    /**
     * Handle subscription activated
     */
    private function handleSubscriptionActivated($resource) {
        $subscriptionId = $resource['id'] ?? '';
        
        $subscription = $this->db->fetchOne(
            "SELECT * FROM subscriptions WHERE paypal_subscription_id = ?",
            [$subscriptionId]
        );
        
        if ($subscription) {
            // Update subscription status
            $this->db->execute(
                "UPDATE subscriptions SET status = 'active' WHERE subscription_id = ?",
                [$subscription['subscription_id']]
            );
            
            // Update user plan
            $planLimits = Config::get('PLAN_LIMITS')[$subscription['plan_type']];
            $this->db->execute(
                "UPDATE users SET plan_type = ?, memory_limit_mb = ? WHERE user_id = ?",
                [$subscription['plan_type'], $planLimits['memory_mb'], $subscription['user_id']]
            );
            
            // Send notification
            $this->notificationManager->queueNotification(
                $subscription['user_id'],
                'Subscription Activated',
                "Your {$subscription['plan_type']} plan subscription has been activated successfully.",
                'subscription_activated'
            );
        }
    }
    
    /**
     * Handle payment completed
     */
    private function handlePaymentCompleted($resource) {
        $subscriptionId = $resource['billing_agreement_id'] ?? '';
        
        if ($subscriptionId) {
            $subscription = $this->db->fetchOne(
                "SELECT * FROM subscriptions WHERE paypal_subscription_id = ?",
                [$subscriptionId]
            );
            
            if ($subscription) {
                // Send payment confirmation
                $this->notificationManager->queueNotification(
                    $subscription['user_id'],
                    'Payment Received',
                    "Payment of $" . $resource['amount']['total'] . " has been processed for your {$subscription['plan_type']} plan.",
                    'payment_completed'
                );
            }
        }
    }
    
    /**
     * Get user email
     */
    private function getUserEmail($userId) {
        $user = $this->db->fetchOne(
            "SELECT email FROM users WHERE user_id = ?",
            [$userId]
        );
        
        return $user['email'] ?? '';
    }
    
    /**
     * Process manual MAIB bank transfer verification
     */
    public function verifyMAIBTransfer($userId, $transferData) {
        $amount = $transferData['amount'] ?? 0;
        $reference = $transferData['reference'] ?? '';
        $planType = $transferData['plan_type'] ?? '';
        
        if (empty($reference) || $amount <= 0 || !in_array($planType, ['Standard', 'Pro'])) {
            throw new Exception("Invalid transfer data");
        }
        
        $expectedAmount = Config::get('PLAN_LIMITS')[$planType]['price'];
        
        if ($amount < $expectedAmount) {
            throw new Exception("Insufficient payment amount");
        }
        
        // Create manual subscription record
        $orderId = 'MAIB_' . $userId . '_' . time();
        
        $this->db->execute(
            "INSERT INTO subscriptions (user_id, paypal_subscription_id, plan_type, status, amount) VALUES (?, ?, ?, 'active', ?)",
            [$userId, $orderId, $planType, $amount]
        );
        
        // Update user plan
        $planLimits = Config::get('PLAN_LIMITS')[$planType];
        $this->db->execute(
            "UPDATE users SET plan_type = ?, memory_limit_mb = ? WHERE user_id = ?",
            [$planType, $planLimits['memory_mb'], $userId]
        );
        
        // Send confirmation
        $this->notificationManager->queueNotification(
            $userId,
            'Manual Payment Verified',
            "Your MAIB bank transfer has been verified and your {$planType} plan has been activated.",
            'manual_payment_verified'
        );
        
        return [
            'success' => true,
            'subscription_id' => $orderId,
            'plan_type' => $planType,
            'status' => 'active'
        ];
    }
}
?>
