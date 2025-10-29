<?php
// Disable error display to prevent HTML output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
session_start();
header('Content-Type: application/json');

// Check if NGO is logged in
if (!isset($_SESSION['ngo_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit();
}

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$ngo_id = $_SESSION['ngo_id'];
$food_post_id = filter_input(INPUT_POST, 'food_post_id', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

// Validate input
if (!$food_post_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters.']);
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']);
    exit();
}

    $success = false;
    $message = "";

    // Get NGO name for assigning to food_posts
    $ngo_name = null;
    $stmt_ngo_name = $conn->prepare("SELECT name FROM ngos WHERE id = ?");
    $stmt_ngo_name->bind_param("i", $ngo_id);
    $stmt_ngo_name->execute();
    $result_ngo_name = $stmt_ngo_name->get_result();
    if ($row_ngo = $result_ngo_name->fetch_assoc()) {
        $ngo_name = $row_ngo['name'];
    }
    $stmt_ngo_name->close();

    if (!$ngo_name) {
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'NGO not found in database.']);
        exit();
    }

    switch ($action) {
        case 'assign_food_volunteer':
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

            // Generate unique 4-digit pickup code (OTP)
            $pickup_code = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $check_code_stmt = $conn->prepare("SELECT id FROM food_posts WHERE pickup_code = ?");
            do {
                $check_code_stmt->bind_param("s", $pickup_code);
                $check_code_stmt->execute();
                $check_code_stmt->store_result();
                if ($check_code_stmt->num_rows > 0) {
                    $pickup_code = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                }
            } while ($check_code_stmt->num_rows > 0);
            $check_code_stmt->close();

            // Get restaurant phone number for SMS
            $restaurant_phone = null;
            $stmt_phone = $conn->prepare("SELECT r.phone FROM food_posts fp JOIN restaurants r ON fp.restaurant_id = r.id WHERE fp.id = ?");
            $stmt_phone->bind_param("i", $food_post_id);
            $stmt_phone->execute();
            $result_phone = $stmt_phone->get_result();
            if ($row_phone = $result_phone->fetch_assoc()) {
                $restaurant_phone = $row_phone['phone'];
            }
            $stmt_phone->close();

            // Update food_posts with volunteer details and pickup code
            $stmt = $conn->prepare("UPDATE food_posts SET volunteer_id = ?, volunteer_name = ?, pickup_code = ?, status = 'Accepted' WHERE id = ? AND ngo_name = ? AND status = 'Accepted' AND volunteer_id IS NULL");
            if ($stmt === false) {
                $message = "Prepare failed: " . $conn->error;
            } else {
                $stmt->bind_param("issis", $volunteer_id, $volunteer_name, $pickup_code, $food_post_id, $ngo_name);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $success = true;
                        $message = "Volunteer assigned successfully! Pickup code: " . $pickup_code;
                        
                        // Send OTP via SMS to restaurant
                        if ($restaurant_phone) {
                            require_once('../../includes/sms_utility.php');
                            try {
                                $sms = new SMSUtility();
                                $sms_result = $sms->sendOTP($restaurant_phone, $pickup_code, 'pickup');
                                
                                if ($sms_result['success']) {
                                    $message .= " OTP has been sent to restaurant's phone.";
                                } else {
                                    $message .= " (SMS could not be sent to restaurant)";
                                }
                            } catch (Exception $e) {
                                error_log("SMS Error: " . $e->getMessage());
                                $message .= " (SMS service unavailable)";
                            }
                        }
                    } else {
                        $message = "Error assigning volunteer or food donation not found/already assigned.";
                    }
                } else {
                    $message = "Error executing statement: " . $stmt->error;
                }
                $stmt->close();
            }
            break;

        default:
            $message = "Unknown action.";
            break;
    }

    $conn->close();
    
    // Return JSON response
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
?>
