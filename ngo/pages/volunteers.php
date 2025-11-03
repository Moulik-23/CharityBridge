<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['ngo_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$ngo_id = $_SESSION['ngo_id'];
$message = "";

// Handle Approve/Reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['map_id'])) {
    $action = $_POST['action'];
    $map_id = intval($_POST['map_id']);

    if (in_array($action, ['approved', 'rejected', 'removed'])) {
        // For 'removed' action, we don't delete - just mark as removed
        $stmt = $conn->prepare("UPDATE volunteer_ngo_map SET status=? WHERE id=?");
        $stmt->bind_param("si", $action, $map_id);
        $stmt->execute();
        $stmt->close();
        $message = "Volunteer status updated!";
    }
}

// Fetch PENDING applications
$sql_pending = "SELECT vnm.id, v.name, v.email, v.phone, v.city, vr.title AS requirement_title, vnm.status
        FROM volunteer_ngo_map vnm
        JOIN volunteers v ON vnm.volunteer_id = v.volunteer_id
        JOIN volunteer_requirements vr ON vnm.req_id = vr.req_id
        WHERE vr.ngo_id = ? AND vnm.status = 'pending'
        ORDER BY vnm.created_at DESC";

$stmt = $conn->prepare($sql_pending);
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$res_pending = $stmt->get_result();
$pending = $res_pending->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch APPROVED volunteers (including withdrawn)
$sql_approved = "SELECT vnm.id, v.name, v.email, v.phone, v.city, vr.title AS requirement_title, vnm.status
        FROM volunteer_ngo_map vnm
        JOIN volunteers v ON vnm.volunteer_id = v.volunteer_id
        JOIN volunteer_requirements vr ON vnm.req_id = vr.req_id
        WHERE vr.ngo_id = ? AND vnm.status IN ('approved', 'withdrawn')
        ORDER BY vnm.created_at DESC";

$stmt = $conn->prepare($sql_approved);
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$res_approved = $stmt->get_result();
$approved = $res_approved->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Volunteers - CharityBridge</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../../css/dashboard.css">
</head>
<body>

<!-- Mobile Header with Hamburger -->
<div class="mobile-header">
  <div class="mobile-logo">
    <i class="fas fa-hands-helping"></i>
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
    <i class="fas fa-hands-helping"></i>
    <span>CharityBridge</span>
  </div>
  <nav class="nav">
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="requirements.php"><i class="fas fa-bullhorn"></i> Post Requirement</a>
    <a href="donations.php"><i class="fas fa-gift"></i> Manage Donations</a>
    <a href="volunteers.php" class="active"><i class="fas fa-users"></i> Volunteers</a>
    <a href="manage_profile.php"><i class="fas fa-user"></i> Manage Profile</a>
    <a href="../backend/logout.php" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
</div>

