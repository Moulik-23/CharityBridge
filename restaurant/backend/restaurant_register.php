<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "charitybridge";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $restaurant_name = trim($_POST['restaurant_name']);
    $owner_name = trim($_POST['owner_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $address = trim($_POST['address']);
    $pincode = trim($_POST['pincode']);
    $fssai_license = trim($_POST['fssai_license']);
    $restaurant_type = trim($_POST['restaurant_type']);
    $status = "Pending";
    $created_at = date("Y-m-d H:i:s");

    // Server-side validation
    if (empty($restaurant_name) || empty($owner_name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password) || empty($address) || empty($pincode) || empty($fssai_license) || empty($restaurant_type)) {
        header("Location: ../auth/restaurant_register.html?error=All fields are required.");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../auth/restaurant_register.html?error=Invalid email address.");
        exit();
    }

    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        header("Location: ../auth/restaurant_register.html?error=Invalid phone number.");
        exit();
    }

    if ($password !== $confirm_password) {
        header("Location: ../auth/restaurant_register.html?error=Passwords do not match.");
        exit();
    }

    if (!preg_match('/^[0-9]{6}$/', $pincode)) {
        header("Location: ../auth/restaurant_register.html?error=Invalid pincode.");
        exit();
    }

    if (!isset($_FILES['license_document']) || $_FILES['license_document']['error'] !== UPLOAD_ERR_OK) {
        header("Location: ../auth/restaurant_register.html?error=License document is required.");
        exit();
    }

    $upload_dir = __DIR__ . "/licenses/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_tmp = $_FILES['license_document']['tmp_name'];
    $file_name = time() . "_" . basename($_FILES['license_document']['name']);
    $target = $upload_dir . $file_name;

    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array(mime_content_type($file_tmp), $allowed_types)) {
        header("Location: ../auth/restaurant_register.html?error=Invalid file type for license document.");
        exit();
    }

    if (!move_uploaded_file($file_tmp, $target)) {
        header("Location: ../auth/restaurant_register.html?error=Failed to upload license document.");
        exit();
    }

    $hashed_password = md5($password);

    $stmt = $conn->prepare("INSERT INTO restaurants (restaurant_name, owner_name, email, phone, password, address, pincode, fssai_license, license_document, restaurant_type, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssss", $restaurant_name, $owner_name, $email, $phone, $hashed_password, $address, $pincode, $fssai_license, $file_name, $restaurant_type, $status, $created_at);

    if ($stmt->execute()) {
        header("Location: ../pge/wait_for_approval.php");
        exit();
    } else {
        header("Location: ../auth/restaurant_register.html?error=Failed to register. Please try again.");
    }

    $stmt->close();
}

$conn->close();
?>
