#!/bin/bash

# Cron Jobs Setup Script for Multiplayer API Web Constructor
# This script sets up all necessary cron jobs for Hostinger deployment

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Setting up cron jobs for Multiplayer API Web Constructor...${NC}"

# Get the current directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# PHP path (adjust for Hostinger)
PHP_PATH="/usr/bin/php"

# Check if PHP exists
if ! command -v $PHP_PATH &> /dev/null; then
    echo -e "${YELLOW}Warning: PHP not found at $PHP_PATH. Please adjust PHP_PATH in this script.${NC}"
    PHP_PATH="php" # Fallback to system PHP
fi

echo -e "${YELLOW}Using PHP path: $PHP_PATH${NC}"
echo -e "${YELLOW}Project directory: $PROJECT_DIR${NC}"

# Create cron jobs
CRON_JOBS="
# Multiplayer API Web Constructor Cron Jobs
# Reset API calls daily at 00:00 UTC
0 0 * * * $PHP_PATH $PROJECT_DIR/cron/cron-handler.php reset-api-calls >> $PROJECT_DIR/cron/cron.log 2>&1

# Update timers every minute
* * * * * $PHP_PATH $PROJECT_DIR/cron/cron-handler.php update-timers >> $PROJECT_DIR/cron/cron.log 2>&1

# Process notifications every 5 minutes
*/5 * * * * $PHP_PATH $PROJECT_DIR/cron/cron-handler.php process-notifications >> $PROJECT_DIR/cron/cron.log 2>&1

# Check subscription statuses daily at 02:00 UTC
0 2 * * * $PHP_PATH $PROJECT_DIR/cron/cron-handler.php check-subscriptions >> $PROJECT_DIR/cron/cron.log 2>&1

# Update system statistics every hour
0 * * * * $PHP_PATH $PROJECT_DIR/cron/cron-handler.php update-stats >> $PROJECT_DIR/cron/cron.log 2>&1

# Clean up old data daily at 03:00 UTC
0 3 * * * $PHP_PATH $PROJECT_DIR/cron/cron-handler.php cleanup >> $PROJECT_DIR/cron/cron.log 2>&1

# Health check every 6 hours
0 */6 * * * $PHP_PATH $PROJECT_DIR/cron/cron-handler.php health-check >> $PROJECT_DIR/cron/cron.log 2>&1
"

# Backup existing crontab
echo -e "${YELLOW}Backing up existing crontab...${NC}"
crontab -l > "$PROJECT_DIR/cron/crontab.backup" 2>/dev/null || echo "No existing crontab found"

# Add new cron jobs
echo -e "${YELLOW}Adding new cron jobs...${NC}"
(crontab -l 2>/dev/null; echo "$CRON_JOBS") | crontab -

# Verify cron jobs were added
echo -e "${GREEN}Current crontab:${NC}"
crontab -l

# Create log file with proper permissions
touch "$PROJECT_DIR/cron/cron.log"
chmod 644 "$PROJECT_DIR/cron/cron.log"

# Create log rotation script
cat > "$PROJECT_DIR/cron/rotate-logs.sh" << 'EOF'
#!/bin/bash
# Log rotation script for cron logs

LOG_FILE="/path/to/your/project/cron/cron.log"
MAX_SIZE=10485760  # 10MB in bytes

if [ -f "$LOG_FILE" ]; then
    SIZE=$(stat -c%s "$LOG_FILE" 2>/dev/null || echo 0)
    if [ $SIZE -gt $MAX_SIZE ]; then
        mv "$LOG_FILE" "$LOG_FILE.old"
        touch "$LOG_FILE"
        chmod 644 "$LOG_FILE"
        echo "$(date): Log rotated" >> "$LOG_FILE"
    fi
fi
EOF

chmod +x "$PROJECT_DIR/cron/rotate-logs.sh"

# Add log rotation to crontab
echo -e "${YELLOW}Adding log rotation...${NC}"
(crontab -l 2>/dev/null; echo "0 4 * * * $PROJECT_DIR/cron/rotate-logs.sh") | crontab -

echo -e "${GREEN}Cron jobs setup completed!${NC}"
echo -e "${YELLOW}Cron jobs added:${NC}"
echo "- API calls reset: Daily at 00:00 UTC"
echo "- Timer updates: Every minute"
echo "- Notification processing: Every 5 minutes"
echo "- Subscription checks: Daily at 02:00 UTC"
echo "- System statistics: Every hour"
echo "- Data cleanup: Daily at 03:00 UTC"
echo "- Health checks: Every 6 hours"
echo "- Log rotation: Daily at 04:00 UTC"

echo -e "${GREEN}Setup complete! Check $PROJECT_DIR/cron/cron.log for cron job output.${NC}"

# Test cron handler
echo -e "${YELLOW}Testing cron handler...${NC}"
$PHP_PATH "$PROJECT_DIR/cron/cron-handler.php" health-check

echo -e "${GREEN}If you see no errors above, the cron system is ready!${NC}"
