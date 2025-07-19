# SJA Foundation Investment Management Platform

A comprehensive web-based investment management platform designed for SJA Foundation with integrated wallet systems, KYC verification, and automated commission tracking.

## 🚀 Features

### Core Features
- **User Management**: Secure registration with referral system
- **Investment Management**: Flexible investment plans with automated calculations
- **Wallet System**: Real-time balance tracking and transaction history
- **KYC Verification**: Complete document verification system with video KYC
- **Commission System**: Multi-level commission structure with automated payouts
- **Admin Panel**: Comprehensive administrative dashboard
- **Mobile Responsive**: Works seamlessly across all devices

### Security Features
- Secure authentication with session management
- Password hashing with bcrypt
- CSRF protection
- Input validation and sanitization
- File upload security
- SQL injection prevention

### Technical Features
- Modern PHP 8.0+ architecture
- MySQL database with optimized queries
- Bootstrap 5 responsive design
- Chart.js for data visualization
- Font Awesome icons
- Progressive Web App ready

## 📋 Requirements

### Server Requirements
- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or higher (or MariaDB 10.5+)
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **SSL Certificate**: Required for production
- **Storage**: Minimum 50GB SSD

### PHP Extensions
- mysqli
- pdo
- gd
- json
- mbstring
- openssl
- fileinfo

### Optional Extensions
- redis (for caching)
- memcached (for caching)
- imagick (for image processing)
- zip (for file compression)

## 🛠️ Installation

### Method 1: Automated Installation (Recommended)

1. **Upload Files**
   ```bash
   # Upload all files to your web server
   # Ensure the web server has write permissions to the following directories:
   # - assets/uploads/
   # - assets/images/
   # - logs/
   ```

2. **Set Permissions**
   ```bash
   chmod 755 sja-platform/
   chmod 755 sja-platform/assets/uploads/
   chmod 755 sja-platform/assets/images/
   chmod 755 sja-platform/logs/
   ```

3. **Run Installation Wizard**
   - Open your browser and navigate to: `http://your-domain.com/sja-platform/install/setup.php`
   - Follow the step-by-step installation process
   - Enter your database credentials
   - Configure site settings
   - Create admin account

4. **Complete Installation**
   - The installer will automatically create all database tables
   - Generate configuration files
   - Set up default admin user
   - Create required directories

### Method 2: Manual Installation

1. **Database Setup**
   ```sql
   -- Create database
   CREATE DATABASE sja_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   
   -- Create user (optional)
   CREATE USER 'sja_user'@'localhost' IDENTIFIED BY 'your_secure_password';
   GRANT ALL PRIVILEGES ON sja_platform.* TO 'sja_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Import Database Schema**
   ```bash
   mysql -u your_username -p sja_platform < install/database.sql
   ```

3. **Configure Database**
   - Edit `includes/config.php`
   - Update database connection details
   - Set site URL and other configurations

4. **Set Permissions**
   ```bash
   chmod 755 assets/uploads/
   chmod 755 assets/images/
   chmod 755 logs/
   ```

## 🔧 Configuration

### Database Configuration
Edit `includes/config.php`:
```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'sja_platform');
define('DB_USER', 'sja_user');
define('DB_PASS', 'your_secure_password');

