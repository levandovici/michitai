# Multiplayer API Web Constructor

A comprehensive multiplayer API web constructor hosted on Hostinger with user management, game configuration, puzzle logic constructor, and subscription-based payments via PayPal with MAIB Moldova bank integration.

## 🚀 Features

### Core Functionality
- **User Management**: Registration, authentication, API token-based access
- **Game Configuration**: Create and manage multiplayer games with custom logic
- **Puzzle Logic Constructor**: Drag-and-drop interface for building game logic
- **Player Management**: Handle player data with cross-platform compatibility
- **Subscription System**: PayPal integration with Paynet/MAIB fallback options
- **Real-time Notifications**: Email and Slack notifications for limits and events

### Technical Highlights
- **Cross-Platform Data Types**: Compatible with JavaScript, PHP, and C#
- **Strict Limits Enforcement**: System (200GB/200 users) and plan-based limits
- **Security First**: bcrypt hashing, PDO prepared statements, API tokens
- **Payment Integration**: PayPal primary, Paynet fallback, MAIB bank transfers
- **Automated Monitoring**: Cron jobs for maintenance and health checks

## 📋 System Requirements

### Hosting (Hostinger)
- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or higher
- **SSL Certificate**: Required for HTTPS
- **Cron Jobs**: Business plan or higher
- **Storage**: 200GB system limit

### External Services
- **PayPal Business Account**: For payment processing
- **MAIB Bank Account**: For Moldova bank transfers (SWIFT: AGRNMD2X)
- **SMTP Service**: For email notifications
- **Slack Workspace**: Optional, for Pro user notifications

## 🏗️ Project Structure

```
api/
├── classes/                 # Core PHP classes
│   ├── Auth.php            # Authentication and rate limiting
│   ├── GameManager.php     # Game and room management
│   ├── PlayerManager.php   # Player data management
│   ├── PaymentManager.php  # PayPal and payment processing
│   └── NotificationManager.php # Email and Slack notifications
├── config/
│   └── database.php        # Database configuration and constants
├── database/
│   └── schema.sql          # MySQL database schema
├── api/
│   └── index.php           # REST API router
├── public/                 # Frontend files
│   ├── index.html          # Main SPA interface
│   └── js/
│       ├── app.js          # Main application logic
│       ├── puzzle-constructor.js # Drag-and-drop logic builder
│       └── payment.js      # Payment integration
├── cron/
│   ├── cron-handler.php    # Scheduled tasks handler
│   └── setup-cron.sh       # Cron jobs setup script
├── tests/
│   └── ApiTest.php         # Automated testing framework
├── docs/
│   ├── SECURITY.md         # Security and compliance guide
│   └── DEPLOYMENT.md       # Hostinger deployment guide
├── .env.example            # Environment configuration template
└── composer.json           # PHP dependencies
```

## 🛠️ Installation

### 1. Clone Repository
```bash
git clone <repository-url>
cd api
```

### 2. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### 3. Configure Environment
```bash
cp .env.example .env
# Edit .env with your database and API credentials
```

### 4. Setup Database
```sql
-- Create database
CREATE DATABASE multiplayer_api;

-- Import schema
mysql -u username -p multiplayer_api < database/schema.sql
```

### 5. Configure Cron Jobs
```bash
chmod +x cron/setup-cron.sh
./cron/setup-cron.sh
```

## 📊 Subscription Plans

| Feature | Free | Standard | Pro |
|---------|------|----------|-----|
| **Storage** | 1GB | 10GB | 50GB |
| **Users** | 10 | 50 | 200 |
| **Games** | 3 | 20 | 100 |
| **Players per Game** | 10 | 50 | 200 |
| **Rooms per Game** | 5 | 25 | 100 |
| **Communities** | 1 | 5 | 20 |
| **Messages per Day** | 100 | 1000 | 5000 |
| **API Calls per Day** | 1000 | 10000 | 50000 |
| **Notifications** | Email | Email | Email + Slack |
| **Price** | Free | $9.99/month | $29.99/month |

## 🔧 API Endpoints

### Authentication
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `GET /api/user` - Get user profile

### Game Management
- `POST /api/game/create` - Create new game
- `GET /api/game/get/{id}` - Get game details
- `POST /api/game/update` - Update game
- `DELETE /api/game/delete/{id}` - Delete game

### Player Management
- `POST /api/player/create` - Create player
- `POST /api/player/auth` - Authenticate player
- `POST /api/player/update` - Update player data
- `GET /api/player/get/{id}` - Get player data

### Subscriptions
- `POST /api/subscribe` - Create subscription
- `POST /api/subscription/cancel` - Cancel subscription
- `GET /api/subscription/status` - Get subscription status

### Monitoring
- `GET /api/monitor/user` - User statistics
- `GET /api/monitor/system` - System statistics

## 💳 Payment Integration

### PayPal (Primary)
- Global Visa/Mastercard support
- Automatic subscription management
- Webhook integration for real-time updates
- Payouts to MAIB Moldova bank (SWIFT: AGRNMD2X)

