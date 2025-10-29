

<?php
session_start();

// Check if NGO is logged in
if (!isset($_SESSION['ngo_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['req_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing requirement ID']);
    exit();
}

$req_id = intval($_GET['req_id']);
$ngo_id = $_SESSION['ngo_id'];

// DB connection
$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Fetch the volunteer requirement (only if it belongs to this NGO)
$stmt = $conn->prepare("SELECT * FROM volunteer_requirements WHERE req_id = ? AND ngo_id = ?");
$stmt->bind_param("ii", $req_id, $ngo_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $requirement = $result->fetch_assoc();
    echo json_encode(['success' => true, 'requirement' => $requirement]);
} else {
    echo json_encode(['success' => false, 'error' => 'Requirement not found or you do not have permission to access it']);
}

$stmt->close();
$conn->close();
?>


