<?php
session_start();

// 🚨 Restrict access: redirect if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../admin/login.html");
    exit();
}

// Database connection
$servername = "localhost";
$username   = "root";
$password   = ""; // empty for XAMPP
$dbname     = "charitybridge";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// ✅ Count Approved NGOs
$result_approved = $conn->query("SELECT COUNT(*) AS approved FROM ngos WHERE status='approved'");
$total_ngos = $result_approved->fetch_assoc()['approved'] ?? 0;

// ✅ Count Pending NGOs
$result_pending = $conn->query("SELECT COUNT(*) AS pending FROM ngos WHERE status='pending'");
$pending_ngos = $result_pending->fetch_assoc()['pending'] ?? 0;

// ✅ Count Total Users (Donors)
$result_users = $conn->query("SELECT COUNT(*) AS total_users FROM donors");
$total_users = $result_users->fetch_assoc()['total_users'] ?? 0;

// ✅ Donations this Month
$result_donations = $conn->query("
    SELECT COALESCE(SUM(amount),0) AS total_donations 
    FROM donations 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
      AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
$total_donations = $result_donations->fetch_assoc()['total_donations'] ?? 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - CharityBridge</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light-color text-text-dark">

<!-- Header -->
<header class="bg-white shadow-md py-4 px-6">
  <div class="container mx-auto flex justify-between items-center">
    <div>
      <a href="../index.html" class="text-3xl font-bold text-primary-color">CharityBridge - Admin</a>
    </div>
    <div class="flex items-center">
      <nav class="hidden md:flex">
        <a href="dashboard.php" class="text-primary-color hover:text-secondary-color px-3 py-2">Dashboard</a>
        <a href=".\approvals.php" class="text-primary-color hover:text-secondary-color px-3 py-2">Approvals</a>
        <a href="#" onclick="confirmLogout(event)" class="text-primary-color hover:text-secondary-color px-3 py-2">Logout</a>
      </nav>
    </div>
  </div>
</header>

<!-- Main Content -->
<main class="py-16">
  <div class="container mx-auto px-6">
    <h1 class="text-4xl font-bold mb-8 text-primary-color">Admin Dashboard</h1>
    
    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
      <div class="bg-white p-6 rounded-xl shadow-lg flex items-center">
        <i class="fas fa-users text-3xl text-primary-color mr-4"></i>
        <div>
          <p class="text-gray-600">Total Users</p>
          <p class="text-2xl font-bold"><?= $total_users ?></p>
        </div>
      </div>
      <div class="bg-white p-6 rounded-xl shadow-lg flex items-center">
        <i class="fas fa-hands-helping text-3xl text-secondary-color mr-4"></i>
        <div>
          <p class="text-gray-600">Approved NGOs</p>
          <p class="text-2xl font-bold"><?= $total_ngos ?></p>
        </div>
      </div>
      <div class="bg-white p-6 rounded-xl shadow-lg flex items-center">
        <i class="fas fa-hourglass-half text-3xl text-yellow-500 mr-4"></i>
        <div>
          <p class="text-gray-600">Pending NGOs</p>
          <p class="text-2xl font-bold"><?= $pending_ngos ?></p>
        </div>
      </div>
      <div class="bg-white p-6 rounded-xl shadow-lg flex items-center">
        <i class="fas fa-chart-line text-3xl text-gray-500 mr-4"></i>
        <div>
          <p class="text-gray-600">Donations this Month</p>
          <p class="text-2xl font-bold">₹<?= $total_donations ?></p>
        </div>
      </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg">
        <h2 class="text-xl font-bold mb-4 text-primary-color">Platform Analytics</h2>
        <div class="h-64">
          <canvas id="donationsChart"></canvas>
        </div>
      </div>
      <div class="bg-white p-6 rounded-xl shadow-lg">
        <h2 class="text-xl font-bold mb-4 text-primary-color">NGO Breakdown</h2>
        <div class="h-64">
          <canvas id="userBreakdownChart"></canvas>
        </div>
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
// Confirm logout
function confirmLogout(event) {
  event.preventDefault();
  if (confirm("Are you sure you want to logout?")) {
    window.location.href = "backend/logout.php";
  }
}

// Charts
document.addEventListener("DOMContentLoaded", () => {
  const donationsCtx = document.getElementById('donationsChart').getContext('2d');
  new Chart(donationsCtx, {
    type: 'line',
    data: {
      labels: ['Jan','Feb','Mar','Apr','May','Jun'],
      datasets: [{
        label: 'Donations (₹)',
        data: [200, 300, 400, 500, 600, 700], // Replace with dynamic query later
        backgroundColor: 'rgba(0, 51, 102, 0.2)',
        borderColor: 'rgba(0, 51, 102, 1)',
        borderWidth: 2,
        fill: true,
        tension: 0.4
      }]
    },
    options: { responsive: true, maintainAspectRatio: false }
  });

  const ngoBreakdownCtx = document.getElementById('userBreakdownChart').getContext('2d');
  new Chart(ngoBreakdownCtx, {
    type: 'doughnut',
    data: {
      labels: ['Approved', 'Pending'],
      datasets: [{
        data: [<?= $total_ngos ?>, <?= $pending_ngos ?>],
        backgroundColor: ['#16a34a','#facc15']
      }]
    },
    options: { responsive: true, maintainAspectRatio: false }
  });
});
</script>

</body>
</html>
