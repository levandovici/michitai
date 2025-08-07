#!/bin/bash
# Security Fix Script for Hostinger Deployment
# Run this script after uploading files to secure permissions

echo "🔒 Securing Michitai API file permissions..."

# Navigate to the private directory
cd /home/u833544264/domains/api.michitai.com/private/api_backend

# Secure the .env file - CRITICAL
chmod 600 .env
echo "✅ .env file secured (600 - owner read/write only)"

# Secure all config files
chmod 600 config/*.php
echo "✅ Config files secured"

# Secure cron scripts
chmod 700 cron/*.sh
chmod 600 cron/*.php
echo "✅ Cron files secured"

# Secure the entire private directory
chmod 700 ../
echo "✅ Private directory secured"

# Set proper ownership (replace with your actual username)
chown -R u833544264:u833544264 ../
echo "✅ Ownership set correctly"

# Verify permissions
echo ""
echo "🔍 Verifying .env permissions:"
ls -la .env

echo ""
echo "🔍 Verifying private directory permissions:"
ls -la ../

echo ""
echo "✅ Security hardening complete!"
echo "⚠️  IMPORTANT: Run this script on your Hostinger server after upload"
