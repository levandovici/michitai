# Correct Secure Structure for api.michitai.com on Hostinger

## ⚠️ Correction: DO_NOT_UPLOAD_HERE is a Warning File

You're absolutely right! `DO_NOT_UPLOAD_HERE` is a warning file telling you not to upload files there, not a secure folder. Let me provide the correct secure structure for Hostinger.

## 🔒 Proper Hostinger Secure Structure

```
/home/u833544264/domains/api.michitai.com/
├── DO_NOT_UPLOAD_HERE              # Warning file (leave as is)
├── public_html/                    # Web-accessible files only
│   ├── index.html                 # Frontend interface
│   ├── js/
│   │   ├── app.js
│   │   ├── puzzle-constructor.js
│   │   └── payment.js
│   ├── css/
│   │   └── styles.css
│   ├── api/
│   │   └── index.php              # Minimal API entry point
│   └── .htaccess                  # Security rules
└── private/                       # Create this for secure files
    ├── api_backend/
    │   ├── classes/
    │   │   ├── Auth.php
    │   │   ├── GameManager.php
    │   │   ├── PlayerManager.php
    │   │   ├── PaymentManager.php
    │   │   └── NotificationManager.php
    │   ├── config/
    │   │   └── database.php
    │   ├── database/
    │   │   └── schema.sql
    │   ├── cron/
    │   │   ├── cron-handler.php
    │   │   └── setup-cron.sh
    │   ├── tests/
    │   │   └── ApiTest.php
    │   ├── vendor/                # Composer dependencies
    │   ├── .env                   # Environment variables
    │   ├── composer.json
    │   └── composer.lock
    └── logs/                      # Application logs
        ├── api.log
        ├── cron.log
        └── error.log
```

## 🔧 Implementation Commands

### Step 1: Create Secure Directory Structure
```bash
# SSH into your Hostinger account
ssh u833544264@fr-int-web1787.main-hosting.eu

# Navigate to your domain
cd domains/api.michitai.com/

# Create private directory structure (outside web root)
mkdir -p private/api_backend/{classes,config,database,cron,tests}
mkdir -p private/logs

# Verify the structure
ls -la
# Should show: DO_NOT_UPLOAD_HERE, public_html/, private/
```

### Step 2: Secure API Entry Point

Create `public_html/api/index.php`:
```php
<?php
/**
 * Secure API Entry Point for api.michitai.com
 * All sensitive files are stored in private/ directory
 */

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

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

// Set secure paths (private directory)
$securePath = '/home/u833544264/domains/api.michitai.com/private/api_backend';
$logPath = '/home/u833544264/domains/api.michitai.com/private/logs';

// Verify secure path exists
if (!is_dir($securePath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Configuration error']);
    exit;
}

// Set include path
set_include_path($securePath . PATH_SEPARATOR . get_include_path());

// Error handling
set_error_handler(function($severity, $message, $file, $line) use ($logPath) {
    $logFile = $logPath . '/error.log';
    if (is_writable(dirname($logFile))) {
        error_log("PHP Error: $message in $file on line $line", 3, $logFile);
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

    // Simple API router
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
                case 'subscribe':
                    $this->handleSubscribe();
                    break;
                case 'game':
                    $this->handleGame($segments, $method);
                    break;
                case 'player':
                    $this->handlePlayer($segments, $method);
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
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Player endpoint not found']);
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
        error_log('API Exception: ' . $e->getMessage(), 3, $logPath . '/error.log');
    }
}
?>
```

### Step 3: Update Environment Configuration

