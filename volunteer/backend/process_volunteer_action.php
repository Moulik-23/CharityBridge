<?php
session_start();
header('Content-Type: application/json');

// Check if volunteer is logged in
if (!isset($_SESSION['volunteer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit();
}

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$volunteer_id = $_SESSION['volunteer_id'];
$goods_donation_id = filter_input(INPUT_POST, 'goods_donation_id', FILTER_VALIDATE_INT);
$food_post_id = filter_input(INPUT_POST, 'food_post_id', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

// Validate input
if ((!$goods_donation_id && !$food_post_id) || !$action) {
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

switch ($action) {
    case 'verify_goods_pickup':
        $pickup_code_input = filter_input(INPUT_POST, 'pickup_code_input', FILTER_SANITIZE_STRING);
        if (!$pickup_code_input) {
            $message = "Pickup code is required.";
            break;
        }

        // Verify the pickup code
        $stmt = $conn->prepare("SELECT pickup_code FROM goods_donations WHERE goods_donation_id = ? AND volunteer_id = ? AND status = 'accepted'");
        $stmt->bind_param("ii", $goods_donation_id, $volunteer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $donation = $result->fetch_assoc();
        $stmt->close();

        if ($donation && $donation['pickup_code'] === $pickup_code_input) {
            // Update status to picked_up
            $stmt_update = $conn->prepare("UPDATE goods_donations SET status = 'picked_up' WHERE goods_donation_id = ?");
            $stmt_update->bind_param("i", $goods_donation_id);
            if ($stmt_update->execute()) {
                $success = true;
                $message = "Pickup verified successfully! Donation status updated to 'Picked Up'.";
            } else {
                $message = "Error updating donation status: " . $conn->error;
            }
            $stmt_update->close();
        } else {
            $message = "Invalid pickup code or donation not in 'accepted' status.";
        }
        break;

    case 'delivered':
        $stmt = $conn->prepare("UPDATE goods_donations SET status = 'delivered' WHERE goods_donation_id = ? AND volunteer_id = ? AND status = 'picked_up'");
        $stmt->bind_param("ii", $goods_donation_id, $volunteer_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $success = true;
            $message = "Donation marked as delivered successfully!";
        } else {
            $message = "Error marking donation as delivered or donation not in 'picked up' status.";
        }
        $stmt->close();
        break;

    case 'verify_food_pickup':
        $pickup_code_input = filter_input(INPUT_POST, 'pickup_code_input', FILTER_SANITIZE_STRING);
        if (!$pickup_code_input) {
            $message = "Pickup code is required.";
            break;
        }

        // Verify the pickup code for food donation
        $stmt = $conn->prepare("SELECT pickup_code FROM food_posts WHERE id = ? AND volunteer_id = ? AND status = 'Accepted'");
        $stmt->bind_param("ii", $food_post_id, $volunteer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $food_donation = $result->fetch_assoc();
        $stmt->close();

        if ($food_donation && $food_donation['pickup_code'] === $pickup_code_input) {
            // Update status to Picked Up
            $stmt_update = $conn->prepare("UPDATE food_posts SET status = 'Picked Up' WHERE id = ?");
            $stmt_update->bind_param("i", $food_post_id);
            if ($stmt_update->execute()) {
                $success = true;
                $message = "Food pickup verified successfully! Donation status updated to 'Picked Up'.";
            } else {
                $message = "Error updating food donation status: " . $conn->error;
            }
            $stmt_update->close();
        } else {
            $message = "Invalid pickup code or food donation not in 'Accepted' status.";
        }
        break;

    case 'food_delivered':
        $stmt = $conn->prepare("UPDATE food_posts SET status = 'Delivered' WHERE id = ? AND volunteer_id = ? AND status = 'Picked Up'");
        $stmt->bind_param("ii", $food_post_id, $volunteer_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $success = true;
            $message = "Food donation marked as delivered successfully!";
        } else {
            $message = "Error marking food donation as delivered or donation not in 'Picked Up' status.";
        }
        $stmt->close();
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
