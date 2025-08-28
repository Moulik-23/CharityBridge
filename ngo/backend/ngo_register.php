<?php
// Database connection
$servername = "localhost";
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
    $phone      = $_POST['mobile'];
    $password   = md5($_POST['password']);  // store hashed password
    $org_pan    = $_POST['org_pan'];
    $reg_number = $_POST['reg_number'];
    $ngo_type   = $_POST['ngo_type'];
    $state      = $_POST['state'];
    $district   = $_POST['district'];
    $darpan_id  = $_POST['darpan_id'];
    $owner_pan  = $_POST['owner_pan'];
    $owner_name = $_POST['owner_name'];
    $acc_no     = $_POST['acc_no'];
    $ifsc_code  = $_POST['ifsc_code'];

    // File upload (certificate)
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] == 0) {
        $certificate = file_get_contents($_FILES['certificate']['tmp_name']);
    } else {
        die("⚠️ Certificate upload failed. Please try again.");
    }

    // Prepare statement with correct order
    $sql = "INSERT INTO ngos 
        (name, email, phone, password, org_pan, reg_number, ngo_type, state, district, darpan_id, owner_pan, owner_name, certificate, acc_no, ifsc_code, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("❌ SQL Error: " . $conn->error);
    }

    // Correct order in bind_param
    $stmt->bind_param("sssssssssssssss",
        $name, $email, $phone, $password, $org_pan, $reg_number, $ngo_type, $state, $district,
        $darpan_id, $owner_pan, $owner_name, $certificate, $acc_no, $ifsc_code
    );

    if ($stmt->execute()) {
        // Redirect to waiting page
        header("Location: wait_for_approval.php");
        exit();
    } else {
        echo "❌ Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
