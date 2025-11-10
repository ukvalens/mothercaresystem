# Maternal Care System

A comprehensive healthcare management system designed for maternal and child health services with role-based dashboards and complete user workflows.

## Features

### 🏥 **Core Healthcare Management**
- **Patient Registration & Management**: Complete patient records with medical history
- **Appointment Scheduling**: Book, confirm, reschedule, and manage appointments
- **Pregnancy Management**: Register pregnancies with AI-powered risk assessment
- **ANC Visit Tracking**: Record and monitor antenatal care visits
- **Delivery Management**: Track and record delivery information
- **Laboratory System**: Order tests, record results, and generate reports
- **Pharmacy Management**: Prescription management and inventory tracking

### 👥 **Role-Based Access Control**
- **Admin**: System administration and user management
- **Doctor**: Patient care, appointments, medical records
- **Nurse**: Patient care support and medical assistance
- **Receptionist**: Patient registration and appointment management
- **Patient**: Personal health records and appointment booking

### 🔔 **Comprehensive Notification System**
- **Real-time Notifications**: In-system alerts for all major activities
- **Email Notifications**: HTML email templates with professional design
- **SMS Integration**: Mobile money confirmations via Africa's Talking API
- **Role-based Filtering**: Smart notification targeting based on user roles
- **Clinical Alerts**: Automatic alerts for high-risk conditions

### 💳 **Payment Management**
- **Multiple Payment Methods**: Cash, cards, bank transfer, mobile money
- **Payment Tracking**: Complete transaction history and status
- **Mobile Money Integration**: SMS confirmations for mobile payments
- **Billing System**: Automated invoicing and payment processing

### 🤖 **AI-Powered Features**
- **Risk Assessment**: AI-powered pregnancy risk calculation
- **Clinical Alerts**: Automated alerts for abnormal vital signs
- **Predictive Analytics**: Risk scoring based on multiple factors

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer for dependency management

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/ukvalens/mothercaresystem.git
   cd mothercaresystem
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Database Configuration**
   - Copy `app/config/database.example.php` to `app/config/database.php`
   - Update with your database credentials
   - Run `app/config/create_tables.php` to create database tables

4. **Email Configuration**
   - Copy `app/config/email_config.example.php` to `app/config/email_config.php`
   - Configure SMTP settings (Gmail recommended)
   - Generate Gmail App Password for authentication

5. **SMS Configuration (Optional)**
   - Sign up for Africa's Talking API
   - Configure SMS settings in `app/config/sms_config.php`
   - See `setup_sms_integration.md` for detailed setup

## Configuration Files

### Required Configuration
- `app/config/database.php` - Database connection settings
- `app/config/email_config.php` - Email/SMTP configuration

### Optional Configuration
- `app/config/sms_config.php` - SMS gateway settings
- `.env` - Environment-specific variables

## Default Login Credentials

After running the database setup, use these default credentials:

- **Admin**: admin@maternalcare.rw / admin123
- **Doctor**: doctor@maternalcare.rw / doctor123
- **Nurse**: nurse@maternalcare.rw / nurse123
- **Receptionist**: receptionist@maternalcare.rw / receptionist123

## System Architecture

### Directory Structure
```
mothercaresystem/
├── app/
│   ├── config/          # Configuration files
│   └── views/           # Application views
│       ├── auth/        # Authentication pages
│       ├── dashboard/   # Dashboard pages
│       ├── patients/    # Patient management
│       ├── appointments/# Appointment system
│       ├── pregnancies/ # Pregnancy management
│       ├── payments/    # Payment processing
│       ├── messages/    # Messaging system
│       ├── notifications/# Notification system
│       ├── laboratory/  # Lab management
│       ├── pharmacy/    # Pharmacy system
│       └── ai/          # AI risk assessment
├── vendors/             # Third-party libraries
└── public/              # Public assets
```

### Key Technologies
- **Backend**: PHP with MySQLi
- **Frontend**: HTML5, CSS3, JavaScript
- **Email**: PHPMailer with SMTP
- **SMS**: Africa's Talking API
- **Database**: MySQL with optimized queries
- **Security**: Password hashing, SQL injection prevention

## Features Documentation

### Notification System
The system includes a comprehensive notification system that automatically sends alerts for:
- Patient registrations
- Appointment changes
- Payment confirmations
- Clinical alerts (high BP, high-risk pregnancies)
- ANC visit records
- Delivery notifications
- System messages

### AI Risk Assessment
Advanced risk calculation considering:
- Maternal age factors
- Obstetric history
- Medical conditions
- Previous complications
- Real-time vital signs monitoring

### Mobile Money Integration
- SMS confirmations for payments
- Transaction tracking
- Multiple payment method support
- Automated receipt generation

## Security Features

- **Password Security**: Bcrypt hashing with salt
- **SQL Injection Prevention**: Prepared statements
- **Session Management**: Secure session handling
- **Role-based Access**: Strict permission controls
- **Data Validation**: Input sanitization and validation

## Support & Documentation

- **Setup Guides**: Detailed setup instructions in `/docs`
- **API Documentation**: SMS and email integration guides
- **Troubleshooting**: Common issues and solutions
- **Configuration Examples**: Template files for easy setup

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Create an issue on GitHub
- Check the documentation in the `/docs` folder
- Review the setup guides for configuration help

---

**Maternal Care System** - Comprehensive healthcare management for maternal and child health services.