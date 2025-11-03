<?php
session_start();

// Check if donor is logged in
if (!isset($_SESSION['donor_id'])) {
    header("Location: login.html");
    exit();
}

// DB connection
$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    die("❌ DB Connection failed: " . $conn->connect_error);
}

$donor_id = $_SESSION['donor_id'];
$donor_name = $_SESSION['donor_name'];

// Fetch donor's recent donations
$donations_history = [];
$stmt = $conn->prepare("SELECT d.amount, d.created_at as donation_date, n.name as ngo_name 
                        FROM donations d 
                        JOIN ngos n ON d.ngo_id = n.id 
                        WHERE d.donor_id = ? 
                        ORDER BY d.created_at DESC LIMIT 5");
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $donations_history[] = $row;
}
$stmt->close();

// Fetch totals
$total_donations_amount = 0;
$ngos_supported_count = 0;

$stmt_total_amount = $conn->prepare("SELECT SUM(amount) as total_amount FROM donations WHERE donor_id = ?");
$stmt_total_amount->bind_param("i", $donor_id);
$stmt_total_amount->execute();
$result_total_amount = $stmt_total_amount->get_result();
if ($row = $result_total_amount->fetch_assoc()) {
    $total_donations_amount = $row['total_amount'] ?? 0;
}
$stmt_total_amount->close();

$stmt_ngos_supported = $conn->prepare(
    "SELECT COUNT(DISTINCT ngo_id) as ngos_count 
     FROM (
         SELECT ngo_id FROM donations WHERE donor_id = ?
         UNION
         SELECT ngo_id FROM goods_donations WHERE donor_id = ?
     ) as combined_donations"
);
$stmt_ngos_supported->bind_param("ii", $donor_id, $donor_id);
$stmt_ngos_supported->execute();
$result_ngos_supported = $stmt_ngos_supported->get_result();
if ($row = $result_ngos_supported->fetch_assoc()) {
    $ngos_supported_count = $row['ngos_count'] ?? 0;
}
$stmt_ngos_supported->close();

// Fetch all approved NGOs
$all_ngos = [];
$stmt_all_ngos = $conn->prepare("SELECT id, name, email, ngo_type, state, district FROM ngos WHERE status = 'approved'");
$stmt_all_ngos->execute();
$result_all_ngos = $stmt_all_ngos->get_result();
while ($row = $result_all_ngos->fetch_assoc()) {
    $all_ngos[] = $row;
}
$stmt_all_ngos->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard - CharityBridge</title>
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
        <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="donate.php"><i class="fas fa-hand-holding-heart"></i> Donate Goods</a>
        <a href="tracking.php"><i class="fas fa-route"></i> Track Donations</a>
        <a href="manage_profile.php"><i class="fas fa-user"></i> Manage Profile</a>
        <a href="backend/donor_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <h1>Welcome, <?php echo htmlspecialchars($donor_name); ?>!</h1>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($donor_name); ?></span>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #e8effe;">
                <i class="fas fa-hand-holding-usd" style="color: #5b7ac7;"></i>
            </div>
            <div class="stat-content">
                <h3>₹<?php echo htmlspecialchars($total_donations_amount); ?></h3>
                <p>Total Donations</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #e6f7ed;">
                <i class="fas fa-hands-helping" style="color: #5ba573;"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo htmlspecialchars($ngos_supported_count); ?></h3>
                <p>NGOs Supported</p>
            </div>
        </div>
    </div>

    <!-- NGO Search Section -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-search"></i> Find an NGO to Support</h2>
        </div>
        <div style="padding: 24px;">
            <div style="display: flex; gap: 12px; margin-bottom: 20px;">
                <input type="text" id="searchInput" style="flex: 1; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95em;" placeholder="Search by name, cause, or location...">
                <button id="searchBtn" class="action-btn primary">Search</button>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>NGO Name</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($all_ngos) > 0): ?>
                            <?php foreach ($all_ngos as $ngo): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ngo['name']); ?></td>
                                    <td><?php echo htmlspecialchars($ngo['ngo_type']); ?></td>
                                    <td><?php echo htmlspecialchars($ngo['state'] . ', ' . $ngo['district']); ?></td>
                                    <td>
                                        <a href="donate_form.php?ngo_id=<?php echo htmlspecialchars($ngo['id']); ?>" class="action-btn primary" style="padding: 6px 16px; font-size: 0.85em;">Donate</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #6b7280; padding: 20px;">No NGOs found to display.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Donation History -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-history"></i> Recent Donation History</h2>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>NGO</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($donations_history) > 0): ?>
                        <?php foreach ($donations_history as $donation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($donation['ngo_name']); ?></td>
                                <td style="font-weight: 600;">₹<?php echo htmlspecialchars($donation['amount']); ?></td>
                                <td><?php echo htmlspecialchars(date("F j, Y", strtotime($donation['donation_date']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: #6b7280; padding: 20px;">No donation history found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const ngoTableBody = document.querySelector('.table-wrapper table tbody');
    const ngoTableRows = ngoTableBody.querySelectorAll('tr');

    searchBtn.addEventListener('click', filterNgos);
    searchInput.addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            filterNgos();
        }
    });

    function filterNgos() {
        const searchTerm = searchInput.value.toLowerCase();
        ngoTableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
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
