<?php
session_start();

// Clear all existing session variables to ensure only one user is logged in at a time
session_unset();
session_destroy();
session_start();
session_regenerate_id(true);


$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "charitybridge";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
 
 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Server-side validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../auth/restaurant_login.html?error=Invalid email address.");
        exit();
    }

    if (empty($password)) {
        header("Location: ../auth/restaurant_login.html?error=Password is required.");
        exit();
    }

    $hashed_password = md5($password); // Hash the password

    $stmt = $conn->prepare("SELECT id, restaurant_name, status FROM restaurants WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $hashed_password);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $restaurant_name, $status);
        $stmt->fetch();

        if ($status === "Approved") {
            $_SESSION['restaurant_id'] = $id;
            $_SESSION['restaurant_name'] = $restaurant_name;
            header("Location: ../pge/dashboard.php"  );
        } else {
            header("Location: ../auth/restaurant_login.html?error=Account not approved yet.");
        }
    } else {
        header("Location: ../auth/restaurant_login.html?error=Invalid email or password.");
    }

    $stmt->close();
}

$conn->close();
?>
