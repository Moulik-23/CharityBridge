<?php
/**
 * SMS Configuration File
 * Loads Twilio credentials from environment variables
 */

// Load environment variables from .env file
if (file_exists(__DIR__ . '/../.env')) {
    $envFile = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envFile as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

return [
    // Set to true to enable SMS sending, false to disable (for testing)
    'sms_enabled' => filter_var($_ENV['SMS_ENABLED'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
    
    // Twilio Account SID (Get from https://console.twilio.com/)
    'twilio_account_sid' => $_ENV['TWILIO_ACCOUNT_SID'] ?? '',
    
    // Twilio Auth Token (Get from https://console.twilio.com/)
    'twilio_auth_token' => $_ENV['TWILIO_AUTH_TOKEN'] ?? '',
    
    // Twilio Phone Number (must be in E.164 format: +1234567890)
    'twilio_from_number' => $_ENV['TWILIO_FROM_NUMBER'] ?? '',
];
?>
