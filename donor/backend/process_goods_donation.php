<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Include SMS utility
require_once('../../includes/sms_utility.php');

if (!isset($_SESSION['donor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.', 'redirect' => '../login.html']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $donor_id = $_SESSION['donor_id'];
    $ngo_id = filter_input(INPUT_POST, 'ngo_id', FILTER_VALIDATE_INT);
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $item_type = trim($_POST['item_type'] ?? '');
    $item_description = trim($_POST['item_description'] ?? '');

    if (!$ngo_id || empty($phone_number) || empty($address) || empty($item_type) || empty($item_description)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit();
    }

    // Validate phone number (10 digits)
    if (!preg_match('/^[0-9]{10}$/', $phone_number)) {
        echo json_encode(['success' => false, 'message' => 'Phone number must be exactly 10 digits.']);
        exit();
    }

    // Validate item_type
    if (!in_array($item_type, ['clothes', 'goods'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid item type.']);
        exit();
    }

    $conn = new mysqli('localhost', 'root', '', 'charitybridge');
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        exit();
    }

    // Generate a unique 4-digit pickup code
    $pickup_code = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    // Ensure the code is unique (simple check, can be improved for high volume)
    $check_code_stmt = $conn->prepare("SELECT goods_donation_id FROM goods_donations WHERE pickup_code = ?");
    do {
        $check_code_stmt->bind_param("s", $pickup_code);
        $check_code_stmt->execute();
        $check_code_stmt->store_result();
        if ($check_code_stmt->num_rows > 0) {
            $pickup_code = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        }
    } while ($check_code_stmt->num_rows > 0);
    $check_code_stmt->close();

    $stmt = $conn->prepare("INSERT INTO goods_donations (donor_id, ngo_id, phone_number, address, item_type, item_description, pickup_code, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iisssss", $donor_id, $ngo_id, $phone_number, $address, $item_type, $item_description, $pickup_code);

    if ($stmt->execute()) {
        // Send OTP via SMS
        try {
            $sms = new SMSUtility();
            $sms_result = $sms->sendOTP($phone_number, $pickup_code, 'pickup');
            
            if ($sms_result['success']) {
                echo json_encode([
                    'success' => true, 
                    'message' => "Donation submitted successfully! Your pickup code is: " . $pickup_code . ". OTP has been sent to your phone number. You can track its status.", 
                    'redirect' => 'tracking.php'
                ]);
            } else {
                // Donation saved but SMS failed
                echo json_encode([
                    'success' => true, 
                    'message' => "Donation submitted successfully! Your pickup code is: " . $pickup_code . ". (Note: SMS could not be sent. Please save this code.)", 
                    'redirect' => 'tracking.php'
                ]);
            }
        } catch (Exception $e) {
            // Donation saved but SMS service error
            error_log("SMS Error: " . $e->getMessage());
            echo json_encode([
                'success' => true, 
                'message' => "Donation submitted successfully! Your pickup code is: " . $pickup_code . ". (SMS service unavailable. Please save this code.)", 
                'redirect' => 'tracking.php'
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => "Error submitting donation: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
