<?php
$conn = new mysqli("localhost", "root", "", "charitybridge");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "UPDATE ngos SET status='approved' WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        echo "✅ NGO Approved Successfully!";
    } else {
        echo "❌ Error: " . $conn->error;
    }
} else {
    echo "⚠️ Invalid Request";
}

$conn->close();
?>
