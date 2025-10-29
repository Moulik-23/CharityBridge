<?php
session_start();

if (!isset($_SESSION['volunteer_id'])) {
    header("Location: ../auth/volunteer_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/scheduler.php");
    exit();
}

$volunteer_id = (int)$_SESSION['volunteer_id'];
$map_id = isset($_POST['map_id']) ? (int)$_POST['map_id'] : 0;

if ($map_id <= 0) {
    $_SESSION['error_message'] = 'Invalid application selected.';
    header('Location: ../pages/scheduler.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    $_SESSION['error_message'] = 'Database connection failed.';
    header('Location: ../pages/scheduler.php');
    exit();
}

// Only allow withdrawal of this volunteer's own approved/pending application
$sql = "UPDATE volunteer_ngo_map 
        SET status = 'withdrawn' 
        WHERE id = ? AND volunteer_id = ? AND status IN ('Approved','pending','approved')";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('ii', $map_id, $volunteer_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['success_message'] = 'Application withdrawn successfully.';
    } else {
        $_SESSION['error_message'] = 'Unable to withdraw. It may already be processed or does not belong to you.';
    }
    $stmt->close();
}

$conn->close();
header('Location: ../pages/scheduler.php');
exit();
?>


