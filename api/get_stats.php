<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "charitybridge";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

// Initialize stats
$stats = [
    'beneficiaries' => 0,
    'ngos' => 0,
    'meals' => 0,
    'volunteerHours' => 0
];

// Count beneficiaries (total unique donors who have donated)
$result = $conn->query("SELECT COUNT(DISTINCT donor_id) as count FROM donations");
if ($result && $row = $result->fetch_assoc()) {
    $stats['beneficiaries'] = (int)$row['count'];
}

// Add donors who donated goods
$result = $conn->query("SELECT COUNT(DISTINCT donor_id) as count FROM goods_donations");
if ($result && $row = $result->fetch_assoc()) {
    $stats['beneficiaries'] += (int)$row['count'];
}

// Count active NGOs (approved status)
$result = $conn->query("SELECT COUNT(*) as count FROM ngos WHERE status = 'approved'");
if ($result && $row = $result->fetch_assoc()) {
    $stats['ngos'] = (int)$row['count'];
}

// Count meals donated (from food_posts that are delivered or picked up)
$result = $conn->query("SELECT COALESCE(SUM(quantity), 0) as total FROM food_posts WHERE status IN ('Delivered', 'Picked Up')");
if ($result && $row = $result->fetch_assoc()) {
    $stats['meals'] = (int)$row['total'];
}

// Count volunteer hours (estimate based on completed deliveries - assume 2 hours per delivery)
$result = $conn->query("SELECT COUNT(*) as count FROM goods_donations WHERE status = 'delivered'");
if ($result && $row = $result->fetch_assoc()) {
    $stats['volunteerHours'] = (int)$row['count'] * 2; // 2 hours per delivery
}

// Add hours from food deliveries
$result = $conn->query("SELECT COUNT(*) as count FROM food_posts WHERE status = 'Delivered'");
if ($result && $row = $result->fetch_assoc()) {
    $stats['volunteerHours'] += (int)$row['count'] * 2; // 2 hours per delivery
}

$conn->close();

// Log the stats for debugging
error_log('Stats API Response: ' . json_encode($stats));

echo json_encode($stats);
?>
