<?php
// Disable error display to prevent HTML output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
session_start();

if (!isset($_SESSION['ngo_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ngo_id = $_SESSION['ngo_id'];
    $goods_donation_id = filter_input(INPUT_POST, 'goods_donation_id', FILTER_VALIDATE_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

    if (!$goods_donation_id || !$action) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit();
    }

    $conn = new mysqli('localhost', 'root', '', 'charitybridge');
    if ($conn->connect_error) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'DB Connection failed.']);
        exit();
    }

    $success = false;
    $message = "";

    switch ($action) {
        case 'accept':
            $stmt = $conn->prepare("UPDATE goods_donations SET status = 'accepted' WHERE goods_donation_id = ? AND ngo_id = ? AND status = 'pending'");
            $stmt->bind_param("ii", $goods_donation_id, $ngo_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success = true;
                $message = "Donation accepted successfully!";
            } else {
                $message = "Error accepting donation or donation not found/already processed.";
            }
            $stmt->close();
            break;

        case 'reject':
            $stmt = $conn->prepare("UPDATE goods_donations SET status = 'rejected' WHERE goods_donation_id = ? AND ngo_id = ? AND status = 'pending'");
            $stmt->bind_param("ii", $goods_donation_id, $ngo_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success = true;
                $message = "Donation rejected successfully!";
            } else {
                $message = "Error rejecting donation or donation not found/already processed.";
            }
            $stmt->close();
            break;

        case 'assign_volunteer':
            $volunteer_id = filter_input(INPUT_POST, 'volunteer_id', FILTER_VALIDATE_INT);
            if (!$volunteer_id) {
                $message = "Please select a volunteer.";
                break;
            }
            // Fetch volunteer name
            $volunteer_name = null;
            $stmt_v_name = $conn->prepare("SELECT name FROM volunteers WHERE volunteer_id = ?");
            $stmt_v_name->bind_param("i", $volunteer_id);
            $stmt_v_name->execute();
            $result_v_name = $stmt_v_name->get_result();
            if ($row = $result_v_name->fetch_assoc()) {
                $volunteer_name = $row['name'];
            }
            $stmt_v_name->close();

            if (!$volunteer_name) {
                $message = "Selected volunteer not found.";
                break;
            }

            // Generate OTP (4-digit pickup code)
            $pickup_code = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            
            // Get donor phone number for SMS
            $donor_phone = null;
            $stmt_phone = $conn->prepare("SELECT d.phone FROM goods_donations gd JOIN donors d ON gd.donor_id = d.donor_id WHERE gd.goods_donation_id = ?");
            $stmt_phone->bind_param("i", $goods_donation_id);
            $stmt_phone->execute();
            $result_phone = $stmt_phone->get_result();
            if ($row_phone = $result_phone->fetch_assoc()) {
                $donor_phone = $row_phone['phone'];
            }
            $stmt_phone->close();

            $stmt = $conn->prepare("UPDATE goods_donations SET volunteer_id = ?, volunteer_name = ?, pickup_code = ?, status = 'accepted' WHERE goods_donation_id = ? AND ngo_id = ? AND status = 'accepted' AND volunteer_id IS NULL");
            $stmt->bind_param("issii", $volunteer_id, $volunteer_name, $pickup_code, $goods_donation_id, $ngo_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success = true;
                $message = "Volunteer assigned successfully! Pickup code: " . $pickup_code;
                
                // Send OTP via SMS to donor (disabled for now)
                if ($donor_phone) {
                    // Temporarily disabled - enable after SMS configuration
                    // require_once('../../includes/sms_utility.php');
                    try {
                        // $sms = new SMSUtility();
                        // $sms_result = $sms->sendOTP($donor_phone, $pickup_code, 'pickup');
                        
                        // if ($sms_result['success']) {
                        //     $message .= " OTP has been sent to donor's phone.";
                        // } else {
                        //     $message .= " (SMS could not be sent to donor)";
                        // }
                        
                        // For now, just log the OTP
                        error_log("Pickup code for donation $goods_donation_id: $pickup_code (Donor phone: $donor_phone)");
                        $message .= " (SMS disabled - check logs for OTP)";
                    } catch (Exception $e) {
                        error_log("SMS Error: " . $e->getMessage());
                        $message .= " (SMS service unavailable)";
                    }
                }
            } else {
                $message = "Error assigning volunteer or donation not found/already assigned.";
            }
            $stmt->close();
            break;

        default:
            $message = "Unknown action.";
            break;
    }

    $conn->close();

    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();

} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}
?>
