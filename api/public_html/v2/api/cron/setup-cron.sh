#!/bin/bash
# Cron jobs setup for api.michitai.com (using private directory structure)
# Updated for Hostinger secure folder structure

CRON_PATH="/home/u833544264/domains/api.michitai.com/private/api_backend/cron"
LOG_PATH="/home/u833544264/domains/api.michitai.com/private/logs"

echo "Setting up cron jobs for Michitai API..."

# Create log directory if it doesn't exist
mkdir -p "$LOG_PATH"

# Make sure cron handler is executable
chmod +x "$CRON_PATH/cron-handler.php"

# Remove any existing Michitai API cron jobs
crontab -l 2>/dev/null | grep -v "Michitai API" | crontab -

# Add new cron jobs
echo "Adding cron jobs..."

# Reset API calls daily at 00:00 UTC
(crontab -l 2>/dev/null; echo "# Michitai API Cron Jobs") | crontab -
(crontab -l 2>/dev/null; echo "0 0 * * * /usr/bin/php $CRON_PATH/cron-handler.php reset-api-calls >> $LOG_PATH/cron.log 2>&1") | crontab -

# Update timers every minute
(crontab -l 2>/dev/null; echo "* * * * * /usr/bin/php $CRON_PATH/cron-handler.php update-timers >> $LOG_PATH/cron.log 2>&1") | crontab -

# Process notifications every 5 minutes
(crontab -l 2>/dev/null; echo "*/5 * * * * /usr/bin/php $CRON_PATH/cron-handler.php process-notifications >> $LOG_PATH/cron.log 2>&1") | crontab -

# Check subscription statuses daily at 02:00 UTC
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/bin/php $CRON_PATH/cron-handler.php check-subscriptions >> $LOG_PATH/cron.log 2>&1") | crontab -

# Update system statistics every hour
(crontab -l 2>/dev/null; echo "0 * * * * /usr/bin/php $CRON_PATH/cron-handler.php update-stats >> $LOG_PATH/cron.log 2>&1") | crontab -

# Clean up old data daily at 03:00 UTC
(crontab -l 2>/dev/null; echo "0 3 * * * /usr/bin/php $CRON_PATH/cron-handler.php cleanup >> $LOG_PATH/cron.log 2>&1") | crontab -

# Health check every 6 hours
(crontab -l 2>/dev/null; echo "0 */6 * * * /usr/bin/php $CRON_PATH/cron-handler.php health-check >> $LOG_PATH/cron.log 2>&1") | crontab -

# Log rotation weekly on Sunday at 04:00 UTC
(crontab -l 2>/dev/null; echo "0 4 * * 0 /usr/bin/find $LOG_PATH -name '*.log' -type f -mtime +7 -delete") | crontab -

echo "Cron jobs configured successfully!"
echo "Current cron jobs:"
crontab -l

echo ""
echo "Log files will be created in: $LOG_PATH"
echo "To monitor cron execution: tail -f $LOG_PATH/cron.log"
echo ""
echo "Setup complete for api.michitai.com!"
