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

// Fetch all approved NGOs and their requirements
$ngos_with_requirements = [];

$stmt_ngos = $conn->prepare("SELECT id, name, ngo_type, state, district FROM ngos WHERE status = 'approved'");
$stmt_ngos->execute();
$result_ngos = $stmt_ngos->get_result();

while ($ngo = $result_ngos->fetch_assoc()) {
    $ngo_id = $ngo['id'];
    $ngo['requirements'] = [];

    // Fetch requirements for clothes and goods (including those without item_type specified)
    $stmt_requirements = $conn->prepare("SELECT id, title, description, item_type FROM requirements WHERE ngo_id = ? AND (item_type IN ('clothes', 'goods') OR item_type IS NULL) ORDER BY created_at DESC");
    $stmt_requirements->bind_param("i", $ngo_id);
    $stmt_requirements->execute();
    $result_requirements = $stmt_requirements->get_result();

    while ($req = $result_requirements->fetch_assoc()) {
        $ngo['requirements'][] = $req;
    }
    $stmt_requirements->close();
    $ngos_with_requirements[] = $ngo;
}
$stmt_ngos->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate - CharityBridge</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        @media (max-width: 1024px) {
            .ngo-grid { grid-template-columns: repeat(2, 1fr) !important; }
        }
        @media (max-width: 768px) {
            .ngo-grid { grid-template-columns: 1fr !important; }
            div[style*="margin-left: 260px"] { margin-left: 0 !important; padding-top: 60px; }
        }
    </style>
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

<!-- Main Content Wrapper for sidebar -->
<div style="margin-left: 260px;">

    <!-- Main Content -->
    <main class="py-16 bg-gray-50">
        <div class="container">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-primary-color mb-3">
                    <i class="fas fa-heart text-secondary-color mr-2"></i>Select an NGO to Support
                </h2>
                <p class="text-gray-600 max-w-xl mx-auto">Browse through our verified NGOs and their current requirements</p>
            </div>

            <div class="ngo-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem;">
                <?php if (count($ngos_with_requirements) > 0): ?>
                    <?php foreach ($ngos_with_requirements as $ngo): ?>
                        <div class="bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border-t-4 border-secondary-color overflow-hidden">
                            <!-- NGO Header -->
                            <div class="bg-gradient-to-br from-primary-color to-secondary-color p-6">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h2 class="text-2xl font-bold text-gray-800 mb-2">
                                            <i class="fas fa-building mr-2 text-gray-700"></i><?php echo htmlspecialchars($ngo['name']); ?>
                                        </h2>
                                        <div class="flex items-center text-gray-700 text-sm space-x-4">
                                            <span class="flex items-center">
                                                <i class="fas fa-tag mr-1"></i>
                                                <?php echo htmlspecialchars($ngo['ngo_type']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- NGO Body -->
                            <div class="p-6">
                                <div class="flex items-center text-gray-600 mb-4">
                                    <i class="fas fa-map-marker-alt text-secondary-color mr-2"></i>
                                    <span class="text-sm"><?php echo htmlspecialchars($ngo['state'] . ', ' . $ngo['district']); ?></span>
                                </div>

                                <div class="border-t border-gray-200 pt-4">
                                    <?php if (count($ngo['requirements']) > 0): ?>
                                        <h3 class="font-bold text-primary-color mb-3 flex items-center">
                                            <i class="fas fa-clipboard-list mr-2 text-secondary-color"></i>
                                            Current Needs
                                        </h3>
                                        <ul class="space-y-2">
                                            <?php foreach ($ngo['requirements'] as $req): ?>
                                                <li class="flex items-start bg-gray-50 p-3 rounded-lg">
                                                    <i class="fas fa-check-circle text-green-500 mr-2 mt-1 flex-shrink-0"></i>
                                                    <div class="flex-1">
                                                        <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($req['title']); ?></span>
                                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($req['description']); ?></p>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-info-circle text-gray-400 text-3xl mb-2"></i>
                                            <p class="text-gray-500 text-sm">No specific requirements listed at the moment.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- NGO Footer -->
                            <div class="px-6 pb-6">
                                <a href="donate_form.php?ngo_id=<?php echo htmlspecialchars($ngo['id']); ?>" 
                                   class="btn btn-primary w-full text-center flex items-center justify-center group">
                                    <i class="fas fa-hand-holding-heart mr-2 group-hover:scale-110 transition-transform"></i>
                                    Donate Now
                                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-16">
                        <i class="fas fa-search text-gray-300 text-6xl mb-4"></i>
                        <p class="text-gray-600 text-lg">No NGOs found with active requirements.</p>
                    </div>
                <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2025 CharityBridge. All rights reserved.</p>
        </div>
    </footer>

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
<script src="../js/script.js"></script>
<script>
    // Create session for donor
    sessionManager.createSession('donor', <?= $donor_id ?>, '<?= htmlspecialchars($donor_name) ?>');
</script>

</body>
</html>
</body>
</html>
