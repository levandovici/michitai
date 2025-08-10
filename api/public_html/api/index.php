<?php
/**
 * Multiplayer API Entry Point - Simplified Structure
 * All backend code now in public_html/api/ secured with .htaccess
 */

// Enable debug mode for development
define('DEBUG_MODE', true);

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS headers for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Token, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Set paths - everything is now in the api directory
$apiPath = __DIR__;
$logPath = $apiPath . '/logs';

// Create directories if they don't exist
if (!is_dir($logPath)) {
    mkdir($logPath, 0755, true);
}

// Debug logging
if (DEBUG_MODE) {
    error_log("API Debug: API path = $apiPath");
    error_log("API Debug: Log path = $logPath");
}

// Error handling with secure logging
set_error_handler(function($severity, $message, $file, $line) use ($logPath) {
    $logFile = $logPath . '/error.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    error_log(date('Y-m-d H:i:s') . " PHP Error: $message in $file on line $line\n", 3, $logFile);
    
    if (DEBUG_MODE) {
        error_log("API Debug: PHP Error - $message in $file:$line");
    }
});

try {
    // Include autoloader if available
    if (file_exists($apiPath . '/vendor/autoload.php')) {
        require_once $apiPath . '/vendor/autoload.php';
        if (DEBUG_MODE) {
            error_log("API Debug: Composer autoloader loaded");
        }
    }

    // Include configuration files
    $configFiles = [
        $apiPath . '/config/ErrorCodes.php',
        $apiPath . '/config/database.php'
    ];
    
    foreach ($configFiles as $file) {
        if (file_exists($file)) {
            require_once $file;
            if (DEBUG_MODE) {
                error_log("API Debug: Loaded config file: $file");
            }
        } else if (DEBUG_MODE) {
            error_log("API Debug: Config file not found: $file");
        }
    }

    // Include class files
    $classFiles = [
        $apiPath . '/classes/Auth.php',
        $apiPath . '/classes/GameManager.php',
        $apiPath . '/classes/PlayerManager.php',
        $apiPath . '/classes/PaymentManager.php',
        $apiPath . '/classes/NotificationManager.php'
    ];
    
    foreach ($classFiles as $file) {
        if (file_exists($file)) {
            require_once $file;
            if (DEBUG_MODE) {
                error_log("API Debug: Loaded class file: $file");
            }
        } else {
            if (DEBUG_MODE) {
                error_log("API Debug: Class file not found: $file");
            }
            // Create stub classes if files don't exist
            $className = basename($file, '.php');
            if (!class_exists($className)) {
                eval("class $className { public function __construct() {} }");
                if (DEBUG_MODE) {
                    error_log("API Debug: Created stub class: $className");
                }
            }
        }
    }

    // API Router class
    class APIRouter {
        private $auth;
        private $gameManager;
        private $playerManager;
        private $paymentManager;
        private $notificationManager;

        public function __construct() {
            $this->auth = new Auth();
            $this->gameManager = new GameManager();
            $this->playerManager = new PlayerManager();
            $this->paymentManager = new PaymentManager();
            $this->notificationManager = new NotificationManager();
        }

        public function route() {
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $segments = explode('/', trim(str_replace('/api', '', $path), '/'));

            // Log API call
            $token = $_SERVER['HTTP_X_API_TOKEN'] ?? null;
            $this->auth->logApiCall($token, $segments[0] ?? 'unknown');

            // Route to appropriate handler
            switch ($segments[0]) {
                case 'register':
                    $this->handleRegister();
                    break;
                case 'login':
                    $this->handleLogin();
                    break;
                case 'auth':
                    $this->handleAuth($segments, $method);
                    break;
                case 'user':
                    $this->handleUser();
                    break;
                case 'subscribe':
                    $this->handleSubscribe();
                    break;
                case 'subscription':
                    $this->handleSubscription($segments, $method);
                    break;
                case 'game':
                    $this->handleGame($segments, $method);
                    break;
                case 'player':
                    $this->handlePlayer($segments, $method);
                    break;
                case 'room':
                    $this->handleRoom($segments, $method);
                    break;
                case 'community':
                    $this->handleCommunity($segments, $method);
                    break;
                case 'chat':
                    $this->handleChat($segments, $method);
                    break;
                case 'matchmaking':
                    $this->handleMatchmaking($segments, $method);
                    break;
                case 'trigger':
                    $this->handleTrigger($segments, $method);
                    break;
                case 'timer':
                    $this->handleTimer($segments, $method);
                    break;
                case 'monitor':
                    $this->handleMonitor($segments);
                    break;
                case 'webhook':
                    $this->handleWebhook($segments);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint not found']);
            }
        }

        private function handleRegister() {
            try {
                if (DEBUG_MODE) {
                    error_log("APIRouter::handleRegister - Starting registration request");
                }

                // Debug point 1: Check HTTP method
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    if (DEBUG_MODE) {
                        error_log("APIRouter::handleRegister - Invalid method: " . $_SERVER['REQUEST_METHOD']);
                    }
                    http_response_code(405);
                    echo json_encode(ErrorCodes::createErrorResponse(ErrorCodes::API_METHOD_NOT_ALLOWED));
                    return;
                }

                // Debug point 2: Parse JSON input
                $rawInput = file_get_contents('php://input');
                if (DEBUG_MODE) {
                    error_log("APIRouter::handleRegister - Raw input: " . $rawInput);
                }

                $input = json_decode($rawInput, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    if (DEBUG_MODE) {
                        error_log("APIRouter::handleRegister - JSON decode error: " . json_last_error_msg());
                    }
                    http_response_code(400);
                    echo json_encode(ErrorCodes::createErrorResponse(ErrorCodes::API_INVALID_JSON));
                    return;
                }

                // Debug point 3: Validate required fields
                if (!$input || !isset($input['email']) || !isset($input['password'])) {
                    if (DEBUG_MODE) {
                        error_log("APIRouter::handleRegister - Missing required fields. Email: " . 
                                 (isset($input['email']) ? 'present' : 'missing') . 
                                 ", Password: " . (isset($input['password']) ? 'present' : 'missing'));
                    }
                    http_response_code(400);
                    echo json_encode(ErrorCodes::createErrorResponse(ErrorCodes::AUTH_MISSING_PARAMETERS));
                    return;
                }

                // Debug point 4: Call Auth service
                if (DEBUG_MODE) {
                    error_log("APIRouter::handleRegister - Calling Auth::register for email: " . $input['email']);
                }

                $newsletter = $input['newsletter'] ?? false;
                $result = $this->auth->register($input['email'], $input['password'], $newsletter);

                // Debug point 5: Return response
                if (DEBUG_MODE) {
                    error_log("APIRouter::handleRegister - Auth response: " . json_encode($result));
                }

                // Set appropriate HTTP status code
                if (isset($result['success']) && $result['success']) {
                    http_response_code(201); // Created
                } else {
                    http_response_code(400); // Bad Request
                }

                echo json_encode($result);

            } catch (Exception $e) {
                if (DEBUG_MODE) {
                    error_log("APIRouter::handleRegister - Exception: " . $e->getMessage());
                    error_log("APIRouter::handleRegister - Stack trace: " . $e->getTraceAsString());
                }

                ErrorCodes::logError(ErrorCodes::SYS_INTERNAL_ERROR, [
                    'function' => 'handleRegister',
                    'input' => $input ?? null
                ], $e);

                http_response_code(500);
                echo json_encode(ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR, null,
                    DEBUG_MODE ? $e->getMessage() : null));
            }
        }

        private function handleLogin() {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['email']) || !isset($input['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Email and password required']);
                return;
            }

            $result = $this->auth->login($input['email'], $input['password']);
            echo json_encode($result);
        }

        private function handleAuth($segments, $method) {
            switch ($segments[1] ?? '') {
                case 'confirm-email':
                    $this->handleConfirmEmail($method);
                    break;
                case 'resend-confirmation':
                    $this->handleResendConfirmation($method);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Auth endpoint not found']);
            }
        }

        private function handleConfirmEmail($method) {
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['token'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Confirmation token required']);
                return;
            }

            $result = $this->auth->confirmEmail($input['token']);
            echo json_encode($result);
        }

        private function handleResendConfirmation($method) {
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['email'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Email address required']);
                return;
            }

            $result = $this->auth->resendConfirmationEmail($input['email']);
            echo json_encode($result);
        }

        private function handleUser() {
            $token = $_SERVER['HTTP_X_API_TOKEN'] ?? null;
            if (!$this->auth->validateToken($token)) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid token']);
                return;
            }

            $result = $this->auth->getUserProfile($token);
            echo json_encode($result);
        }

        private function handleSubscribe() {
            $token = $_SERVER['HTTP_X_API_TOKEN'] ?? null;
            if (!$this->auth->validateToken($token)) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid token']);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $planType = $input['plan_type'] ?? 'Standard';
            
            $result = $this->paymentManager->createSubscription($token, $planType);
            echo json_encode($result);
        }

        private function handleSubscription($segments, $method) {
            $token = $_SERVER['HTTP_X_API_TOKEN'] ?? null;
            if (!$this->auth->validateToken($token)) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid token']);
                return;
            }

            switch ($segments[1] ?? '') {
                case 'status':
                    $result = $this->paymentManager->getSubscriptionStatus($token);
                    echo json_encode($result);
                    break;
                case 'cancel':
                    if ($method !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method not allowed']);
                        return;
                    }
                    $input = json_decode(file_get_contents('php://input'), true);
                    $result = $this->paymentManager->cancelSubscription($token, $input);
                    echo json_encode($result);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Subscription endpoint not found']);
            }
        }

        private function handleGame($segments, $method) {
            $endpoint = $segments[1] ?? '';
            
            // Get API token from headers
            $token = $_SERVER['HTTP_X_API_TOKEN'] ?? null;
            
            // Require authentication for all game endpoints
            if (!$token || !$this->auth->validateToken($token)) {
                http_response_code(401);
                echo json_encode([
                    'error' => 'Authentication required',
                    'code' => 'AUTH_REQUIRED',
                    'message' => 'A valid API token is required to access this resource'
                ]);
                return;
            }

            // Get user ID from token (works with both api_token and session_token)
            $user = $this->auth->validateToken($token);
            if (!$user) {
                http_response_code(401);
                echo json_encode([
                    'error' => 'Invalid user',
                    'code' => 'INVALID_USER',
                    'message' => 'Could not determine user from token'
                ]);
                return;
            }
            $userId = $user['user_id'];

            // Route to appropriate handler based on endpoint
            switch ($endpoint) {
                case 'create':
                    if ($method !== 'POST') {
                        http_response_code(405);
                        echo json_encode([
                            'error' => 'Method not allowed',
                            'code' => 'METHOD_NOT_ALLOWED',
                            'allowed_methods' => ['POST']
                        ]);
                        return;
                    }
                    
                    // Parse and validate input
                    $input = json_decode(file_get_contents('php://input'), true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        http_response_code(400);
                        echo json_encode([
                            'error' => 'Invalid JSON input',
                            'code' => 'INVALID_JSON'
                        ]);
                        return;
                    }
                    
                    // Ensure required fields are present
                    if (empty($input['name'])) {
                        http_response_code(400);
                        echo json_encode([
                            'error' => 'Game name is required',
                            'code' => 'MISSING_FIELD',
                            'field' => 'name'
                        ]);
                        return;
                    }
                    
                    // Create the game
                    $result = $this->gameManager->createGame($token, $input);
                    echo json_encode($result);
                    break;
                    
                case 'list':
                    if ($method !== 'GET') {
                        http_response_code(405);
                        echo json_encode([
                            'error' => 'Method not allowed',
                            'code' => 'METHOD_NOT_ALLOWED',
                            'allowed_methods' => ['GET']
                        ]);
                        return;
                    }
                    
                    // Get games for the authenticated user
                    $result = $this->gameManager->getGames($token);
                    echo json_encode($result);
                    break;
                case 'get':
                    $gameId = $segments[2] ?? null;
                    if (!$gameId) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Game ID required']);
                        return;
                    }
                    $result = $this->gameManager->getGame($token, $gameId);
                    echo json_encode($result);
                    break;
                case 'update':
                    if ($method !== 'PUT' && $method !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method not allowed']);
                        return;
                    }
                    $gameId = $segments[2] ?? null;
                    if (!$gameId) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Game ID required']);
                        return;
                    }
                    $input = json_decode(file_get_contents('php://input'), true);
                    $result = $this->gameManager->updateGame($token, $gameId, $input);
                    echo json_encode($result);
                    break;
                case 'delete':
                    if ($method !== 'DELETE') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method not allowed']);
                        return;
                    }
                    $gameId = $segments[2] ?? null;
                    if (!$gameId) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Game ID required']);
                        return;
                    }
                    $result = $this->gameManager->deleteGame($token, $gameId);
                    echo json_encode($result);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Game endpoint not found']);
            }
        }

        private function handlePlayer($segments, $method) {
            $token = $_SERVER['HTTP_X_API_TOKEN'] ?? null;
            if (!$this->auth->validateToken($token)) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid token']);
                return;
            }

            switch ($segments[1] ?? '') {
                case 'create':
                    if ($method !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method not allowed']);
                        return;
                    }
                    $input = json_decode(file_get_contents('php://input'), true);
                    $result = $this->playerManager->createPlayer($token, $input);
                    echo json_encode($result);
                    break;
                case 'auth':
                    if ($method !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method not allowed']);
                        return;
                    }
                    $input = json_decode(file_get_contents('php://input'), true);
                    $result = $this->playerManager->authenticatePlayer($input);
                    echo json_encode($result);
                    break;
                case 'update':
                    if ($method !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method not allowed']);
                        return;
                    }
                    $input = json_decode(file_get_contents('php://input'), true);
                    $result = $this->playerManager->updatePlayer($token, $input);
                    echo json_encode($result);
                    break;
                case 'get':
                    $playerId = $segments[2] ?? null;
                    if (!$playerId) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Player ID required']);
                        return;
                    }
                    $result = $this->playerManager->getPlayer($token, $playerId);
                    echo json_encode($result);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Player endpoint not found']);
            }
        }

        private function handleRoom($segments, $method) {
            $token = $_SERVER['HTTP_X_API_TOKEN'] ?? null;
            if (!$this->auth->validateToken($token)) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid token']);
                return;
            }

            switch ($segments[1] ?? '') {
                case 'create':
                    if ($method !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method not allowed']);
                        return;
                    }
                    $input = json_decode(file_get_contents('php://input'), true);
                    $result = $this->gameManager->createRoom($token, $input);
                    echo json_encode($result);
                    break;
                case 'join':
                    if ($method !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method not allowed']);
                        return;
                    }
                    $input = json_decode(file_get_contents('php://input'), true);
                    $result = $this->gameManager->joinRoom($token, $input);
                    echo json_encode($result);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Room endpoint not found']);
            }
        }

        private function handleCommunity($segments, $method) {
            $token = $_SERVER['HTTP_X_API_TOKEN'] ?? null;
            if (!$this->auth->validateToken($token)) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid token']);
                return;
            }

            switch ($segments[1] ?? '') {
                case 'create':
                    if ($method !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method not allowed']);
                        return;
                    }
                    $input = json_decode(file_get_contents('php://input'), true);
                    $result = $this->gameManager->createCommunity($token, $input);
                    echo json_encode($result);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Community endpoint not found']);
            }
        }

        private function handleChat($segments, $method) {
            $token = $_SERVER['HTTP_X_API_TOKEN'] ?? null;
            if (!$this->auth->validateToken($token)) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid token']);
                return;
            }

            switch ($segments[1] ?? '') {
                case 'send':
                    if ($method !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method not allowed']);
                        return;
                    }
                    $input = json_decode(file_get_contents('php://input'), true);
                    $result = $this->gameManager->sendMessage($token, $input);
                    echo json_encode($result);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Chat endpoint not found']);
            }
        }

        private function handleMatchmaking($segments, $method) {
            $token = $_SERVER['HTTP_X_API_TOKEN'] ?? null;
            if (!$this->auth->validateToken($token)) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid token']);
                return;
            }

            switch ($segments[1] ?? '') {
                case 'find':
                    if ($method !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method not allowed']);
                        return;
                    }
                    $input = json_decode(file_get_contents('php://input'), true);
                    $result = $this->gameManager->findMatch($token, $input);
                    echo json_encode($result);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Matchmaking endpoint not found']);
            }
        }

        private function handleTrigger($segments, $method) {
            $token = $_SERVER['HTTP_X_API_TOKEN'] ?? null;
            if (!$this->auth->validateToken($token)) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid token']);
                return;
            }

            switch ($segments[1] ?? '') {
                case 'create':
                    if ($method !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method not allowed']);
                        return;
                    }
                    $input = json_decode(file_get_contents('php://input'), true);
                    $result = $this->gameManager->createTrigger($token, $input);
                    echo json_encode($result);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Trigger endpoint not found']);
            }
        }

        private function handleTimer($segments, $method) {
            $token = $_SERVER['HTTP_X_API_TOKEN'] ?? null;
            if (!$this->auth->validateToken($token)) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid token']);
                return;
            }

            switch ($segments[1] ?? '') {
                case 'create':
                    if ($method !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method not allowed']);
                        return;
                    }
                    $input = json_decode(file_get_contents('php://input'), true);
                    $result = $this->gameManager->createTimer($token, $input);
                    echo json_encode($result);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Timer endpoint not found']);
            }
        }

        private function handleMonitor($segments) {
            $token = $_SERVER['HTTP_X_API_TOKEN'] ?? null;
            if (!$this->auth->validateToken($token)) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid token']);
                return;
            }

            switch ($segments[1] ?? '') {
                case 'user':
                    $result = $this->gameManager->getUserStats($token);
                    echo json_encode($result);
                    break;
                case 'system':
                    $result = $this->gameManager->getSystemStats($token);
                    echo json_encode($result);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Monitor endpoint not found']);
            }
        }

        private function handleWebhook($segments) {
            switch ($segments[1] ?? '') {
                case 'paypal':
                    $result = $this->paymentManager->handlePayPalWebhook();
                    echo json_encode($result);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Webhook not found']);
            }
        }
    }

    // Route the request
    $router = new APIRouter();
    $router->route();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    if (is_writable($logPath)) {
        error_log(date('Y-m-d H:i:s') . ' API Exception: ' . $e->getMessage() . "\n", 3, $logPath . '/error.log');
    }
}
?>
