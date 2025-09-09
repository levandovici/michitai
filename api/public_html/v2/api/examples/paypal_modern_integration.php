<?php
/**
 * Modern PayPal Server SDK Integration Example
 * For Michitai API Constructor - Updated for paypal/paypal-server-sdk v0.4+
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PayPal\Sdk\PayPalServerSDK;
use PayPal\Sdk\Environment;
use PayPal\Sdk\Models\OrderRequest;
use PayPal\Sdk\Models\PurchaseUnitRequest;
use PayPal\Sdk\Models\AmountWithBreakdown;
use PayPal\Sdk\Models\Money;

class ModernPayPalIntegration {
    private $paypalClient;
    
    public function __construct() {
        // Load environment variables
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
        
        // Initialize PayPal client with modern SDK
        $this->paypalClient = new PayPalServerSDK([
            'environment' => $_ENV['PAYPAL_MODE'] === 'live' ? Environment::PRODUCTION : Environment::SANDBOX,
            'clientId' => $_ENV['PAYPAL_CLIENT_ID'],
            'clientSecret' => $_ENV['PAYPAL_CLIENT_SECRET']
        ]);
    }
    
    /**
     * Create a subscription for Standard or Pro plan
     */
    public function createSubscription($planType, $userEmail) {
        $planIds = [
            'Standard' => 'P-5ML4271244454362WXNWU5NQ', // Replace with your actual plan IDs
            'Pro' => 'P-2J945307GW612762WXNWU6NQ'
        ];
        
        if (!isset($planIds[$planType])) {
            throw new Exception('Invalid plan type');
        }
        
        $subscriptionRequest = [
            'plan_id' => $planIds[$planType],
            'subscriber' => [
                'email_address' => $userEmail,
                'name' => [
                    'given_name' => 'API',
                    'surname' => 'User'
                ]
            ],
            'application_context' => [
                'brand_name' => 'Michitai API Constructor',
                'locale' => 'en-US',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'SUBSCRIBE_NOW',
                'payment_method' => [
                    'payer_selected' => 'PAYPAL',
                    'payee_preferred' => 'IMMEDIATE_PAYMENT_REQUIRED'
                ],
                'return_url' => $_ENV['APP_URL'] . '/api/webhook/paypal/success',
                'cancel_url' => $_ENV['APP_URL'] . '/api/webhook/paypal/cancel'
            ]
        ];
        
        try {
            $response = $this->paypalClient->subscriptions()->create($subscriptionRequest);
            
            // Extract approval URL
            $approvalUrl = null;
            foreach ($response->links as $link) {
                if ($link->rel === 'approve') {
                    $approvalUrl = $link->href;
                    break;
                }
            }
            
            return [
                'subscription_id' => $response->id,
                'approval_url' => $approvalUrl,
                'status' => $response->status
            ];
            
        } catch (Exception $e) {
            throw new Exception('PayPal subscription creation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get subscription details
     */
    public function getSubscription($subscriptionId) {
        try {
            $response = $this->paypalClient->subscriptions()->get($subscriptionId);
            
            return [
                'id' => $response->id,
                'status' => $response->status,
                'plan_id' => $response->plan_id,
                'start_time' => $response->start_time,
                'billing_info' => $response->billing_info,
                'subscriber' => $response->subscriber
            ];
            
        } catch (Exception $e) {
            throw new Exception('Failed to get subscription: ' . $e->getMessage());
        }
    }
    
    /**
     * Cancel a subscription
     */
    public function cancelSubscription($subscriptionId, $reason = 'User requested cancellation') {
        try {
            $cancelRequest = [
                'reason' => $reason
            ];
            
            $response = $this->paypalClient->subscriptions()->cancel($subscriptionId, $cancelRequest);
            
            return [
                'success' => true,
                'message' => 'Subscription cancelled successfully'
            ];
            
        } catch (Exception $e) {
            throw new Exception('Failed to cancel subscription: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle webhook notifications
     */
    public function handleWebhook($webhookData) {
        $eventType = $webhookData['event_type'];
        $resource = $webhookData['resource'];
        
        switch ($eventType) {
            case 'BILLING.SUBSCRIPTION.ACTIVATED':
                // Subscription activated - upgrade user plan
                $this->activateUserSubscription($resource['id'], $resource['plan_id']);
                break;
                
            case 'BILLING.SUBSCRIPTION.CANCELLED':
                // Subscription cancelled - downgrade to free
                $this->deactivateUserSubscription($resource['id']);
                break;
                
            case 'BILLING.SUBSCRIPTION.SUSPENDED':
                // Payment failed - suspend access
                $this->suspendUserSubscription($resource['id']);
                break;
                
            case 'PAYMENT.SALE.COMPLETED':
                // Payment successful - extend subscription
                $this->recordPayment($resource);
                break;
        }
    }
    
    private function activateUserSubscription($subscriptionId, $planId) {
        // Update user plan in database
        // Implementation depends on your database structure
    }
    
    private function deactivateUserSubscription($subscriptionId) {
        // Downgrade user to free plan
        // Implementation depends on your database structure
    }
    
    private function suspendUserSubscription($subscriptionId) {
        // Suspend user access
        // Implementation depends on your database structure
    }
    
    private function recordPayment($paymentData) {
        // Record payment in database
        // Implementation depends on your database structure
    }
}

// Usage example:
/*
$paypal = new ModernPayPalIntegration();

// Create subscription
$result = $paypal->createSubscription('Standard', 'user@example.com');
echo "Approval URL: " . $result['approval_url'];

// Get subscription details
$details = $paypal->getSubscription('I-BW452GLLEP1G');
print_r($details);

// Cancel subscription
$paypal->cancelSubscription('I-BW452GLLEP1G', 'User requested cancellation');
*/
?>
