<?php
session_start();

// Redirect to login if volunteer not logged in
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: ../auth/volunteer_login.php");
    exit();
}

$servername = "127.0.0.1";
$username   = "root";
$password   = "";
$dbname     = "charitybridge";


$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$volunteer_id = $_SESSION['volunteer_id'];

// Fetch goods donations assigned to this volunteer
$sql = "SELECT gd.goods_donation_id, gd.created_at as donation_date, gd.item_type, gd.item_description, 
               gd.address as pickup_location, n.name as ngo_name, gd.pickup_code, gd.status 
        FROM goods_donations gd
        JOIN ngos n ON gd.ngo_id = n.id
        WHERE gd.volunteer_id = ? 
        ORDER BY gd.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}
$stmt->bind_param("i", $volunteer_id);
$stmt->execute();
$result = $stmt->get_result();
$assigned_donations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch food donations assigned to this volunteer
$sql_food = "SELECT fp.id AS food_post_id, fp.posted_time AS donation_date, fp.food_item, fp.quantity, fp.unit,
                    r.address AS pickup_location, r.restaurant_name, fp.status, fp.pickup_code
             FROM food_posts fp
             JOIN restaurants r ON fp.restaurant_id = r.id
             WHERE fp.volunteer_id = ?
             ORDER BY fp.posted_time DESC";

