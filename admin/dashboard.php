<?php
session_start();

// 🚨 Restrict access: redirect if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../admin/login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username   = "root";
$password   = ""; // empty for XAMPP
$dbname     = "charitybridge";

$conn = new mysqli('localhost', 'root', '', 'charitybridge');

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// ✅ Count Approved NGOs
// Approved NGOs
$result_approved_ngos = $conn->query("SELECT COUNT(*) AS approved FROM ngos WHERE status='approved'");
$total_ngos = $result_approved_ngos->fetch_assoc()['approved'] ?? 0;

// ✅ Count Pending NGOs
// Pending NGOs
$result_pending_ngos = $conn->query("SELECT COUNT(*) AS pending FROM ngos WHERE status='pending'");
$pending_ngos = $result_pending_ngos->fetch_assoc()['pending'] ?? 0;

// ✅ Count Total Donors
$result_donors = $conn->query("SELECT COUNT(*) AS total_donors FROM donors");
$total_donors = $result_donors->fetch_assoc()['total_donors'] ?? 0;

// ✅ Count Approved Restaurants
$result_approved_restaurants = $conn->query("SELECT COUNT(*) AS approved_restaurants FROM restaurants WHERE status='approved'");
$approved_restaurants = $result_approved_restaurants->fetch_assoc()['approved_restaurants'] ?? 0;

// ✅ Count Pending Restaurants
$result_pending_restaurants = $conn->query("SELECT COUNT(*) AS pending_restaurants FROM restaurants WHERE status='pending'");
$pending_restaurants = $result_pending_restaurants->fetch_assoc()['pending_restaurants'] ?? 0;

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
  <link rel="stylesheet" href="../css/dashboard.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<!-- Mobile Header with Hamburger -->
<div class="mobile-header">
  <div class="mobile-logo">
    <i class="fas fa-user-shield"></i>
    <span>CharityBridge Admin</span>
  </div>
  <button class="hamburger" id="hamburgerBtn">
    <span></span>
    <span></span>
    <span></span>
  </button>
</div>

<!-- Overlay for mobile -->
<div class="overlay" id="overlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <div class="logo">
    <i class="fas fa-user-shield"></i>
    <span>CharityBridge</span>
  </div>
  <nav class="nav">
    <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
    <a href="approvals.php"><i class="fas fa-check-circle"></i> Approvals</a>
    <a href="#" onclick="confirmLogout(event)"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
</div>

<!-- Main Content -->
<div class="main-content">
  <div class="header">
    <h1>Admin Dashboard</h1>
    <div class="user-info">
      <i class="fas fa-user-circle"></i>
      <span>Administrator</span>
    </div>
  </div>

  <!-- Quick Stats -->
  <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 32px;">
    <div class="stat-card">
      <div class="stat-icon" style="background: #e8effe;">
        <i class="fas fa-users" style="color: #5b7ac7;"></i>
      </div>
      <div class="stat-content">
        <h3><?= $total_donors ?></h3>
        <p>Total Donors</p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background: #e6f7ed;">
        <i class="fas fa-hands-helping" style="color: #5ba573;"></i>
      </div>
      <div class="stat-content">
        <h3><?= $total_ngos ?></h3>
        <p>Approved NGOs</p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background: #fff4e6;">
        <i class="fas fa-utensils" style="color: #d97706;"></i>
      </div>
      <div class="stat-content">
        <h3><?= $approved_restaurants ?></h3>
        <p>Approved Restaurants</p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background: #fef3c7;">
        <i class="fas fa-hourglass-half" style="color: #92400e;"></i>
      </div>
      <div class="stat-content">
        <h3><?= $pending_ngos ?></h3>
        <p>Pending NGOs</p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background: #e0e7ff;">
        <i class="fas fa-chart-line" style="color: #3730a3;"></i>
      </div>
      <div class="stat-content">
        <h3>₹<?= $total_donations ?></h3>
        <p>Donations this Month</p>
      </div>
    </div>
  </div>

  <!-- Charts -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 space-y-6">
      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-chart-pie"></i> NGO Status</h2>
        </div>
        <div style="padding: 20px; height: 250px;">
          <canvas id="ngoStatusChart"></canvas>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-chart-pie"></i> Restaurant Status</h2>
        </div>
        <div style="padding: 20px; height: 250px;">
          <canvas id="restaurantStatusChart"></canvas>
        </div>
      </div>
    </div>
    <div class="lg:col-span-2">
      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-chart-line"></i> Donations Trend</h2>
        </div>
        <div style="padding: 24px; height: 400px;">
          <canvas id="donationsChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

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
  // Mobile menu toggle
  const hamburger = document.getElementById('hamburgerBtn');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');

  function toggleMenu() {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    hamburger.classList.toggle('active');
  }

  hamburger.addEventListener('click', toggleMenu);
  overlay.addEventListener('click', toggleMenu);

  // Close sidebar when clicking a nav link on mobile
  document.querySelectorAll('.nav a').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 768) {
        toggleMenu();
      }
    });
  });

  const ngoCtx = document.getElementById('ngoStatusChart').getContext('2d');
  new Chart(ngoCtx, {
    type: 'doughnut',
    data: {
      labels: ['Approved NGOs', 'Pending NGOs'],
      datasets: [{
        data: [<?= $total_ngos ?>, <?= $pending_ngos ?>],
        backgroundColor: ['#16a34a','#f59e0b']
      }]
    },
    options: { responsive: true, maintainAspectRatio: false }
  });

  const restaurantCtx = document.getElementById('restaurantStatusChart').getContext('2d');
  new Chart(restaurantCtx, {
    type: 'doughnut',
    data: {
      labels: ['Approved Restaurants', 'Pending Restaurants'],
      datasets: [{
        data: [<?= $approved_restaurants ?>, <?= $pending_restaurants ?>],
        backgroundColor: ['#3b82f6','#a78bfa']
      }]
    },
    options: { responsive: true, maintainAspectRatio: false }
  });

  // Donations line chart (restored)
  const donationsCtx = document.getElementById('donationsChart').getContext('2d');
  new Chart(donationsCtx, {
    type: 'line',
    data: {
      labels: ['Jan','Feb','Mar','Apr','May','Jun'],
      datasets: [{
        label: 'Donations (₹)',
        data: [200, 300, 400, 500, 600, 700],
        backgroundColor: 'rgba(59,130,246,0.15)',
        borderColor: 'rgba(59,130,246,1)',
        borderWidth: 2,
        fill: true,
        tension: 0.4
      }]
    },
    options: { responsive: true, maintainAspectRatio: false }
  });
});
</script>

<!-- Session Manager -->
<script src="../js/session_manager.js"></script>
<script src="../js/dynamic_updates.js"></script>
<script>
    // Create session for admin
    sessionManager.createSession('admin', 1, 'Administrator');
</script>

</body>
</html>