### Paynet (Fallback - Moldova)
- Local payment processing for Moldova
- Manual verification required
- Bank transfer instructions provided

### MAIB Bank Transfer (Manual)
- Direct bank transfer option
- Manual verification process
- Moldova-specific banking integration

## 🔒 Security Features

### Authentication
- bcrypt password hashing
- API token-based authentication
- Rate limiting per plan
- Session management

### Data Protection
- PDO prepared statements
- Input validation and sanitization
- HTTPS enforcement
- Cross-platform data type validation

### Payment Security
- PCI-compliant payment processing
- Secure webhook handling
- Encrypted sensitive data storage
- GDPR compliance

## 🧪 Testing

### Automated Testing
```bash
# Run full test suite
php tests/ApiTest.php http://localhost/api

# Test specific functionality
php tests/ApiTest.php https://yourdomain.com/api
```

### Manual Testing Checklist
- [ ] User registration and login
- [ ] Game creation and management
- [ ] Player data operations
- [ ] Payment flow (PayPal sandbox)
- [ ] Notification delivery
- [ ] Rate limiting enforcement
- [ ] Memory usage tracking
- [ ] Cron job execution

## 📈 Monitoring

### System Health
- Memory usage tracking
- API call monitoring
- Database performance
- Error logging

### Automated Tasks
- Daily API call resets
- Timer updates (every minute)
- Subscription status checks
- Notification processing
- System statistics updates
- Data cleanup and maintenance

## 🚀 Deployment

### Hostinger Deployment
1. Follow the detailed [Deployment Guide](docs/DEPLOYMENT.md)
2. Configure SSL certificate
3. Set up cron jobs
4. Configure environment variables
5. Test all functionality

### Production Checklist
- [ ] SSL certificate active
- [ ] Environment variables configured
- [ ] Database schema imported
- [ ] PayPal live mode configured
- [ ] Cron jobs scheduled
- [ ] Monitoring setup
- [ ] Backup procedures in place

## 📚 Documentation

- **[Security Guide](docs/SECURITY.md)** - Security and compliance requirements
- **[Deployment Guide](docs/DEPLOYMENT.md)** - Step-by-step Hostinger deployment
- **[API Documentation](docs/API.md)** - Complete API reference
- **[Testing Guide](tests/)** - Automated testing framework

## 🛠️ Development

### Local Development
```bash
# Start local server
php -S localhost:8000 -t public/

# Run tests
php tests/ApiTest.php http://localhost:8000/api

# Check code quality
composer run-script phpstan
composer run-script phpcs
```

### Code Quality
- PHPStan for static analysis
- PHP CodeSniffer for coding standards
- PHPUnit for unit testing
- Automated security scanning

## 🔧 Configuration

### Environment Variables
```env
# Database
DB_HOST=localhost
DB_NAME=multiplayer_api
DB_USERNAME=your_username
DB_PASSWORD=your_password

# PayPal
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_CLIENT_SECRET=your_client_secret

# SMTP
SMTP_HOST=smtp.hostinger.com
SMTP_PORT=587
SMTP_USERNAME=noreply@yourdomain.com
SMTP_PASSWORD=your_password

# Slack (Optional)
SLACK_WEBHOOK_URL=https://hooks.slack.com/...
```

### System Limits
- **Maximum Storage**: 200GB
- **Maximum Users**: 200
- **Memory per User**: Based on plan
- **API Rate Limits**: Plan-dependent

## 🤝 Support

### Technical Support
- **Documentation**: Complete guides in `docs/` folder
- **Testing**: Automated test suite in `tests/`
- **Monitoring**: Built-in health checks and logging

### Payment Support
- **PayPal**: developer.paypal.com
- **MAIB Bank**: +373-22-269-269
- **Paynet**: Moldova local support

### Hosting Support
- **Hostinger**: 24/7 chat support
- **SSL Issues**: Hostinger control panel
- **Cron Jobs**: Business plan required

## 📄 License

This project is proprietary software. All rights reserved.

## 🏆 Features Completed

✅ User authentication and management  
✅ Game creation and configuration  
✅ Puzzle logic constructor with drag-and-drop  
✅ Player data management  
✅ PayPal subscription integration  
✅ Paynet and MAIB fallback payments  
✅ Email and Slack notifications  
✅ Rate limiting and memory management  
✅ Cross-platform data type compatibility  
✅ Automated testing framework  
✅ Security and compliance measures  
✅ Hostinger deployment configuration  
✅ Cron jobs for maintenance  
✅ Comprehensive documentation  

## 🎯 Ready for Production

This multiplayer API web constructor is fully implemented and ready for deployment on Hostinger. All core features are complete, tested, and documented. The system enforces strict limits, provides secure payment processing, and includes comprehensive monitoring and maintenance capabilities.

**Next Steps**: Follow the [Deployment Guide](docs/DEPLOYMENT.md) to deploy on Hostinger and start accepting users and payments.
