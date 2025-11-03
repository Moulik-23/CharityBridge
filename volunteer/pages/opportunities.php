<?php
session_start();

// ✅ PREVENT CACHING - Force browser to fetch fresh data
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

if (!isset($_SESSION['volunteer_id'])) {
    header("Location: ../auth/login.html");
    exit();
}

$volunteer_id = (int)$_SESSION['volunteer_id'];

$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ Disable MySQL query cache for this connection to get fresh data
$conn->query("SET SESSION query_cache_type = OFF");

/* Fetch all opportunities */
$all_opportunities = [];
$sql = "SELECT vr.req_id, vr.title, vr.description, vr.required_skills, vr.slots, vr.event_date, vr.location, vr.ngo_id, n.name AS ngo_name
        FROM volunteer_requirements vr
        JOIN ngos n ON vr.ngo_id = n.id
        ORDER BY vr.created_at DESC";
if ($res = $conn->query($sql)) {
    $all_opportunities = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
}

/* Fetch applications by this volunteer (and build status map) */
$my_applications = [];
$app_status = []; // keyed by req_id => ['status'=>..., 'map_id'=>...]

// ✅ Force fresh data from database - no cache
$applied_sql = "SELECT SQL_NO_CACHE vnm.id AS map_id, vnm.req_id, vnm.status, vr.title, vr.event_date, vr.location, n.name AS ngo_name
                FROM volunteer_ngo_map vnm
                JOIN volunteer_requirements vr ON vnm.req_id = vr.req_id
                JOIN ngos n ON vr.ngo_id = n.id
                WHERE vnm.volunteer_id = ?
                ORDER BY vnm.created_at DESC";

if ($stmt = $conn->prepare($applied_sql)) {
    $stmt->bind_param("i", $volunteer_id);
    $stmt->execute();
    $res2 = $stmt->get_result();
    if ($res2) {
        while ($row = $res2->fetch_assoc()) {
            $my_applications[] = $row;
            // ✅ Store the latest status for each req_id
            $app_status[$row['req_id']] = [
                'status' => $row['status'], 
                'map_id' => $row['map_id']
            ];
        }
        $res2->free();
    }
    $stmt->close();
}

/* optional: read messages */
$msg = isset($_GET['msg']) ? $_GET['msg'] : null;
$error = isset($_GET['error']) ? $_GET['error'] : null;

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <title>Volunteer Opportunities - CharityBridge</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../../css/dashboard.css">
  <link rel="stylesheet" href="../../css/style.css">
  <script>
    function switchTab(tabId){
      document.getElementById('browse-tab').classList.add('hidden');
      document.getElementById('myapps-tab').classList.add('hidden');
      document.getElementById(tabId).classList.remove('hidden');
    }
    // show browse by default
    document.addEventListener('DOMContentLoaded', function(){ 
      switchTab('browse-tab'); 
    });
  </script>
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
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="opportunities.php" class="active"><i class="fas fa-bullhorn"></i> Opportunities</a>
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
    document.getElementById('logout-link').addEventListener('click', function(event) {
      event.preventDefault(); // Prevent default link behavior
      if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../logout.php'; // Redirect to logout script
      }
    });
    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    const logoutLinkMobile = document.getElementById('logout-link-mobile');
    if (mobileMenuButton) {
      mobileMenuButton.addEventListener('click', function() {
        mobileMenu.classList.toggle('hidden');
      });
    }
    if (logoutLinkMobile) {
      logoutLinkMobile.addEventListener('click', function(event) {
        event.preventDefault();
        if (confirm('Are you sure you want to logout?')) {
          window.location.href = '../logout.php';
        }
      });
    }
  });
