<?php
/**
 * Secure API Entry Point for api.michitai.com
 * All sensitive files are stored in private/ directory outside web root
 */

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CORS headers for your domains
$allowedOrigins = [
    'https://api.michitai.com',
    'https://michitai.com',
    'https://games.michitai.com'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Token, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Set secure paths (private directory outside web root)
$basePath = '/home/u833544264/domains/api.michitai.com';
$securePath = $basePath . '/private/api_backend';
$logPath = $basePath . '/private/logs';

// Verify secure path exists
if (!is_dir($securePath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Configuration error']);
    exit;
}

// Set include path
set_include_path($securePath . PATH_SEPARATOR . get_include_path());

// Error handling with secure logging
set_error_handler(function($severity, $message, $file, $line) use ($logPath) {
    $logFile = $logPath . '/error.log';
    if (is_writable(dirname($logFile))) {
        error_log(date('Y-m-d H:i:s') . " PHP Error: $message in $file on line $line\n", 3, $logFile);
    }
});

try {
    // Include autoloader
    if (file_exists($securePath . '/vendor/autoload.php')) {
        require_once $securePath . '/vendor/autoload.php';
    }

    // Include configuration
    require_once $securePath . '/config/database.php';

    // Include all classes
    require_once $securePath . '/classes/Auth.php';
    require_once $securePath . '/classes/GameManager.php';
    require_once $securePath . '/classes/PlayerManager.php';
    require_once $securePath . '/classes/PaymentManager.php';
    require_once $securePath . '/classes/NotificationManager.php';

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

            $result = $this->auth->register($input['email'], $input['password']);
            echo json_encode($result);
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
