<?php
session_start();

// DB connection
$conn = new mysqli("localhost", "root", "", "charitybridge");
if ($conn->connect_error) {
    die("❌ DB Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password']; 
    $password_md5 = md5($password); // assuming passwords stored in md5

    // Fetch NGO by email
    $stmt = $conn->prepare("SELECT id, name, password, status FROM ngos WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $ngo = $result->fetch_assoc();

        // Verify password
        if ($ngo['password'] === $password_md5) {
            // Save session
            $_SESSION['ngo_id'] = $ngo['id'];
            $_SESSION['ngo_name'] = $ngo['name'];
            $_SESSION['ngo_status'] = $ngo['status'];

            if ($ngo['status'] === "approved") {
                header("Location: ../pages/dashboard.php");
                exit();
            } elseif ($ngo['status'] === "pending") {
                header("Location: ../wait_for_approval.php");
                exit();
            } else {
                echo "❌ Your account is rejected or inactive.";
            }
        } else {
            echo "❌ Invalid password!";
        }
    } else {
        echo "❌ NGO not found!";
    }

    $stmt->close();
}

$conn->close();
?>
