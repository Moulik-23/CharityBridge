<?php
session_start();

// Database connection
$servername = "localhost";
$username   = "root";
$password   = ""; // empty for XAMPP
$dbname     = "charitybridge";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Capture login form data
$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? '';

if ($role !== 'admin') {
    die("❌ Invalid role.");
}

// ✅ Check against admins table
$sql  = "SELECT * FROM admins WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();

    // Compare password (assuming stored as md5 for now)
    if ($row['password'] === md5($password)) {
        // Set session
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $row['email'];

        // Redirect to dashboard
        header("Location: ../dashboard.php");
        exit();
    } else {
        echo "<script>alert('❌ Invalid password'); window.location.href='../login.html';</script>";
    }
} else {
    echo "<script>alert('❌ Invalid email'); window.location.href='../login.html';</script>";
}

$stmt->close();
$conn->close();
?>
