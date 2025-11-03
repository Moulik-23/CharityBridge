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
    $plain_password = $_POST['password'];
    $plain_confirm_password = $_POST['confirm_password'];
    $phone      = $_POST['phone'];
    $address    = $_POST['address'];
    $city       = $_POST['city'];

    // Check if passwords match
    if ($plain_password !== $plain_confirm_password) {
        echo "❌ Passwords do not match!";
        exit();
    }
    
    // Password strength validation
    if (strlen($plain_password) < 8) {
        echo "❌ Password must be at least 8 characters long.";
        exit();
    }
    if (!preg_match('/[A-Z]/', $plain_password)) {
        echo "❌ Password must contain at least one uppercase letter.";
        exit();
    }
    if (!preg_match('/[a-z]/', $plain_password)) {
        echo "❌ Password must contain at least one lowercase letter.";
        exit();
    }
    if (!preg_match('/[0-9]/', $plain_password)) {
        echo "❌ Password must contain at least one number.";
        exit();
    }
    if (!preg_match('/[^A-Za-z0-9]/', $plain_password)) {
        echo "❌ Password must contain at least one special character (!@#$%^&* etc.).";
        exit();
    }
    
    $password = md5($plain_password);  // store hashed password
    $confirm_password = md5($plain_confirm_password);

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
