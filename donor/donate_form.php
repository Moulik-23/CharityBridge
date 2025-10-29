<?php
session_start();

if (!isset($_SESSION['donor_id'])) {
    header("Location: login.html");
    exit();
}

$ngo_id = isset($_GET['ngo_id']) ? intval($_GET['ngo_id']) : 0;

if ($ngo_id === 0) {
    // Redirect if no NGO ID is provided
    header("Location: donate.php");
    exit();
}

// Fetch NGO details
$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    die("❌ DB Connection failed: " . $conn->connect_error);
}

$stmt_ngo = $conn->prepare("SELECT name FROM ngos WHERE id = ?");
$stmt_ngo->bind_param("i", $ngo_id);
$stmt_ngo->execute();
$result_ngo = $stmt_ngo->get_result();
$ngo_details = $result_ngo->fetch_assoc();
$stmt_ngo->close();
$conn->close();

if (!$ngo_details) {
    // Redirect if NGO not found
    header("Location: donate.php");
    exit();
}

$ngo_name = htmlspecialchars($ngo_details['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate Goods/Clothes - CharityBridge</title>
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
        <a href="donate.php" class="active"><i class="fas fa-hand-holding-heart"></i> Donate Goods</a>
        <a href="tracking.php"><i class="fas fa-route"></i> Track Donations</a>
        <a href="manage_profile.php"><i class="fas fa-user"></i> Manage Profile</a>
        <a href="backend/donor_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <h1>Donate to <?php echo $ngo_name; ?></h1>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo $_SESSION['donor_name']; ?></span>
        </div>
    </div>

    <!-- Main Content -->
    <main style="padding: 0;">
        <div class="container">
            <div class="max-w-3xl mx-auto">
                <div class="mb-4">
                    <a href="donate.php" class="inline-flex items-center text-primary-color hover:text-secondary-color font-semibold">
                        <i class="fas fa-arrow-left mr-2"></i> Back to NGO List
                    </a>
                </div>
                <div class="text-center mb-6">
                    <h2 class="text-xl font-bold text-primary-color mb-2">Goods & Clothes Donation</h2>
                    <p class="text-sm text-gray-600">Fill out the form below to schedule a pickup</p>
                </div>

                <div id="messageDisplay" class="mb-4 p-3 rounded-md text-center hidden"></div>
                
                <form id="donationForm" class="compact-form">
                    <input type="hidden" name="ngo_id" value="<?php echo $ngo_id; ?>">
                    
                    <!-- Contact Information Section -->
                    <div class="form-section">
                        <h3>Contact Information</h3>
                        <div class="form-group">
                            <label for="phone_number">Phone Number</label>
                            <input type="tel" id="phone_number" name="phone_number" placeholder="Enter 10-digit phone number" pattern="[0-9]{10}" maxlength="10" required>
                            <small class="text-gray-600">Enter exactly 10 digits</small>
                        </div>
                    </div>

                    <!-- Pickup Details Section -->
                    <div class="form-section">
                        <h3>Pickup Details</h3>
                        <div class="form-group">
                            <label for="address">Pickup Address</label>
                            <textarea id="address" name="address" rows="4" placeholder="Enter complete address for pickup" required></textarea>
                        </div>
                    </div>

                    <!-- Donation Details Section -->
                    <div class="form-section">
                        <h3>Donation Details</h3>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="item_type">Item Type</label>
                                <select id="item_type" name="item_type" required>
                                    <option value="">Select Item Type</option>
                                    <option value="clothes">Clothes</option>
                                    <option value="goods">Goods</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="item_description">Item Description</label>
                            <textarea id="item_description" name="item_description" rows="4" placeholder="Describe your donation (e.g., '5 shirts, 3 trousers', 'old books', 'kitchen utensils')" required></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-full text-lg py-4">
                        <i class="fas fa-heart mr-2"></i>
                        Submit Donation Request
                    </button>
                </form>
            </div>
        </div>
    </main>

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

<!-- Session Manager -->
<script src="../js/session_manager.js"></script>
<script src="../js/dynamic_updates.js"></script>
<script src="../js/donor_donate_form.js"></script>
<script>
    // Create session for donor
    sessionManager.createSession('donor', <?= $_SESSION['donor_id'] ?>, '<?= htmlspecialchars($_SESSION['donor_name']) ?>');
</script>

</body>
</html>
