<?php
/**
 * Multiplayer API Web Constructor - Main API Router
 * Handles all REST API endpoints with authentication and rate limiting
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Token');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/GameManager.php';
require_once __DIR__ . '/../classes/PlayerManager.php';
require_once __DIR__ . '/../classes/PaymentManager.php';
require_once __DIR__ . '/../classes/NotificationManager.php';

class APIRouter {
    private $auth;
    private $gameManager;
    private $playerManager;
    private $paymentManager;
    private $notificationManager;
    private $startTime;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->gameManager = new GameManager();
        $this->playerManager = new PlayerManager();
        $this->paymentManager = new PaymentManager();
        $this->notificationManager = new NotificationManager();
        $this->startTime = microtime(true);
    }
    
    /**
     * Route the API request
     */
    public function route() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $path = str_replace('/api', '', $path);
            $path = trim($path, '/');
            $segments = explode('/', $path);
            
            // Get request data
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $headers = getallheaders();
            $apiToken = $headers['X-API-Token'] ?? $input['api_token'] ?? null;
            
            $response = null;
            $userId = null;
            
            // Public endpoints (no authentication required)
            if ($method === 'POST' && $segments[0] === 'register') {
                $response = $this->handleRegister($input);
            } elseif ($method === 'POST' && $segments[0] === 'login') {
                $response = $this->handleLogin($input);
            } 
            // Protected endpoints (authentication required)
            else {
                // Authenticate user
                $user = $this->auth->authenticateToken($apiToken);
                $userId = $user['user_id'];
                
                // Check rate limit
                $this->auth->checkRateLimit($userId, $user['plan_type']);
                
                // Route to appropriate handler
                switch ($segments[0]) {
                    case 'subscribe':
                        $response = $this->handleSubscription($input, $user);
                        break;
                    case 'subscription':
                        $response = $this->handleSubscriptionManagement($segments, $input, $user);
                        break;
                    case 'game':
                        $response = $this->handleGame($segments, $method, $input, $user);
                        break;
                    case 'player':
                        $response = $this->handlePlayer($segments, $method, $input, $user);
                        break;
                    case 'room':
                        $response = $this->handleRoom($segments, $method, $input, $user);
                        break;
                    case 'community':
                        $response = $this->handleCommunity($segments, $method, $input, $user);
                        break;
                    case 'chat':
                        $response = $this->handleChat($segments, $method, $input, $user);
                        break;
                    case 'matchmaking':
                        $response = $this->handleMatchmaking($segments, $method, $input, $user);
                        break;
                    case 'trigger':
                        $response = $this->handleTrigger($segments, $method, $input, $user);
                        break;
                    case 'timer':
                        $response = $this->handleTimer($segments, $method, $input, $user);
                        break;
                    case 'monitor':
                        $response = $this->handleMonitor($segments, $method, $input, $user);
                        break;
                    default:
                        throw new Exception("Endpoint not found", 404);
                }
            }
            
            // Log API call
            if ($userId) {
                $executionTime = (microtime(true) - $this->startTime) * 1000;
                $this->auth->logApiCall($userId, $path, $method, 200, $executionTime);
            }
            
            $this->sendResponse(200, $response);
            
        } catch (Exception $e) {
            $code = $e->getCode() ?: 400;
            $this->sendResponse($code, ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Send JSON response
     */
    private function sendResponse($code, $data) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    /**
     * Handle user registration
     */
    private function handleRegister($input) {
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            throw new Exception("Email and password are required");
        }
        
        return $this->auth->register($email, $password);
    }
    
    /**
     * Handle user login
     */
    private function handleLogin($input) {
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            throw new Exception("Email and password are required");
        }
        
        return $this->auth->login($email, $password);
    }
    
    /**
     * Handle subscription creation
     */
    private function handleSubscription($input, $user) {
        $planType = $input['plan_type'] ?? '';
        
        if (!in_array($planType, ['Standard', 'Pro'])) {
            throw new Exception("Invalid plan type");
        }
        
        return $this->paymentManager->createSubscription($user['user_id'], $planType);
    }
    
    /**
     * Handle subscription management
     */
    private function handleSubscriptionManagement($segments, $input, $user) {
        $action = $segments[1] ?? '';
        
        switch ($action) {
            case 'update':
                $planType = $input['plan_type'] ?? '';
                return $this->paymentManager->updateSubscription($user['user_id'], $planType);
            case 'cancel':
                return $this->paymentManager->cancelSubscription($user['user_id']);
            case 'status':
                return $this->paymentManager->getSubscriptionStatus($user['user_id']);
            default:
                throw new Exception("Invalid subscription action");
        }
    }
    
    /**
     * Handle game operations
     */
    private function handleGame($segments, $method, $input, $user) {
        $action = $segments[1] ?? '';
        
        switch ($action) {
            case 'create':
                if ($method !== 'POST') throw new Exception("Method not allowed", 405);
                return $this->gameManager->createGame($user['user_id'], $input);
            case 'list':
                if ($method !== 'GET') throw new Exception("Method not allowed", 405);
                return $this->gameManager->getUserGames($user['user_id']);
            case 'get':
                if ($method !== 'GET') throw new Exception("Method not allowed", 405);
                $gameId = $segments[2] ?? '';
                return $this->gameManager->getGame($user['user_id'], $gameId);
            case 'update':
                if ($method !== 'POST') throw new Exception("Method not allowed", 405);
                $gameId = $input['game_id'] ?? '';
                return $this->gameManager->updateGame($user['user_id'], $gameId, $input);
            case 'delete':
                if ($method !== 'POST') throw new Exception("Method not allowed", 405);
                $gameId = $input['game_id'] ?? '';
                return $this->gameManager->deleteGame($user['user_id'], $gameId);
            default:
                throw new Exception("Invalid game action");
        }
    }
    
    /**
     * Handle player operations
     */
    private function handlePlayer($segments, $method, $input, $user) {
        $action = $segments[1] ?? '';
        
        switch ($action) {
            case 'create':
                if ($method !== 'POST') throw new Exception("Method not allowed", 405);
                $gameId = $input['game_id'] ?? '';
                return $this->playerManager->createPlayer($user['user_id'], $gameId);
            case 'update':
                if ($method !== 'POST') throw new Exception("Method not allowed", 405);
                $playerId = $input['player_id'] ?? '';
                $jsonData = $input['json_data'] ?? [];
                return $this->playerManager->updatePlayer($playerId, $jsonData);
            case 'get':
                if ($method !== 'GET') throw new Exception("Method not allowed", 405);
                $playerId = $segments[2] ?? '';
                return $this->playerManager->getPlayer($playerId);
            case 'authenticate':
                if ($method !== 'POST') throw new Exception("Method not allowed", 405);
                $playerId = $input['player_id'] ?? '';
                $passwordGuid = $input['password_guid'] ?? '';
                return $this->playerManager->authenticatePlayer($playerId, $passwordGuid);
            default:
                throw new Exception("Invalid player action");
        }
    }
    
    /**
     * Handle room operations
     */
    private function handleRoom($segments, $method, $input, $user) {
        $action = $segments[1] ?? '';
        
        switch ($action) {
            case 'create':
                return $this->gameManager->createRoom($user['user_id'], $input);
            case 'join':
                return $this->gameManager->joinRoom($input);
            case 'leave':
                return $this->gameManager->leaveRoom($input);
            case 'data':
                return $this->gameManager->getRoomData($input);
            case 'remove':
                return $this->gameManager->removeRoom($user['user_id'], $input);
            default:
                throw new Exception("Invalid room action");
        }
    }
    
    /**
     * Handle community operations
     */
    private function handleCommunity($segments, $method, $input, $user) {
        $action = $segments[1] ?? '';
        
        switch ($action) {
            case 'create':
                return $this->gameManager->createCommunity($user['user_id'], $input);
            case 'join':
                return $this->gameManager->joinCommunity($input);
            case 'leave':
                return $this->gameManager->leaveCommunity($input);
            case 'data':
                return $this->gameManager->getCommunityData($input);
            case 'privileges':
                return $this->gameManager->updateCommunityPrivileges($user['user_id'], $input);
            case 'remove':
                return $this->gameManager->removeCommunity($user['user_id'], $input);
            default:
                throw new Exception("Invalid community action");
        }
    }
    
    /**
     * Handle chat operations
     */
    private function handleChat($segments, $method, $input, $user) {
        $action = $segments[1] ?? '';
        
        switch ($action) {
            case 'create':
                return $this->gameManager->createChat($user['user_id'], $input);
            case 'write':
                return $this->gameManager->writeMessage($input);
            case 'read':
                return $this->gameManager->readMessages($input);
            case 'edit':
                return $this->gameManager->editMessage($input);
            case 'remove':
                return $this->gameManager->removeChat($user['user_id'], $input);
            default:
                throw new Exception("Invalid chat action");
        }
    }
    
    /**
     * Handle matchmaking operations
     */
    private function handleMatchmaking($segments, $method, $input, $user) {
        $action = $segments[1] ?? '';
        
        switch ($action) {
            case 'find':
                return $this->gameManager->findMatch($input);
            case 'create':
                return $this->gameManager->createMatch($user['user_id'], $input);
            case 'join':
                return $this->gameManager->joinMatch($input);
            case 'leave':
                return $this->gameManager->leaveMatch($input);
            default:
                throw new Exception("Invalid matchmaking action");
        }
    }
    
    /**
     * Handle trigger operations
     */
    private function handleTrigger($segments, $method, $input, $user) {
        if ($method !== 'POST') throw new Exception("Method not allowed", 405);
        
        $name = $input['name'] ?? '';
        $parameters = $input['parameters'] ?? [];
        $gameId = $input['game_id'] ?? '';
        
        return $this->gameManager->createTrigger($user['user_id'], $gameId, $name, $parameters);
    }
    
    /**
     * Handle timer operations
     */
    private function handleTimer($segments, $method, $input, $user) {
        if ($method !== 'POST') throw new Exception("Method not allowed", 405);
        
        $action = $segments[1] ?? '';
        
        switch ($action) {
            case 'set':
                return $this->gameManager->setTimer($input);
            case 'math':
                return $this->gameManager->performTimerMath($input);
            case 'multiplier':
                return $this->gameManager->setTimerMultiplier($input);
            default:
                throw new Exception("Invalid timer action");
        }
    }
    
    /**
     * Handle monitoring operations
     */
    private function handleMonitor($segments, $method, $input, $user) {
        if ($method !== 'GET') throw new Exception("Method not allowed", 405);
        
        $type = $segments[1] ?? '';
        
        switch ($type) {
            case 'user':
                $targetUserId = $_GET['user_id'] ?? $user['user_id'];
                return $this->gameManager->getUserMonitorData($targetUserId);
            case 'system':
                return $this->gameManager->getSystemMonitorData();
            default:
                throw new Exception("Invalid monitor type");
        }
    }
}

// Initialize and route the request
$router = new APIRouter();
$router->route();
?>
