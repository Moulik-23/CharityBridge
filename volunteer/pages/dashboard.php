<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: ../auth/volunteer_login.html");
    exit();
}

$volunteer_id = $_SESSION['volunteer_id'];

$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch volunteer details
$sql_vol = "SELECT name, skills FROM volunteers WHERE volunteer_id = ?";
$stmt = $conn->prepare($sql_vol);
$stmt->bind_param("i", $volunteer_id);
$stmt->execute();
$volunteer = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch approved opportunities for this volunteer (similar to scheduler.php)
$sql_activities = "SELECT vr.title, vr.description, vr.event_date, vr.location, n.name AS ngo_name, m.status
                   FROM volunteer_ngo_map m
                   JOIN volunteer_requirements vr ON m.req_id = vr.req_id
                   JOIN ngos n ON vr.ngo_id = n.id
                   WHERE m.volunteer_id = ? AND m.status = 'Approved'
                   ORDER BY vr.event_date ASC";
$stmt = $conn->prepare($sql_activities);
$stmt->bind_param("i", $volunteer_id);
$stmt->execute();
$result = $stmt->get_result();
$activities = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Volunteer Dashboard - CharityBridge</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../../css/dashboard.css">
  <link rel="stylesheet" href="../../css/style.css">
</head>
<body>

<!-- Mobile Header with Hamburger -->
<div class="mobile-header">
    <div class="mobile-logo">
        <i class="fas fa-users"></i>
        <span>CharityBridge</span>
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
        <i class="fas fa-users"></i>
        <span>CharityBridge</span>
    </div>
    <nav class="nav">
        <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="opportunities.php"><i class="fas fa-bullhorn"></i> Opportunities</a>
        <a href="scheduler.php"><i class="fas fa-calendar"></i> My Schedule</a>
        <a href="logistics.php"><i class="fas fa-truck"></i> Logistics</a>
        <a href="manage_profile.php"><i class="fas fa-user"></i> Manage Profile</a>
        <a href="#" id="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">

<main style="padding: 0;">
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Logout functionality
    document.getElementById('logout-link').addEventListener('click', function(event) {
      event.preventDefault();
      if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../logout.php';
      }
    });
  });
</script>
  <div class="container">
    <h1>Welcome, <?= htmlspecialchars($volunteer['name']) ?>!</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      
      <!-- Upcoming Activities -->
      <div class="bg-white p-6 rounded-xl shadow-lg col-span-1 md:col-span-2 border border-gray-light">
        <h2 class="mb-4 flex items-center">
          <i class="fas fa-calendar-alt mr-3 text-primary-color"></i> Your Enrolled Activities
        </h2>
        <ul class="space-y-4">
          <?php if (count($activities) > 0): ?>
            <?php foreach ($activities as $activity): ?>
              <li class="flex items-center justify-between p-4 bg-light-color rounded-lg border border-gray-light">
                <div>
                  <p class="font-semibold"><?= htmlspecialchars($activity['title']) ?> (<?= htmlspecialchars($activity['ngo_name']) ?>)</p>
                  <p class="text-sm text-gray-500">
                    <?= date("d M Y", strtotime($activity['event_date'])) ?> @ <?= htmlspecialchars($activity['location']) ?>
                  </p>
                </div>
                <span class="text-sm font-bold 
                  <?php 
                    if ($activity['status'] == 'Approved') {
                        echo 'text-blue-600'; // Approved activities are scheduled
                    } else {
                        echo 'text-yellow-600'; // Other statuses like 'Pending'
                    }
                  ?>">
                  <?= htmlspecialchars($activity['status']) ?>
                </span>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="text-gray-500 text-center py-4">No activities enrolled yet.</li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Volunteer Skills -->
      <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-light">
        <h2 class="mb-4 flex items-center">
          <i class="fas fa-star mr-3 text-accent-color"></i> Your Skills
        </h2>
        <div class="flex flex-wrap">
          <?php if (!empty($volunteer['skills'])): 
            $skills = explode(",", $volunteer['skills']);
            foreach ($skills as $skill): ?>
              <span class="bg-blue-100 text-blue-800 text-sm font-medium mr-2 mb-2 px-2.5 py-0.5 rounded-full">
                <?= htmlspecialchars(trim($skill)) ?>
              </span>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-gray-500">No skills added yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>
</div>

<footer>
  <div class="container">
    <p>&copy; 2025 CharityBridge. All rights reserved.</p>
  </div>
</footer>

<script>
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
</script>

<!-- Session Manager -->
<script src="../../js/session_manager.js"></script>
<script src="../../js/dynamic_updates.js"></script>
<script>
    // Create session for volunteer
    sessionManager.createSession('volunteer', <?= $volunteer_id ?>, '<?= htmlspecialchars($volunteer['name']) ?>');
</script>

</body>
</html>
