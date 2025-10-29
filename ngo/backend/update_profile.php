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

$ngo_id = $_SESSION['ngo_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'];
    $upi_id = $_POST['upi_id'] ?? null;
    

    $qr_code_image = null;
    if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] == 0) {
        $target_dir = "qrcodes/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $qr_code_image = basename($_FILES["qr_code"]["name"]);
        $target_file = $target_dir . $qr_code_image;
        move_uploaded_file($_FILES["qr_code"]["tmp_name"], $target_file);
    }

    if ($qr_code_image) {
        $sql = "UPDATE ngos SET name=?, email=?, phone=?, address=?, upi_id=?, qr_code_image=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $name, $email, $phone, $address, $upi_id, $qr_code_image, $ngo_id);
    } else {
        $sql = "UPDATE ngos SET name=?, email=?, phone=?, address=?, upi_id=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $name, $email, $phone, $address, $upi_id, $ngo_id);
    }

    if ($stmt->execute()) {
        header("Location: ../pages/manage_profile.php?success=1");
    } else {
        header("Location: ../pages/manage_profile.php?error=1");
    }

    $stmt->close();
}

$conn->close();
?>
