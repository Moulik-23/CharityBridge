<?php
session_start();

// Clear all existing session variables to ensure only one user is logged in at a time
session_unset();
session_destroy();
session_start();
session_regenerate_id(true);

$conn = new mysqli('localhost', 'root', '', 'charitybridge');

if($conn->connect_error){ die("DB Connection Error"); }

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';

if($role !== 'admin'){
    die("Invalid role");
}

$stmt = $conn->prepare("SELECT * FROM admins WHERE email=?");
$stmt->bind_param("s",$email);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 1){
    $row = $result->fetch_assoc();
    // For debugging, remove in production
    echo "DB password: '".$row['password']."'<br>";
    echo "Form password: '".$password."'<br>";
    if($row['password'] === $password){
        echo "✅ Password matches!"; // For debugging, remove in production
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $row['email'];
        header("Location: ../dashboard.php");
        exit;
    } else {
        echo "❌ Invalid password";
    }
} else {
    echo "❌ Invalid email";
}

$stmt->close();
$conn->close();
?>
