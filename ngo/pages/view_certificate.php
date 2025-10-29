<?php
if (isset($_GET['id'])) {
    $ngo_id = $_GET['id'];

    $conn = new mysqli('localhost', 'root', '', 'charitybridge');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT certificate FROM ngos WHERE id = ?");
    $stmt->bind_param("i", $ngo_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($certificate);
        $stmt->fetch();

        // Determine content type dynamically if possible, default to PDF
        // A more robust solution might store the MIME type in the database
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"certificate.pdf\"");
        echo $certificate;
    } else {
        echo "Certificate not found.";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
?>
