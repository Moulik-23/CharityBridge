<?php
session_start();

// 🚨 Security: only logged-in NGOs can access
if (!isset($_SESSION['ngo_id'])) {
    header("Location: login.html");
    exit();
}

// DB connection
$servername = "localhost";
$username   = "root";
$password   = ""; // XAMPP default
$dbname     = "charitybridge";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

$ngo_id = $_SESSION['ngo_id'];

// ✅ Fetch NGO requirements
$req_sql = "SELECT * FROM requirements WHERE ngo_id=? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($req_sql);
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$requirements = $stmt->get_result();

// ✅ Fetch NGO donations
$don_sql = "SELECT d.donation_id, dn.name AS donor_name, d.amount, d.payment_method, d.created_at
            FROM donations d
            JOIN donors dn ON d.donor_id = dn.donor_id
            WHERE d.ngo_id=?
            ORDER BY d.created_at DESC LIMIT 5";
$stmt2 = $conn->prepare($don_sql);
$stmt2->bind_param("i", $ngo_id);
$stmt2->execute();
$donations = $stmt2->get_result();

// ✅ NGO search (if form submitted)
$search_results = [];
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $q = "%" . $_GET['q'] . "%";
    $search_sql = "SELECT id, name, ngo_type, state, district 
                   FROM ngos 
                   WHERE status='approved' AND (name LIKE ? OR ngo_type LIKE ? OR state LIKE ? OR district LIKE ?)";
    $stmt3 = $conn->prepare($search_sql);
    $stmt3->bind_param("ssss", $q, $q, $q, $q);
    $stmt3->execute();
    $search_results = $stmt3->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NGO Dashboard - CharityBridge</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light-color text-text-dark">

<!-- Header -->
<header class="bg-white shadow-md py-4 px-6">
  <div class="container mx-auto flex justify-between items-center">
    <div>
      <a href="dashboard.php" class="text-3xl font-bold text-primary-color">CharityBridge</a>
    </div>
    <div class="flex items-center">
      <nav class="hidden md:flex">
        <a href="dashboard.php" class="text-primary-color hover:text-secondary-color px-3 py-2">Dashboard</a>
        <a href="requirements.php" class="text-primary-color hover:text-secondary-color px-3 py-2">Post Requirement</a>
        <a href="donations.php" class="text-primary-color hover:text-secondary-color px-3 py-2">Manage Donations</a>
        <a href="volunteers.php" class="text-primary-color hover:text-secondary-color px-3 py-2">Volunteers</a>
        <a href="javascript:void(0);" id="logoutBtn" class="px-3 py-2">Logout </a>
      </nav>
    </div>
  </div>
</header>

<!-- Main -->
<main class="py-16">
  <div class="container mx-auto px-6">
    <h1 class="text-4xl font-bold mb-8 text-primary-color">Welcome to Your NGO Dashboard</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      
      <!-- Current Needs -->
      <div class="bg-white p-6 rounded-xl shadow-lg">
        <h2 class="text-2xl font-bold mb-4 flex items-center text-primary-color">
          <i class="fas fa-bullhorn mr-3 text-accent-color"></i> Current Needs
        </h2>
        <ul class="space-y-3">
          <?php if ($requirements->num_rows > 0): ?>
            <?php while($row = $requirements->fetch_assoc()): ?>
              <li class="flex items-center">
                <i class="fas fa-check mr-3 text-secondary-color"></i> <?= htmlspecialchars($row['title']) ?> - <?= htmlspecialchars($row['description']) ?>
              </li>
            <?php endwhile; ?>
          <?php else: ?>
            <li>No requirements posted yet.</li>
          <?php endif; ?>
        </ul>
        <a href="requirements.php" class="mt-4 inline-block btn btn-primary">Post a New Requirement</a>
      </div>

      <!-- Recent Donations -->
      <div class="bg-white p-6 rounded-xl shadow-lg col-span-1 md:col-span-2">
        <h2 class="text-2xl font-bold mb-4 flex items-center text-primary-color">
          <i class="fas fa-gift mr-3 text-secondary-color"></i> Recent Donations Received
        </h2>
        <ul class="space-y-4">
          <?php if ($donations->num_rows > 0): ?>
            <?php while($row = $donations->fetch_assoc()): ?>
              <li class="flex items-center justify-between p-4 bg-light-color rounded-lg">
                <div>
                  <p class="font-semibold">₹<?= htmlspecialchars($row['amount']) ?> from <?= htmlspecialchars($row['donor_name']) ?></p>
                  <p class="text-sm text-gray-500"><?= htmlspecialchars($row['payment_method']) ?> | <?= htmlspecialchars($row['created_at']) ?></p>
                </div>
              </li>
            <?php endwhile; ?>
          <?php else: ?>
            <li>No donations yet.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <!-- NGO Search -->
    <div class="mt-16">
      <h2 class="text-3xl font-bold mb-6 text-primary-color">Find Other NGOs</h2>
      <form method="GET" class="flex items-center mb-6">
        <input type="text" name="q" class="w-full px-4 py-3 border rounded-l-lg focus:outline-none" placeholder="Search by name, cause, or location...">
        <button type="submit" class="btn btn-primary">Search</button>
      </form>

      <div id="results-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php if (!empty($search_results) && $search_results->num_rows > 0): ?>
          <?php while($ngo = $search_results->fetch_assoc()): ?>
            <div class="bg-white p-6 rounded-xl shadow-lg">
              <h3 class="text-xl font-bold"><?= htmlspecialchars($ngo['name']) ?></h3>
              <p class="text-gray-600"><?= htmlspecialchars($ngo['ngo_type']) ?></p>
              <p class="text-sm text-gray-500"><?= htmlspecialchars($ngo['district']) ?>, <?= htmlspecialchars($ngo['state']) ?></p>
            </div>
          <?php endwhile; ?>
        <?php elseif (isset($_GET['q'])): ?>
          <p>No NGOs found.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<!-- Footer -->
<footer class="bg-dark-color text-white py-12">
  <div class="container mx-auto text-center">
    <p>&copy; 2025 CharityBridge. All rights reserved.</p>
  </div>
</footer>


<script>
document.addEventListener("DOMContentLoaded", function() {
  const logoutBtn = document.getElementById("logoutBtn");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", function(event) {
      event.preventDefault();
      if (confirm("Are you sure you want to logout?")) {
        window.location.href = "../ngo/backend/logout.php";
      }
    });
  }
});
</script>
</body>
</html>
