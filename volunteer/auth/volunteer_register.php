<?php
session_start();

// DB Connection
$servername = "127.0.0.1";
$username   = "root";
$password   = "";
$dbname     = "charitybridge";


$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = md5($_POST['password']); // store hashed
    $phone    = $_POST['phone'];
    $aadhaar  = $_POST['aadhaar'];
    $address  = $_POST['address'];
    $city     = $_POST['city'];
    $skills   = !empty($_POST['skills']) ? $_POST['skills'] : NULL;
    $availability = !empty($_POST['availability']) ? $_POST['availability'] : NULL;

    $sql = "INSERT INTO volunteers 
        (name, email, password_hash, phone, aadhaar, address, city, skills, availability) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("sssssssss", 
        $name, $email, $password, $phone, $aadhaar, $address, $city, $skills, $availability
    );

    if ($stmt->execute()) {
        $_SESSION['volunteer_id'] = $stmt->insert_id; // login directly
        header("Location: ../pages/dashboard.php"); // redirect
        exit();
    } else {
        echo "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>
