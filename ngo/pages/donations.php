<?php
session_start();

// 🚨 Redirect to login if NGO is not logged in
if (!isset($_SESSION['ngo_id'])) {
    header("Location: ../auth/login.html");
    exit();
}

// ✅ Database connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "charitybridge";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$ngo_id = $_SESSION['ngo_id'];

// ✅ Fetch all donations with donor details
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
$donations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ✅ Calculate total donations received
$total_sql = "SELECT SUM(amount) AS total_amount, COUNT(*) AS total_donations 
              FROM donations WHERE ngo_id = ?";
$stmt2 = $conn->prepare($total_sql);
$stmt2->bind_param("i", $ngo_id);
$stmt2->execute();
$total_result = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Donations - CharityBridge</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../css/style.css">
</head>
<body class="bg-light-color text-text-dark">

<!-- Header -->
<header class="bg-white shadow-md py-4 px-6">
  <div class="container mx-auto flex justify-between items-center">
    <a href="dashboard.php" class="text-3xl font-bold text-primary-color">CharityBridge - NGO</a>
    <nav class="hidden md:flex">
      <a href="dashboard.php" class="px-3 py-2">Dashboard</a>
      <a href="requirements.php" class="px-3 py-2">Post Requirement</a>
      <a href="donations.php" class="px-3 py-2 font-bold text-secondary-color">Manage Donations</a>
      <a href="volunteers.php" class="px-3 py-2">Volunteers</a>
      <a href="manage_profile.html" class="px-3 py-2">Manage Profile</a>
      <a href="../backend/logout.php" class="px-3 py-2">Logout</a>
    </nav>
  </div>
</header>

<!-- Main -->
<main class="py-16">
  <div class="container mx-auto px-6">

    <!-- ✅ Donation Summary -->
    <div class="bg-white shadow-lg rounded-xl p-6 mb-8 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-primary-color">Donations Received</h1>
        <p class="text-gray-600 mt-2">Track all contributions made to your NGO.</p>
      </div>
      <div class="text-right">
        <p class="text-lg font-semibold text-secondary-color">Total Donations: <?= $total_result['total_donations'] ?? 0 ?></p>
        <p class="text-2xl font-bold text-green-600">₹<?= number_format($total_result['total_amount'] ?? 0, 2) ?></p>
      </div>
    </div>

    <!-- ✅ All Donations -->
    <div class="overflow-x-auto bg-white rounded-xl shadow-lg">
      <table class="min-w-full text-left">
        <thead>
          <tr class="bg-primary-color text-white">
            <th class="py-3 px-4">Donor Name</th>
            <th class="py-3 px-4">Email</th>
            <th class="py-3 px-4">Amount</th>
            <th class="py-3 px-4">Payment Method</th>
            <th class="py-3 px-4">Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($donations) > 0): ?>
            <?php foreach ($donations as $donation): ?>
              <tr class="border-b hover:bg-gray-100">
                <td class="py-2 px-4"><?= htmlspecialchars($donation['donor_name']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($donation['donor_email']) ?></td>
                <td class="py-2 px-4 font-semibold text-green-600">₹<?= htmlspecialchars($donation['amount']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($donation['payment_method']) ?></td>
                <td class="py-2 px-4"><?= date("d M Y", strtotime($donation['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center py-4 text-gray-500">No donations received yet.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</main>

<!-- Footer -->
<footer class="bg-dark-color text-white py-6 text-center">
  <p>&copy; 2025 CharityBridge. All rights reserved.</p>
</footer>

</body>
</html>
