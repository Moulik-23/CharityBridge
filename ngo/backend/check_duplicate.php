<?php
header('Content-Type: application/json');

// Database connection (replace with your actual database credentials)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "charitybridge"; // Assuming your database name is charitybridge

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['isDuplicate' => true, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

$isDuplicate = false;
$message = '';

if (empty($field) || empty($value)) {
    echo json_encode(['isDuplicate' => true, 'message' => 'Invalid request: field and value are required.']);
    $conn->close();
    exit();
}

// Sanitize input to prevent SQL injection
$field = $conn->real_escape_string($field);
$value = $conn->real_escape_string($value);

// Determine table and column based on the field
$table = 'ngos'; // Assuming 'ngos' is the table for NGO registrations
$column = '';

switch ($field) {
    case 'email':
        $column = 'email';
        break;
    case 'mobile': // The HTML input has id/name 'mobile', but the database column is 'phone'
        $column = 'phone';
        break;
    case 'org_pan':
        $column = 'org_pan';
        break;
    case 'reg_number':
        $column = 'reg_number';
        break;
    case 'darpan_id':
        $column = 'darpan_id';
        break;
    case 'owner_pan':
        $column = 'owner_pan';
        break;
    case 'acc_no': // Assuming bank account number is stored here
        $column = 'acc_no';
        break;
    case 'ifsc_code': // Assuming IFSC code is stored here
        $column = 'ifsc_code';
        break;
    default:
        echo json_encode(['isDuplicate' => true, 'message' => 'Invalid field for duplicate check.']);
        $conn->close();
        exit();
}

$sql = "SELECT COUNT(*) as count FROM $table WHERE $column = '$value'";
$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    if ($row['count'] > 0) {
        $isDuplicate = true;
        $message = ucfirst(str_replace('_', ' ', $field)) . ' already exists.';
    } else {
        $message = ucfirst(str_replace('_', ' ', $field)) . ' is available.';
    }
} else {
    $isDuplicate = true;
    $message = 'Database query failed: ' . $conn->error;
}

echo json_encode(['isDuplicate' => $isDuplicate, 'message' => $message]);

$conn->close();
?>
