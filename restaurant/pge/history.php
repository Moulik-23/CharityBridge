<?php
session_start();
if (!isset($_SESSION['restaurant_id'])) {
    header("Location: ../auth/restaurant_login.html");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];
$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$result = $conn->query("SELECT * FROM food_posts WHERE restaurant_id = $restaurant_id ORDER BY posted_time DESC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation History - CharityBridge</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: center;
        }

        th {
            background: #3498db;
            color: white;
        }
    </style>
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
            <a href="my_donations.php"><i class="fas fa-list"></i> My Donations</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Donation History</h1>
        </div>
    <table>
        <tr>
            <th>Image</th>
            <th>Date</th>
            <th>Food Item</th>
            <th>Quantity</th>
            <th>Status</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><img src="<?= $row['image_path'] ?>" width="60" height="40"></td>
                <td><?= $row['posted_time'] ?></td>
                <td><?= $row['food_item'] ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><?= $row['status'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
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
</body>

</html>
