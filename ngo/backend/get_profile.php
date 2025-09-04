<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['ngo_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "charitybridge";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB Connection failed: ' . $conn->connect_error]);
    exit();
}

$ngo_id = $_SESSION['ngo_id'];

$sql = "SELECT name, ngo_type, address, qr_code_image FROM ngos WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $ngo_data = $result->fetch_assoc();
    echo json_encode(['success' => true, 'ngo' => $ngo_data]);
} else {
    echo json_encode(['success' => false, 'error' => 'NGO not found']);
}

$stmt->close();
$conn->close();
?>
