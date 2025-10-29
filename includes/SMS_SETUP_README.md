# SMS OTP Setup Guide

This guide explains how to configure SMS OTP functionality for CharityBridge using Twilio.

## Overview

The SMS OTP feature sends pickup verification codes to:
- **Donors** when they submit goods/clothes donations
- **Restaurants** when NGOs accept their food donations

## Files Added/Modified

### New Files:
1. `includes/sms_utility.php` - SMS utility class with Twilio integration
2. `includes/sms_config.php` - Configuration file for SMS credentials
3. `includes/SMS_SETUP_README.md` - This setup guide

### Modified Files:
1. `donor/backend/process_goods_donation.php` - Added SMS sending after goods donation
2. `ngo/backend/accept_food_donation.php` - Added SMS sending when food donation is accepted

## Setup Instructions

### Step 1: Create a Twilio Account

1. Go to [https://www.twilio.com/](https://www.twilio.com/)
2. Sign up for a free account
3. Verify your email and phone number
4. You'll get **$15 free credit** for testing

### Step 2: Get Twilio Credentials

1. Log in to [Twilio Console](https://console.twilio.com/)
2. From the dashboard, note down:
   - **Account SID** (looks like: `ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`)
   - **Auth Token** (click "Show" to reveal it)

### Step 3: Get a Twilio Phone Number

1. In Twilio Console, go to **Phone Numbers** → **Manage** → **Buy a number**
2. Select your country (e.g., India for +91 numbers)
3. Filter for SMS capability
4. Choose and buy a number (free trial numbers available)
5. Note down this phone number in E.164 format (e.g., `+1234567890`)

### Step 4: Configure Your Application

1. Open `includes/sms_config.php`
2. Update the following values:

```php
return [
    // Enable SMS sending
    'sms_enabled' => true, // Change from false to true
    
    // Your Twilio Account SID
    'twilio_account_sid' => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    
    // Your Twilio Auth Token
    'twilio_auth_token' => 'your_auth_token_here',
    
    // Your Twilio Phone Number (in E.164 format)
    'twilio_from_number' => '+1234567890',
];
```

### Step 5: Verify Phone Numbers (Trial Account)

**Important**: With a Twilio trial account, you can only send SMS to verified phone numbers.

1. Go to Twilio Console → **Phone Numbers** → **Manage** → **Verified Caller IDs**
2. Click **Add a new Caller ID**
3. Enter the phone number you want to test with
4. Complete the verification process
5. Repeat for all test phone numbers

### Step 6: Test the Functionality

1. **For Goods Donations**:
   - Log in as a donor
   - Submit a goods/clothes donation with a verified phone number
   - You should receive an SMS with the 4-digit pickup code

2. **For Food Donations**:
   - Log in as a restaurant and post food donation
   - Log in as an NGO and accept the food donation
   - The restaurant should receive an SMS with the pickup code

## Testing Without SMS (Development Mode)

If you want to test without actually sending SMS:

1. Keep `'sms_enabled' => false` in `includes/sms_config.php`
2. OTP codes will be logged to PHP error log instead of being sent via SMS
3. Check your XAMPP error logs at: `C:\xampp\apache\logs\error.log`

## Important Notes

### For Production Use:

1. **Upgrade Twilio Account**: Trial accounts have limitations
   - Can only send to verified numbers
   - Messages include "Sent from your Twilio trial account" prefix

2. **Add Credits**: After trial credit expires, add payment method

3. **Security**: 
   - Never commit `sms_config.php` with real credentials to version control
   - Add it to `.gitignore`
   - Use environment variables for production

4. **Phone Number Format**:
   - The system accepts 10-digit Indian phone numbers (e.g., `9876543210`)
   - Automatically adds `+91` country code for SMS sending
   - Twilio requires E.164 format: `+919876543210`

### Twilio Pricing (India):

- **SMS Cost**: ₹0.46 per message (approximately)
- **Phone Number**: Free trial number or ~$2/month
- Check current pricing at [Twilio Pricing](https://www.twilio.com/pricing)

## Alternative SMS Providers

If you prefer a different SMS provider, you can modify `includes/sms_utility.php` to use:
- **MSG91** (India-focused)
- **AWS SNS**
- **Vonage (Nexmo)**
- **2Factor.in**

The `sendViaTwilio()` method would need to be replaced with the provider's API implementation.

## Troubleshooting

### SMS Not Being Sent:

1. Check `sms_enabled` is set to `true`
2. Verify Twilio credentials are correct
3. Ensure phone number is verified (for trial accounts)
4. Check PHP error logs for detailed error messages
5. Verify cURL is enabled in PHP: `php -m | grep curl`

### Phone Number Format Issues:

- Ensure phone numbers in database are 10 digits
- System automatically adds +91 prefix
- Twilio requires E.164 format

### Error Messages:

- Check `C:\xampp\apache\logs\error.log` for detailed errors
- Twilio API errors are logged with HTTP status codes

## Support

- **Twilio Docs**: [https://www.twilio.com/docs/sms](https://www.twilio.com/docs/sms)
- **Twilio Support**: Available through console for paid accounts
- **CharityBridge**: Contact your development team

## Security Checklist

- [ ] SMS credentials stored securely
- [ ] `sms_config.php` added to `.gitignore`
- [ ] Phone numbers validated before sending
- [ ] Error handling implemented
- [ ] SMS sending doesn't block main application flow
- [ ] Rate limiting considered for production
- [ ] Audit logging for SMS sends (if needed for compliance)
