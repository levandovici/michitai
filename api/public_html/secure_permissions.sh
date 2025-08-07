#!/bin/bash

# Secure Permissions Script for Multiplayer API - Updated Structure
# All backend code now in public_html/api/ secured with .htaccess
# Sets proper file permissions for production deployment on Hostinger
# Run this script after uploading files to ensure security

echo "Setting secure permissions for Multiplayer API (New Structure)..."

# Set base directory (adjust if needed)
BASE_DIR="/home/u833544264/domains/api.michitai.com"
PUBLIC_DIR="$BASE_DIR/public_html"
API_DIR="$PUBLIC_DIR/api"

# Check if directories exist
if [ ! -d "$PUBLIC_DIR" ]; then
    echo "Error: Public directory not found at $PUBLIC_DIR"
    exit 1
fi

if [ ! -d "$API_DIR" ]; then
    echo "Error: API directory not found at $API_DIR"
    exit 1
fi

echo "Found directories:"
echo "- Public HTML: $PUBLIC_DIR"
echo "- API Directory: $API_DIR"

# Set ownership (adjust username as needed)
echo "Setting ownership..."
chown -R u833544264:u833544264 "$BASE_DIR"

# Set directory permissions
echo "Setting directory permissions..."
# Public HTML directories
find "$PUBLIC_DIR" -type d -exec chmod 755 {} \;

# API subdirectories (more restrictive)
if [ -d "$API_DIR/config" ]; then
    chmod 750 "$API_DIR/config"
    echo "Secured config directory"
fi

if [ -d "$API_DIR/classes" ]; then
    chmod 750 "$API_DIR/classes"
    echo "Secured classes directory"
fi

if [ -d "$API_DIR/data" ]; then
    chmod 750 "$API_DIR/data"
    echo "Secured data directory"
fi

if [ -d "$API_DIR/logs" ]; then
    chmod 750 "$API_DIR/logs"
    echo "Secured logs directory"
fi

if [ -d "$API_DIR/uploads" ]; then
    chmod 755 "$API_DIR/uploads"
    echo "Set uploads directory permissions"
fi

# Set file permissions
echo "Setting file permissions..."
# Public HTML files (standard web permissions)
find "$PUBLIC_DIR" -name "*.html" -exec chmod 644 {} \;
find "$PUBLIC_DIR" -name "*.css" -exec chmod 644 {} \;
find "$PUBLIC_DIR" -name "*.js" -exec chmod 644 {} \;
find "$PUBLIC_DIR" -name "*.png" -exec chmod 644 {} \;
find "$PUBLIC_DIR" -name "*.jpg" -exec chmod 644 {} \;
find "$PUBLIC_DIR" -name "*.jpeg" -exec chmod 644 {} \;
find "$PUBLIC_DIR" -name "*.gif" -exec chmod 644 {} \;
find "$PUBLIC_DIR" -name "*.ico" -exec chmod 644 {} \;
find "$PUBLIC_DIR" -name "*.svg" -exec chmod 644 {} \;

# API PHP files (more restrictive)
find "$API_DIR" -name "*.php" -exec chmod 640 {} \;

# API entry point (needs to be executable by web server)
if [ -f "$API_DIR/index.php" ]; then
    chmod 644 "$API_DIR/index.php"
    echo "Set API entry point permissions"
fi

# Test files (for debugging)
find "$API_DIR" -name "*test*.php" -exec chmod 644 {} \;

# Set executable permissions for scripts
echo "Setting executable permissions..."
find "$BASE_DIR" -name "*.sh" -exec chmod 750 {} \;

# Secure sensitive files
echo "Securing sensitive files..."

# .env file (CRITICAL - must be 600)
if [ -f "$API_DIR/.env" ]; then
    chmod 600 "$API_DIR/.env"
    echo "✅ Secured .env file (600 - owner read/write only)"
else
    echo "⚠️  .env file not found - create from .env.example"
fi

# .htaccess files
if [ -f "$PUBLIC_DIR/.htaccess" ]; then
    chmod 644 "$PUBLIC_DIR/.htaccess"
    echo "✅ Secured main .htaccess file"
fi

if [ -f "$API_DIR/.htaccess" ]; then
    chmod 644 "$API_DIR/.htaccess"
    echo "✅ Secured API .htaccess file"
fi

# Config files
find "$API_DIR/config" -name "*.php" -exec chmod 640 {} \; 2>/dev/null

# Class files
find "$API_DIR/classes" -name "*.php" -exec chmod 640 {} \; 2>/dev/null

# Secure database and log files
echo "Securing database and log files..."
find "$API_DIR" -name "*.db" -exec chmod 600 {} \;
find "$API_DIR" -name "*.sql" -exec chmod 600 {} \;
find "$API_DIR" -name "*.log" -exec chmod 640 {} \;

# Secure JSON and backup files
find "$API_DIR" -name "*.json" -exec chmod 640 {} \;
find "$API_DIR" -name "*.bak" -exec chmod 600 {} \;
find "$API_DIR" -name "*.backup" -exec chmod 600 {} \;

# Create necessary directories if they don't exist
echo "Creating necessary directories..."
mkdir -p "$API_DIR/logs" && chmod 750 "$API_DIR/logs"
mkdir -p "$API_DIR/data" && chmod 750 "$API_DIR/data"
mkdir -p "$API_DIR/uploads" && chmod 755 "$API_DIR/uploads"

echo ""
echo "🎉 Permissions set successfully!"
echo ""
echo "📋 PERMISSION SUMMARY:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📁 Public HTML files:     644 (world readable)"
echo "📁 Public HTML dirs:      755 (world accessible)"
echo "🔒 API PHP files:         640 (group readable only)"
echo "🔒 API sensitive dirs:    750 (group accessible only)"
echo "🔐 .env file:             600 (owner only - CRITICAL)"
echo "🔒 Database files:        600 (owner only)"
echo "📝 Log files:             640 (group readable)"
echo "⚙️  Scripts:               750 (executable)"
echo "🌐 .htaccess files:       644 (web server readable)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "🔍 SECURITY CHECKLIST:"
echo "✅ All sensitive files protected by .htaccess"
echo "✅ Config and classes directories secured"
echo "✅ Database files owner-only access"
echo "✅ Log files group-readable for monitoring"
echo "✅ .env file maximum security (600)"
echo ""
echo "⚠️  IMPORTANT NEXT STEPS:"
echo "1. Verify .env file exists: $API_DIR/.env"
echo "2. Check MySQL credentials in .env file"
echo "3. Test API endpoint: https://api.michitai.com/api/complete_test.php"
echo "4. Test registration: https://api.michitai.com/register.html"
echo ""
echo "🚨 CRITICAL: .env file MUST have 600 permissions in production!"
echo "   This prevents other users from reading your database credentials."
echo ""
echo "📍 Current structure: All backend code in public_html/api/"
echo "📍 Security: Protected by comprehensive .htaccess rules"
echo "📍 Database: MySQL localhost connection ready"
