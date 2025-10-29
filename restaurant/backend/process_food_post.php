<?php
session_start();

if (!isset($_SESSION['restaurant_id'])) {
    header("Location: ../auth/restaurant_login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $restaurant_id = $_SESSION['restaurant_id'];
    $food_item = filter_input(INPUT_POST, 'food_item', FILTER_SANITIZE_STRING);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $unit = filter_input(INPUT_POST, 'unit', FILTER_SANITIZE_STRING);
    $posted_time = date('Y-m-d H:i:s'); // Current timestamp

    if (!$food_item || !$quantity || !$unit) {
        $_SESSION['error_message'] = "All fields are required.";
        header("Location: ../pge/post_food.php");
        exit();
    }

    $image_path = null;
    if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../pge/uploads/";
        // Ensure the uploads directory exists
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['image_path']['name'], PATHINFO_EXTENSION);
        $new_file_name = time() . '_' . uniqid() . '.' . $file_extension; // Unique filename
        $target_file = $target_dir . $new_file_name;

        if (move_uploaded_file($_FILES['image_path']['tmp_name'], $target_file)) {
            $image_path = $new_file_name; // Store only the filename in the database
        } else {
            $_SESSION['error_message'] = "Failed to upload image.";
            header("Location: ../pge/post_food.php");
            exit();
        }
    }

    $conn = new mysqli('localhost', 'root', '', 'charitybridge');
    if ($conn->connect_error) {
        die("❌ DB Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("INSERT INTO food_posts (restaurant_id, food_item, quantity, posted_time, status, image_path, unit) VALUES (?, ?, ?, ?, 'Waiting', ?, ?)");
    $stmt->bind_param("isssss", $restaurant_id, $food_item, $quantity, $posted_time, $image_path, $unit);

    $success = false;
    $message = "";

    if ($stmt->execute()) {
        $success = true;
        $message = "Food donation posted successfully! It is now awaiting NGO acceptance.";
    } else {
        $message = "Error posting food donation: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();

} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}
?>
