<?php
/**
 * SMS Utility Class for sending OTP via SMS
 * Uses Twilio API
 */

class SMSUtility {
    private $account_sid;
    private $auth_token;
    private $from_number;
    private $enabled;

    public function __construct() {
        // Load configuration
        $config = include(__DIR__ . '/sms_config.php');
        
        $this->account_sid = $config['twilio_account_sid'];
        $this->auth_token = $config['twilio_auth_token'];
        $this->from_number = $config['twilio_from_number'];
        $this->enabled = $config['sms_enabled'];
    }

    /**
     * Send OTP via SMS
     * 
     * @param string $to_number Phone number (format: +91XXXXXXXXXX)
     * @param string $otp The OTP code to send
     * @param string $context Additional context (e.g., 'pickup', 'verification')
     * @return array Result array with 'success' and 'message' keys
     */
    public function sendOTP($to_number, $otp, $context = 'pickup') {
        if (!$this->enabled) {
            // SMS is disabled - log instead of sending
            error_log("SMS would be sent to $to_number: OTP is $otp");
            return [
                'success' => true, 
                'message' => 'SMS disabled in config. OTP logged.'
            ];
        }

        // Validate phone number format
        if (!$this->validatePhoneNumber($to_number)) {
            return [
                'success' => false,
                'message' => 'Invalid phone number format'
            ];
        }

        // Format phone number (add +91 if not present)
        $formatted_number = $this->formatPhoneNumber($to_number);

        // Create message
        $message = $this->createMessage($otp, $context);

        // Send SMS via Twilio
        try {
            $result = $this->sendViaTwilio($formatted_number, $message);
            return $result;
        } catch (Exception $e) {
            error_log("SMS Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send SMS: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate phone number
     */
    private function validatePhoneNumber($number) {
        // Accept 10 digit numbers or numbers with country code
        $pattern = '/^(\+91)?[6-9][0-9]{9}$/';
        return preg_match($pattern, $number);
    }

    /**
     * Format phone number to international format
     */
    private function formatPhoneNumber($number) {
        // Remove spaces and dashes
        $number = preg_replace('/[\s\-]/', '', $number);
        
        // Add +91 if not present
        if (!preg_match('/^\+91/', $number)) {
            $number = '+91' . $number;
        }
        
        return $number;
    }

    /**
     * Create SMS message
     */
    private function createMessage($otp, $context) {
        $messages = [
            'pickup' => "Your CharityBridge pickup verification code is: $otp. Please share this code with the volunteer during pickup. Do not share with anyone else.",
            'verification' => "Your CharityBridge verification code is: $otp. Valid for 10 minutes.",
            'default' => "Your CharityBridge OTP is: $otp"
        ];

        return $messages[$context] ?? $messages['default'];
    }

    /**
     * Send SMS via Twilio API
     */
    private function sendViaTwilio($to_number, $message) {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->account_sid}/Messages.json";

        $data = [
            'From' => $this->from_number,
            'To' => $to_number,
            'Body' => $message
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->account_sid}:{$this->auth_token}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            throw new Exception("cURL Error: $curl_error");
        }

        $response_data = json_decode($response, true);

        if ($http_code >= 200 && $http_code < 300) {
            return [
                'success' => true,
                'message' => 'SMS sent successfully',
                'sid' => $response_data['sid'] ?? null
            ];
        } else {
            $error_message = $response_data['message'] ?? 'Unknown error';
            throw new Exception("Twilio API Error ($http_code): $error_message");
        }
    }
}
?>