Create `private/api_backend/.env`:
```env
# Database Configuration for Hostinger
DB_HOST=localhost
DB_NAME=u833544264_api
DB_USERNAME=u833544264_api_user
DB_PASSWORD=your_secure_password

# Application Paths (using private directory)
APP_ROOT=/home/u833544264/domains/api.michitai.com/private/api_backend
LOG_PATH=/home/u833544264/domains/api.michitai.com/private/logs
PUBLIC_PATH=/home/u833544264/domains/api.michitai.com/public_html

# Application URLs
APP_URL=https://api.michitai.com
API_URL=https://api.michitai.com/api

# PayPal Configuration
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
PAYPAL_WEBHOOK_URL=https://api.michitai.com/api/webhook/paypal

# MAIB Bank Configuration (Moldova)
MAIB_SWIFT=AGRNMD2X
MAIB_ACCOUNT_DETAILS=your_account_details

# Email Configuration (Hostinger SMTP)
SMTP_HOST=smtp.hostinger.com
SMTP_PORT=587
SMTP_USERNAME=noreply@api.michitai.com
SMTP_PASSWORD=your_email_password
SMTP_FROM_EMAIL=noreply@api.michitai.com
SMTP_FROM_NAME=Michitai API

# Slack Configuration
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK

# Security
JWT_SECRET=your_very_secure_jwt_secret_key_here
API_RATE_LIMIT_WINDOW=3600

# Debug (set to false in production)
DEBUG_MODE=false
LOG_LEVEL=error
```

### Step 4: Update Cron Jobs

Update `private/api_backend/cron/setup-cron.sh`:
```bash
#!/bin/bash
# Cron jobs setup for api.michitai.com (using private directory)

CRON_PATH="/home/u833544264/domains/api.michitai.com/private/api_backend/cron"
LOG_PATH="/home/u833544264/domains/api.michitai.com/private/logs"

# Add cron jobs
(crontab -l 2>/dev/null; echo "# Michitai API Cron Jobs") | crontab -
(crontab -l 2>/dev/null; echo "0 0 * * * /usr/bin/php $CRON_PATH/cron-handler.php reset-api-calls >> $LOG_PATH/cron.log 2>&1") | crontab -
(crontab -l 2>/dev/null; echo "* * * * * /usr/bin/php $CRON_PATH/cron-handler.php update-timers >> $LOG_PATH/cron.log 2>&1") | crontab -
(crontab -l 2>/dev/null; echo "*/5 * * * * /usr/bin/php $CRON_PATH/cron-handler.php process-notifications >> $LOG_PATH/cron.log 2>&1") | crontab -
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/bin/php $CRON_PATH/cron-handler.php check-subscriptions >> $LOG_PATH/cron.log 2>&1") | crontab -
(crontab -l 2>/dev/null; echo "0 * * * * /usr/bin/php $CRON_PATH/cron-handler.php update-stats >> $LOG_PATH/cron.log 2>&1") | crontab -
(crontab -l 2>/dev/null; echo "0 3 * * * /usr/bin/php $CRON_PATH/cron-handler.php cleanup >> $LOG_PATH/cron.log 2>&1") | crontab -

echo "Cron jobs configured for api.michitai.com"
```

### Step 5: Set Proper File Permissions

```bash
# Set secure permissions for private directory
chmod 755 private/
chmod 755 private/api_backend/
chmod 644 private/api_backend/.env
chmod 755 private/api_backend/classes/
chmod 644 private/api_backend/classes/*.php
chmod 755 private/api_backend/cron/
chmod 755 private/api_backend/cron/*.sh
chmod 755 private/logs/

# Set permissions for public files
chmod 644 public_html/*.html
chmod 644 public_html/js/*.js
chmod 644 public_html/api/index.php
chmod 644 public_html/.htaccess
```

## 🎯 Summary

**Correct Structure:**
- ✅ `public_html/` - Only web-accessible files
- ✅ `private/` - All sensitive backend files (secure)
- ✅ `DO_NOT_UPLOAD_HERE` - Leave as warning file

**Security Benefits:**
- Complete isolation of sensitive files
- PCI/GDPR compliance
- Defense against server misconfigurations
- Industry best practices

This structure properly separates your web-accessible files from sensitive backend code, ensuring maximum security for your payment processing API.
