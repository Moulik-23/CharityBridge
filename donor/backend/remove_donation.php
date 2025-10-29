<?php
session_start();
header('Content-Type: application/json');

// Check if donor is logged in
if (!isset($_SESSION['donor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$donation_id = $input['donation_id'] ?? null;
$donor_id = $_SESSION['donor_id'];

if (!$donation_id) {
    echo json_encode(['success' => false, 'message' => 'Donation ID is required']);
    exit();
}

// DB connection
$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if donation exists, belongs to this donor, and is pending
$stmt = $conn->prepare("SELECT status FROM goods_donations WHERE goods_donation_id = ? AND donor_id = ?");
$stmt->bind_param("ii", $donation_id, $donor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Donation not found']);
    $stmt->close();
    $conn->close();
    exit();
}

$donation = $result->fetch_assoc();
$stmt->close();

if ($donation['status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'Only pending donations can be removed']);
    $conn->close();
    exit();
}

// Delete the donation
$delete_stmt = $conn->prepare("DELETE FROM goods_donations WHERE goods_donation_id = ? AND donor_id = ?");
$delete_stmt->bind_param("ii", $donation_id, $donor_id);

if ($delete_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Donation removed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove donation']);
}

$delete_stmt->close();
$conn->close();
?>
