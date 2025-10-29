<?php
session_start();

// Clear all existing session variables to ensure only one user is logged in at a time
session_unset();
session_destroy();
session_start();
session_regenerate_id(true);

header('Content-Type: application/json'); // Set content type to JSON

// DB connection
$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        $conn->close();
        exit();
    }

    $password_md5 = md5($password); // assuming passwords stored in md5

    // Fetch donor by email
    $stmt = $conn->prepare("SELECT donor_id, name, password_hash FROM donors WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $donor = $result->fetch_assoc();

        // Verify password
        if ($donor['password_hash'] === $password_md5) {
            // Save session
            $_SESSION['donor_id'] = $donor['donor_id'];
            $_SESSION['donor_name'] = $donor['name'];

            echo json_encode(['success' => true, 'redirect' => '../donor/dashboard.php']); // Return JSON for success
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password!']); // Return JSON for invalid password
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password!']); // Return JSON for donor not found
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
