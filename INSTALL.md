# Analytics Service - Installation Guide

## Requirements
- PHP 7.4+ with PDO, JSON, MBString extensions
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite enabled (or Nginx)
- HTTPS recommended for production

## Installation Steps

### 1. Database Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE analytics_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema
mysql -u root -p analytics_service < sql/schema.sql

# (Optional) Import sample data for testing
mysql -u root -p analytics_service < sql/seed_sample_data.sql
```

### 2. Configuration

Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'analytics_service');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_secure_password');
define('SITE_URL', 'https://your-domain.com');
```

### 3. Set Permissions

```bash
chmod 755 /workspace
chmod 644 /workspace/*.php
chmod 644 /workspace/admin/*.php
chmod 644 /workspace/api/*.php
chmod 644 /workspace/includes/*.php
chmod 600 /workspace/includes/config.php
```

### 4. Change Admin Password (IMPORTANT!)

The default admin credentials are:
- Email: `admin@example.com`
- Password: `admin123`

**Change immediately after first login!**

Or update via SQL:
```sql
UPDATE users SET password_hash = PASSWORD('your_new_secure_password') WHERE email = 'admin@example.com';
-- Or use PHP:
-- password_hash('your_new_secure_password', PASSWORD_DEFAULT)
```

### 5. Apache Configuration

Ensure `.htaccess` files are enabled:
```apache
<Directory /var/www/html>
    AllowOverride All
    Require all granted
</Directory>
```

Enable required modules:
```bash
a2enmod rewrite
a2enmod headers
a2enmod ssl
systemctl restart apache2
```

### 6. HTTPS Setup (Production)

```bash
# Using Let's Encrypt
apt install certbot python3-certbot-apache
certbot --apache -d your-domain.com
```

## Verification

1. Visit `https://your-domain.com/admin/login.php`
2. Login with admin credentials
3. Create a new project in Projects section
4. Copy the tracking code from "Tracking Code" page
5. Paste the code on your website before `</body>` tag
6. Visit your website and check Real-time events in dashboard

## Troubleshooting

### Permission Denied Errors
```bash
chown -R www-data:www-data /workspace
find /workspace -type f -exec chmod 644 {} \;
find /workspace -type d -exec chmod 755 {} \;
```

### Database Connection Failed
- Check MySQL is running: `systemctl status mysql`
- Verify credentials in config.php
- Ensure user has privileges: `GRANT ALL ON analytics_service.* TO 'user'@'localhost';`

### CORS Errors
- Ensure your project domain matches the website URL
- Check browser console for specific CORS errors
- Verify `.htaccess` files are loaded

### Events Not Appearing
- Check browser console for JavaScript errors
- Verify tracking code is installed correctly
- Check `api/track.php` logs: `tail -f /var/log/apache2/error.log`
- Ensure `navigator.sendBeacon` is supported (fallback available)

## Security Checklist

- [ ] Changed default admin password
- [ ] Enabled HTTPS
- [ ] Set secure file permissions
- [ ] Configured firewall (UFW/iptables)
- [ ] Enabled fail2ban for brute-force protection
- [ ] Regular database backups
- [ ] Updated PHP and system packages

## Default Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | admin123 |
| Client | client@example.com | client123 |

**⚠️ CHANGE THESE IMMEDIATELY!**
