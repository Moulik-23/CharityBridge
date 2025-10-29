<?php
session_start();
if (!isset($_SESSION['restaurant_id'])) {
    header("Location: ../auth/restaurant_login.html");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$restaurant_id = $_SESSION['restaurant_id'];
$restaurant_name = $_SESSION['restaurant_name']; // Assuming restaurant_name is also in session

$sql = "SELECT id, food_item, quantity, unit, status, posted_time, ngo_name, volunteer_name, pickup_code FROM food_posts WHERE restaurant_id = ? ORDER BY posted_time DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$result = $stmt->get_result();
$food_donations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Donations - CharityBridge</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>

    <!-- Mobile Header with Hamburger -->
    <div class="mobile-header">
        <div class="mobile-logo">
            <i class="fas fa-utensils"></i>
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
            <i class="fas fa-utensils"></i>
            <span>CharityBridge</span>
        </div>
        <nav class="nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="post_food.php"><i class="fas fa-plus-circle"></i> Post Food</a>
            <a href="my_donations.php" class="active"><i class="fas fa-list"></i> My Donations</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <main style="padding: 0;">
        <div class="container">
            <div class="bg-white shadow-lg rounded-xl p-6 mb-8 border border-gray-light">
                <h1 class="text-2xl font-bold mb-4 text-primary-color">My Food Donations</h1>
                <input type="text" id="searchDonations" placeholder="Search food name..." class="w-full p-3 border rounded-lg mb-4">
                <div class="overflow-x-auto">
                    <table id="donationsTable" class="min-w-full text-left table-auto">
                        <thead>
                            <tr class="bg-primary-color text-white">
                                <th class="py-3 px-4 border-b-2 border-gray-200">Food Name</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Quantity</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Unit</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Status</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Posted Time</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">NGO Name</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Volunteer</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Pickup Code</th>
                                <th class="py-3 px-4 border-b-2 border-gray-200">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($food_donations) > 0): ?>
                                <?php foreach ($food_donations as $row): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-2 px-4"><?= htmlspecialchars($row['food_item']) ?></td>
                                        <td class="py-2 px-4"><?= htmlspecialchars($row['quantity']) ?></td>
                                        <td class="py-2 px-4"><?= htmlspecialchars($row['unit']) ?></td>
                                        <td class="py-2 px-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                                <?php 
                                                    if ($row['status'] == 'Waiting') echo 'bg-yellow-100 text-yellow-800';
                                                    else if ($row['status'] == 'Accepted') echo 'bg-green-100 text-green-800';
                                                    else if ($row['status'] == 'Picked Up') echo 'bg-blue-100 text-blue-800';
                                                    else if ($row['status'] == 'Delivered') echo 'bg-purple-100 text-purple-800';
                                                    else if ($row['status'] == 'Cancelled') echo 'bg-red-100 text-red-800';
                                                ?>">
                                                <?= htmlspecialchars($row['status']) ?>
                                            </span>
                                        </td>
                                        <td class="py-2 px-4"><?= htmlspecialchars(date("d M h:i A", strtotime($row['posted_time']))) ?></td>
                                        <td class="py-2 px-4"><?= htmlspecialchars($row['ngo_name'] ?: 'N/A') ?></td>
                                        <td class="py-2 px-4"><?= htmlspecialchars($row['volunteer_name'] ?: 'N/A') ?></td>
                                        <td class="py-2 px-4 font-bold text-primary-color"><?= htmlspecialchars($row['pickup_code'] ?: 'N/A') ?></td>
                        <td class="py-2 px-4" id="actions-<?= $row['id'] ?>">
                            <?php if ($row['status'] == 'Waiting'): ?>
                                <button class="btn btn-danger btn-sm cancel-btn" data-id="<?= $row['id'] ?>">Cancel</button>
                            <?php else: ?>
                                <span class="text-gray-600"><?= htmlspecialchars($row['status']) ?></span>
                            <?php endif; ?>
                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan='9' class="py-4 text-center text-gray-500">No food donations posted yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    </div>

    <footer class="bg-dark-color text-white py-6 text-center">
        <p>&copy; 2025 CharityBridge | <a href="#" class="text-accent-color hover:underline">Help</a> | <a href="#" class="text-accent-color hover:underline">Privacy</a> | <a href="#" class="text-accent-color hover:underline">Terms</a></p>
    </footer>

    <script>
        document.getElementById('searchDonations').addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#donationsTable tbody tr');
            rows.forEach(row => {
                let food = row.cells[0].textContent.toLowerCase();
                row.style.display = food.includes(filter) ? '' : 'none';
            });
        });

        document.querySelectorAll('.cancel-btn').forEach(button => {
            button.addEventListener('click', function() {
                const postId = this.getAttribute('data-id');

                fetch('../backend/cancel_post.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'post_id=' + postId
                    })
                    .then(response => {
                        if (!response.ok) {
                            console.error('Server responded with an error status:', response.status, response.statusText);
                            return response.text().then(text => { throw new Error(text); });
                        }
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            return response.json();
                        } else {
                            return response.text().then(text => { throw new Error('Expected JSON, but received: ' + text); });
                        }
                    })
                    .then(jsonResponse => {
                        alert(jsonResponse.message);
                        if (jsonResponse.success) {
                            // Update the UI dynamically
                            const row = button.closest('tr');
                            const statusCell = row.querySelector('td:nth-child(4) span'); // Assuming status is the 4th td
                            const actionsCell = row.querySelector(`#actions-${postId}`);

                            if (statusCell) {
                                statusCell.textContent = 'Cancelled';
                                statusCell.className = 'px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800';
                            }
                            if (actionsCell) {
                                actionsCell.innerHTML = '<span class="text-gray-600">Cancelled</span>';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        alert('An error occurred while cancelling the post. Check console for details.');
                    });
            });
        });
    </script>

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
