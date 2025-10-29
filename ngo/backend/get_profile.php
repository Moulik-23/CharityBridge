<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['ngo_id'])) {
	echo json_encode(['success' => false, 'error' => 'Unauthorized']);
	exit();
}

$ngo_id = $_SESSION['ngo_id'];

$servername = '127.0.0.1';
$username = 'root';
$password = '';
$dbname = 'charitybridge';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
	echo json_encode(['success' => false, 'error' => 'Database connection failed']);
	exit();
}

$sql = "SELECT id, name, email, phone, org_pan, reg_number, ngo_type, state, district, address, qr_code_image, darpan_id, owner_pan, owner_name, acc_no, ifsc_code FROM ngos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$result = $stmt->get_result();
$ngo = $result->fetch_assoc();
$stmt->close();
$conn->close();

if ($ngo) {
	echo json_encode(['success' => true, 'ngo' => $ngo]);
} else {
	echo json_encode(['success' => false, 'error' => 'NGO not found']);
}
?>

