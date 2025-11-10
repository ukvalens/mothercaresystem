# Deployment Guide - Maternal Care System

## 🚀 Deployment Options

### 1. **Local Development (XAMPP/WAMP)**
```bash
# Already set up in c:\xampp\htdocs\mothercaresystem
# Access via: http://localhost/mothercaresystem
```

### 2. **Shared Hosting (cPanel)**
1. **Upload Files**: Upload all files to public_html folder
2. **Create Database**: Create MySQL database via cPanel
3. **Configure**: Update `app/config/database.php` with hosting credentials
4. **Run Setup**: Execute `app/config/create_tables.php` once

### 3. **VPS/Cloud Hosting**
```bash
# Clone repository
git clone https://github.com/ukvalens/mothercaresystem.git
cd mothercaresystem

# Install dependencies
composer install

# Set permissions
chmod -R 755 app/
chmod -R 777 app/config/

# Configure web server (Apache/Nginx)
```

### 4. **Free Hosting Options**
- **InfinityFree**: Free PHP/MySQL hosting
- **000webhost**: Free hosting with PHP support
- **Heroku**: Free tier with ClearDB MySQL addon
- **Railway**: Free deployment with database

## 📋 Pre-Deployment Checklist

### Required Configuration Files
- [ ] `app/config/database.php` - Database credentials
- [ ] `app/config/email_config.php` - SMTP settings
- [ ] `app/config/sms_config.php` - SMS gateway (optional)

### Database Setup
- [ ] Create MySQL database
- [ ] Run `app/config/create_tables.php`
- [ ] Verify default users created

### Security Settings
- [ ] Change default passwords
- [ ] Update email credentials
- [ ] Set proper file permissions
- [ ] Enable HTTPS (recommended)

## 🌐 Website Pages Created

### 1. **Landing Page**: `/public/index.html`
- Professional homepage
- Feature showcase
- System overview
- Login access

### 2. **System Access**: `/app/views/auth/login.php`
- User authentication
- Role-based dashboards
- Password reset functionality

### 3. **User Dashboards**: `/app/views/dashboard/index.php`
- Admin dashboard
- Doctor dashboard
- Nurse dashboard
- Receptionist dashboard
- Patient portal

## 🔧 Quick Deployment Steps

### For Shared Hosting:
1. **Download/Clone** the repository
2. **Upload** all files to your hosting account
3. **Create** MySQL database via hosting control panel
4. **Copy** `app/config/database.example.php` to `database.php`
5. **Update** database credentials in `database.php`
6. **Run** `yourdomain.com/mothercaresystem/app/config/create_tables.php`
7. **Access** `yourdomain.com/mothercaresystem/public/index.html`

### For Local Testing:
1. **Ensure** XAMPP is running (Apache + MySQL)
2. **Access** `http://localhost/mothercaresystem/public/index.html`
3. **Login** with default credentials
4. **Test** all features

## 📱 Mobile Responsiveness
- Fully responsive design
- Mobile-friendly interface
- Touch-optimized controls
- Progressive Web App ready

## 🔒 Security Features
- Password hashing (bcrypt)
- SQL injection prevention
- Session management
- Role-based access control
- CSRF protection

## 📊 Performance Optimization
- Optimized database queries
- Compressed assets
- Caching headers
- Minimal dependencies

## 🆘 Troubleshooting

### Common Issues:
1. **Database Connection Error**
   - Check credentials in `database.php`
   - Verify database server is running

2. **Email Not Sending**
   - Update SMTP settings in `email_config.php`
   - Check Gmail app password

3. **Permission Denied**
   - Set proper file permissions (755/777)
   - Check web server configuration

4. **404 Errors**
   - Verify .htaccess file exists
   - Check web server mod_rewrite

## 📞 Support
- **GitHub**: https://github.com/ukvalens/mothercaresystem
- **Email**: ukwitegetsev9@gmail.com
- **Documentation**: Check README.md and setup guides

---

**Ready to deploy!** The system is production-ready with comprehensive features and security measures.