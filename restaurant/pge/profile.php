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

// Fetch restaurant info
$query = $conn->query("SELECT * FROM restaurants WHERE id = $restaurant_id");
$restaurant = $query->fetch_assoc();

// Fetch donation stats
$totalPosted = $conn->query("SELECT COUNT(*) FROM food_posts WHERE restaurant_id = $restaurant_id")->fetch_row()[0];
$totalCompleted = $conn->query("SELECT COUNT(*) FROM food_posts WHERE restaurant_id = $restaurant_id AND status='Completed'")->fetch_row()[0];

$success = $error = "";

// Change password handler
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current = md5($_POST['current_password']);
    $new = md5($_POST['new_password']);
    $confirm = md5($_POST['confirm_password']);

    $res = $conn->query("SELECT password FROM restaurants WHERE id=$restaurant_id");
    $row = $res->fetch_assoc();

    if ($current !== $row['password']) {
        $error = "❌ Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $error = "⚠️ New passwords do not match.";
    } else {
        $conn->query("UPDATE restaurants SET password='$new' WHERE id=$restaurant_id");
        $success = "✅ Password changed successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - CharityBridge</title>
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
        <a href="my_donations.php"><i class="fas fa-list"></i> My Donations</a>
        <a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</div>

<div class="main-content">
<main style="padding: 0;">
    <div class="container">
        <div class="bg-white shadow-lg rounded-xl p-6 mb-8 border border-gray-light">
            <h1 class="text-2xl font-bold mb-4 text-primary-color">Restaurant Profile</h1>

            <?php if ($success): ?>
                <div class="message success mb-4"><?= $success ?></div>
            <?php elseif ($error): ?>
                <div class="message error mb-4"><?= $error ?></div>
            <?php endif; ?>

            <div class="profile-info">
                <p class="mb-2"><strong>Restaurant Name:</strong> <?= htmlspecialchars($restaurant['restaurant_name']) ?></p>
                <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($restaurant['email']) ?></p>
                <p class="mb-4"><strong>Phone:</strong> <?= htmlspecialchars($restaurant['phone']) ?></p>
                <p class="mb-4"><strong>Address:</strong> <?= htmlspecialchars($restaurant['address']) ?></p>
                <p class="mb-4"><strong>Pincode:</strong> <?= htmlspecialchars($restaurant['pincode']) ?></p>
                <p class="mb-4"><strong>FSSAI License:</strong> <?= htmlspecialchars($restaurant['fssai_license']) ?></p>
                <p class="mb-4"><strong>Restaurant Type:</strong> <?= htmlspecialchars($restaurant['restaurant_type']) ?></p>
                <p class="mb-4"><strong>Status:</strong> 
                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                        <?php 
                            if ($restaurant['status'] == 'Approved') echo 'bg-green-100 text-green-800';
                            else if ($restaurant['status'] == 'Pending') echo 'bg-yellow-100 text-yellow-800';
                            else if ($restaurant['status'] == 'Rejected') echo 'bg-red-100 text-red-800';
                        ?>">
                        <?= htmlspecialchars($restaurant['status']) ?>
                    </span>
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 mb-8">
                    <div class="bg-gray-100 p-4 rounded-lg text-center shadow-sm">
                        <p class="text-gray-600">Total Posted Food</p>
                        <p class="text-3xl font-bold text-primary-color"><?= $totalPosted ?></p>
                    </div>
                    <div class="bg-gray-100 p-4 rounded-lg text-center shadow-sm">
                        <p class="text-gray-600">Successful Donations</p>
                        <p class="text-3xl font-bold text-secondary-color"><?= $totalCompleted ?></p>
                    </div>
                </div>

                <button type="button" id="togglePasswordBtn" class="btn btn-primary">🔑 Change Password</button>

                <div class="password-section hidden mt-6 p-4 bg-gray-50 rounded-lg border border-gray-light" id="passwordSection">
                    <form method="POST" onsubmit="return validatePassword(event);">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" name="current_password" id="current_password" autocomplete="off" required>
                            <span class="error-message message error" id="currentPasswordError"></span>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" name="new_password" id="new_password" autocomplete="off" required>
                            <span class="error-message message error" id="newPasswordError"></span>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" autocomplete="off" required>
                            <span class="error-message message error" id="confirmPasswordError"></span>
                        </div>

                        <button type="submit" name="change_password" class="btn btn-secondary">Save Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
</div>

<footer class="bg-dark-color text-white py-6 text-center">
    <p>&copy; 2025 CharityBridge | <a href="#" class="text-accent-color hover:underline">Help</a> | <a href="#" class="text-accent-color hover:underline">Privacy</a> | <a href="#" class="text-accent-color hover:underline">Terms</a></p>
</footer>

<script>
document.getElementById("togglePasswordBtn").addEventListener("click", function() {
    const section = document.getElementById("passwordSection");
    section.classList.toggle("hidden"); // Toggle hidden class
});

function showError(element, message) {
    element.textContent = message;
    element.style.display = 'block';
}

function hideError(element) {
    element.textContent = '';
    element.style.display = 'none';
}

function validatePassword(event) {
    let isValid = true;

    const currentPassInput = document.getElementById("current_password");
    const newPassInput = document.getElementById("new_password");
    const confirmPassInput = document.getElementById("confirm_password");

    const currentPassError = document.getElementById("currentPasswordError");
    const newPassError = document.getElementById("newPasswordError");
    const confirmPassError = document.getElementById("confirmPasswordError");

    hideError(currentPassError);
    hideError(newPassError);
    hideError(confirmPassError);

    const currentPass = currentPassInput.value;
    const newPass = newPassInput.value;
    const confirmPass = confirmPassInput.value;

    if (!currentPass) {
        showError(currentPassError, "Current password is required.");
        isValid = false;
    }

    if (!newPass) {
        showError(newPassError, "New password is required.");
        isValid = false;
    } else if (newPass.length < 8) {
        showError(newPassError, "New password must be at least 8 characters long.");
        isValid = false;
    } else if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]).{8,}$/.test(newPass)) {
        showError(newPassError, "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.");
        isValid = false;
    }

    if (!confirmPass) {
        showError(confirmPassError, "Confirm new password is required.");
        isValid = false;
    } else if (newPass !== confirmPass) {
        showError(confirmPassError, "New passwords do not match.");
        isValid = false;
    }

    if (newPass && currentPass && newPass === currentPass) {
        showError(newPassError, "New password cannot be the same as the current password.");
        isValid = false;
    }

    if (!isValid) {
        event.preventDefault(); // Prevent form submission if validation fails
    }
    return isValid;
}
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
