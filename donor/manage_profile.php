<?php
session_start();

if (!isset($_SESSION['donor_id'])) {
    header("Location: login.html");
    exit();
}

$donor_id = $_SESSION['donor_id'];
$donor_name = $_SESSION['donor_name'];

$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$message_type = "";

// Fetch donor details first
$sql_donor = "SELECT name, email, phone, address FROM donors WHERE donor_id = ?";
$stmt = $conn->prepare($sql_donor);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$donor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $sql_update = "UPDATE donors SET name = ?, email = ?, phone = ?, address = ? WHERE donor_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    if (!$stmt_update) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt_update->bind_param("ssssi", $name, $email, $phone, $address, $donor_id);

    if ($stmt_update->execute()) {
        $_SESSION['donor_name'] = $name; // Update session name if changed
        $donor_name = $name; // Update local variable
        $message = "Profile updated successfully!";
        $message_type = "success";
        // Refresh donor data
        $donor['name'] = $name;
        $donor['email'] = $email;
        $donor['phone'] = $phone;
        $donor['address'] = $address;
    } else {
        $message = "Error updating profile: " . $conn->error;
        $message_type = "error";
    }
    $stmt_update->close();
}

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
  <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

<!-- Mobile Header with Hamburger -->
<div class="mobile-header">
    <div class="mobile-logo">
        <i class="fas fa-heart"></i>
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
        <i class="fas fa-heart"></i>
        <span>CharityBridge</span>
    </div>
    <nav class="nav">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="donate.php"><i class="fas fa-hand-holding-heart"></i> Donate Goods</a>
        <a href="tracking.php"><i class="fas fa-route"></i> Track Donations</a>
        <a href="manage_profile.php" class="active"><i class="fas fa-user"></i> Manage Profile</a>
        <a href="backend/donor_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <h1>Manage Your Profile</h1>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($donor_name); ?></span>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: 2rem; padding: 1rem; border-radius: 8px; background: <?php echo $message_type == 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $message_type == 'success' ? '#155724' : '#721c24'; ?>; border: 1px solid <?php echo $message_type == 'success' ? '#c3e6cb' : '#f5c6cb'; ?>;">
            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-user-edit"></i> Update Profile Information</h2>
        </div>
        <div style="padding: 24px;">
            <form action="manage_profile.php" method="POST">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($donor['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($donor['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($donor['phone'] ?? '') ?>" maxlength="10" pattern="[0-9]{10}" placeholder="10-digit phone number">
                </div>
                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea id="address" name="address" rows="3"><?= htmlspecialchars($donor['address'] ?? '') ?></textarea>
                </div>
                <div class="flex items-center justify-end mt-6 space-x-4">
                    <a href="dashboard.php" class="action-btn secondary">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                    <button type="submit" class="action-btn primary">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                </div>
            </form>
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
    link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
            toggleMenu();
        }
    });
});
</script>

<!-- Session Manager -->
<script src="../js/session_manager.js"></script>
<script src="../js/dynamic_updates.js"></script>
<script>
    // Create session for donor
    sessionManager.createSession('donor', <?= $donor_id ?>, '<?= htmlspecialchars($donor_name) ?>');
</script>

</body>
</html>