<!-- Main Content -->
<div class="main-content">
  <div class="header">
    <h1>Volunteer Management</h1>
    <div class="user-info">
      <i class="fas fa-user-circle"></i>
      <span>NGO User</span>
    </div>
  </div>

    <?php if ($message): ?>
      <div class="message success mb-4">
        <span class="block sm:inline"><?= $message ?></span>
      </div>
    <?php endif; ?>

    <!-- Tab Buttons -->
    <div class="flex space-x-4 mb-6">
      <button onclick="switchTab('pending-tab')" class="btn btn-primary">Pending Applications</button>
      <button onclick="switchTab('approved-tab')" class="btn btn-secondary">Approved Volunteers</button>
    </div>

    <!-- Pending Volunteers -->
    <div id="pending-tab" class="bg-white p-8 rounded-xl shadow-lg border border-gray-light">
      <h2 class="text-xl font-bold mb-4 text-primary-color">Pending Applications</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full text-left table-auto">
          <thead>
            <tr class="bg-primary-color text-white">
              <th class="py-3 px-4 border-b-2 border-gray-200">Name</th>
              <th class="py-3 px-4 border-b-2 border-gray-200">Email</th>
              <th class="py-3 px-4 border-b-2 border-gray-200">Phone</th>
              <th class="py-3 px-4 border-b-2 border-gray-200">City</th>
              <th class="py-3 px-4 border-b-2 border-gray-200">Requirement</th>
              <th class="py-3 px-4 text-center border-b-2 border-gray-200">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($pending) > 0): ?>
              <?php foreach ($pending as $p): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                  <td class="py-2 px-4"><?= htmlspecialchars($p['name']) ?></td>
                  <td class="py-2 px-4"><?= htmlspecialchars($p['email']) ?></td>
                  <td class="py-2 px-4"><?= htmlspecialchars($p['phone']) ?></td>
                  <td class="py-2 px-4"><?= htmlspecialchars($p['city']) ?></td>
                  <td class="py-2 px-4"><?= htmlspecialchars($p['requirement_title']) ?></td>
                  <td class="py-2 px-4 text-center">
                    <form method="POST" class="inline-block mr-2">
                      <input type="hidden" name="map_id" value="<?= $p['id'] ?>">
                      <input type="hidden" name="action" value="approved">
                      <button class="btn btn-success btn-sm">Approve</button>
                    </form>
                    <form method="POST" class="inline-block">
                      <input type="hidden" name="map_id" value="<?= $p['id'] ?>">
                      <input type="hidden" name="action" value="rejected">
                      <button class="btn btn-danger btn-sm">Reject</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="6" class="py-4 text-center text-gray-500">No pending applications.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Approved Volunteers -->
    <div id="approved-tab" class="hidden bg-white p-8 rounded-xl shadow-lg border border-gray-light mt-8">
      <h2 class="text-xl font-bold mb-4 text-primary-color">Approved Volunteers</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full text-left table-auto">
          <thead>
            <tr class="bg-primary-color text-white">
              <th class="py-3 px-4 border-b-2 border-gray-200">Name</th>
              <th class="py-3 px-4 border-b-2 border-gray-200">Email</th>
              <th class="py-3 px-4 border-b-2 border-gray-200">Phone</th>
              <th class="py-3 px-4 border-b-2 border-gray-200">City</th>
              <th class="py-3 px-4 border-b-2 border-gray-200">Requirement</th>
              <th class="py-3 px-4 border-b-2 border-gray-200">Status</th>
              <th class="py-3 px-4 text-center border-b-2 border-gray-200">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($approved) > 0): ?>
              <?php foreach ($approved as $a): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50 <?= $a['status'] === 'withdrawn' ? 'bg-gray-100' : '' ?>">
                  <td class="py-2 px-4"><?= htmlspecialchars($a['name']) ?></td>
                  <td class="py-2 px-4"><?= htmlspecialchars($a['email']) ?></td>
                  <td class="py-2 px-4"><?= htmlspecialchars($a['phone']) ?></td>
                  <td class="py-2 px-4"><?= htmlspecialchars($a['city']) ?></td>
                  <td class="py-2 px-4"><?= htmlspecialchars($a['requirement_title']) ?></td>
                  <td class="py-2 px-4">
                    <?php if ($a['status'] === 'withdrawn'): ?>
                      <span class="px-2 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800">
                        Volunteer Withdraw
                      </span>
                    <?php else: ?>
                      <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                        Active
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="py-2 px-4 text-center">
                    <?php if ($a['status'] !== 'withdrawn'): ?>
                      <form method="POST" class="inline-block" onsubmit="return confirm('Remove this approved volunteer?');">
                        <input type="hidden" name="map_id" value="<?= $a['id'] ?>">
                        <input type="hidden" name="action" value="removed">
                        <button class="skill-delete-btn" title="Remove volunteer" aria-label="Remove volunteer">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                    <?php else: ?>
                      <span class="text-gray-400 text-sm">Withdrawn</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="7" class="py-4 text-center text-gray-500">No approved volunteers yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
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
    link.addEventListener('click', (e) => {
      if (window.innerWidth <= 768) {
        toggleMenu();
      }
      // Logout confirmation
      if (link.id === 'logoutBtn' && !confirm("Are you sure you want to logout?")) {
        e.preventDefault();
      }
    });
  });

  // Tab switching functions
  function switchTab(tabId) {
    document.getElementById("pending-tab").classList.add("hidden");
    document.getElementById("approved-tab").classList.add("hidden");
    document.getElementById(tabId).classList.remove("hidden");
  }
</script>

</body>
</html>
