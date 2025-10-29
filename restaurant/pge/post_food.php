<?php
session_start();

if (!isset($_SESSION['restaurant_id'])) {
    header("Location: ../auth/restaurant_login.html");
    exit();
}

// Fetch restaurant details (optional, but good for display)
$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    die("❌ DB Connection failed: " . $conn->connect_error);
}

$restaurant_id = $_SESSION['restaurant_id'];
$stmt_restaurant = $conn->prepare("SELECT restaurant_name FROM restaurants WHERE id = ?");
$stmt_restaurant->bind_param("i", $restaurant_id);
$stmt_restaurant->execute();
$result_restaurant = $stmt_restaurant->get_result();
$restaurant_details = $result_restaurant->fetch_assoc();
$stmt_restaurant->close();
$conn->close();

$restaurant_name = $restaurant_details ? htmlspecialchars($restaurant_details['restaurant_name']) : "Your Restaurant";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Food Donation - CharityBridge</title>
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
            <a href="post_food.php" class="active"><i class="fas fa-plus-circle"></i> Post Food</a>
            <a href="my_donations.php"><i class="fas fa-list"></i> My Donations</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <main style="padding: 0;">
        <div class="container">
            <h1 class="text-2xl font-bold mb-6">Post Food Donation from <?php echo $restaurant_name; ?></h1>
            <form id="postFoodForm" class="bg-white p-8 rounded-xl shadow-lg max-w-lg mx-auto">
                
                <div class="mb-4">
                    <label for="food_item" class="block text-gray-700 text-sm font-bold mb-2">Food Item Name:</label>
                    <input type="text" id="food_item" name="food_item" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                
                <div class="mb-4">
                    <label for="quantity" class="block text-gray-700 text-sm font-bold mb-2">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" min="1" required>
                </div>

                <div class="mb-4">
                    <label for="unit" class="block text-gray-700 text-sm font-bold mb-2">Unit (e.g., kg, plates, servings):</label>
                    <input type="text" id="unit" name="unit" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="e.g., kg, plates" required>
                </div>

                <div class="mb-6">
                    <label for="image_path" class="block text-gray-700 text-sm font-bold mb-2">Upload Food Image (Optional):</label>
                    <input type="file" id="image_path" name="image_path" accept="image/*" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div class="flex items-center justify-between">
                    <button type="submit" class="btn btn-primary">Post Food</button>
                </div>
            </form>
        </div>
    </main>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2025 CharityBridge. All rights reserved.</p>
        </div>
    </footer>

    <script src="../../js/script.js"></script>
    <script>
        document.getElementById('postFoodForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const form = event.target;
            const formData = new FormData(form);

            fetch('../backend/process_food_post.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json()) // Expect JSON response
            .then(data => {
                alert(data.message);
                if (data.success) {
                    window.location.href = 'my_donations.php'; // Redirect to my donations page on success
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during food post submission.');
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
