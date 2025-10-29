<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

header('Content-Type: application/json');

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

    // Update status to 'Rejected'
    $stmt = $conn->prepare("UPDATE food_posts SET status = 'Rejected' WHERE id = ? AND status = 'Waiting'");
    if ($stmt === false) {
        $message = "Prepare failed: " . $conn->error;
    } else {
        $stmt->bind_param("i", $food_post_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $success = true;
            $message = "Food donation rejected successfully!";
        } else {
            $message = "Error rejecting food donation or donation not found/already processed.";
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
