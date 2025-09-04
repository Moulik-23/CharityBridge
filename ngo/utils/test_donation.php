<?php
session_start();

// Print current session NGO ID
if (!isset($_SESSION['ngo_id'])) {
    echo "<h2>❌ No NGO is logged in. Please log in first.</h2>";
    exit();
}

$ngo_id = $_SESSION['ngo_id'];
echo "<h2>✅ NGO ID in session: " . htmlspecialchars($ngo_id) . "</h2>";

// DB connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "charitybridge";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// Run query
$sql = "SELECT d.donation_id, dn.name AS donor_name, dn.email AS donor_email,
               d.amount, d.payment_method, d.created_at
        FROM donations d
        JOIN donors dn ON d.donor_id = dn.donor_id
        WHERE d.ngo_id = ?
        ORDER BY d.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$result = $stmt->get_result();

// Show results
if ($result->num_rows > 0) {
    echo "<h3>✅ Donations found:</h3><ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>Donor: " . htmlspecialchars($row['donor_name']) .
             " | Amount: ₹" . htmlspecialchars($row['amount']) .
             " | Method: " . htmlspecialchars($row['payment_method']) .
             " | Date: " . htmlspecialchars($row['created_at']) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<h3>❌ No donations found for NGO ID = $ngo_id</h3>";
}

$stmt->close();
$conn->close();
?>
