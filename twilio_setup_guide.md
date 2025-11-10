# Twilio SMS Setup Guide (Free Trial)

## 🆓 **Free Trial Benefits:**
- **$15 free credit** when you sign up
- Send SMS to **verified phone numbers**
- Perfect for testing and development

## 📋 **Step-by-Step Setup:**

### **Step 1: Create Twilio Account**
1. Go to **https://www.twilio.com**
2. Click **"Sign up for free"**
3. Verify your email and phone number
4. Get **$15 free trial credit**

### **Step 2: Get Your Credentials**
1. Login to **Twilio Console** (https://console.twilio.com)
2. Find your **Account SID** and **Auth Token** on dashboard
3. Go to **Phone Numbers** → **Manage** → **Active numbers**
4. Copy your **Twilio phone number**

### **Step 3: Update Configuration**
Edit `app/config/sms_config.php`:
```php
private $accountSid = 'YOUR_TWILIO_ACCOUNT_SID';
private $authToken = 'YOUR_TWILIO_AUTH_TOKEN';
private $fromNumber = '+1234567890'; // Your Twilio number
```

### **Step 4: Verify Phone Numbers (Trial Mode)**
1. Go to **Phone Numbers** → **Manage** → **Verified Caller IDs**
2. Click **"Add a new number"**
3. Enter your phone number (+250780468216)
4. Verify with SMS code

### **Step 5: Test SMS**
- Process a mobile money payment
- SMS will be sent to verified numbers only (trial mode)
- Check your phone for SMS

## 🔧 **Example Configuration:**
```php
private $accountSid = 'YOUR_TWILIO_ACCOUNT_SID';
private $authToken = 'YOUR_TWILIO_AUTH_TOKEN';
private $fromNumber = '+15551234567';
```

## 💰 **Cost Information:**
- **Trial**: $15 free credit
- **SMS Cost**: ~$0.0075 per SMS
- **Trial Limitations**: Only verified numbers
- **Upgrade**: Remove limitations, pay per use

## 🚀 **Production Upgrade:**
When ready for production:
1. Add payment method to Twilio account
2. Remove trial restrictions
3. Send SMS to any valid phone number
4. Monitor usage and costs

## 🔍 **Troubleshooting:**
- **Error 21211**: Invalid 'To' number (verify the number first)
- **Error 20003**: Authentication error (check SID/Token)
- **Error 21408**: Permission denied (upgrade account)

## 📱 **SMS Format:**
```
MATERNAL CARE SYSTEM
Payment Confirmation
Transaction: T123
Patient: John Doe
Amount: 5000 RWF
Method: Mobile Money
Status: Completed
Thank you for using our services!
```