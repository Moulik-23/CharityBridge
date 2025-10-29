<?php
session_start();

// Ensure single-session login context
session_unset();
session_destroy();
session_start();
session_regenerate_id(true);

// DB connection (align with existing project settings)
$servername = "127.0.0.1";
$username   = "root";
$password   = ""; // XAMPP default
$dbname     = "charitybridge";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Basic validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email address.";
        $conn->close();
        exit();
    }

    if (empty($password)) {
        echo "Password is required.";
        $conn->close();
        exit();
    }

    $password_md5 = md5($password);

    // Fetch NGO by email/password
    $stmt = $conn->prepare("SELECT id, name, status FROM ngos WHERE email = ? AND password = ?");
    if (!$stmt) {
        echo "SQL prepare error: " . $conn->error;
        $conn->close();
        exit();
    }
    $stmt->bind_param("ss", $email, $password_md5);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $ngo = $result->fetch_assoc();
        if (strtolower($ngo['status']) === 'approved') {
            $_SESSION['ngo_id'] = $ngo['id'];
            $_SESSION['ngo_name'] = $ngo['name'];
            header("Location: ../pages/dashboard.php");
            exit();
        } elseif (strtolower($ngo['status']) === 'pending') {
            echo "Account not approved yet.";
        } else {
            echo "Account status: " . htmlspecialchars($ngo['status']);
        }
    } else {
        echo "Invalid email or password.";
    }

    $stmt && $stmt->close();
}

$conn->close();
?>


