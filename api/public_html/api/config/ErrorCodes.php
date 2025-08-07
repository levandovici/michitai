<?php
/**
 * Error Codes and Messages for Multiplayer API
 * Professional debugging and error tracking system
 */

class ErrorCodes {
    // Authentication Errors (1000-1999)
    const AUTH_INVALID_CREDENTIALS = 1001;
    const AUTH_TOKEN_EXPIRED = 1002;
    const AUTH_TOKEN_INVALID = 1003;
    const AUTH_USER_NOT_FOUND = 1004;
    const AUTH_EMAIL_ALREADY_EXISTS = 1005;
    const AUTH_PASSWORD_TOO_WEAK = 1006;
    const AUTH_EMAIL_NOT_VERIFIED = 1007;
    const AUTH_ACCOUNT_LOCKED = 1008;
    const AUTH_RATE_LIMITED = 1009;
    const AUTH_MISSING_PARAMETERS = 1010;

    // Registration Errors (2000-2999)
    const REG_INVALID_EMAIL = 2001;
    const REG_EMAIL_EXISTS = 2002;
    const REG_PASSWORD_REQUIREMENTS = 2003;
    const REG_TERMS_NOT_ACCEPTED = 2004;
    const REG_EMAIL_SEND_FAILED = 2005;
    const REG_DATABASE_ERROR = 2006;
    const REG_VALIDATION_FAILED = 2007;

    // API Errors (3000-3999)
    const API_METHOD_NOT_ALLOWED = 3001;
    const API_ENDPOINT_NOT_FOUND = 3002;
    const API_INVALID_JSON = 3003;
    const API_MISSING_HEADERS = 3004;
    const API_RATE_LIMIT_EXCEEDED = 3005;
    const API_MAINTENANCE_MODE = 3006;
    const API_VERSION_DEPRECATED = 3007;

    // Database Errors (4000-4999)
    const DB_CONNECTION_FAILED = 4001;
    const DB_QUERY_FAILED = 4002;
    const DB_TRANSACTION_FAILED = 4003;
    const DB_CONSTRAINT_VIOLATION = 4004;
    const DB_TIMEOUT = 4005;

    // System Errors (5000-5999)
    const SYS_INTERNAL_ERROR = 5001;
    const SYS_SERVICE_UNAVAILABLE = 5002;
    const SYS_CONFIGURATION_ERROR = 5003;
    const SYS_FILE_NOT_FOUND = 5004;
    const SYS_PERMISSION_DENIED = 5005;
    const SYS_MEMORY_LIMIT = 5006;
    const SYS_TIMEOUT = 5007;

    // Payment Errors (6000-6999)
    const PAY_INVALID_AMOUNT = 6001;
    const PAY_PAYMENT_FAILED = 6002;
    const PAY_SUBSCRIPTION_EXPIRED = 6003;
    const PAY_INSUFFICIENT_FUNDS = 6004;
    const PAY_WEBHOOK_VERIFICATION_FAILED = 6005;

    // Game Logic Errors (7000-7999)
    const GAME_NOT_FOUND = 7001;
    const GAME_PLAYER_LIMIT_REACHED = 7002;
    const GAME_INVALID_STATE = 7003;
    const GAME_PERMISSION_DENIED = 7004;
    const GAME_LOGIC_ERROR = 7005;

