<?php
session_start();

if (!isset($_SESSION['ngo_id'])) {
	header("Location: ../auth/login.php");
	exit();
}

$servername = '127.0.0.1';
$username = 'root';
$password = '';
$dbname = 'charitybridge';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}

$ngo_id = $_SESSION['ngo_id'];

$stmt = $conn->prepare("SELECT name, email, phone, address FROM ngos WHERE id = ?");
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$ngo = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Profile - CharityBridge</title>
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
    <a href="volunteers.php"><i class="fas fa-users"></i> Volunteers</a>
    <a href="manage_profile.php" class="active"><i class="fas fa-user"></i> Manage Profile</a>
    <a href="../backend/logout.php" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
</div>

<!-- Main Content -->
<div class="main-content">
  <div class="header">
    <h1>Manage Your Profile</h1>
    <div class="user-info">
      <i class="fas fa-user-circle"></i>
      <span>NGO User</span>
    </div>
  </div>

    <div class="card" style="max-width: 900px; margin: 0 auto;">
      <div class="card-header">
        <h2><i class="fas fa-user-edit"></i> Update Profile Information</h2>
      </div>
      <div style="padding: 24px;">
      <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        <div class="alert alert-success mb-6">
          <i class="fas fa-check-circle mr-2"></i>
          Profile updated successfully!
        </div>
      <?php endif; ?>
      
      <?php if (isset($_GET['error']) && $_GET['error'] == '1'): ?>
        <div class="alert alert-error mb-6">
          <i class="fas fa-exclamation-triangle mr-2"></i>
          Error updating profile. Please try again.
        </div>
      <?php endif; ?>
      
      <form action="../backend/update_profile.php" method="POST" enctype="multipart/form-data" style="display: grid; gap: 1.5rem;">
        <div>
          <label for="name" style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #374151;">
            <i class="fas fa-building" style="margin-right: 0.5rem; color: #0F62FE;"></i>NGO Name
          </label>
          <input type="text" id="name" name="name" required value="<?= htmlspecialchars($ngo['name'] ?? '') ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.95rem;">
        </div>
        
        <div>
          <label for="email" style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #374151;">
            <i class="fas fa-envelope" style="margin-right: 0.5rem; color: #0F62FE;"></i>Email
          </label>
          <input type="email" id="email" name="email" required value="<?= htmlspecialchars($ngo['email'] ?? '') ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.95rem;">
        </div>
        
        <div>
          <label for="phone" style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #374151;">
            <i class="fas fa-phone" style="margin-right: 0.5rem; color: #0F62FE;"></i>Phone
          </label>
          <input type="text" id="phone" name="phone" required value="<?= htmlspecialchars($ngo['phone'] ?? '') ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.95rem;">
        </div>
        
        <div>
          <label for="address" style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #374151;">
            <i class="fas fa-map-marker-alt" style="margin-right: 0.5rem; color: #0F62FE;"></i>Address
          </label>
          <textarea id="address" name="address" rows="4" required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.95rem; resize: vertical;"><?= htmlspecialchars($ngo['address'] ?? '') ?></textarea>
        </div>
        
        <button type="submit" class="action-btn primary" style="width: 100%; margin-top: 1rem; padding: 0.875rem;">
          <i class="fas fa-save" style="margin-right: 0.5rem;"></i>Update Profile
        </button>
      </form>
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
</script>
</body>
</html>


