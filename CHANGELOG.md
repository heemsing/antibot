# Changelog - Analytics Service

## [1.1.0] - 2024-01-XX - Bug Fixes & Security Improvements

### 🔒 Security Fixes
- **CSRF Protection**: Added CSRF token validation to login form
- **Session Fixation**: Implemented session_regenerate_id() after successful login
- **CORS Hardening**: Replaced wildcard `*` with domain-based validation
- **Input Validation**: Added filter_var() for email sanitization
- **.htaccess Files**: Added security headers and access restrictions:
  - Root `.htaccess` - blocks access to sensitive files
  - `admin/.htaccess` - CSP headers, X-Frame-Options DENY
  - `api/.htaccess` - limits methods to POST/OPTIONS only
  - `includes/.htaccess` - denies all direct access
  - `sql/.htaccess` - denies all direct access

### 🐛 Bug Fixes
- **JSON Parsing**: Changed to JSON_THROW_ON_ERROR with proper JsonException handling
- **Session Time Calculation**: Fixed to include hours and days (was only seconds + minutes)
- **Tracking Script**: Complete rewrite of bot.php:
  - Now sends events to `/api/track.php` (was only Yandex Metrika)
  - Uses navigator.sendBeacon() for reliable delivery
  - Proper session ID management with sessionStorage
  - Tracks: page views, clicks, scrolls, forms, activity time
  - UTM parameter collection
  - Device/Browser detection
- **Required Fields**: Removed 'tracking_code' from required fields in API (validated earlier)

### ✨ New Features
- **Installation Guide**: Created comprehensive INSTALL.md
- **Changelog**: This CHANGELOG.md file
- **Security Headers**: X-Content-Type-Options, X-XSS-Protection, Referrer-Policy
- **Error Logging**: Improved error logging with context

### 📝 Documentation
- Updated default credentials warning
- Added security checklist
- Added troubleshooting section
- Documented CORS configuration

### 🔧 Code Quality
- Added validateCsrfToken() as alias for verifyCsrfToken() for consistency
- Improved error messages with specific details
- Better code comments in multiple languages (EN/RU)

---

## [1.0.0] - Initial Release

### Core Features
- User authentication with roles (Admin/Client)
- Project management
- Goal tracking
- Event collection API
- Session tracking
- Dashboard with basic statistics
- MySQL database with partitioned tables

### Known Issues (Fixed in 1.1.0)
- CSRF not validated on login
- CORS wildcard allowed all origins
- bot.php didn't send data to API
- Session time calculation incorrect
- No security headers
- Sensitive files accessible directly
