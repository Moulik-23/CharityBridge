<?php
// Disable error display to prevent HTML output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
session_start();

header('Content-Type: application/json');

// Include SMS utility (only used when assigning volunteers)
// require_once('../../includes/sms_utility.php');

if (!isset($_SESSION['ngo_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ngo_id = $_SESSION['ngo_id'];
    $food_post_id = filter_input(INPUT_POST, 'food_post_id', FILTER_VALIDATE_INT);

    if (!$food_post_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid request. Missing food_post_id.']);
        exit();
    }

    $conn = new mysqli('localhost', 'root', '', 'charitybridge');
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'DB Connection failed: ' . $conn->connect_error]);
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
        echo json_encode(['success' => false, 'message' => 'NGO not found.']);
        exit();
    }

    // Update status to 'Accepted', assign ngo_name, and set volunteer fields and pickup_code to NULL
    // OTP will be generated and sent when volunteer is assigned
    $stmt = $conn->prepare("UPDATE food_posts SET status = 'Accepted', ngo_name = ?, pickup_code = NULL, volunteer_id = NULL, volunteer_name = NULL WHERE id = ? AND status = 'Waiting'");
    if ($stmt === false) {
        $message = "Prepare failed: " . $conn->error;
    } else {
        $stmt->bind_param("si", $ngo_name, $food_post_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $success = true;
                $message = "Food donation accepted successfully! Please assign a volunteer to generate pickup code.";
            } else {
                $message = "Error accepting food donation: No rows affected. Donation not found or already processed.";
            }
        } else {
            $message = "Error executing statement: " . $stmt->error;
        }
        $stmt->close();
    }

    $conn->close();
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}
?>
