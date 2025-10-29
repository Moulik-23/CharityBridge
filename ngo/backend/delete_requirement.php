<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['ngo_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("HTTP/1.1 405 Method Not Allowed");
    exit();
}

// Get the requirement ID from the request body
$data = json_decode(file_get_contents("php://input"));
if (!isset($data->id) || !is_numeric($data->id)) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(["error" => "Invalid requirement ID."]);
    exit();
}

$requirement_id = (int)$data->id;
$ngo_id = $_SESSION['ngo_id'];

$servername = "127.0.0.1";
$username   = "root";
$password   = ""; 
$dbname     = "charitybridge";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(["error" => "Database connection failed."]);
    exit();
}

// Verify that the requirement belongs to the logged-in NGO before deleting
$stmt = $conn->prepare("DELETE FROM requirements WHERE id = ? AND ngo_id = ?");
$stmt->bind_param("ii", $requirement_id, $ngo_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // Successfully deleted
        header("Content-Type: application/json");
        echo json_encode(["success" => true]);
    } else {
        // No rows affected, meaning the requirement either didn't exist or didn't belong to the NGO
        header("HTTP/1.1 403 Forbidden");
        echo json_encode(["error" => "You do not have permission to delete this requirement."]);
    }
} else {
    // SQL execution error
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(["error" => "Failed to delete the requirement."]);
}

$stmt->close();
$conn->close();
?>
