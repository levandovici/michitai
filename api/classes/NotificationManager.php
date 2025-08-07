<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Notification Management Class
 * Handles email notifications (Free/Standard) and Slack notifications (Pro)
 * Supports PHPMailer for email and webhook for Slack
 */
class NotificationManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Queue notification for later processing
     */
    public function queueNotification($userId, $subject, $message, $type = 'general', $data = []) {
        // Get user plan to determine notification method
        $user = $this->db->fetchOne(
            "SELECT plan_type, email FROM users WHERE user_id = ?",
            [$userId]
        );
        
        if (!$user) {
            throw new Exception("User not found");
        }
        
        // Determine notification type based on plan
        $notificationType = ($user['plan_type'] === 'Pro') ? 'slack' : 'email';
        
        // Queue notification
        $this->db->execute(
            "INSERT INTO notifications (user_id, type, subject, message, data) VALUES (?, ?, ?, ?, ?)",
            [$userId, $notificationType, $subject, $message, json_encode($data)]
        );
        
        return [
            'notification_id' => $this->db->lastInsertId(),
            'type' => $notificationType,
            'queued_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Send email notification using PHPMailer
     */
    private function sendEmailNotification($notification) {
        // Get user email
        $user = $this->db->fetchOne(
            "SELECT email FROM users WHERE user_id = ?",
            [$notification['user_id']]
        );
        
        if (!$user) {
            throw new Exception("User not found");
        }
        
        // PHPMailer configuration
        $smtpHost = $_ENV['SMTP_HOST'] ?? '';
        $smtpPort = $_ENV['SMTP_PORT'] ?? 587;
        $smtpUsername = $_ENV['SMTP_USERNAME'] ?? '';
        $smtpPassword = $_ENV['SMTP_PASSWORD'] ?? '';
        $fromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? '';
        $fromName = $_ENV['SMTP_FROM_NAME'] ?? 'Multiplayer API Constructor';
        
        if (empty($smtpHost) || empty($smtpUsername)) {
            throw new Exception("SMTP configuration not set");
        }
        
        // Create email content
        $htmlContent = $this->createEmailTemplate($notification['subject'], $notification['message'], $notification['data']);
        
        // Send email using curl (simplified SMTP)
        $emailData = [
            'to' => $user['email'],
            'subject' => $notification['subject'],
            'html' => $htmlContent,
            'from_email' => $fromEmail,
            'from_name' => $fromName
        ];
        
        // Use a simple email service API or SMTP
        return $this->sendViaSMTP($emailData);
    }
    
    /**
     * Send email via SMTP (simplified implementation)
     */
    private function sendViaSMTP($emailData) {
        // This is a simplified implementation
        // In production, use PHPMailer or similar library
        
        $to = $emailData['to'];
        $subject = $emailData['subject'];
        $message = $emailData['html'];
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $emailData['from_name'] . ' <' . $emailData['from_email'] . '>',
            'Reply-To: ' . $emailData['from_email'],
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $success = mail($to, $subject, $message, implode("\r\n", $headers));
        
        if (!$success) {
            throw new Exception("Failed to send email");
        }
        
        return true;
    }
    
    /**
     * Send Slack notification via webhook
     */
    private function sendSlackNotification($notification) {
        $webhookUrl = $_ENV['SLACK_WEBHOOK_URL'] ?? '';
        
        if (empty($webhookUrl)) {
            throw new Exception("Slack webhook URL not configured");
        }
        
        // Get user info
        $user = $this->db->fetchOne(
            "SELECT email FROM users WHERE user_id = ?",
            [$notification['user_id']]
        );
        
        $data = json_decode($notification['data'], true) ?? [];
        
        // Create Slack message
        $slackMessage = [
            'text' => $notification['subject'],
            'attachments' => [
                [
                    'color' => $this->getSlackColor($data['type'] ?? 'general'),
                    'fields' => [
                        [
                            'title' => 'Message',
                            'value' => $notification['message'],
                            'short' => false
                        ],
                        [
                            'title' => 'User',
                            'value' => $user['email'] ?? 'Unknown',
                            'short' => true
                        ],
                        [
                            'title' => 'Time',
                            'value' => date('Y-m-d H:i:s'),
                            'short' => true
                        ]
                    ]
                ]
            ]
        ];
        
        // Add additional fields based on notification type
        if (isset($data['type'])) {
            switch ($data['type']) {
                case 'subscription_activated':
                case 'subscription_canceled':
                    $slackMessage['attachments'][0]['fields'][] = [
                        'title' => 'Plan Type',
                        'value' => $data['plan_type'] ?? 'Unknown',
                        'short' => true
                    ];
                    break;
                case 'payment_completed':
                    $slackMessage['attachments'][0]['fields'][] = [
                        'title' => 'Amount',
                        'value' => '$' . ($data['amount'] ?? '0'),
                        'short' => true
                    ];
                    break;
                case 'limit_warning':
                    $slackMessage['attachments'][0]['fields'][] = [
                        'title' => 'Usage',
                        'value' => ($data['usage_percent'] ?? 0) . '%',
                        'short' => true
                    ];
                    break;
            }
        }
        
        // Send to Slack
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $webhookUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($slackMessage));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Failed to send Slack notification: " . $response);
        }
        
        return true;
    }
    
    /**
     * Get Slack color based on notification type
     */
    private function getSlackColor($type) {
        switch ($type) {
            case 'subscription_activated':
            case 'payment_completed':
                return 'good'; // Green
            case 'subscription_canceled':
            case 'limit_exceeded':
                return 'danger'; // Red
            case 'limit_warning':
                return 'warning'; // Yellow
            default:
                return '#36a64f'; // Default blue
        }
    }
    
    /**
     * Create HTML email template
     */
    private function createEmailTemplate($subject, $message, $data) {
        $dataArray = is_string($data) ? json_decode($data, true) : $data;
        $type = $dataArray['type'] ?? 'general';
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($subject) . '</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4f46e5; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
        .footer { text-align: center; margin-top: 20px; color: #6b7280; font-size: 14px; }
        .button { display: inline-block; background: #4f46e5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
        .alert { padding: 15px; border-radius: 6px; margin: 15px 0; }
        .alert-success { background: #d1fae5; border-left: 4px solid #10b981; }
        .alert-warning { background: #fef3c7; border-left: 4px solid #f59e0b; }
        .alert-danger { background: #fee2e2; border-left: 4px solid #ef4444; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Multiplayer API Constructor</h1>
        <p>' . htmlspecialchars($subject) . '</p>
    </div>
    <div class="content">';
        
        // Add alert based on type
        switch ($type) {
            case 'subscription_activated':
            case 'payment_completed':
                $html .= '<div class="alert alert-success">';
                break;
            case 'limit_warning':
                $html .= '<div class="alert alert-warning">';
                break;
            case 'subscription_canceled':
            case 'limit_exceeded':
                $html .= '<div class="alert alert-danger">';
                break;
            default:
                $html .= '<div class="alert alert-success">';
        }
        
        $html .= '<p>' . nl2br(htmlspecialchars($message)) . '</p>
        </div>';
        
        // Add additional information based on type
        if (isset($dataArray['plan_type'])) {
            $html .= '<p><strong>Plan:</strong> ' . htmlspecialchars($dataArray['plan_type']) . '</p>';
        }
        
        if (isset($dataArray['amount'])) {
            $html .= '<p><strong>Amount:</strong> $' . htmlspecialchars($dataArray['amount']) . '</p>';
        }
        
        if (isset($dataArray['usage_percent'])) {
            $html .= '<p><strong>Usage:</strong> ' . htmlspecialchars($dataArray['usage_percent']) . '%</p>';
        }
        
        $html .= '<p>If you have any questions, please contact our support team.</p>
        <a href="https://yourdomain.com/dashboard" class="button">Go to Dashboard</a>
    </div>
    <div class="footer">
        <p>&copy; ' . date('Y') . ' Multiplayer API Constructor. All rights reserved.</p>
        <p>MAIB Bank Transfer: SWIFT Code AGRNMD2X</p>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Process queued notifications
     */
    public function processQueuedNotifications($limit = 50) {
        $notifications = $this->db->fetchAll(
            "SELECT * FROM notifications WHERE status = 'pending' ORDER BY created_at ASC LIMIT ?",
            [$limit]
        );
        
        $processed = 0;
        $failed = 0;
        
        foreach ($notifications as $notification) {
            try {
                // Mark as processing
                $this->db->execute(
                    "UPDATE notifications SET status = 'processing', attempts = attempts + 1 WHERE notification_id = ?",
                    [$notification['notification_id']]
                );
                
                // Send notification based on type
                if ($notification['type'] === 'email') {
                    $this->sendEmailNotification($notification);
                } elseif ($notification['type'] === 'slack') {
                    $this->sendSlackNotification($notification);
                }
                
                // Mark as sent
                $this->db->execute(
                    "UPDATE notifications SET status = 'sent', sent_at = CURRENT_TIMESTAMP WHERE notification_id = ?",
                    [$notification['notification_id']]
                );
                
                $processed++;
                
            } catch (Exception $e) {
                // Mark as failed
                $this->db->execute(
                    "UPDATE notifications SET status = 'failed' WHERE notification_id = ?",
                    [$notification['notification_id']]
                );
                
                error_log("Failed to send notification {$notification['notification_id']}: " . $e->getMessage());
                $failed++;
            }
        }
        
        return [
            'processed' => $processed,
            'failed' => $failed,
            'total' => count($notifications)
        ];
    }
    
    /**
     * Send immediate notification (bypass queue)
     */
    public function sendImmediateNotification($userId, $subject, $message, $type = 'general', $data = []) {
        // Queue the notification first
        $result = $this->queueNotification($userId, $subject, $message, $type, $data);
        
        // Process it immediately
        $notification = $this->db->fetchOne(
            "SELECT * FROM notifications WHERE notification_id = ?",
            [$result['notification_id']]
        );
        
        try {
            if ($notification['type'] === 'email') {
                $this->sendEmailNotification($notification);
            } elseif ($notification['type'] === 'slack') {
                $this->sendSlackNotification($notification);
            }
            
            // Mark as sent
            $this->db->execute(
                "UPDATE notifications SET status = 'sent', sent_at = CURRENT_TIMESTAMP WHERE notification_id = ?",
                [$notification['notification_id']]
            );
            
            return ['success' => true, 'sent_immediately' => true];
            
        } catch (Exception $e) {
            // Mark as failed
            $this->db->execute(
                "UPDATE notifications SET status = 'failed' WHERE notification_id = ?",
                [$notification['notification_id']]
            );
            
            throw $e;
        }
    }
    
    /**
     * Send limit warning notifications
     */
    public function sendLimitWarnings() {
        // Check memory usage warnings (80% threshold)
        $memoryWarnings = $this->db->fetchAll(
            "SELECT user_id, email, plan_type, memory_used_mb, memory_limit_mb, 
                    (memory_used_mb / memory_limit_mb * 100) as usage_percent 
             FROM users 
             WHERE (memory_used_mb / memory_limit_mb) >= 0.8 
             AND user_id NOT IN (
                 SELECT user_id FROM notifications 
                 WHERE subject LIKE '%Memory Usage Warning%' 
                 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             )"
        );
        
        foreach ($memoryWarnings as $user) {
            $this->queueNotification(
                $user['user_id'],
                'Memory Usage Warning',
                "Your memory usage is at {$user['usage_percent']}% ({$user['memory_used_mb']}MB of {$user['memory_limit_mb']}MB). Consider upgrading your plan or optimizing your data.",
                'limit_warning',
                [
                    'type' => 'memory_warning',
                    'usage_percent' => round($user['usage_percent'], 1),
                    'used_mb' => $user['memory_used_mb'],
                    'limit_mb' => $user['memory_limit_mb']
                ]
            );
        }
        
        // Check API call warnings (80% threshold)
        $apiWarnings = $this->db->fetchAll(
            "SELECT u.user_id, u.email, u.plan_type, u.api_calls_today,
                    CASE u.plan_type 
                        WHEN 'Free' THEN 1000
                        WHEN 'Standard' THEN 10000
                        WHEN 'Pro' THEN 1000000
                    END as api_limit,
                    (u.api_calls_today / CASE u.plan_type 
                        WHEN 'Free' THEN 1000
                        WHEN 'Standard' THEN 10000
                        WHEN 'Pro' THEN 1000000
                    END * 100) as usage_percent
             FROM users u
             WHERE (u.api_calls_today / CASE u.plan_type 
                        WHEN 'Free' THEN 1000
                        WHEN 'Standard' THEN 10000
                        WHEN 'Pro' THEN 1000000
                    END) >= 0.8
             AND u.user_id NOT IN (
                 SELECT user_id FROM notifications 
                 WHERE subject LIKE '%API Usage Warning%' 
                 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             )"
        );
        
        foreach ($apiWarnings as $user) {
            $this->queueNotification(
                $user['user_id'],
                'API Usage Warning',
                "Your API usage is at {$user['usage_percent']}% ({$user['api_calls_today']} of {$user['api_limit']} calls). Consider upgrading your plan if you need more API calls.",
                'limit_warning',
                [
                    'type' => 'api_warning',
                    'usage_percent' => round($user['usage_percent'], 1),
                    'calls_used' => $user['api_calls_today'],
                    'calls_limit' => $user['api_limit']
                ]
            );
        }
        
        return [
            'memory_warnings' => count($memoryWarnings),
            'api_warnings' => count($apiWarnings)
        ];
    }
}
?>
