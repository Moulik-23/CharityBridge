<?php
session_start();
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: ../auth/login.html");
    exit();
}

$volunteer_id = (int)$_SESSION['volunteer_id'];

// Database connection
$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $req_id = isset($_POST['req_id']) ? (int)$_POST['req_id'] : 0;
    $ngo_id = isset($_POST['ngo_id']) ? (int)$_POST['ngo_id'] : 0;

    if ($req_id <= 0 || $ngo_id <= 0) {
        header("Location: opportunities.php?error=invalid");
        exit();
    }

    // ✅ CHECK 1: Has this volunteer already applied for THIS SPECIFIC requirement?
    $check_sql = "SELECT status FROM volunteer_ngo_map 
                  WHERE volunteer_id = ? AND req_id = ?";
    
    if ($stmt = $conn->prepare($check_sql)) {
        $stmt->bind_param("ii", $volunteer_id, $req_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Application exists for this requirement
            $status = $row['status'];
            
            // ✅ VALIDATION: If rejected, don't allow reapplication to SAME requirement
            if ($status === 'rejected') {
                $stmt->close();
                $conn->close();
                header("Location: opportunities.php?error=rejected");
                exit();
            }
            
            // If pending or approved, show already applied message
            $stmt->close();
            $conn->close();
            header("Location: opportunities.php?error=already_applied");
            exit();
        }
        
        $stmt->close();
    }

    // ✅ CHECK 2: Check if slots are still available
    $slot_check = "SELECT slots, 
                   (SELECT COUNT(*) FROM volunteer_ngo_map 
                    WHERE req_id = ? AND status = 'approved') as filled_slots
                   FROM volunteer_requirements 
                   WHERE req_id = ?";
    
    if ($stmt = $conn->prepare($slot_check)) {
        $stmt->bind_param("ii", $req_id, $req_id);
        $stmt->execute();
        $slot_result = $stmt->get_result();
        
        if ($slot_row = $slot_result->fetch_assoc()) {
            if ($slot_row['filled_slots'] >= $slot_row['slots']) {
                $stmt->close();
                $conn->close();
                header("Location: opportunities.php?error=slots_full");
                exit();
            }
        }
        $stmt->close();
    }

    // ✅ All validations passed - Insert new application with 'pending' status
    $insert_sql = "INSERT INTO volunteer_ngo_map (volunteer_id, ngo_id, req_id, status, created_at) 
                   VALUES (?, ?, ?, 'pending', NOW())";
    
    if ($stmt = $conn->prepare($insert_sql)) {
        $stmt->bind_param("iii", $volunteer_id, $ngo_id, $req_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: opportunities.php?msg=applied");
            exit();
        } else {
            $stmt->close();
            $conn->close();
            header("Location: opportunities.php?error=db");
            exit();
        }
    }
}

$conn->close();
header("Location: opportunities.php");
exit();
?>