</script>
  <div class="container">
  <h1 class="mb-6">Volunteer Opportunities</h1>

  <!-- messages -->
  <?php if ($msg === 'applied'): ?>
    <div class="message success mb-4">
      ✅ Application submitted. NGO will review your request.
    </div>
  <?php elseif ($error === 'rejected'): ?>
    <div class="message error mb-4">
      ❌ You were rejected for this opportunity — re-apply is not allowed.
    </div>
  <?php elseif ($error === 'already_applied'): ?>
    <div class="message error mb-4">
      ⚠️ You have already applied for this opportunity.
    </div>
  <?php elseif ($error === 'db'): ?>
    <div class="message error mb-4">
      ❌ Database error, please try again later.
    </div>
  <?php elseif ($error === 'slots_full'): ?>
    <div class="message error mb-4">
      ❌ Sorry, all slots for this opportunity are filled.
    </div>
  <?php elseif ($error === 'invalid'): ?>
    <div class="message error mb-4">
      ❌ Invalid request. Please try again.
    </div>
  <?php endif; ?>

  <div class="flex space-x-4 my-6">
    <button onclick="switchTab('browse-tab')" class="btn btn-primary">
      Browse Opportunities
    </button>
    <button onclick="switchTab('myapps-tab')" class="btn btn-success">
      My Applications
    </button>
  </div>

  <!-- BROWSE TAB -->
  <div id="browse-tab">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php if (count($all_opportunities) > 0): ?>
        <?php foreach ($all_opportunities as $opp): ?>
          <div class="bg-white shadow rounded-lg p-6 border border-gray-light">
            <h3 class="text-lg font-bold"><?= htmlspecialchars($opp['title']) ?></h3>
            <p class="text-gray-700 mb-2"><?= htmlspecialchars($opp['description']) ?></p>
            <p class="text-sm"><strong>NGO:</strong> <?= htmlspecialchars($opp['ngo_name']) ?></p>
            <p class="text-sm"><strong>Date:</strong> <?= htmlspecialchars(date("d M Y", strtotime($opp['event_date']))) ?></p>
            <p class="text-sm"><strong>Location:</strong> <?= htmlspecialchars($opp['location']) ?></p>
            <p class="text-sm"><strong>Skills:</strong> <?= htmlspecialchars($opp['required_skills']) ?></p>
            <p class="text-sm"><strong>Slots:</strong> <?= htmlspecialchars($opp['slots']) ?></p>

            <!-- ✅ Check application status for THIS specific requirement -->
            <?php if (isset($app_status[$opp['req_id']])): ?>
                <?php 
                $current_status = $app_status[$opp['req_id']]['status']; 
                ?>
                
                <!-- Debug info (remove in production) -->
                <!-- Status from DB: <?= $current_status ?> for req_id: <?= $opp['req_id'] ?> -->
                
                <?php if ($current_status === 'pending'): ?>
                    <button class="w-full btn btn-accent mt-3 cursor-not-allowed" disabled>
                      ⏳ Pending Review
                    </button>
                <?php elseif ($current_status === 'approved'): ?>
                    <button class="w-full btn btn-success mt-3 cursor-not-allowed" disabled>
                      ✅ Approved
                    </button>
                <?php elseif ($current_status === 'rejected'): ?>
                    <button class="w-full btn btn-danger mt-3 cursor-not-allowed" disabled>
                      ❌ Rejected
                    </button>
                <?php elseif ($current_status === 'withdrawn'): ?>
                    <button class="w-full btn bg-orange-500 text-white mt-3 cursor-not-allowed" disabled>
                      🚫 Withdraw
                    </button>
                <?php else: ?>
                    <button class="w-full btn btn-secondary mt-3 cursor-not-allowed" disabled>
                      Applied (<?= htmlspecialchars($current_status) ?>)
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <!-- Not applied yet to THIS requirement -->
                <form method="POST" action="apply_opportunity.php" class="mt-3">
                  <input type="hidden" name="req_id" value="<?= (int)$opp['req_id'] ?>">
                  <input type="hidden" name="ngo_id" value="<?= (int)$opp['ngo_id'] ?>">
                  <button type="submit" class="w-full btn btn-primary">
                    Apply Now
                  </button>
                </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-gray-600 col-span-3">No volunteer opportunities available at the moment.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- MY APPLICATIONS TAB -->
  <div id="myapps-tab" class="hidden bg-white shadow rounded-lg overflow-hidden border border-gray-light p-6 mt-8">
    <table class="min-w-full text-left table-auto">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 border-b-2 border-gray-200">Title</th>
          <th class="px-4 py-2 border-b-2 border-gray-200">NGO</th>
          <th class="px-4 py-2 border-b-2 border-gray-200">Date</th>
          <th class="px-4 py-2 border-b-2 border-gray-200">Location</th>
          <th class="px-4 py-2 border-b-2 border-gray-200">Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if (count($my_applications) > 0): ?>
        <?php foreach ($my_applications as $app): ?>
          <tr class="border-b border-gray-100 hover:bg-gray-50">
            <td class="px-4 py-2"><?= htmlspecialchars($app['title']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($app['ngo_name']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars(date("d M Y", strtotime($app['event_date']))) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($app['location']) ?></td>
            <td class="px-4 py-2">
              <?php if ($app['status'] === 'pending'): ?>
                <span class="inline-block bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold">
                  ⏳ Pending
                </span>
              <?php elseif ($app['status'] === 'approved'): ?>
                <span class="inline-block bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">
                  ✅ Approved
                </span>
              <?php elseif ($app['status'] === 'rejected'): ?>
                <span class="inline-block bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-semibold">
                  ❌ Rejected
                </span>
              <?php elseif ($app['status'] === 'withdrawn'): ?>
                <span class="inline-block bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-sm font-semibold">
                  🚫 Withdraw
                </span>
              <?php else: ?>
                <span class="inline-block bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-semibold">
                  <?= htmlspecialchars($app['status']) ?>
                </span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="5" class="px-4 py-4 text-center text-gray-500">
            You haven't applied for any opportunities yet.
          </td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  </div>
</main>
</div>

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
