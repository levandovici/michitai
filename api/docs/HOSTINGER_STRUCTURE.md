# Secure Structure for api.michitai.com on Hostinger

## 🔒 Your Current Hostinger Structure

```
/home/u833544264/domains/api.michitai.com/
├── DO_NOT_UPLOAD_HERE/          # Perfect for secure files!
└── public_html/                 # Web-accessible files only
```

## ✅ Recommended Secure File Organization

```
/home/u833544264/domains/api.michitai.com/
├── DO_NOT_UPLOAD_HERE/          # Secure backend files
│   ├── api_backend/
│   │   ├── classes/
│   │   │   ├── Auth.php
│   │   │   ├── GameManager.php
│   │   │   ├── PlayerManager.php
│   │   │   ├── PaymentManager.php
│   │   │   └── NotificationManager.php
│   │   ├── config/
│   │   │   └── database.php
│   │   ├── database/
│   │   │   └── schema.sql
│   │   ├── cron/
│   │   │   ├── cron-handler.php
│   │   │   └── setup-cron.sh
│   │   ├── tests/
│   │   │   └── ApiTest.php
│   │   ├── vendor/              # Composer dependencies
│   │   ├── .env                 # Environment variables
│   │   ├── composer.json
│   │   └── composer.lock
│   └── logs/                    # Application logs
│       ├── api.log
│       ├── cron.log
│       └── error.log
└── public_html/                 # Web-accessible files
    ├── index.html               # Main frontend
    ├── js/
    │   ├── app.js
    │   ├── puzzle-constructor.js
    │   └── payment.js
    ├── css/
    │   └── styles.css
    ├── api/
    │   └── index.php            # Minimal API entry point
    └── .htaccess                # Web security rules
```

## 🔧 Implementation Commands

### Step 1: Create Secure Directory Structure
```bash
# SSH into your Hostinger account
ssh u833544264@fr-int-web1787.main-hosting.eu

# Navigate to your domain
cd domains/api.michitai.com/

# Create secure backend structure
mkdir -p DO_NOT_UPLOAD_HERE/api_backend/{classes,config,database,cron,tests}
mkdir -p DO_NOT_UPLOAD_HERE/logs

# Create public structure
mkdir -p public_html/{js,css,api}
```

### Step 2: Upload Files to Secure Locations

**Upload to DO_NOT_UPLOAD_HERE/api_backend/:**
- All PHP classes (`classes/`)
- Database config (`config/`)
- Environment file (`.env`)
- Composer files (`composer.json`, `vendor/`)
- Cron scripts (`cron/`)
- Tests (`tests/`)

**Upload to public_html/:**
- Frontend files (`index.html`)
- JavaScript files (`js/`)
- CSS files (`css/`)
- Minimal API entry point (`api/index.php`)

### Step 3: Create Secure API Entry Point

Create `public_html/api/index.php`:
```php
<?php
/**
 * Secure API Entry Point for api.michitai.com
 * All sensitive files are stored outside web root
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

// Set secure paths
$securePath = '/home/u833544264/domains/api.michitai.com/DO_NOT_UPLOAD_HERE/api_backend';
$logPath = '/home/u833544264/domains/api.michitai.com/DO_NOT_UPLOAD_HERE/logs';

// Set include path
set_include_path($securePath . PATH_SEPARATOR . get_include_path());

// Error handling
set_error_handler(function($severity, $message, $file, $line) use ($logPath) {
    error_log("PHP Error: $message in $file on line $line", 3, $logPath . '/error.log');
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

    // Initialize API router (you'll need to create this class)
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
            $this->auth->logApiCall($_SERVER['HTTP_X_API_TOKEN'] ?? null, $segments[0] ?? 'unknown');

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
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint not found']);
            }
        }

        // Add your handler methods here...
        private function handleRegister() {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $result = $this->auth->register($input['email'] ?? '', $input['password'] ?? '');
            echo json_encode($result);
        }

        // ... implement other handlers
    }

    // Route the request
    $router = new APIRouter();
    $router->route();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    error_log('API Exception: ' . $e->getMessage(), 3, $logPath . '/error.log');
}
?>
```

### Step 4: Update .htaccess for Security

Create `public_html/.htaccess`:
```apache
# Force HTTPS for api.michitai.com
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# Prevent access to sensitive files
<FilesMatch "\.(env|log|sql|md|json|lock)$">
    Order deny,allow
    Deny from all
</FilesMatch>

# Block access to hidden files and directories
<FilesMatch "^\.">
    Order deny,allow
    Deny from all
</FilesMatch>

# Block access to backup files
<FilesMatch "\.(bak|backup|old|tmp)$">
    Order deny,allow
    Deny from all
</FilesMatch>

# API routing
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/index.php [QSA,L]

# Frontend SPA routing
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !^/api/
RewriteRule ^(.*)$ index.html [QSA,L]

# Disable server signature
ServerSignature Off

# Disable directory browsing
Options -Indexes
```

### Step 5: Update Environment Configuration

Update paths in `DO_NOT_UPLOAD_HERE/api_backend/.env`:
```env
# Database Configuration
DB_HOST=localhost
DB_NAME=u833544264_api
DB_USERNAME=u833544264_api_user
DB_PASSWORD=your_secure_password

# Application Paths
APP_ROOT=/home/u833544264/domains/api.michitai.com/DO_NOT_UPLOAD_HERE/api_backend
LOG_PATH=/home/u833544264/domains/api.michitai.com/DO_NOT_UPLOAD_HERE/logs
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

### Step 6: Update Cron Jobs

Update `DO_NOT_UPLOAD_HERE/api_backend/cron/setup-cron.sh`:
```bash
#!/bin/bash
# Cron jobs setup for api.michitai.com

CRON_PATH="/home/u833544264/domains/api.michitai.com/DO_NOT_UPLOAD_HERE/api_backend/cron"
LOG_PATH="/home/u833544264/domains/api.michitai.com/DO_NOT_UPLOAD_HERE/logs"

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

### Step 7: File Permissions

```bash
# Set secure permissions
chmod 755 DO_NOT_UPLOAD_HERE/api_backend/
chmod 644 DO_NOT_UPLOAD_HERE/api_backend/.env
chmod 755 DO_NOT_UPLOAD_HERE/api_backend/classes/
chmod 644 DO_NOT_UPLOAD_HERE/api_backend/classes/*.php
chmod 755 DO_NOT_UPLOAD_HERE/api_backend/cron/
chmod 755 DO_NOT_UPLOAD_HERE/api_backend/cron/*.sh
chmod 755 DO_NOT_UPLOAD_HERE/logs/
chmod 644 public_html/*.html
chmod 644 public_html/js/*.js
chmod 644 public_html/api/index.php
```

## 🎯 Benefits of This Structure

1. **Maximum Security**: All sensitive files in `DO_NOT_UPLOAD_HERE/`
2. **Hostinger Optimized**: Uses Hostinger's secure folder structure
3. **Domain Specific**: Configured for `api.michitai.com`
4. **PCI Compliant**: Meets payment processing security requirements
5. **Easy Maintenance**: Clear separation of concerns

## 🚀 Deployment Steps

1. Upload files to respective directories
2. Set file permissions
3. Configure database in Hostinger control panel
4. Update `.env` with your credentials
5. Run `setup-cron.sh` to configure scheduled tasks
6. Test API endpoints

Your `DO_NOT_UPLOAD_HERE` folder is perfect for this secure setup! 🔒
