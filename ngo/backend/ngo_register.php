<?php
// Database connection
$servername = "127.0.0.1";
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
    if (!isset($_FILES['certificate']) || $_FILES['certificate']['error'] != 0) {
        die("⚠️ Certificate upload failed. Please try again.");
    }

    // Prepare statement without certificate first
    $sql = "INSERT INTO ngos 
        (name, email, phone, password, org_pan, reg_number, ngo_type, state, district, darpan_id, owner_pan, owner_name, acc_no, ifsc_code, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("❌ SQL Error: " . $conn->error);
    }

    // Bind parameters without certificate
    $stmt->bind_param("ssssssssssssss",
        $name, $email, $phone, $password, $org_pan, $reg_number, $ngo_type, $state, $district,
        $darpan_id, $owner_pan, $owner_name, $acc_no, $ifsc_code
    );

    if ($stmt->execute()) {
        $ngo_id = $stmt->insert_id;

        // Handle file upload
        $target_dir = "../certificates/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION);
        $new_filename = $ngo_id . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES['certificate']['tmp_name'], $target_file)) {
            // Update the certificate path in the database
            $update_sql = "UPDATE ngos SET certificate = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            if (!$update_stmt) {
                die("❌ SQL Error: " . $conn->error);
            }
            $update_stmt->bind_param("si", $new_filename, $ngo_id);
            
            if ($update_stmt->execute()) {
                // Redirect to waiting page
                header("Location: wait_for_approval.php");
                exit();
            } else {
                echo "❌ Error updating certificate path: " . $update_stmt->error;
            }
            $update_stmt->close();
        } else {
            // Optional: Delete the inserted NGO record if file upload fails
            $delete_sql = "DELETE FROM ngos WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $ngo_id);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            echo "❌ Error uploading certificate. Registration cancelled.";
        }
    } else {
        echo "❌ Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