    // Error Messages
    private static $messages = [
        // Authentication
        self::AUTH_INVALID_CREDENTIALS => 'Invalid email or password',
        self::AUTH_TOKEN_EXPIRED => 'Authentication token has expired',
        self::AUTH_TOKEN_INVALID => 'Invalid authentication token',
        self::AUTH_USER_NOT_FOUND => 'User account not found',
        self::AUTH_EMAIL_ALREADY_EXISTS => 'Email address is already registered',
        self::AUTH_PASSWORD_TOO_WEAK => 'Password does not meet security requirements',
        self::AUTH_EMAIL_NOT_VERIFIED => 'Email address must be verified before login',
        self::AUTH_ACCOUNT_LOCKED => 'Account has been temporarily locked',
        self::AUTH_RATE_LIMITED => 'Too many authentication attempts, please try again later',
        self::AUTH_MISSING_PARAMETERS => 'Required authentication parameters are missing',

        // Registration
        self::REG_INVALID_EMAIL => 'Please provide a valid email address',
        self::REG_EMAIL_EXISTS => 'An account with this email already exists',
        self::REG_PASSWORD_REQUIREMENTS => 'Password must be at least 8 characters with uppercase, lowercase, and numbers',
        self::REG_TERMS_NOT_ACCEPTED => 'You must accept the terms of service to register',
        self::REG_EMAIL_SEND_FAILED => 'Failed to send verification email, please try again',
        self::REG_DATABASE_ERROR => 'Database error during registration, please try again',
        self::REG_VALIDATION_FAILED => 'Registration data validation failed',

        // API
        self::API_METHOD_NOT_ALLOWED => 'HTTP method not allowed for this endpoint',
        self::API_ENDPOINT_NOT_FOUND => 'API endpoint not found',
        self::API_INVALID_JSON => 'Invalid JSON format in request body',
        self::API_MISSING_HEADERS => 'Required headers are missing',
        self::API_RATE_LIMIT_EXCEEDED => 'API rate limit exceeded, please slow down',
        self::API_MAINTENANCE_MODE => 'API is currently under maintenance',
        self::API_VERSION_DEPRECATED => 'This API version is deprecated',

        // Database
        self::DB_CONNECTION_FAILED => 'Database connection failed',
        self::DB_QUERY_FAILED => 'Database query execution failed',
        self::DB_TRANSACTION_FAILED => 'Database transaction failed',
        self::DB_CONSTRAINT_VIOLATION => 'Database constraint violation',
        self::DB_TIMEOUT => 'Database operation timed out',

        // System
        self::SYS_INTERNAL_ERROR => 'Internal server error occurred',
        self::SYS_SERVICE_UNAVAILABLE => 'Service temporarily unavailable',
        self::SYS_CONFIGURATION_ERROR => 'Server configuration error',
        self::SYS_FILE_NOT_FOUND => 'Required file not found',
        self::SYS_PERMISSION_DENIED => 'Permission denied',
        self::SYS_MEMORY_LIMIT => 'Memory limit exceeded',
        self::SYS_TIMEOUT => 'Operation timed out',

        // Payment
        self::PAY_INVALID_AMOUNT => 'Invalid payment amount',
        self::PAY_PAYMENT_FAILED => 'Payment processing failed',
        self::PAY_SUBSCRIPTION_EXPIRED => 'Subscription has expired',
        self::PAY_INSUFFICIENT_FUNDS => 'Insufficient funds',
        self::PAY_WEBHOOK_VERIFICATION_FAILED => 'Payment webhook verification failed',

        // Game Logic
        self::GAME_NOT_FOUND => 'Game not found',
        self::GAME_PLAYER_LIMIT_REACHED => 'Maximum number of players reached',
        self::GAME_INVALID_STATE => 'Invalid game state',
        self::GAME_PERMISSION_DENIED => 'Permission denied for this game action',
        self::GAME_LOGIC_ERROR => 'Game logic error occurred',
    ];

    /**
     * Get error message by code
     */
    public static function getMessage($code) {
        return self::$messages[$code] ?? 'Unknown error occurred';
    }

    /**
     * Create standardized error response
     */
    public static function createErrorResponse($code, $details = null, $debugInfo = null) {
        $response = [
            'success' => false,
            'error_code' => $code,
            'error_message' => self::getMessage($code),
            'timestamp' => date('c')
        ];

        if ($details) {
            $response['details'] = $details;
        }

        // Include debug info only in development
        if ($debugInfo && (defined('DEBUG_MODE') && DEBUG_MODE)) {
            $response['debug'] = $debugInfo;
        }

        return $response;
    }

    /**
     * Create standardized success response
     */
    public static function createSuccessResponse($data = null, $message = null) {
        $response = [
            'success' => true,
            'timestamp' => date('c')
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data) {
            $response['data'] = $data;
        }

        return $response;
    }

    /**
     * Log error with context
     */
    public static function logError($code, $context = [], $exception = null) {
        $logData = [
            'timestamp' => date('c'),
            'error_code' => $code,
            'message' => self::getMessage($code),
            'context' => $context,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];

        if ($exception) {
            $logData['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }

        // Log to file in logs directory
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        error_log('API Error: ' . json_encode($logData), 3, $logDir . '/api_errors.log');
    }
}
