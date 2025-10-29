<?php
session_start();
// Assuming you have a database connection established
$conn = new mysqli('localhost', 'root', '', 'charitybridge');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $restaurant_id = $_POST['id'];

    // Update the status of the restaurant to 'Approved'
    $stmt = $conn->prepare("UPDATE restaurants SET status = 'Approved' WHERE id = ?");
    $stmt->bind_param("i", $restaurant_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Restaurant approved successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve restaurant.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
?>
