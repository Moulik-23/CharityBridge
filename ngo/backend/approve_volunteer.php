<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "charitybridge";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "UPDATE volunteers SET status = 'Approved' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

$conn->close();
header("Location: volunteers.php");
exit();
?>
