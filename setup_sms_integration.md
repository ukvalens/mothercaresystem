# SMS Integration Setup Guide

## 1. Africa's Talking API Setup (Recommended for Rwanda)

### Step 1: Create Account
1. Visit https://africastalking.com
2. Sign up for an account
3. Verify your account

### Step 2: Get API Credentials
1. Login to your dashboard
2. Go to "Settings" → "API Keys"
3. Copy your:
   - Username
   - API Key
   - Sender ID/Short Code

### Step 3: Configure SMS Gateway
Edit `app/config/sms_config.php`:
```php
private $username = 'YOUR_ACTUAL_USERNAME';
private $apiKey = 'YOUR_ACTUAL_API_KEY';
private $shortCode = 'YOUR_SENDER_ID';
```

### Step 4: Test Configuration
- Start with sandbox mode
- Send test SMS to verify integration
- Switch to production when ready

## 2. MTN Mobile Money API (Optional)

### For Direct Mobile Money Integration:
1. Register at https://momodeveloper.mtn.com
2. Get sandbox credentials
3. Configure in `sms_config.php`
4. Test payment requests

## 3. Alternative SMS Providers

### Twilio (Global)
- Sign up at https://twilio.com
- Get Account SID and Auth Token
- Modify SMS class to use Twilio API

### Local Rwanda SMS Providers
- Check with local telecom providers
- MTN Rwanda Business API
- Airtel Rwanda API

## 4. Production Deployment

### Security Checklist:
- [ ] Store API keys in environment variables
- [ ] Use HTTPS for all API calls
- [ ] Implement rate limiting
- [ ] Add error logging
- [ ] Test with real phone numbers
- [ ] Monitor SMS delivery rates

### Environment Variables (.env):
```
SMS_USERNAME=your_username
SMS_API_KEY=your_api_key
SMS_SHORTCODE=your_shortcode
MTN_SUBSCRIPTION_KEY=your_mtn_key
```

## 5. Cost Considerations

### Africa's Talking Pricing (Approximate):
- SMS: $0.01 - $0.05 per SMS
- Bulk rates available
- Pay-as-you-go or monthly plans

### Budget Planning:
- Estimate SMS volume per month
- Factor in delivery confirmations
- Consider backup SMS provider

## 6. Testing Checklist

- [ ] SMS delivery to Rwanda numbers (+250)
- [ ] International number formatting
- [ ] Error handling for failed SMS
- [ ] Message content and formatting
- [ ] API rate limits and quotas
- [ ] Fallback mechanisms

## 7. Monitoring & Analytics

### Track:
- SMS delivery rates
- Failed delivery reasons
- API response times
- Cost per SMS
- User engagement with SMS

### Logging:
```php
// Add to payment process
error_log("SMS sent to {$mobile_number}: " . json_encode($sms_result));
```