<?php
/**
 * Notification Manager Stub Class for Development
 */

class NotificationManager {
    public function __construct() {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("NotificationManager: Stub class initialized");
        }
    }
    
    // Stub methods for development
    public function sendEmail($to, $subject, $body) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("NotificationManager: Simulating email send to $to with subject: $subject");
        }
        return ['success' => true, 'message' => 'Email simulated in development mode'];
    }
    
    public function sendSlackNotification($message) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("NotificationManager: Simulating Slack notification: $message");
        }
        return ['success' => true, 'message' => 'Slack notification simulated in development mode'];
    }
}
