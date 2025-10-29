<?php
session_start();
if (!isset($_SESSION['ngo_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$volunteer_id = intval($_GET['id']);
$ngo_id = $_SESSION['ngo_id'];

$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update mapping table to Approved
$sql = "UPDATE volunteer_ngo_map SET status='Approved' WHERE volunteer_id=? AND ngo_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $volunteer_id, $ngo_id);
$stmt->execute();

$stmt->close();
$conn->close();

header("Location: ../volunteers.php?msg=approved");
exit();
?>
