<?php
session_start();

// Clear all existing session variables to ensure only one user is logged in at a time
session_unset();
session_destroy();
session_start();
session_regenerate_id(true);

// DB Connection
$servername = "127.0.0.1";
$username   = "root";
$password   = "";
$dbname     = "charitybridge";


$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = $_POST['email'];
    $password = md5($_POST['password']); // match MD5 hash

    $sql = "SELECT volunteer_id, name FROM volunteers WHERE email=? AND password_hash=?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $volunteer = $result->fetch_assoc();

        // store session
        $_SESSION['volunteer_id'] = $volunteer['volunteer_id'];
        $_SESSION['volunteer_name'] = $volunteer['name'];

        header("Location: ../pages/dashboard.php"); 
        exit();
    } else {
        echo "<script>alert('❌ Invalid email or password!'); window.location.href='login.html';</script>";
    }

    $stmt->close();
}
$conn->close();
?>
