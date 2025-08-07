<?php
/**
 * Development API Entry Point with Professional Debugging
 * Supports both development and production environments
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

// Determine environment and set paths
$isProduction = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'michitai.com') !== false;

if ($isProduction) {
    // Production paths
    $basePath = '/home/u833544264/domains/api.michitai.com';
    $securePath = $basePath . '/private/api_backend';
    $logPath = $basePath . '/private/logs';
} else {
    // Development paths
    $basePath = dirname(dirname(__DIR__));
    $securePath = $basePath . '/private/api_backend';
    $logPath = $basePath . '/private/logs';
}

// Create directories if they don't exist (development)
if (!$isProduction) {
    if (!is_dir($securePath)) {
        mkdir($securePath, 0755, true);
    }
    if (!is_dir($logPath)) {
        mkdir($logPath, 0755, true);
    }
}

// Debug logging
if (DEBUG_MODE) {
    error_log("API Debug: Environment = " . ($isProduction ? 'production' : 'development'));
    error_log("API Debug: Base path = $basePath");
    error_log("API Debug: Secure path = $securePath");
}

// Set include path
set_include_path($securePath . PATH_SEPARATOR . get_include_path());

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
    if (file_exists($securePath . '/vendor/autoload.php')) {
        require_once $securePath . '/vendor/autoload.php';
        if (DEBUG_MODE) {
            error_log("API Debug: Composer autoloader loaded");
        }
    }

    // Include configuration files
    $configFiles = [
        $securePath . '/config/ErrorCodes.php',
        $securePath . '/config/database.php'
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
        $securePath . '/classes/Auth.php',
        $securePath . '/classes/GameManager.php',
        $securePath . '/classes/PlayerManager.php',
        $securePath . '/classes/PaymentManager.php',
        $securePath . '/classes/NotificationManager.php'
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
            // For development, create stub classes if files don't exist
            if (!$isProduction) {
                $className = basename($file, '.php');
                if (!class_exists($className)) {
                    eval("class $className { public function __construct() {} }");
                    if (DEBUG_MODE) {
                        error_log("API Debug: Created stub class: $className");
                    }
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
                    $result = $this->gameManager->createGame($token, $input);
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
                    if ($method !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method not allowed']);
                        return;
                    }
                    $input = json_decode(file_get_contents('php://input'), true);
                    $result = $this->gameManager->updateGame($token, $input);
                    echo json_encode($result);
                    break;
                case 'delete':
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
