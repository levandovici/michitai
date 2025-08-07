<?php
/**
 * Cron Jobs Handler for Multiplayer API Web Constructor
 * Handles daily API resets, timer updates, subscription checks, and notifications
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/PaymentManager.php';
require_once __DIR__ . '/../classes/NotificationManager.php';

class CronHandler {
    private $db;
    private $auth;
    private $paymentManager;
    private $notificationManager;
    private $logFile;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
        $this->paymentManager = new PaymentManager();
        $this->notificationManager = new NotificationManager();
        $this->logFile = __DIR__ . '/cron.log';
    }
    
    /**
     * Log cron activity
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        echo $logEntry;
    }
    
    /**
     * Daily reset of API calls (runs at 00:00 UTC)
     */
    public function resetDailyApiCalls() {
        $this->log("Starting daily API calls reset...");
        
        try {
            // Reset API call counters for all users
            $result = $this->db->execute("UPDATE users SET api_calls_today = 0");
            
            $this->log("API calls reset completed successfully");
            
            // Update system stats
            $this->updateSystemStats();
            
        } catch (Exception $e) {
            $this->log("Error resetting API calls: " . $e->getMessage());
        }
    }
    
    /**
     * Update timers (runs every minute)
     */
    public function updateTimers() {
        $this->log("Starting timer updates...");
        
        try {
            // Get all running timers
            $timers = $this->db->fetchAll(
                "SELECT * FROM timers WHERE is_running = 1"
            );
            
            $updatedCount = 0;
            $completedCount = 0;
            
            foreach ($timers as $timer) {
                // Calculate new timer value based on multiplier and time elapsed
                $lastUpdate = strtotime($timer['updated_at']);
                $currentTime = time();
                $elapsedSeconds = $currentTime - $lastUpdate;
                
                // Apply multiplier
                $timeReduction = $elapsedSeconds * $timer['multiplier'];
                $newValue = max(0, $timer['value'] - $timeReduction);
                
                // Update timer
                $this->db->execute(
                    "UPDATE timers SET value = ?, updated_at = CURRENT_TIMESTAMP WHERE timer_id = ?",
                    [$newValue, $timer['timer_id']]
                );
                
                $updatedCount++;
                
                // Check if timer completed
                if ($newValue <= 0 && $timer['value'] > 0) {
                    $this->completeTimer($timer);
                    $completedCount++;
                }
            }
            
            $this->log("Timer updates completed: $updatedCount updated, $completedCount completed");
            
        } catch (Exception $e) {
            $this->log("Error updating timers: " . $e->getMessage());
        }
    }
    
    /**
     * Handle timer completion
     */
    private function completeTimer($timer) {
        try {
            // Mark timer as completed
            $this->db->execute(
                "UPDATE timers SET is_running = 0, completed_at = CURRENT_TIMESTAMP WHERE timer_id = ?",
                [$timer['timer_id']]
            );
            
            // Execute associated trigger if exists
            if ($timer['trigger_id']) {
                $this->executeTrigger($timer['trigger_id'], $timer);
            }
            
            $this->log("Timer {$timer['timer_id']} ({$timer['name']}) completed");
            
        } catch (Exception $e) {
            $this->log("Error completing timer {$timer['timer_id']}: " . $e->getMessage());
        }
    }
    
    /**
     * Execute trigger
     */
    private function executeTrigger($triggerId, $context = []) {
        try {
            $trigger = $this->db->fetchOne(
                "SELECT * FROM triggers WHERE trigger_id = ? AND is_active = 1",
                [$triggerId]
            );
            
            if (!$trigger) {
                return;
            }
            
            $parameters = json_decode($trigger['parameters'], true) ?? [];
            
            // Execute trigger based on action type
            switch ($trigger['action_type']) {
                case 'timer':
                    $this->handleTimerTrigger($trigger, $parameters, $context);
                    break;
                case 'event':
                    $this->handleEventTrigger($trigger, $parameters, $context);
                    break;
                case 'condition':
                    $this->handleConditionTrigger($trigger, $parameters, $context);
                    break;
                case 'function':
                    $this->handleFunctionTrigger($trigger, $parameters, $context);
                    break;
            }
            
            $this->log("Trigger {$triggerId} ({$trigger['name']}) executed");
            
        } catch (Exception $e) {
            $this->log("Error executing trigger $triggerId: " . $e->getMessage());
        }
    }
    
    /**
     * Handle timer trigger
     */
    private function handleTimerTrigger($trigger, $parameters, $context) {
        // Create new timer if specified
        if (isset($parameters['create_timer'])) {
            $this->db->execute(
                "INSERT INTO timers (game_id, player_id, name, value, initial_value, multiplier, is_running) VALUES (?, ?, ?, ?, ?, ?, 1)",
                [
                    $trigger['game_id'],
                    $context['player_id'] ?? null,
                    $parameters['create_timer']['name'] ?? 'auto_timer',
                    $parameters['create_timer']['value'] ?? 60,
                    $parameters['create_timer']['value'] ?? 60,
                    $parameters['create_timer']['multiplier'] ?? 1.0
                ]
            );
        }
    }
    
    /**
     * Handle event trigger
     */
    private function handleEventTrigger($trigger, $parameters, $context) {
        // Send notification about event
        if (isset($parameters['notify_players'])) {
            $game = $this->db->fetchOne(
                "SELECT user_id FROM games WHERE game_id = ?",
                [$trigger['game_id']]
            );
            
            if ($game) {
                $this->notificationManager->queueNotification(
                    $game['user_id'],
                    'Game Event Triggered',
                    "Event '{$trigger['name']}' was triggered in your game.",
                    'game_event',
                    ['trigger_name' => $trigger['name'], 'game_id' => $trigger['game_id']]
                );
            }
        }
    }
    
    /**
     * Handle condition trigger
     */
    private function handleConditionTrigger($trigger, $parameters, $context) {
        // Evaluate condition and execute actions
        if (isset($parameters['condition']) && isset($parameters['actions'])) {
            // This is a simplified condition evaluation
            // In a real implementation, you'd want a proper expression parser
            $condition = $parameters['condition'];
            $actions = $parameters['actions'];
            
            // For now, just log the condition
            $this->log("Condition trigger evaluated: $condition");
        }
    }
    
    /**
     * Handle function trigger
     */
    private function handleFunctionTrigger($trigger, $parameters, $context) {
        // Execute custom function
        if (isset($parameters['function_name'])) {
            $functionName = $parameters['function_name'];
            $this->log("Function trigger executed: $functionName");
            
            // You can add custom function implementations here
        }
    }
    
    /**
     * Check subscription statuses (runs daily)
     */
    public function checkSubscriptionStatuses() {
        $this->log("Starting subscription status check...");
        
        try {
            // Check for expired subscriptions
            $expiredSubscriptions = $this->db->fetchAll(
                "SELECT s.*, u.email FROM subscriptions s 
                 JOIN users u ON s.user_id = u.user_id 
                 WHERE s.status = 'active' AND s.expires_at IS NOT NULL AND s.expires_at < NOW()"
            );
            
            foreach ($expiredSubscriptions as $subscription) {
                // Mark as past due
                $this->db->execute(
                    "UPDATE subscriptions SET status = 'past_due' WHERE subscription_id = ?",
                    [$subscription['subscription_id']]
                );
                
                // Downgrade user to Free plan
                $freeLimits = Config::get('PLAN_LIMITS')['Free'];
                $this->db->execute(
                    "UPDATE users SET plan_type = 'Free', memory_limit_mb = ? WHERE user_id = ?",
                    [$freeLimits['memory_mb'], $subscription['user_id']]
                );
                
                // Send notification
                $this->notificationManager->queueNotification(
                    $subscription['user_id'],
                    'Subscription Expired',
                    'Your subscription has expired and your account has been downgraded to the Free plan. Please renew your subscription to continue using premium features.',
                    'subscription_expired'
                );
                
                $this->log("Subscription {$subscription['subscription_id']} marked as expired for user {$subscription['user_id']}");
            }
            
            // Check PayPal subscription statuses
            $this->checkPayPalSubscriptions();
            
            $this->log("Subscription status check completed: " . count($expiredSubscriptions) . " expired");
            
        } catch (Exception $e) {
            $this->log("Error checking subscription statuses: " . $e->getMessage());
        }
    }
    
    /**
     * Check PayPal subscription statuses
     */
    private function checkPayPalSubscriptions() {
        $paypalSubscriptions = $this->db->fetchAll(
            "SELECT * FROM subscriptions WHERE status = 'active' AND paypal_subscription_id IS NOT NULL AND paypal_subscription_id NOT LIKE 'PAYNET_%' AND paypal_subscription_id NOT LIKE 'MAIB_%'"
        );
        
        foreach ($paypalSubscriptions as $subscription) {
            try {
                // This would normally check PayPal API for subscription status
                // For now, we'll just log it
                $this->log("Checking PayPal subscription: {$subscription['paypal_subscription_id']}");
                
            } catch (Exception $e) {
                $this->log("Error checking PayPal subscription {$subscription['paypal_subscription_id']}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Process notification queue (runs every 5 minutes)
     */
    public function processNotifications() {
        $this->log("Starting notification processing...");
        
        try {
            $result = $this->notificationManager->processQueuedNotifications(50);
            
            $this->log("Notification processing completed: {$result['processed']} sent, {$result['failed']} failed");
            
            // Send limit warnings
            $warnings = $this->notificationManager->sendLimitWarnings();
            $this->log("Limit warnings sent: {$warnings['memory_warnings']} memory, {$warnings['api_warnings']} API");
            
        } catch (Exception $e) {
            $this->log("Error processing notifications: " . $e->getMessage());
        }
    }
    
    /**
     * Update system statistics
     */
    public function updateSystemStats() {
        $this->log("Updating system statistics...");
        
        try {
            // Calculate current stats
            $totalUsers = $this->db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
            $totalMemory = $this->db->fetchOne("SELECT SUM(memory_used_mb) as total FROM users")['total'] ?? 0;
            $totalApiCalls = $this->db->fetchOne("SELECT SUM(api_calls_today) as total FROM users")['total'] ?? 0;
            $activeGames = $this->db->fetchOne("SELECT COUNT(*) as count FROM games WHERE is_active = 1")['count'];
            $activePlayers = $this->db->fetchOne("SELECT COUNT(*) as count FROM players WHERE is_online = 1")['count'];
            
            // Update system stats
            $this->db->execute(
                "INSERT INTO system_stats (total_users, total_memory_mb, total_api_calls_today, active_games, active_players) VALUES (?, ?, ?, ?, ?)",
                [$totalUsers, $totalMemory, $totalApiCalls, $activeGames, $activePlayers]
            );
            
            // Clean up old stats (keep only last 30 days)
            $this->db->execute(
                "DELETE FROM system_stats WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
            
            $this->log("System statistics updated successfully");
            
        } catch (Exception $e) {
            $this->log("Error updating system statistics: " . $e->getMessage());
        }
    }
    
    /**
     * Clean up old data
     */
    public function cleanupOldData() {
        $this->log("Starting data cleanup...");
        
        try {
            // Clean up old API logs (keep only last 7 days)
            $deletedLogs = $this->db->execute(
                "DELETE FROM api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );
            
            // Clean up old notifications (keep only last 30 days)
            $deletedNotifications = $this->db->execute(
                "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
            
            // Set inactive players offline if not active for 24 hours
            $inactivePlayers = $this->db->execute(
                "UPDATE players SET is_online = 0 WHERE last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR) AND is_online = 1"
            );
            
            $this->log("Data cleanup completed: API logs cleaned, notifications cleaned, inactive players set offline");
            
        } catch (Exception $e) {
            $this->log("Error during data cleanup: " . $e->getMessage());
        }
    }
    
    /**
     * Health check
     */
    public function healthCheck() {
        $this->log("Running health check...");
        
        try {
            // Check database connection
            $this->db->fetchOne("SELECT 1");
            
            // Check system limits
            $systemStats = $this->db->fetchOne(
                "SELECT * FROM system_stats ORDER BY recorded_at DESC LIMIT 1"
            );
            
            if ($systemStats) {
                $memoryUsage = ($systemStats['total_memory_mb'] / Config::get('SYSTEM_MAX_MEMORY_MB')) * 100;
                $userUsage = ($systemStats['total_users'] / Config::get('SYSTEM_MAX_USERS')) * 100;
                
                if ($memoryUsage > 90) {
                    $this->log("WARNING: System memory usage is at {$memoryUsage}%");
                }
                
                if ($userUsage > 90) {
                    $this->log("WARNING: System user usage is at {$userUsage}%");
                }
            }
            
            $this->log("Health check completed successfully");
            
        } catch (Exception $e) {
            $this->log("CRITICAL: Health check failed: " . $e->getMessage());
        }
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $cronHandler = new CronHandler();
    
    $command = $argv[1] ?? '';
    
    switch ($command) {
        case 'reset-api-calls':
            $cronHandler->resetDailyApiCalls();
            break;
        case 'update-timers':
            $cronHandler->updateTimers();
            break;
        case 'check-subscriptions':
            $cronHandler->checkSubscriptionStatuses();
            break;
        case 'process-notifications':
            $cronHandler->processNotifications();
            break;
        case 'update-stats':
            $cronHandler->updateSystemStats();
            break;
        case 'cleanup':
            $cronHandler->cleanupOldData();
            break;
        case 'health-check':
            $cronHandler->healthCheck();
            break;
        case 'all':
            $cronHandler->updateTimers();
            $cronHandler->processNotifications();
            $cronHandler->updateSystemStats();
            break;
        default:
            echo "Usage: php cron-handler.php [command]\n";
            echo "Commands:\n";
            echo "  reset-api-calls     - Reset daily API call counters (daily at 00:00 UTC)\n";
            echo "  update-timers       - Update game timers (every minute)\n";
            echo "  check-subscriptions - Check subscription statuses (daily)\n";
            echo "  process-notifications - Process notification queue (every 5 minutes)\n";
            echo "  update-stats        - Update system statistics\n";
            echo "  cleanup             - Clean up old data\n";
            echo "  health-check        - Run system health check\n";
            echo "  all                 - Run all frequent tasks\n";
            break;
    }
}
?>
