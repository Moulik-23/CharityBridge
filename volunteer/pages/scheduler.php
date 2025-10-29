<?php
session_start();

// Redirect if not logged in as volunteer
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: ../auth/login.html");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$volunteer_id = $_SESSION['volunteer_id'];

// Fetch approved opportunities for this volunteer (include map id for withdrawal)
$sql = "SELECT m.id AS map_id, m.req_id, vr.title, vr.description, vr.event_date, vr.location, n.name AS ngo_name
        FROM volunteer_ngo_map m
        JOIN volunteer_requirements vr ON m.req_id = vr.req_id
        JOIN ngos n ON vr.ngo_id = n.id
        WHERE m.volunteer_id = ? AND m.status = 'Approved'
        ORDER BY vr.event_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $volunteer_id);
$stmt->execute();
$result = $stmt->get_result();
$events = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - CharityBridge</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-calendar-js@1.4.2/dist/simple-calendar.css">
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
        <a href="scheduler.php" class="active"><i class="fas fa-calendar"></i> My Schedule</a>
        <a href="logistics.php"><i class="fas fa-truck"></i> Logistics</a>
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
        <h1>My Schedule</h1>
        <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg p-8 border border-gray-light">
            <div id="calendar-container"></div>
            <div class="mt-8">
                <h2 class="mb-4">Upcoming Approved Events</h2>
                <ul class="space-y-3">
                    <?php if (count($events) > 0): ?>
                        <?php foreach ($events as $event): ?>
                            <li class="flex items-center justify-between p-3 bg-light-color rounded-lg border border-gray-light">
                                <div class="flex items-center">
                                    <i class="fas fa-calendar-check mr-3 text-green-500"></i>
                                    <?= htmlspecialchars($event['title']) ?> (<?= htmlspecialchars($event['ngo_name']) ?>) 
                                    - <?= date("d M Y", strtotime($event['event_date'])) ?> @ <?= htmlspecialchars($event['location']) ?>
                                </div>
                                <form method="POST" action="../backend/withdraw_application.php" onsubmit="return confirm('Withdraw from this opportunity?');">
                                    <input type="hidden" name="map_id" value="<?= (int)$event['map_id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Withdraw</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="text-gray-500">No approved events yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</main>
</div>

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

<script src="https://cdn.jsdelivr.net/npm/simple-calendar-js@1.4.2/dist/simple-calendar.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var events = [
            <?php foreach ($events as $event): ?>
            {
                startDate: new Date("<?= date('Y-m-d', strtotime($event['event_date'])) ?>"),
                endDate: new Date("<?= date('Y-m-d', strtotime($event['event_date'])) ?>"),
                summary: "<?= addslashes($event['title']) ?>"
            },
            <?php endforeach; ?>
        ];

        new SimpleCalendar('#calendar-container', { events: events });
    });
</script>
</body>
</html>
