# Deployment Guide for Hostinger

## Overview

This guide provides step-by-step instructions for deploying the Multiplayer API Web Constructor on Hostinger with PayPal/Paynet payments and MAIB Moldova bank integration.

## Prerequisites

### Hostinger Requirements
- **Hosting Plan**: Business or Premium plan (supports PHP 8.x and MySQL 8.x)
- **PHP Version**: 8.0 or higher
- **MySQL Version**: 8.0 or higher
- **SSL Certificate**: Required for HTTPS (included with Hostinger plans)
- **Cron Jobs**: Available on Business plans and higher

### External Services
- **PayPal Business Account**: For payment processing
- **MAIB Bank Account**: For Moldova bank transfers (SWIFT: AGRNMD2X)
- **Email Service**: SMTP for notifications (Hostinger includes email)
- **Slack Workspace**: Optional, for Pro user notifications

## Step 1: Hostinger Setup

### 1.1 Domain and Hosting
1. Purchase domain and hosting plan from Hostinger
2. Configure DNS settings to point to Hostinger servers
3. Enable SSL certificate in Hostinger control panel
4. Verify HTTPS is working

### 1.2 File Manager Setup
1. Access Hostinger File Manager
2. Navigate to `public_html` directory
3. Upload all project files or use Git deployment
4. Set proper file permissions:
   ```bash
   # Files: 644
   find . -type f -exec chmod 644 {} \;
   
   # Directories: 755
   find . -type d -exec chmod 755 {} \;
   
   # Executables: 755
   chmod 755 cron/*.sh
   ```

### 1.3 PHP Configuration
1. In Hostinger control panel, go to PHP Configuration
2. Set PHP version to 8.0 or higher
3. Enable required extensions:
   - `mysqli`
   - `pdo_mysql`
   - `curl`
   - `json`
   - `mbstring`
   - `openssl`
4. Increase limits if needed:
   - `memory_limit = 256M`
   - `max_execution_time = 300`
   - `upload_max_filesize = 10M`

## Step 2: Database Setup

### 2.1 Create MySQL Database
1. In Hostinger control panel, go to MySQL Databases
2. Create new database: `multiplayer_api`
3. Create database user with full privileges
4. Note down database credentials

### 2.2 Import Database Schema
1. Access phpMyAdmin from Hostinger control panel
2. Select your database
3. Import the schema file: `database/schema.sql`
4. Verify all tables are created successfully

### 2.3 Database Configuration
1. Copy `.env.example` to `.env`
2. Update database credentials:
   ```env
   DB_HOST=localhost
   DB_NAME=multiplayer_api
   DB_USERNAME=your_db_username
   DB_PASSWORD=your_db_password
   ```

## Step 3: PayPal Configuration

