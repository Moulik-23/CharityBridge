<?php
// Database connection
$servername = "127.0.0.1";
$username   = "root";
$password   = "";   // empty password for XAMPP
$dbname     = "charitybridge";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name       = $_POST['name'];
    $email      = $_POST['email'];
    $password   = md5($_POST['password']);  // store hashed password
    $confirm_password = md5($_POST['confirm_password']);
    $phone      = $_POST['phone'];
    $address    = $_POST['address'];
    $city       = $_POST['city'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        echo "❌ Passwords do not match!";
        exit();
    }

    // Check if email already exists
    $check_email_stmt = $conn->prepare("SELECT donor_id FROM donors WHERE email = ?");
    $check_email_stmt->bind_param("s", $email);
    $check_email_stmt->execute();
    $check_email_result = $check_email_stmt->get_result();

    if ($check_email_result->num_rows > 0) {
        echo "❌ Email already registered!";
        $check_email_stmt->close();
        exit();
    }
    $check_email_stmt->close();

    // Insert donor data
    $sql = "INSERT INTO donors (name, email, password_hash, phone, address, city) VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("❌ SQL Error: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param("ssssss", $name, $email, $password, $phone, $address, $city);

    if ($stmt->execute()) {
        // Redirect to donor login page after successful registration
        header("Location: ../login.html");
        exit();
    } else {
        echo "❌ Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
