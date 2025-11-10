# Email Setup Instructions

## 1. Configure Gmail SMTP

1. Go to your Gmail account settings
2. Enable 2-Factor Authentication
3. Generate App Password:
   - Go to Security → App passwords
   - Select "Mail" and generate password
   - Copy the 16-character password

## 2. Update Email Configuration

Edit `app/config/email_config.php`:

```php
define('SMTP_USERNAME', 'your-gmail@gmail.com');
define('SMTP_PASSWORD', 'your-16-char-app-password');
```

## 3. Test the System

1. Run: `http://localhost/mothercaresystem/add_reset_columns.php`
2. Go to: `http://localhost/mothercaresystem/app/views/auth/forgot-password.php`
3. Enter a valid email address
4. Check your email for reset link

## Files Installed:
- ✅ PHPMailer library
- ✅ Email configuration
- ✅ Updated forgot password page
- ✅ Reset password page

## Ready to use!