// Site Configuration
define('SITE_URL', 'https://your-domain.com/sja-platform');
define('SITE_NAME', 'SJA Foundation');
define('ADMIN_EMAIL', 'admin@sja-foundation.com');
```

### Security Configuration
```php
// Security Settings
define('ENCRYPTION_KEY', 'your-32-character-encryption-key');
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes
```

### Commission Structure
The platform includes a 12-level commission structure:
1. Professional Ambassador (₹1L-20L) - 0.25%
2. Rubies Ambassador (₹30L) - 0.37%
3. Topaz Ambassador (₹40L) - 0.50%
4. Silver Ambassador (₹50L) - 0.70%
5. Golden Ambassador (₹60L) - 0.85%
6. Platinum Ambassador (₹70L) - 1.00%
7. Diamond Ambassador (₹80L) - 1.25%
8. MTA (₹90L) - 1.50%
9. Channel Partner (₹1CR) - 2.00%
10. Co-Director
11. Director
12. MD/CEO/CMD

## 📁 Directory Structure

```
sja-platform/
├── admin/                 # Admin panel files
│   ├── index.php         # Admin dashboard
│   ├── users.php         # User management
│   ├── investments.php   # Investment management
│   ├── transactions.php  # Transaction management
│   ├── kyc.php          # KYC management
│   ├── withdrawals.php   # Withdrawal management
│   ├── reports.php      # Reports and analytics
│   └── settings.php     # System settings
├── client/               # Client portal files
│   ├── index.php        # Client dashboard
│   ├── profile.php      # Profile management
│   ├── investments.php  # Investment interface
│   ├── wallet.php       # Wallet management
│   └── referrals.php    # Referral system
├── assets/              # Static assets
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   ├── images/         # Images and icons
│   └── uploads/        # User uploaded files
├── includes/           # Core PHP files
│   ├── config.php     # Configuration
│   ├── database.php   # Database connection
│   ├── auth.php       # Authentication system
│   └── functions.php  # Utility functions
├── api/               # API endpoints
│   ├── auth.php       # Authentication API
│   ├── transactions.php # Transaction API
│   └── notifications.php # Notification API
├── install/           # Installation files
│   ├── setup.php      # Installation wizard
│   └── database.sql   # Database schema
├── index.php          # Main landing page
├── login.php          # Login page
├── register.php       # Registration page
├── logout.php         # Logout script
└── README.md          # This file
```

## 🔐 Security Features

### Authentication & Authorization
- Secure password hashing with bcrypt
- Session-based authentication
- Role-based access control (Admin/Client)
- Session timeout protection
- Brute force protection

### Data Protection
- Input validation and sanitization
- SQL injection prevention with prepared statements
- XSS protection with output encoding
- CSRF token validation
- Secure file upload validation

### File Security
- Restricted file upload types
- Secure file storage outside web root
- File size limitations
- Virus scanning integration ready

## 📊 Database Schema

The platform uses 15 main tables:
- `users` - User accounts and authentication
- `clients` - Client-specific information
- `investments` - Investment records
- `transactions` - Financial transactions
- `wallets` - User wallet balances
- `kyc_docs` - KYC document storage
- `nominees` - Nominee information
- `referrals` - Referral relationships
- `earnings` - Commission and earnings
- `notifications` - System notifications
- `activity_logs` - User activity tracking
- `sessions` - Session management
- `investment_plans` - Investment plan definitions
- `withdrawal_requests` - Withdrawal applications
- `video_kyc` - Video KYC records
- `system_settings` - System configuration

## 🚀 Deployment

### Production Deployment Checklist

1. **Server Setup**
   - [ ] SSL certificate installed
   - [ ] PHP 8.0+ installed with required extensions
   - [ ] MySQL 8.0+ installed and configured
   - [ ] Web server configured (Apache/Nginx)

2. **Application Setup**
   - [ ] Files uploaded to web server
   - [ ] Database created and schema imported
   - [ ] Configuration files updated
   - [ ] File permissions set correctly

3. **Security Setup**
   - [ ] HTTPS enabled
   - [ ] Firewall configured
   - [ ] Database user with minimal privileges
   - [ ] Regular backup schedule configured

4. **Performance Setup**
   - [ ] OPcache enabled
   - [ ] Database indexes optimized
   - [ ] CDN configured (optional)
   - [ ] Caching enabled (optional)

### Nginx Configuration Example
```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    root /var/www/sja-platform;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
    
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

## 🔧 Maintenance

### Regular Maintenance Tasks
1. **Database Backups**
   ```bash
   # Daily backup script
   mysqldump -u username -p sja_platform > backup_$(date +%Y%m%d).sql
   ```

2. **Log Rotation**
   ```bash
   # Rotate application logs
   logrotate /etc/logrotate.d/sja-platform
   ```

3. **Security Updates**
   - Keep PHP updated
   - Keep MySQL updated
   - Monitor security advisories

4. **Performance Monitoring**
   - Monitor server resources
   - Check database performance
   - Review error logs

### Backup Strategy
- **Daily**: Database backup
- **Weekly**: Full system backup
- **Monthly**: Long-term archive backup
- **Real-time**: Database replication (optional)

## 🆘 Support

### Getting Help
1. **Documentation**: Check this README and inline code comments
2. **Error Logs**: Check `logs/error.log` for detailed error information
3. **Database Logs**: Check MySQL error logs for database issues
4. **Web Server Logs**: Check Apache/Nginx logs for server issues

### Common Issues

#### Installation Issues
- **Database Connection Failed**: Check database credentials and server connectivity
- **Permission Denied**: Ensure proper file permissions (755 for directories, 644 for files)
- **Missing Extensions**: Install required PHP extensions

#### Runtime Issues
- **Session Problems**: Check session configuration and storage permissions
- **File Upload Issues**: Verify upload directory permissions and PHP settings
- **Performance Issues**: Enable OPcache and optimize database queries

### Contact Information
- **Email**: support@sja-foundation.com
- **Phone**: +91 98765 43210
- **Website**: https://sja-foundation.com

## 📄 License

This software is proprietary and confidential to SJA Foundation. All rights reserved.

## 🔄 Version History

### Version 1.0 (July 2025)
- Initial release
- Complete investment management system
- KYC verification system
- Commission tracking
- Admin panel
- Mobile responsive design

---

**SJA Foundation Investment Management Platform** - Professional investment management solution for modern businesses.