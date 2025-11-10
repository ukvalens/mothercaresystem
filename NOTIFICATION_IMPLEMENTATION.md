# Comprehensive Notification System Implementation

## Overview
The Maternal Care System now has a complete notification system that automatically sends notifications for all major system activities. Users receive notifications both in the system and via email for important events.

## Implemented Notifications

### 1. Patient Registration
**File**: `app/views/patients/register.php`
**Trigger**: When a new patient is registered
**Recipients**: 
- Medical staff (Doctors, Nurses) - notified of new patient
- Admin - notified of registration activity
**Notification**: "New patient [Name] has been registered in the system"

### 2. Appointment Management
**Files**: 
- `app/views/appointments/book.php` (patient booking)
- `app/views/appointments/view.php` (doctor management)

**Triggers & Recipients**:
- **Appointment Scheduled**: Doctor receives notification of new appointment
- **Appointment Confirmed**: Patient receives confirmation notification
- **Appointment Rejected**: Patient receives rejection notice, Admin receives alert
- **Appointment Rescheduled**: Patient receives new schedule details
- **Appointment Completed**: Patient receives completion confirmation

### 3. Pregnancy Management
**File**: `app/views/pregnancies/register.php`
**Triggers & Recipients**:
- **Pregnancy Registered**: 
  - Medical staff notified of new pregnancy
  - Patient receives registration confirmation with EDD
- **High Risk Pregnancy**: 
  - Doctors receive immediate high-risk alerts
  - Special monitoring notifications activated

### 4. Payment Processing
**File**: `app/views/payments/process.php`
**Triggers & Recipients**:
- **Payment Completed**: 
  - Patient receives payment confirmation
  - Admin receives financial update
- **Payment Overdue**: 
  - Patient receives overdue notice
  - Admin and Receptionist receive collection alerts

### 5. ANC Visit Recording
**File**: `app/views/visits/anc_visit.php`
**Triggers & Recipients**:
- **ANC Visit Recorded**: Patient receives visit summary
- **Clinical Alerts**: Doctors receive alerts for abnormal vital signs (high BP, etc.)

### 6. Delivery Management
**File**: `app/views/deliveries/record_delivery.php`
**Triggers & Recipients**:
- **Delivery Recorded**: 
  - All medical staff notified of successful delivery
  - Patient receives congratulations and record confirmation
  - Admin receives birth statistics update

### 7. Messaging System
**File**: `app/views/messages/index.php`
**Trigger**: When a message is sent
**Recipients**: Message recipient receives notification of new message

## Notification Types

### System Notifications
- **Patient Registration**: New patient alerts
- **Appointment**: Booking, status changes, reminders
- **Pregnancy**: Registration, risk updates
- **Payment**: Confirmations, overdue alerts
- **Medical Record**: ANC visits, delivery records
- **Message**: New message alerts
- **Clinical Alert**: High-risk conditions, abnormal vitals
- **System Activity**: Administrative notifications

### Email Notifications
All system notifications are also sent via email using the configured SMTP system with HTML templates.

## Automated Daily Checks
**File**: `app/config/activity_hooks.php` - `run_daily_checks()` function

### Automated Notifications:
1. **Overdue Payment Alerts**: Daily check for payments past due date
2. **Appointment Reminders**: Next-day appointment notifications
3. **High-Risk Pregnancy Monitoring**: Alerts for high-risk patients without recent visits

## Notification Features

### Multi-Channel Delivery
- **In-System**: Notifications appear in user dashboard
- **Email**: HTML email notifications with professional templates
- **SMS**: Mobile money confirmations (Africa's Talking API)

### Role-Based Targeting
- **Doctors**: Clinical alerts, high-risk notifications, appointment updates
- **Nurses**: Patient care notifications, clinical alerts
- **Receptionist**: Payment alerts, appointment management
- **Admin**: System activity, financial updates, user management
- **Patients**: Personal health updates, appointment confirmations

### Smart Filtering
- Notifications are filtered by user role and relevance
- High-priority clinical alerts get immediate delivery
- System automatically prevents notification spam

## Configuration Files

### Core Notification System
- `app/config/notification_system.php` - Main notification functions
- `app/config/activity_hooks.php` - Activity tracking and hooks
- `app/config/email_config.php` - Email delivery system

### Integration Points
All major system actions now include notification hooks:
```php
// Example usage in any file
require_once '../../config/activity_hooks.php';

// After successful operation
hook_patient_registered($patient_id, $_SESSION['user_id']);
hook_appointment_activity($appointment_id, 'confirmed', $_SESSION['user_id']);
hook_payment_activity($transaction_id, 'completed', $_SESSION['user_id']);
```

## Benefits

### For Healthcare Providers
- Immediate alerts for high-risk conditions
- Automated appointment management
- Real-time patient activity updates
- Streamlined communication workflow

### For Patients
- Automatic appointment confirmations
- Health record updates
- Payment confirmations
- Important health alerts

### For Administrators
- Complete system activity oversight
- Financial transaction monitoring
- User activity tracking
- Automated reporting alerts

## Technical Implementation

### Database Integration
- Uses existing `notifications` table
- Automatic status tracking (Pending → Sent → Read)
- Reference linking to related records

### Performance Optimized
- Batch notification processing
- Efficient database queries
- Minimal system overhead
- Asynchronous email delivery

### Error Handling
- Graceful failure handling
- Email delivery status tracking
- Notification retry mechanisms
- Comprehensive logging

## Usage Instructions

### For Developers
1. Include activity hooks in any new features
2. Use appropriate hook functions for different activities
3. Follow existing notification patterns
4. Test email delivery in development

### For System Administrators
1. Configure email settings in `email_config.php`
2. Set up daily automated checks via cron job
3. Monitor notification delivery status
4. Customize notification templates as needed

### For End Users
- Notifications appear automatically in dashboard
- Check email for important updates
- Use notification panel to manage alerts
- Configure personal notification preferences

This comprehensive notification system ensures all stakeholders stay informed about important system activities while maintaining appropriate privacy and relevance filtering.