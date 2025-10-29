<?php
session_start();

// 🚨 Security: only logged-in NGOs can access
if (!isset($_SESSION['ngo_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// DB connection
$servername = "127.0.0.1";
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

// ✅ Fetch recent donations from all sources (monetary, goods, food)
$recent_donations = [];

// 1. Monetary donations
$monetary_sql = "SELECT d.donation_id as id, dn.name AS donor_name, d.amount, d.payment_method, d.created_at, 'monetary' as donation_type
                 FROM donations d
                 JOIN donors dn ON d.donor_id = dn.donor_id
                 WHERE d.ngo_id=?
                 ORDER BY d.created_at DESC LIMIT 5";
$stmt2 = $conn->prepare($monetary_sql);
$stmt2->bind_param("i", $ngo_id);
$stmt2->execute();
$monetary_donations = $stmt2->get_result();

while($row = $monetary_donations->fetch_assoc()) {
    $recent_donations[] = $row;
}

// 2. Goods donations
$goods_sql = "SELECT gd.goods_donation_id as id, dn.name AS donor_name, gd.item_description as description, gd.status, gd.created_at, 'goods' as donation_type
              FROM goods_donations gd
              JOIN donors dn ON gd.donor_id = dn.donor_id
              WHERE gd.ngo_id=?
              ORDER BY gd.created_at DESC LIMIT 5";
$stmt3 = $conn->prepare($goods_sql);
$stmt3->bind_param("i", $ngo_id);
$stmt3->execute();
$goods_donations = $stmt3->get_result();

while($row = $goods_donations->fetch_assoc()) {
    $recent_donations[] = $row;
}

// 3. Food donations (from restaurants)
// First get the NGO name to match with food_posts
$ngo_name_sql = "SELECT name FROM ngos WHERE id = ?";
$stmt_ngo = $conn->prepare($ngo_name_sql);
$stmt_ngo->bind_param("i", $ngo_id);
$stmt_ngo->execute();
$ngo_result = $stmt_ngo->get_result();
$ngo_name = $ngo_result->fetch_assoc()['name'];

$food_sql = "SELECT fp.id, r.restaurant_name AS donor_name, fp.food_item as description, fp.quantity, fp.posted_time as created_at, 'food' as donation_type
             FROM food_posts fp
             JOIN restaurants r ON fp.restaurant_id = r.id
             WHERE fp.ngo_name = ?
             ORDER BY fp.posted_time DESC LIMIT 5";
$stmt4 = $conn->prepare($food_sql);
$stmt4->bind_param("s", $ngo_name);
$stmt4->execute();
$food_donations = $stmt4->get_result();

while($row = $food_donations->fetch_assoc()) {
    $recent_donations[] = $row;
}

// Sort all donations by created_at and get top 5
usort($recent_donations, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recent_donations = array_slice($recent_donations, 0, 5);

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
    <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
    <a href="requirements.php"><i class="fas fa-bullhorn"></i> Post Requirement</a>
    <a href="donations.php"><i class="fas fa-gift"></i> Manage Donations</a>
    <a href="volunteers.php"><i class="fas fa-users"></i> Volunteers</a>
    <a href="manage_profile.php"><i class="fas fa-user"></i> Manage Profile</a>
    <a href="../backend/logout.php" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
</div>

<!-- Main Content -->
<div class="main-content">
  <div class="header">
    <h1>Welcome to Your NGO Dashboard</h1>
    <div class="user-info">
      <i class="fas fa-user-circle"></i>
      <span>NGO User</span>
    </div>
  </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      
      <!-- Current Needs -->
      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-bullhorn"></i> Current Needs</h2>
        </div>
        <div style="padding: 24px;">
        <ul class="space-y-3" id="requirements-list">
          <?php if ($requirements->num_rows > 0): ?>
            <?php
            $requirements_data = [];
            while($row = $requirements->fetch_assoc()) {
                $requirements_data[] = $row;
            }
            // Reset pointer to use it again if needed, though not necessary here
            // $requirements->data_seek(0); 
            foreach($requirements_data as $row):
            ?>
              <li class="flex justify-between items-start p-3 bg-light-color rounded-lg border border-gray-light" id="requirement-<?= $row['id'] ?>">
                <div>
                  <i class="fas fa-check mr-3 text-secondary-color"></i>
                  <span><?= htmlspecialchars($row['title']) ?> - <?= htmlspecialchars($row['description']) ?></span>
                </div>
                <button onclick="deleteRequirement(<?= $row['id'] ?>)" class="text-red-500 hover:text-red-700 font-semibold ml-4">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li id="no-requirements-message" class="text-gray-600">No requirements posted yet.</li>
          <?php endif; ?>
        </ul>
        <a href="requirements.php" class="action-btn primary" style="margin-top: 1rem; display: inline-block;">Post a New Requirement</a>
        </div>
      </div>

      <!-- Recent Donations -->
      <div class="card" style="grid-column: span 2;">
        <div class="card-header">
          <h2><i class="fas fa-gift"></i> Top 5 Recent Donations</h2>
        </div>
        <div style="padding: 24px;">
        <ul class="space-y-4">
          <?php if (!empty($recent_donations)): ?>
            <?php foreach($recent_donations as $donation): ?>
              <li class="flex items-start justify-between p-4 bg-light-color rounded-lg border border-gray-light hover:shadow-md transition-shadow">
                <div class="flex-1">
                  <div class="flex items-center mb-2">
                    <?php if($donation['donation_type'] == 'monetary'): ?>
                      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 mr-3">
                        <i class="fas fa-money-bill-wave mr-1"></i> Monetary
                      </span>
                    <?php elseif($donation['donation_type'] == 'goods'): ?>
                      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-3">
                        <i class="fas fa-box mr-1"></i> Goods
                      </span>
                    <?php elseif($donation['donation_type'] == 'food'): ?>
                      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 mr-3">
                        <i class="fas fa-utensils mr-1"></i> Food
                      </span>
                    <?php endif; ?>
                    <span class="text-sm text-gray-500"><?= date('M j, Y g:i A', strtotime($donation['created_at'])) ?></span>
                  </div>
                  
                  <div class="font-semibold text-lg mb-1">
                    <?php if($donation['donation_type'] == 'monetary'): ?>
                      ₹<?= number_format($donation['amount'], 2) ?> from <?= htmlspecialchars($donation['donor_name']) ?>
                    <?php elseif($donation['donation_type'] == 'goods'): ?>
                      <?= htmlspecialchars($donation['description']) ?> from <?= htmlspecialchars($donation['donor_name']) ?>
                    <?php elseif($donation['donation_type'] == 'food'): ?>
                      <?= htmlspecialchars($donation['description']) ?> (<?= htmlspecialchars($donation['quantity']) ?>) from <?= htmlspecialchars($donation['donor_name']) ?>
                    <?php endif; ?>
                  </div>
                  
                  <div class="text-sm text-gray-600">
                    <?php if($donation['donation_type'] == 'monetary'): ?>
                      <i class="fas fa-credit-card mr-1"></i> <?= htmlspecialchars($donation['payment_method']) ?>
                    <?php elseif($donation['donation_type'] == 'goods'): ?>
                      <i class="fas fa-truck mr-1"></i> Status: <span class="capitalize font-medium text-<?= $donation['status'] == 'delivered' ? 'green' : ($donation['status'] == 'accepted' ? 'blue' : 'yellow') ?>-600"><?= htmlspecialchars($donation['status']) ?></span>
                    <?php elseif($donation['donation_type'] == 'food'): ?>
                      <i class="fas fa-utensils mr-1"></i> Food Donation
                    <?php endif; ?>
                  </div>
                </div>
                
                <div class="ml-4">
                  <?php if($donation['donation_type'] == 'monetary'): ?>
                    <i class="fas fa-money-bill-wave text-green-500 text-xl"></i>
                  <?php elseif($donation['donation_type'] == 'goods'): ?>
                    <i class="fas fa-box text-blue-500 text-xl"></i>
                  <?php elseif($donation['donation_type'] == 'food'): ?>
                    <i class="fas fa-utensils text-orange-500 text-xl"></i>
                  <?php endif; ?>
                </div>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="text-center py-8 text-gray-600">
              <i class="fas fa-gift text-4xl text-gray-300 mb-4"></i>
              <p class="text-lg">No donations received yet</p>
              <p class="text-sm">Donations will appear here once donors start contributing</p>
            </li>
          <?php endif; ?>
        </ul>
        
        <?php if (!empty($recent_donations)): ?>
        <div style="margin-top: 1.5rem; text-align: center;">
          <a href="donations.php" class="action-btn primary">
            <i class="fas fa-eye mr-2"></i> View All Donations
          </a>
        </div>
        <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- NGO Search -->
    <div class="card" style="margin-top: 2rem;">
      <div class="card-header">
        <h2><i class="fas fa-search"></i> Find Other NGOs</h2>
      </div>
      <div style="padding: 24px;">
        <form method="GET" style="display: flex; gap: 12px; margin-bottom: 20px;">
          <input type="text" name="q" style="flex: 1; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95em;" placeholder="Search by name, cause, or location...">
          <button type="submit" class="action-btn primary">Search</button>
        </form>

        <div id="results-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <?php if (!empty($search_results) && $search_results->num_rows > 0): ?>
            <?php while($ngo = $search_results->fetch_assoc()): ?>
              <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-light">
                <h3 class="text-xl font-bold"><?= htmlspecialchars($ngo['name']) ?></h3>
                <p class="text-gray-600"><?= htmlspecialchars($ngo['ngo_type']) ?></p>
                <p class="text-sm text-gray-500"><?= htmlspecialchars($ngo['district']) ?>, <?= htmlspecialchars($ngo['state']) ?></p>
              </div>
            <?php endwhile; ?>
          <?php elseif (isset($_GET['q'])): ?>
            <p class="text-gray-600">No NGOs found.</p>
          <?php endif; ?>
        </div>
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

async function deleteRequirement(id) {
  if (!confirm("Are you sure you want to delete this requirement?")) {
    return;
  }

  try {
    const response = await fetch('../backend/delete_requirement.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ id: id })
    });

    if (response.ok) {
      const result = await response.json();
      if (result.success) {
        const requirementElement = document.getElementById('requirement-' + id);
        if (requirementElement) {
          requirementElement.remove();
        }

        const requirementsList = document.getElementById('requirements-list');
        if (requirementsList.children.length === 0) {
           const noReqMessage = document.createElement('li');
           noReqMessage.id = 'no-requirements-message';
           noReqMessage.textContent = 'No requirements posted yet.';
           requirementsList.appendChild(noReqMessage);
        }
      } else {
        alert('Error: ' + (result.error || 'Could not delete the requirement.'));
      }
    } else {
      alert('An error occurred while trying to delete the requirement.');
    }
  } catch (error) {
    console.error('Deletion error:', error);
    alert('A network error occurred. Please try again.');
  }
}
</script>

<!-- Session Manager -->
<script src="../../js/session_manager.js"></script>
<script src="../../js/dynamic_updates.js"></script>
<script>
    // Create session for NGO
    sessionManager.createSession('ngo', <?= $ngo_id ?>, 'NGO User');
</script>

</body>
</html>