$stmt_food = $conn->prepare($sql_food);
if (!$stmt_food) {
    die("SQL Error: " . $conn->error);
}
$stmt_food->bind_param("i", $volunteer_id);
$stmt_food->execute();
$result_food = $stmt_food->get_result();
$assigned_food_donations = $result_food->fetch_all(MYSQLI_ASSOC);
$stmt_food->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Logistics - CharityBridge</title>
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
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="opportunities.php"><i class="fas fa-bullhorn"></i> Opportunities</a>
        <a href="scheduler.php"><i class="fas fa-calendar"></i> My Schedule</a>
        <a href="logistics.php" class="active"><i class="fas fa-truck"></i> Logistics</a>
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
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="message success mb-8">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="message error mb-8">
                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="mb-6 flex space-x-2">
                <button id="tab-active" class="tab-btn active">Active Deliveries</button>
                <button id="tab-history" class="tab-btn">History</button>
            </div>

            <h1>Assigned Goods/Clothes Pickups</h1>
            <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-light">
                <h2 class="mb-6 flex items-center">
                    <i class="fas fa-box-open mr-3 text-primary-color"></i> Your Assigned Pickups
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Date</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Item Type</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Description</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Pickup Address</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">NGO</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Status</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($assigned_donations) > 0): ?>
                                <?php foreach ($assigned_donations as $donation): ?>
                                    <?php 
                                        $goodsStatus = strtolower($donation['status']);
                                        $isActive = in_array($goodsStatus, ['pending','accepted','picked_up']);
                                        $rowClass = $isActive ? 'row-active' : 'row-history';
                                    ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 <?= $rowClass ?>" data-status="<?= htmlspecialchars($goodsStatus) ?>">
                                        <td class="py-3 px-4"><?= htmlspecialchars(date("Y-m-d", strtotime($donation['donation_date']))) ?></td>
                                        <td class="py-3 px-4"><?= htmlspecialchars(ucfirst($donation['item_type'])) ?></td>
                                        <td class="py-3 px-4"><?= htmlspecialchars($donation['item_description']) ?></td>
                                        <td class="py-3 px-4"><?= htmlspecialchars($donation['pickup_location']) ?></td>
                                        <td class="py-3 px-4"><?= htmlspecialchars($donation['ngo_name']) ?></td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                                <?php 
                                                    if ($donation['status'] == 'pending' || $donation['status'] == 'accepted') echo 'bg-yellow-100 text-yellow-800';
                                                    else if ($donation['status'] == 'picked_up') echo 'bg-blue-100 text-blue-800';
                                                    else if ($donation['status'] == 'delivered') echo 'bg-green-100 text-green-800';
                                                ?>">
                                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $donation['status']))) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php if ($donation['status'] == 'accepted'): ?>
                                                <button class="btn-sm btn-primary verify-pickup-btn" data-donation-id="<?= $donation['goods_donation_id'] ?>">Verify Pickup</button>
                                            <?php elseif ($donation['status'] == 'picked_up'): ?>
                                                <button type="button" onclick="processVolunteerAction(<?= $donation['goods_donation_id'] ?>, null, 'delivered', null, this)" class="btn-sm btn-success">Mark as Delivered</button>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-4 text-center text-gray-500">No goods/clothes pickups assigned yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <h1>Assigned Food Pickups</h1>
            <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-light mt-8">
                <h2 class="mb-6 flex items-center">
                    <i class="fas fa-utensils mr-3 text-primary-color"></i> Your Assigned Food Pickups
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Date</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Restaurant</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Food Item</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Quantity</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Pickup Address</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Status</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($assigned_food_donations) > 0): ?>
                                <?php foreach ($assigned_food_donations as $food_donation): ?>
                                    <?php 
                                        $foodStatusLower = strtolower($food_donation['status']);
                                        $isActiveFood = in_array($foodStatusLower, ['accepted','picked up','picked_up']);
                                        $rowClassFood = $isActiveFood ? 'row-active' : 'row-history';
                                    ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 <?= $rowClassFood ?>" data-status="<?= htmlspecialchars($foodStatusLower) ?>">
                                        <td class="py-3 px-4"><?= htmlspecialchars(date("Y-m-d H:i", strtotime($food_donation['donation_date']))) ?></td>
                                        <td class="py-3 px-4"><?= htmlspecialchars($food_donation['restaurant_name']) ?></td>
                                        <td class="py-3 px-4"><?= htmlspecialchars($food_donation['food_item']) ?></td>
                                        <td class="py-3 px-4"><?= htmlspecialchars($food_donation['quantity']) ?> <?= htmlspecialchars($food_donation['unit']) ?></td>
                                        <td class="py-3 px-4"><?= htmlspecialchars($food_donation['pickup_location']) ?></td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                                <?php 
                                                    if ($food_donation['status'] == 'Accepted') echo 'bg-yellow-100 text-yellow-800';
                                                    else if ($food_donation['status'] == 'Picked Up') echo 'bg-blue-100 text-blue-800';
                                                    else if ($food_donation['status'] == 'Delivered') echo 'bg-green-100 text-green-800';
                                                ?>">
                                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $food_donation['status']))) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php if ($food_donation['status'] == 'Accepted'): ?>
                                                <button class="btn-sm btn-primary verify-food-pickup-btn" data-food-post-id="<?= $food_donation['food_post_id'] ?>">Verify Pickup</button>
                                            <?php elseif ($food_donation['status'] == 'Picked Up'): ?>
                                                <button type="button" onclick="processVolunteerAction(null, <?= $food_donation['food_post_id'] ?>, 'food_delivered', null, this)" class="btn-sm btn-success">Mark as Delivered</button>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-4 text-center text-gray-500">No food pickups assigned yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Verify Goods Pickup Modal -->
        <div id="verifyGoodsPickupModal" class="modal hidden">
            <div class="modal-content">
                <span class="close-button">&times;</span>
                <h2 class="text-xl font-bold mb-4">Verify Goods Pickup Code</h2>
                <form id="verifyGoodsPickupForm">
                    <input type="hidden" name="goods_donation_id" id="modalVerifyGoodsDonationId">
                    <input type="hidden" name="action" value="verify_goods_pickup">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Enter 4-digit Pickup Code:</label>
                        <div class="flex space-x-3 justify-center">
                            <input inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-input w-12 h-12 text-center border-2 rounded focus:outline-none focus:border-blue-500" />
                            <input inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-input w-12 h-12 text-center border-2 rounded focus:outline-none focus:border-blue-500" />
                            <input inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-input w-12 h-12 text-center border-2 rounded focus:outline-none focus:border-blue-500" />
                            <input inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-input w-12 h-12 text-center border-2 rounded focus:outline-none focus:border-blue-500" />
                        </div>
                        <input type="hidden" id="goods_pickup_code_input" name="pickup_code_input" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Verify</button>
                </form>
            </div>
        </div>

        <!-- Verify Food Pickup Modal -->
        <div id="verifyFoodPickupModal" class="modal hidden">
            <div class="modal-content">
                <span class="close-button">&times;</span>
                <h2 class="text-xl font-bold mb-4">Verify Food Pickup Code</h2>
                <form id="verifyFoodPickupForm">
                    <input type="hidden" name="food_post_id" id="modalVerifyFoodDonationId">
                    <input type="hidden" name="action" value="verify_food_pickup">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Enter 4-digit Pickup Code:</label>
                        <div class="flex space-x-3 justify-center">
                            <input inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-input w-12 h-12 text-center border-2 rounded focus:outline-none focus:border-blue-500" />
                            <input inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-input w-12 h-12 text-center border-2 rounded focus:outline-none focus:border-blue-500" />
                            <input inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-input w-12 h-12 text-center border-2 rounded focus:outline-none focus:border-blue-500" />
                            <input inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-input w-12 h-12 text-center border-2 rounded focus:outline-none focus:border-blue-500" />
                        </div>
                        <input type="hidden" id="food_pickup_code_input" name="pickup_code_input" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Verify</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Logout functionality
            document.getElementById('logout-link').addEventListener('click', function(event) {
                event.preventDefault();
                if (confirm('Are you sure you want to logout?')) {
                    window.location.href = '../logout.php';
                }
            });

            // Tabs logic (Active vs History)
            const tabActive = document.getElementById('tab-active');
            const tabHistory = document.getElementById('tab-history');
            const toggleTab = (showActive) => {
                if (showActive) {
                    tabActive.classList.add('active');
                    tabHistory.classList.remove('active');
                } else {
                    tabHistory.classList.add('active');
                    tabActive.classList.remove('active');
                }

                document.querySelectorAll('tr.row-active').forEach(r => r.style.display = showActive ? '' : 'none');
                document.querySelectorAll('tr.row-history').forEach(r => r.style.display = showActive ? 'none' : '');
            };
            tabActive.addEventListener('click', () => toggleTab(true));
            tabHistory.addEventListener('click', () => toggleTab(false));
            toggleTab(true);

            // Goods Donation Modal Logic
            const verifyGoodsPickupModal = document.getElementById('verifyGoodsPickupModal');
            const closeGoodsButtons = verifyGoodsPickupModal.querySelectorAll('.close-button');
            const verifyGoodsPickupButtons = document.querySelectorAll('.verify-pickup-btn');
            const modalVerifyGoodsDonationId = document.getElementById('modalVerifyGoodsDonationId');

            verifyGoodsPickupButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const donationId = this.dataset.donationId;
                    modalVerifyGoodsDonationId.value = donationId;
                    verifyGoodsPickupModal.classList.remove('hidden');
                });
            });

            closeGoodsButtons.forEach(button => {
                button.addEventListener('click', function() {
                    verifyGoodsPickupModal.classList.add('hidden');
                });
            });

            window.addEventListener('click', function(event) {
                if (event.target == verifyGoodsPickupModal) {
                    verifyGoodsPickupModal.classList.add('hidden');
                }
            });

            // Food Donation Modal Logic
            const verifyFoodPickupModal = document.getElementById('verifyFoodPickupModal');
            const closeFoodButtons = verifyFoodPickupModal.querySelectorAll('.close-button');
            const verifyFoodPickupButtons = document.querySelectorAll('.verify-food-pickup-btn');
            const modalVerifyFoodDonationId = document.getElementById('modalVerifyFoodDonationId');

            verifyFoodPickupButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const foodPostId = this.dataset.foodPostId;
                    modalVerifyFoodDonationId.value = foodPostId;
                    verifyFoodPickupModal.classList.remove('hidden');
                });
            });

            closeFoodButtons.forEach(button => {
                button.addEventListener('click', function() {
                    verifyFoodPickupModal.classList.add('hidden');
                });
            });

            window.addEventListener('click', function(event) {
                if (event.target == verifyFoodPickupModal) {
                    verifyFoodPickupModal.classList.add('hidden');
                }
            });

            // Handle Goods Pickup Verification
            document.getElementById('verifyGoodsPickupForm').addEventListener('submit', function(event) {
                event.preventDefault();
                const form = event.target;
                const formData = new FormData(form);
                
                processVolunteerAction(formData.get('goods_donation_id'), null, formData.get('action'), formData.get('pickup_code_input'), form);
            });

            // Handle Food Pickup Verification
            document.getElementById('verifyFoodPickupForm').addEventListener('submit', function(event) {
                event.preventDefault();
                const form = event.target;
                const formData = new FormData(form);
                
                processVolunteerAction(null, formData.get('food_post_id'), formData.get('action'), formData.get('pickup_code_input'), form);
            });

            // OTP inputs behavior for both modals
            function wireUpOtp(container, hiddenInputId) {
                const inputs = container.querySelectorAll('.otp-input');
                const hidden = document.getElementById(hiddenInputId);
                inputs.forEach((input, idx) => {
                    input.addEventListener('input', (e) => {
                        e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0,1);
                        if (e.target.value && idx < inputs.length - 1) {
                            inputs[idx + 1].focus();
                        }
                        hidden.value = Array.from(inputs).map(i => i.value).join('');
                    });
                    input.addEventListener('keydown', (e) => {
                        if (e.key === 'Backspace' && !e.target.value && idx > 0) {
                            inputs[idx - 1].focus();
                        }
                    });
                    input.addEventListener('paste', (e) => {
                        e.preventDefault();
                        const data = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0, inputs.length);
                        data.split('').forEach((ch, i) => { inputs[i].value = ch; });
                        hidden.value = data;
                        if (inputs[data.length - 1]) inputs[data.length - 1].focus();
                    });
                });
            }
            wireUpOtp(document.getElementById('verifyGoodsPickupForm'), 'goods_pickup_code_input');
            wireUpOtp(document.getElementById('verifyFoodPickupForm'), 'food_pickup_code_input');
        });

        function processVolunteerAction(goodsDonationId, foodPostId, action, pickupCode, element) {
            const formData = new FormData();
            if (goodsDonationId) {
                formData.append('goods_donation_id', goodsDonationId);
            }
            if (foodPostId) {
                formData.append('food_post_id', foodPostId);
            }
            formData.append('action', action);
            if (pickupCode) {
                formData.append('pickup_code_input', pickupCode);
            }

            fetch('../backend/process_volunteer_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    location.reload(); // Reload to reflect changes
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
            });
        }
    </script>

</main>
</div>

<!-- Footer -->
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

</body>
</html>
