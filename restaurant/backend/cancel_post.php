<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['restaurant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_id'])) {
    $restaurant_id = $_SESSION['restaurant_id'];
    $food_post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT); // Changed to post_id
    $action = 'cancel'; // Action is implicitly cancel from this endpoint

    if (!$food_post_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid request. Missing post ID.']);
        exit();
    }

    $conn = new mysqli('localhost', 'root', '', 'charitybridge');
    if ($conn->connect_error) {
        die("❌ DB Connection failed: " . $conn->connect_error);
    }

    // Update the status of the food post to 'Cancelled'
    $stmt = $conn->prepare("UPDATE food_posts SET status = 'Cancelled' WHERE id = ? AND restaurant_id = ? AND status = 'Waiting'");
    $stmt->bind_param("ii", $food_post_id, $restaurant_id);

    $success = false;
    $message = "";

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $success = true;
            $message = "Food post deleted successfully.";
        } else {
            $message = "Error deleting food post: Not found, already processed, or not owned by this restaurant.";
        }
    } else {
        $message = "Error cancelling food post: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}
?>
