<?php
session_start();

// Check if NGO is logged in
if (!isset($_SESSION['ngo_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['req_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing requirement ID']);
    exit();
}

$req_id = intval($input['req_id']);
$ngo_id = $_SESSION['ngo_id'];

// DB connection
$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Delete the volunteer requirement (only if it belongs to this NGO)
$stmt = $conn->prepare("DELETE FROM volunteer_requirements WHERE req_id = ? AND ngo_id = ?");
$stmt->bind_param("ii", $req_id, $ngo_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Volunteer requirement deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Requirement not found or you do not have permission to delete it']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete requirement: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>


