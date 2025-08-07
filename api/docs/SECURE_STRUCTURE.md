# Secure Folder Structure for Hostinger

## 🔒 Recommended Security Structure

### Problem with All Files in public_html
Storing everything in `public_html` exposes sensitive files to potential web access, even with `.htaccess` protection. A misconfigured server or `.htaccess` failure could expose:
- Database credentials (`.env`)
- PHP classes with business logic
- Configuration files
- Backup files

### ✅ Recommended Secure Structure

```
/home/username/
├── public_html/                    # Web-accessible files only
│   ├── index.html                 # Main frontend
│   ├── js/
│   │   ├── app.js
│   │   ├── puzzle-constructor.js
│   │   └── payment.js
│   ├── css/
│   │   └── styles.css
│   ├── api/
│   │   └── index.php              # API entry point only
│   └── .htaccess                  # Web server configuration
├── api_secure/                    # Private files (NOT web-accessible)
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
│   ├── vendor/                    # Composer dependencies
│   ├── .env                       # Environment variables
│   └── composer.json
└── logs/                          # Log files (private)
    ├── api.log
    ├── cron.log
    └── error.log
```

## 🔧 Implementation Steps

### Step 1: Create Secure Folder Structure
```bash
# On Hostinger, create these folders outside public_html
mkdir -p /home/username/api_secure/{classes,config,database,cron,tests}
mkdir -p /home/username/logs
```

### Step 2: Move Files to Secure Locations
```bash
# Move sensitive files out of public_html
mv public_html/classes/* /home/username/api_secure/classes/
mv public_html/config/* /home/username/api_secure/config/
mv public_html/.env /home/username/api_secure/
mv public_html/composer.json /home/username/api_secure/
mv public_html/vendor /home/username/api_secure/
```

### Step 3: Update API Entry Point
Create a minimal `public_html/api/index.php` that includes files from secure location:

```php
<?php
// public_html/api/index.php - Secure API entry point

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Set secure include path
$securePath = '/home/username/api_secure';
set_include_path($securePath . PATH_SEPARATOR . get_include_path());

// Include autoloader
require_once $securePath . '/vendor/autoload.php';

// Include configuration
require_once $securePath . '/config/database.php';

// Include API router
require_once $securePath . '/classes/Auth.php';
require_once $securePath . '/classes/GameManager.php';
require_once $securePath . '/classes/PlayerManager.php';
require_once $securePath . '/classes/PaymentManager.php';
require_once $securePath . '/classes/NotificationManager.php';

// Initialize and route API requests
try {
    $router = new APIRouter();
    $router->route();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    error_log('API Error: ' . $e->getMessage());
}
?>
```

### Step 4: Secure .htaccess Configuration

#### public_html/.htaccess
```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

# Prevent access to sensitive files
<FilesMatch "\.(env|log|sql|md|json)$">
    Order deny,allow
    Deny from all
</FilesMatch>

# Block access to hidden files
<FilesMatch "^\.">
    Order deny,allow
    Deny from all
</FilesMatch>

# API routing
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/index.php [QSA,L]

# Frontend routing (SPA)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !^/api/
RewriteRule ^(.*)$ index.html [QSA,L]
```

#### Additional Protection (if needed)
```apache
# public_html/sensitive/.htaccess (for any remaining sensitive files)
Order deny,allow
Deny from all
```

## 🛡️ Security Benefits

### 1. File System Isolation
- **Sensitive files** are completely outside web root
- **No web access** to configuration, classes, or credentials
- **Server misconfiguration** won't expose private files

### 2. Principle of Least Privilege
- **Only necessary files** in web-accessible directory
- **API entry point** is minimal and controlled
- **Business logic** hidden from web access

### 3. Defense in Depth
- **Multiple security layers**: file system + .htaccess + PHP security
- **Fail-safe design**: even if .htaccess fails, files aren't accessible
- **Reduced attack surface**: fewer files exposed to web

## 🔧 Updated File Paths

### Environment Configuration
Update paths in your secure configuration:

```php
// api_secure/config/database.php
class Database {
    private static $envPath = '/home/username/api_secure/.env';
    private static $logPath = '/home/username/logs/';
    
    // ... rest of configuration
}
```

### Cron Jobs Update
```bash
# Update cron paths to secure location
0 0 * * * /usr/bin/php /home/username/api_secure/cron/cron-handler.php reset-api-calls
* * * * * /usr/bin/php /home/username/api_secure/cron/cron-handler.php update-timers
```

### Composer Autoloader
```php
// In your secure API files
require_once '/home/username/api_secure/vendor/autoload.php';
```

## 📁 Alternative: Hostinger-Specific Structure

Some Hostinger plans may have restrictions. Alternative secure structure:

```
public_html/
├── index.html                     # Frontend only
├── js/, css/                      # Static assets
├── api/
│   └── index.php                  # Minimal entry point
└── .htaccess                      # Strong protection rules

private_html/                      # Hostinger private folder (if available)
├── api/                          # All backend code
├── .env                          # Environment variables
└── vendor/                       # Dependencies
```

## ⚠️ Important Security Notes

### 1. File Permissions
```bash
# Set secure permissions
chmod 644 /home/username/api_secure/.env
chmod 755 /home/username/api_secure/classes/
chmod 644 /home/username/api_secure/classes/*.php
```

### 2. Log File Security
```bash
# Secure log files
chmod 640 /home/username/logs/*.log
```

### 3. Database Credentials
- **Never** store database credentials in web-accessible files
- Use **environment variables** or files outside web root
- Consider **encrypted configuration** for sensitive data

### 4. Backup Security
- **Exclude** sensitive files from web-accessible backups
- Store **backups outside** web root
- **Encrypt** backup files containing sensitive data

## 🎯 Recommendation

**Use the secure folder structure** with files outside `public_html`. This provides:
- ✅ **Maximum security** against file exposure
- ✅ **Industry best practices** compliance
- ✅ **Future-proof** against server misconfigurations
- ✅ **PCI compliance** for payment processing
- ✅ **GDPR compliance** for data protection

The minimal performance overhead is worth the significant security improvement!
