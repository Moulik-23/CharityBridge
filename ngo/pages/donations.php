<?php
session_start();

// 🚨 Redirect to login if NGO is not logged in
if (!isset($_SESSION['ngo_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// ✅ Database connection
$servername = "127.0.0.1";
$username   = "root";
$password   = "";
$dbname     = "charitybridge";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$ngo_id = $_SESSION['ngo_id'];

// Fetch all goods donations with donor details
$sql = "SELECT gd.goods_donation_id, dn.name AS donor_name, dn.email AS donor_email, 
               gd.phone_number, gd.address, gd.item_type, gd.item_description, 
               gd.pickup_code, gd.status, gd.volunteer_id, gd.volunteer_name, gd.created_at
        FROM goods_donations gd
        JOIN donors dn ON gd.donor_id = dn.donor_id
        WHERE gd.ngo_id = ?
        ORDER BY gd.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$result = $stmt->get_result();
$goods_donations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all approved volunteers for this NGO
$volunteers = [];
$sql_volunteers = "SELECT v.volunteer_id, v.name FROM volunteers v
                   JOIN volunteer_ngo_map vnm ON v.volunteer_id = vnm.volunteer_id
                   WHERE vnm.ngo_id = ? AND vnm.status = 'Approved'";
$stmt_volunteers = $conn->prepare($sql_volunteers);
$stmt_volunteers->bind_param("i", $ngo_id);
$stmt_volunteers->execute();
$result_volunteers = $stmt_volunteers->get_result();
while ($row = $result_volunteers->fetch_assoc()) {
    $volunteers[] = $row;
}
$stmt_volunteers->close();

// Fetch all food donations from restaurants
$food_donations = [];
$sql_food = "SELECT fp.id AS food_post_id, r.restaurant_name, r.address AS restaurant_address, r.phone AS restaurant_phone,
                    fp.food_item, fp.quantity, fp.unit, fp.posted_time, fp.status, fp.image_path,
                    fp.ngo_name, fp.volunteer_id, fp.volunteer_name
             FROM food_posts fp
             JOIN restaurants r ON fp.restaurant_id = r.id
             WHERE fp.status = 'Waiting' 
                OR (fp.status IN ('Accepted', 'Picked Up', 'Delivered') AND fp.ngo_name = (SELECT name FROM ngos WHERE id = ?))
             ORDER BY fp.posted_time DESC"; // Show 'Waiting', 'Accepted', 'Picked Up', or 'Delivered' for this NGO

$stmt_food = $conn->prepare($sql_food);
if (!$stmt_food) {
    die("SQL Error: " . $conn->error);
}
$stmt_food->bind_param("i", $ngo_id);
$stmt_food->execute();
$result_food = $stmt_food->get_result();
$food_donations = $result_food->fetch_all(MYSQLI_ASSOC);
$stmt_food->close();


$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Donations - CharityBridge</title>
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
    <a href="donations.php" class="active"><i class="fas fa-gift"></i> Manage Donations</a>
    <a href="volunteers.php"><i class="fas fa-users"></i> Volunteers</a>
    <a href="manage_profile.php"><i class="fas fa-user"></i> Manage Profile</a>
    <a href="../backend/logout.php" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
</div>

<!-- Main Content -->
<div class="main-content">
  <div class="header">
    <h1>Manage Donations</h1>
    <div class="user-info">
      <i class="fas fa-user-circle"></i>
      <span>NGO User</span>
    </div>
  </div>

    <!-- Donation Type Selection -->
    <div class="bg-white shadow-lg rounded-xl p-6 mb-8 border border-gray-light">
        <h1 class="text-3xl font-bold text-primary-color mb-6">Manage Donations</h1>
        <p class="text-gray-600 mb-6">Choose the type of donations you want to manage.</p>
        
        <div class="flex flex-col sm:flex-row gap-4">
            <button id="goodsTab" onclick="showGoodsDonations()" class="btn btn-primary flex-1 text-center py-4 px-6 text-lg">
                <i class="fas fa-box mr-3"></i>
                Goods Donations
            </button>
            <button id="restaurantTab" onclick="showRestaurantDonations()" class="btn bg-gray-100 text-gray-700 hover:bg-gray-200 flex-1 text-center py-4 px-6 text-lg">
                <i class="fas fa-utensils mr-3"></i>
                Restaurant Donations
            </button>
        </div>
    </div>

    <!-- Goods Donations Section -->
    <div id="goodsDonationsSection" class="donation-section">
        <div class="bg-white shadow-lg rounded-xl p-6 mb-8 border border-gray-light">
            <h2 class="text-2xl font-bold text-primary-color flex items-center">
                <i class="fas fa-box mr-3 text-secondary-color"></i>
                Goods/Clothes Donations
            </h2>
            <p class="text-gray-600 mt-2">Manage goods and clothes donations received by your NGO.</p>
        </div>

    <div class="overflow-x-auto bg-white rounded-xl shadow-lg border border-gray-light">
        <table class="min-w-full text-left table-auto">
            <thead>
                <tr class="bg-primary-color text-white">
                    <th class="py-3 px-4 border-b-2 border-gray-200">Donor Name</th>
                    <th class="py-3 px-4 border-b-2 border-gray-200">Contact</th>
                    <th class="py-3 px-4 border-b-2 border-gray-200">Address</th>
                    <th class="py-3 px-4 border-b-2 border-gray-200">Item</th>
                    <th class="py-3 px-4 border-b-2 border-gray-200">Pickup Code</th>
                    <th class="py-3 px-4 border-b-2 border-gray-200">Status</th>
                    <th class="py-3 px-4 border-b-2 border-gray-200">Volunteer</th>
                    <th class="py-3 px-4 border-b-2 border-gray-200">Date</th>
                    <th class="py-3 px-4 border-b-2 border-gray-200">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($goods_donations) > 0): ?>
                    <?php foreach ($goods_donations as $donation): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-2 px-4"><?= htmlspecialchars($donation['donor_name']) ?></td>
                            <td class="py-2 px-4">
                                <?= htmlspecialchars($donation['donor_email']) ?><br>
                                <?= htmlspecialchars($donation['phone_number']) ?>
                            </td>
                            <td class="py-2 px-4"><?= htmlspecialchars($donation['address']) ?></td>
                            <td class="py-2 px-4">
                                <span class="font-semibold"><?= htmlspecialchars(ucfirst($donation['item_type'])) ?>:</span> 
                                <?= htmlspecialchars($donation['item_description']) ?>
                            </td>
                            <td class="py-2 px-4 font-bold text-primary-color"><?= htmlspecialchars($donation['pickup_code']) ?></td>
                            <td class="py-2 px-4">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                    <?php 
                                        if ($donation['status'] == 'pending') echo 'bg-yellow-100 text-yellow-800';
                                        else if ($donation['status'] == 'accepted') echo 'bg-green-100 text-green-800';
                                        else if ($donation['status'] == 'rejected') echo 'bg-red-100 text-red-800';
                                        else if ($donation['status'] == 'picked_up') echo 'bg-blue-100 text-blue-800';
                                        else if ($donation['status'] == 'delivered') echo 'bg-purple-100 text-purple-800';
                                    ?>">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $donation['status']))) ?>
                                </span>
                            </td>
                            <td class="py-2 px-4">
                                <?php if ($donation['volunteer_name']): ?>
                                    <?= htmlspecialchars($donation['volunteer_name']) ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4"><?= date("d M Y", strtotime($donation['created_at'])) ?></td>
                            <td class="py-2 px-4">
                                <?php if ($donation['status'] == 'pending'): ?>
                                    <div class="flex gap-2">
                                        <button type="button" onclick="processGoodsDonation(<?= $donation['goods_donation_id'] ?>, 'accept', this)" class="btn btn-success text-sm px-3 py-2">Accept</button>
                                        <button type="button" onclick="processGoodsDonation(<?= $donation['goods_donation_id'] ?>, 'reject', this)" class="btn btn-danger text-sm px-3 py-2">Reject</button>
                                    </div>
                                <?php elseif ($donation['status'] == 'accepted' && !$donation['volunteer_id']): ?>
                                    <button class="btn btn-primary text-sm px-3 py-2 assign-volunteer-btn" data-donation-id="<?= $donation['goods_donation_id'] ?>">Assign Volunteer</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-gray-500">No goods/clothes donations found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Assign Volunteer Modal for Goods Donations -->
    <div id="assignGoodsVolunteerModal" class="modal hidden">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2 class="text-xl font-bold mb-4">Assign Volunteer to Goods Donation</h2>
            <form id="assignGoodsVolunteerForm">
                <input type="hidden" name="goods_donation_id" id="modalGoodsDonationId">
                <input type="hidden" name="action" value="assign_volunteer">
                <div class="mb-4">
                    <label for="goods_volunteer_id" class="block text-gray-700 text-sm font-bold mb-2">Select Volunteer:</label>
                    <select id="goods_volunteer_id" name="volunteer_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($volunteers as $volunteer): ?>
                            <option value="<?= $volunteer['volunteer_id'] ?>"><?= htmlspecialchars($volunteer['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Assign</button>
            </form>
        </div>
    </div>
    </div>

    <!-- Restaurant Donations Section -->
    <div id="restaurantDonationsSection" class="donation-section hidden">
        <div class="bg-white shadow-lg rounded-xl p-6 mb-8 border border-gray-light">
            <h2 class="text-2xl font-bold text-primary-color flex items-center">
                <i class="fas fa-utensils mr-3 text-accent-color"></i>
                Food Donations from Restaurants
            </h2>
            <p class="text-gray-600 mt-2">Manage food donations posted by restaurants.</p>
        </div>

    <div class="overflow-x-auto bg-white rounded-xl shadow-lg border border-gray-light mb-8">
        <table class="min-w-full text-left table-auto">
            <thead>
                <tr class="bg-primary-color text-white">
                    <th class="py-3 px-4 border-b-2 border-gray-200">Restaurant Name</th>
                    <th class="py-3 px-4 border-b-2 border-gray-200">Contact</th>
                    <th class="py-3 px-4 border-b-2 border-gray-200">Address</th>
                    <th class="py-3 px-4 border-b-2 border-gray-200">Food Item</th>
                    <th class="py-3 px-4 border-b-2 border-gray-200">Quantity</th>
                    <th class="py-3 px-4 border-b-2 border-gray-200">Status</th>
                    <th class="py-3 px-4 border-b-2 border-gray-200">Volunteer</th>
                    <th class="py-3 px-4 border-b-2 border-gray-200">Posted Time</th>
                    <th class="py-3 px-4 border-b-2 border-gray-200">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($food_donations) > 0): ?>
                    <?php foreach ($food_donations as $food_donation): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-2 px-4"><?= htmlspecialchars($food_donation['restaurant_name']) ?></td>
                            <td class="py-2 px-4">
                                <?= htmlspecialchars($food_donation['restaurant_phone']) ?><br>
                            </td>
                            <td class="py-2 px-4"><?= htmlspecialchars($food_donation['restaurant_address']) ?></td>
                            <td class="py-2 px-4">
                                <span class="font-semibold"><?= htmlspecialchars($food_donation['food_item']) ?></span>
                                <?php if ($food_donation['image_path']): ?>
                                    <br><a href="../../restaurant/pge/uploads/<?= htmlspecialchars($food_donation['image_path']) ?>" target="_blank" class="text-primary-color hover:underline text-sm">View Image</a>
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4"><?= htmlspecialchars($food_donation['quantity']) ?> <?= htmlspecialchars($food_donation['unit']) ?></td>
                            <td class="py-2 px-4">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                    <?php 
                                        if ($food_donation['status'] == 'Waiting') echo 'bg-yellow-100 text-yellow-800';
                                        else if ($food_donation['status'] == 'Accepted') echo 'bg-green-100 text-green-800';
                                        else if ($food_donation['status'] == 'Rejected') echo 'bg-red-100 text-red-800';
                                        else if ($food_donation['status'] == 'Picked Up') echo 'bg-blue-100 text-blue-800';
                                        else if ($food_donation['status'] == 'Delivered') echo 'bg-purple-100 text-purple-800';
                                    ?>">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $food_donation['status']))) ?>
                                </span>
                            </td>
                            <td class="py-2 px-4">
                                <?php if ($food_donation['volunteer_name']): ?>
                                    <?= htmlspecialchars($food_donation['volunteer_name']) ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4"><?= date("d M Y H:i", strtotime($food_donation['posted_time'])) ?></td>
                                <td class="py-2 px-4">
                                    <?php if ($food_donation['status'] == 'Waiting'): ?>
                                        <div class="flex gap-2">
                                            <button onclick="acceptFoodDonation(<?= $food_donation['food_post_id'] ?>, this)" class="btn btn-success text-sm px-3 py-2">Accept</button>
                                            <button onclick="rejectFoodDonation(<?= $food_donation['food_post_id'] ?>, this)" class="btn btn-danger text-sm px-3 py-2">Reject</button>
                                        </div>
                                    <?php elseif ($food_donation['status'] == 'Accepted' && !$food_donation['volunteer_id']): ?>
                                        <button class="btn btn-primary text-sm px-3 py-2 assign-food-volunteer-btn" data-food-post-id="<?= $food_donation['food_post_id'] ?>">Assign Volunteer</button>
                                    <?php endif; ?>
                                </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-gray-500">No food donations found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Assign Volunteer Modal for Food Donations -->
    <div id="assignFoodVolunteerModal" class="modal hidden">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2 class="text-xl font-bold mb-4">Assign Volunteer to Food Donation</h2>
            <form id="assignFoodVolunteerForm">
                <input type="hidden" name="food_post_id" id="modalFoodDonationId">
                <input type="hidden" name="action" value="assign_food_volunteer">
                <div class="mb-4">
                    <label for="food_volunteer_id" class="block text-gray-700 text-sm font-bold mb-2">Select Volunteer:</label>
                    <select id="food_volunteer_id" name="volunteer_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($volunteers as $volunteer): ?>
                            <option value="<?= $volunteer['volunteer_id'] ?>"><?= htmlspecialchars($volunteer['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Assign</button>
            </form>
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

        // Tab switching functions
        function showGoodsDonations() {
            // Hide restaurant section
            document.getElementById('restaurantDonationsSection').classList.add('hidden');
            // Show goods section
            document.getElementById('goodsDonationsSection').classList.remove('hidden');
            
            // Update button styles
            document.getElementById('goodsTab').className = 'btn btn-primary flex-1 text-center py-4 px-6 text-lg';
            document.getElementById('restaurantTab').className = 'btn bg-gray-100 text-gray-700 hover:bg-gray-200 flex-1 text-center py-4 px-6 text-lg';
        }

        function showRestaurantDonations() {
            // Hide goods section
            document.getElementById('goodsDonationsSection').classList.add('hidden');
            // Show restaurant section
            document.getElementById('restaurantDonationsSection').classList.remove('hidden');
            
            // Update button styles
            document.getElementById('restaurantTab').className = 'btn btn-primary flex-1 text-center py-4 px-6 text-lg';
            document.getElementById('goodsTab').className = 'btn bg-gray-100 text-gray-700 hover:bg-gray-200 flex-1 text-center py-4 px-6 text-lg';
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize with goods donations shown by default
            showGoodsDonations();
            // Goods Donation Modal Logic
            const assignGoodsVolunteerModal = document.getElementById('assignGoodsVolunteerModal');
            const closeGoodsButton = assignGoodsVolunteerModal.querySelector('.close-button');
            const assignGoodsVolunteerButtons = document.querySelectorAll('.assign-volunteer-btn');
            const modalGoodsDonationId = document.getElementById('modalGoodsDonationId');

            assignGoodsVolunteerButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const donationId = this.dataset.donationId;
                    modalGoodsDonationId.value = donationId;
                    assignGoodsVolunteerModal.classList.remove('hidden');
                });
            });

            closeGoodsButton.addEventListener('click', function() {
                assignGoodsVolunteerModal.classList.add('hidden');
            });

            window.addEventListener('click', function(event) {
                if (event.target == assignGoodsVolunteerModal) {
                    assignGoodsVolunteerModal.classList.add('hidden');
                }
            });

            // Food Donation Modal Logic
            const assignFoodVolunteerModal = document.getElementById('assignFoodVolunteerModal');
            const closeFoodButton = assignFoodVolunteerModal.querySelector('.close-button');
            const assignFoodVolunteerButtons = document.querySelectorAll('.assign-food-volunteer-btn');
            const modalFoodDonationId = document.getElementById('modalFoodDonationId');

            assignFoodVolunteerButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const foodPostId = this.dataset.foodPostId;
                    modalFoodDonationId.value = foodPostId;
                    assignFoodVolunteerModal.classList.remove('hidden');
                });
            });

            closeFoodButton.addEventListener('click', function() {
                assignFoodVolunteerModal.classList.add('hidden');
            });

            window.addEventListener('click', function(event) {
                if (event.target == assignFoodVolunteerModal) {
                    assignFoodVolunteerModal.classList.add('hidden');
                }
            });

            // Handle Goods Donation Action (Accept/Reject/Assign)
            document.getElementById('assignGoodsVolunteerForm').addEventListener('submit', function(event) {
                event.preventDefault();
                const form = event.target;
                const formData = new FormData(form);
                
                fetch('../backend/process_goods_donation_action.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred.');
                });
            });

            // Handle Food Donation Assign Volunteer Action
            document.getElementById('assignFoodVolunteerForm').addEventListener('submit', function(event) {
                event.preventDefault();
                const form = event.target;
                const formData = new FormData(form);
                
                fetch('../backend/process_food_donation_action.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred.');
                });
            });
        });

        function processGoodsDonation(goodsDonationId, action, buttonElement) {
            fetch('../backend/process_goods_donation_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `goods_donation_id=${goodsDonationId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
            });
        }
    </script>
    <script src="../../js/ngo_donations.js"></script>
</body>
</html>