### 3.1 PayPal Developer Account
1. Go to [PayPal Developer](https://developer.paypal.com)
2. Create or log into your developer account
3. Create new application
4. Note down Client ID and Client Secret

### 3.2 PayPal Sandbox Testing
1. Use sandbox credentials for testing:
   ```env
   PAYPAL_MODE=sandbox
   PAYPAL_CLIENT_ID=your_sandbox_client_id
   PAYPAL_CLIENT_SECRET=your_sandbox_client_secret
   ```

### 3.3 PayPal Live Configuration
1. For production, switch to live credentials:
   ```env
   PAYPAL_MODE=live
   PAYPAL_CLIENT_ID=your_live_client_id
   PAYPAL_CLIENT_SECRET=your_live_client_secret
   ```

### 3.4 Webhook Configuration
1. In PayPal Developer dashboard, configure webhooks
2. Set webhook URL: `https://yourdomain.com/api/webhook/paypal`
3. Subscribe to events:
   - `BILLING.SUBSCRIPTION.ACTIVATED`
   - `PAYMENT.SALE.COMPLETED`
   - `BILLING.SUBSCRIPTION.CANCELLED`
   - `BILLING.SUBSCRIPTION.SUSPENDED`
4. Note webhook ID and add to `.env`

### 3.5 MAIB Payout Configuration
1. Contact MAIB bank to set up international transfers
2. Provide PayPal with MAIB account details:
   - Bank Name: Moldova Agroindbank
   - SWIFT Code: AGRNMD2X
   - Account details for payouts
3. Configure payout preferences in PayPal

## Step 4: Email Configuration

### 4.1 Hostinger Email Setup
1. Create email account in Hostinger control panel
2. Use Hostinger SMTP settings:
   ```env
   SMTP_HOST=smtp.hostinger.com
   SMTP_PORT=587
   SMTP_USERNAME=noreply@yourdomain.com
   SMTP_PASSWORD=your_email_password
   SMTP_FROM_EMAIL=noreply@yourdomain.com
   SMTP_FROM_NAME=Multiplayer API Constructor
   ```

### 4.2 Alternative SMTP Providers
For better deliverability, consider:
- **SendGrid**: Professional email delivery
- **Mailgun**: Developer-friendly email API
- **Amazon SES**: Cost-effective email service

## Step 5: Cron Jobs Setup

### 5.1 Hostinger Cron Jobs
1. In Hostinger control panel, go to Cron Jobs
2. Add the following cron jobs:

```bash
# Reset API calls daily at 00:00 UTC
0 0 * * * /usr/bin/php /home/username/public_html/cron/cron-handler.php reset-api-calls

# Update timers every minute
* * * * * /usr/bin/php /home/username/public_html/cron/cron-handler.php update-timers

# Process notifications every 5 minutes
*/5 * * * * /usr/bin/php /home/username/public_html/cron/cron-handler.php process-notifications

# Check subscription statuses daily at 02:00 UTC
0 2 * * * /usr/bin/php /home/username/public_html/cron/cron-handler.php check-subscriptions

# Update system statistics every hour
0 * * * * /usr/bin/php /home/username/public_html/cron/cron-handler.php update-stats

# Clean up old data daily at 03:00 UTC
0 3 * * * /usr/bin/php /home/username/public_html/cron/cron-handler.php cleanup

# Health check every 6 hours
0 */6 * * * /usr/bin/php /home/username/public_html/cron/cron-handler.php health-check
```

### 5.2 Alternative Setup Script
Run the setup script if SSH access is available:
```bash
chmod +x cron/setup-cron.sh
./cron/setup-cron.sh
```

## Step 6: Composer Dependencies

### 6.1 Install Dependencies
If Composer is available on Hostinger:
```bash
composer install --no-dev --optimize-autoloader
```

### 6.2 Manual Installation
If Composer is not available:
1. Download dependencies locally
2. Upload `vendor` folder to server
3. Ensure autoloader is working

## Step 7: Security Configuration

### 7.1 HTTPS Setup
1. Verify SSL certificate is active
2. Force HTTPS redirects in `.htaccess`:
   ```apache
   RewriteEngine On
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

### 7.2 File Protection
Create `.htaccess` files to protect sensitive directories:

```apache
# /config/.htaccess
Order deny,allow
Deny from all

# /classes/.htaccess
Order deny,allow
Deny from all

# /cron/.htaccess
Order deny,allow
Deny from all

# /docs/.htaccess
Order deny,allow
Deny from all
```

### 7.3 Environment Security
1. Ensure `.env` file is not web-accessible
2. Set strong passwords and secrets
3. Use secure API tokens

## Step 8: Testing Deployment

### 8.1 Basic Functionality Test
1. Access your domain: `https://yourdomain.com`
2. Test user registration and login
3. Create a test game
4. Verify API endpoints are working

### 8.2 Payment Testing
1. Test PayPal sandbox payments
2. Verify subscription creation
3. Test webhook processing
4. Check notification system

### 8.3 Cron Jobs Testing
```bash
# Test individual cron jobs
php cron/cron-handler.php health-check
php cron/cron-handler.php update-stats
```

## Step 9: Production Checklist

### 9.1 Pre-Launch
- [ ] All environment variables configured
- [ ] Database schema imported
- [ ] SSL certificate active
- [ ] PayPal live mode configured
- [ ] Email notifications working
- [ ] Cron jobs scheduled
- [ ] File permissions set correctly
- [ ] Security headers configured

### 9.2 Post-Launch
- [ ] Monitor error logs
- [ ] Check cron job execution
- [ ] Verify payment processing
- [ ] Test notification delivery
- [ ] Monitor system performance
- [ ] Set up backup procedures

## Step 10: Monitoring and Maintenance

### 10.1 Log Monitoring
Monitor these log files:
- `cron/cron.log` - Cron job execution
- Error logs in Hostinger control panel
- PayPal webhook logs
- Application error logs

### 10.2 Performance Monitoring
- API response times
- Database query performance
- Memory usage
- Disk space usage

### 10.3 Regular Maintenance
- **Daily**: Check cron job logs
- **Weekly**: Review system statistics
- **Monthly**: Update dependencies
- **Quarterly**: Security audit

## Troubleshooting

### Common Issues

#### Database Connection Errors
```php
// Check database credentials in .env
// Verify MySQL service is running
// Check database user permissions
```

#### PayPal Integration Issues
- Verify API credentials
- Check webhook URL accessibility
- Ensure HTTPS is working
- Review PayPal developer logs

#### Cron Jobs Not Running
- Check cron job syntax
- Verify PHP path
- Check file permissions
- Review cron logs

#### Email Delivery Issues
- Verify SMTP credentials
- Check spam folders
- Test with different email providers
- Review email logs

### Support Resources
- **Hostinger Support**: Available 24/7 via chat
- **PayPal Developer Support**: developer.paypal.com
- **MAIB Bank Support**: +373-22-269-269
- **Project Documentation**: docs/ folder

## Backup and Recovery

### 10.1 Database Backups
Set up automated database backups:
```bash
# Daily backup script
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

### 10.2 File Backups
- Use Hostinger backup features
- Consider external backup solutions
- Test restore procedures regularly

### 10.3 Recovery Procedures
1. Restore database from backup
2. Restore files from backup
3. Update configuration if needed
4. Test all functionality
5. Monitor for issues

## Performance Optimization

### 10.1 Database Optimization
- Regular OPTIMIZE TABLE operations
- Monitor slow queries
- Add indexes as needed
- Archive old data

### 10.2 Application Optimization
- Enable PHP OPcache
- Optimize API responses
- Implement caching where appropriate
- Monitor memory usage

### 10.3 CDN Configuration
Consider using a CDN for static assets:
- Cloudflare (free tier available)
- MaxCDN
- Amazon CloudFront

This completes the deployment guide for Hostinger. Follow these steps carefully and test thoroughly before going live.
