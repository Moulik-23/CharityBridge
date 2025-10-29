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

// Fetch all donations for the logged-in donor
$goods_donations = [];
$stmt = $conn->prepare("SELECT gd.goods_donation_id as id, gd.created_at as donation_date, gd.item_type, gd.item_description, gd.pickup_code, gd.status, n.name as ngo_name, gd.volunteer_name FROM goods_donations gd JOIN ngos n ON gd.ngo_id = n.id WHERE gd.donor_id = ? ORDER BY gd.created_at DESC");
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $goods_donations[] = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Donations - CharityBridge</title>
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
        <a href="tracking.php" class="active"><i class="fas fa-route"></i> Track Donations</a>
        <a href="manage_profile.php"><i class="fas fa-user"></i> Manage Profile</a>
        <a href="backend/donor_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <h1>Track Your Donations</h1>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($donor_name); ?></span>
        </div>
    </div>

    <main style="padding: 0;">
        <div class="container">
            <h1>Track Your Donations</h1>
            <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-light">
                <div class="overflow-x-auto">
                    <table class="w-full text-left table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Date</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Item Type</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Description</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Pickup Code</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Status</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">NGO</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Volunteer</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($goods_donations) > 0): ?>
                                <?php foreach ($goods_donations as $donation): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-3 px-4"><?php echo htmlspecialchars(date("Y-m-d", strtotime($donation['donation_date']))); ?></td>
                                        <td class="py-3 px-4"><?php echo htmlspecialchars($donation['item_type']); ?></td>
                                        <td class="py-3 px-4"><?php echo htmlspecialchars($donation['item_description']); ?></td>
                                        <td class="py-3 px-4 font-bold text-primary-color"><?php echo htmlspecialchars($donation['pickup_code']); ?></td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                                <?php 
                                                    if ($donation['status'] == 'pending') echo 'bg-yellow-100 text-yellow-800';
                                                    else if ($donation['status'] == 'accepted') echo 'bg-green-100 text-green-800';
                                                    else if ($donation['status'] == 'rejected') echo 'bg-red-100 text-red-800';
                                                    else if ($donation['status'] == 'picked_up') echo 'bg-blue-100 text-blue-800';
                                                    else if ($donation['status'] == 'delivered') echo 'bg-purple-100 text-purple-800';
                                                ?>">
                                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $donation['status']))); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4"><?php echo htmlspecialchars($donation['ngo_name']); ?></td>
                                        <td class="py-3 px-4"><?php echo htmlspecialchars($donation['volunteer_name'] ?? 'N/A'); ?></td>
                                        <td class="py-3 px-4 text-center">
                                            <?php if ($donation['status'] == 'pending'): ?>
                                                <button onclick="removeDonation(<?php echo $donation['id']; ?>)" 
                                                    class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded text-sm transition duration-300">
                                                    <i class="fas fa-trash"></i> Remove
                                                </button>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-xs">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="py-3 px-4 text-center text-gray-600">No goods/clothes donations found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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

// Remove donation function
function removeDonation(donationId) {
    if (confirm('Are you sure you want to remove this donation?')) {
        fetch('backend/remove_donation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ donation_id: donationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Donation removed successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while removing the donation.');
        });
    }
}
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
