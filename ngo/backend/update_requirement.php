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

if (!isset($input['id']) || !isset($input['title']) || !isset($input['description'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

$req_id = intval($input['id']);
$ngo_id = $_SESSION['ngo_id'];
$title = trim($input['title']);
$description = trim($input['description']);

// Validate required fields
if (empty($title) || empty($description)) {
    echo json_encode(['success' => false, 'error' => 'Title and description are required']);
    exit();
}

// DB connection
$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Update the general requirement (only if it belongs to this NGO)
$stmt = $conn->prepare("UPDATE requirements SET title = ?, description = ? WHERE id = ? AND ngo_id = ?");
$stmt->bind_param("ssii", $title, $description, $req_id, $ngo_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'General requirement updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Requirement not found or you do not have permission to update it']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update requirement: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>


