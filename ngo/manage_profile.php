<?php
session_start();

if (!isset($_SESSION['ngo_id'])) {
    header("Location: ../login.php");
    exit();
}

$servername = "127.0.0.1";
$username   = "root";
$password   = "";
$dbname     = "charitybridge";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$ngo_id  = $_SESSION['ngo_id'];
$name    = $_POST['name'];
$ngo_type = $_POST['ngo_type'];
$address = $_POST['address'];

$qr_code_image = null;

// ✅ Check if NGO uploaded new QR code
if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . "/qrcodes/"; // folder path (backend/qrcodes/)
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_tmp  = $_FILES['qr_code']['tmp_name'];
    $file_name = time() . "_" . basename($_FILES['qr_code']['name']); // unique file name
    $target    = $upload_dir . $file_name;

    if (move_uploaded_file($file_tmp, $target)) {
        $qr_code_image = $file_name; // save file name to DB
    }
}

// ✅ Update profile (with or without QR)
if ($qr_code_image) {
    $sql = "UPDATE ngos SET name=?, ngo_type=?, address=?, qr_code_image=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $name, $ngo_type, $address, $qr_code_image, $ngo_id);
} else {
    $sql = "UPDATE ngos SET name=?, ngo_type=?, address=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $name, $ngo_type, $address, $ngo_id);
}

if ($stmt->execute()) {
    header("Location: ../manage_profile.php?success=1");
    exit();
} else {
    echo "Error updating profile: " . htmlspecialchars($stmt->error);
}

$stmt->close();
$conn->close();
?>
