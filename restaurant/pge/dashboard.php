  <?php
    session_start();
    $restaurant_id = $_SESSION['restaurant_id'];

    $conn = new mysqli('localhost', 'root', '', 'charitybridge');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $profile_result = $conn->query("SELECT * FROM restaurants WHERE id = $restaurant_id");
    if ($profile_result->num_rows == 0) {
        // Restaurant not found, redirect to login
        header("Location: ../auth/restaurant_login.html");
        exit();
    }
    $profile = $profile_result->fetch_assoc();

    $totalDonations = $conn->query("SELECT COUNT(*) FROM food_posts WHERE restaurant_id = $restaurant_id")->fetch_row()[0];
    // $mealsServed = $conn->query("SELECT SUM(quantity) FROM food_posts WHERE restaurant_id = $restaurant_id")->fetch_row()[0];
    $activePosts = $conn->query("SELECT COUNT(*) FROM food_posts WHERE restaurant_id = $restaurant_id AND status IN ('Waiting', 'Claimed')")->fetch_row()[0];

    $activeFoodPosts = $conn->query("SELECT * FROM food_posts WHERE restaurant_id = $restaurant_id AND status IN ('Waiting', 'Claimed')");
    $donationHistory = $conn->query("SELECT * FROM food_posts WHERE restaurant_id = $restaurant_id ORDER BY posted_time DESC");

    ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CharityBridge</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
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
            <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="post_food.php"><i class="fas fa-plus-circle"></i> Post Food</a>
            <a href="my_donations.php"><i class="fas fa-list"></i> My Donations</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Welcome, <?= $profile['restaurant_name'] ?>!</h1>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?= $profile['restaurant_name'] ?></span>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #e8effe;">
                    <i class="fas fa-box" style="color: #5b7ac7;"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $totalDonations ?></h3>
                    <p>Total Donations</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #e6f7ed;">
                    <i class="fas fa-clock" style="color: #5ba573;"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $activePosts ?></h3>
                    <p>Active Posts</p>
                </div>
            </div>
        </div>

        <div class="action-bar">
            <a href="post_food.php" class="action-btn primary">
                <i class="fas fa-plus"></i> Post Surplus Food
            </a>
            <a href="my_donations.php" class="action-btn secondary">
                <i class="fas fa-list"></i> Active Food Posts
            </a>
            <a href="history.php" class="action-btn tertiary">
                <i class="fas fa-history"></i> Donation History
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-utensils"></i> Active Food Posts</h2>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Food Item</th>
                            <th>Quantity</th>
                            <th>Posted Time</th>
                            <th>Status</th>
                            <th>NGO</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $activeFoodPosts->fetch_assoc()): ?>
                            <tr>
                                <td><img src="<?= $row['image_path'] ?>" width="60" height="45" class="table-img"></td>
                                <td><?= $row['food_item'] ?></td>
                                <td><?= $row['quantity'] . ' ' . $row['unit'] ?></td>
                                <td><?= date("d M h:i A", strtotime($row['posted_time'])) ?></td>
                                <td>
                                    <span class="badge <?php echo ($row['status'] == 'Waiting') ? 'badge-warning' : 'badge-info'; ?>">
                                        <?= $row['status'] ?>
                                    </span>
                                </td>
                                <td><?= $row['ngo_name'] ?? '--' ?></td>
                                <td>
                                    <?php if ($row['status'] == 'Waiting'): ?>
                                        <button class="btn-cancel cancel-btn" data-id="<?= $row['id'] ?>">Cancel</button>
                                    <?php else: ?>
                                        <span style="color: #6b7280;"><?= $row['status'] ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-bell"></i> Notifications</h2>
            </div>
            <div id="acceptedNotifications" class="notification-area"></div>
        </div>

        <script>
            const notifications = JSON.parse(localStorage.getItem('foodNotifications')) || [];

            function renderAcceptedNotifications() {
                const container = document.getElementById('acceptedNotifications');
                container.innerHTML = '';

                const accepted = notifications.filter(note => note.status === "Accepted");

                if (accepted.length === 0) {
                    container.innerHTML = "<p style='color: #6b7280; text-align: center; padding: 1rem;'>No accepted food posts yet.</p>";
                    return;
                }

                accepted.forEach(note => {
                    const div = document.createElement('div');
                    div.className = 'notification-item success';
                    div.innerHTML = `<i class="fas fa-check-circle"></i> <span>NGO accepted your post: <strong>${note.foodItem}</strong></span>`;
                    container.appendChild(div);
                });
            }

            renderAcceptedNotifications();
        </script>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-history"></i> Donation History</h2>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Food Item</th>
                            <th>Image</th>
                            <th>NGO</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $donationHistory->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['posted_time'] ?></td>
                                <td><?= $row['food_item'] ?></td>
                                <td><img src="<?= $row['image_path'] ?>" width="60" height="40" class="table-img"></td>
                                <td><?= $row['ngo_name'] ?? '--' ?></td>
                                <td>
                                    <span class="badge <?php 
                                        if ($row['status'] == 'Waiting') echo 'badge-warning';
                                        else if ($row['status'] == 'Accepted') echo 'badge-success';
                                        else if ($row['status'] == 'Picked Up') echo 'badge-info';
                                        else if ($row['status'] == 'Delivered') echo 'badge-primary';
                                    ?>">
                                        <?= $row['status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-cog"></i> Profile & Settings</h2>
            </div>
            <div class="profile-info">
                <div class="info-row">
                    <span class="label">Restaurant Name:</span>
                    <span class="value"><?= $profile['restaurant_name'] ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Address:</span>
                    <span class="value"><?= $profile['address'] ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Contact:</span>
                    <span class="value"><?= $profile['phone'] ?></span>
                </div>
                <div class="button-group">
                    <a href="profile.php" class="action-btn primary"><i class="fas fa-edit"></i> Edit Profile</a>
                    <a href="profile.php" class="action-btn secondary"><i class="fas fa-key"></i> Change Password</a>
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
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    toggleMenu();
                }
            });
        });

        // Cancel button functionality
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
                              location.reload(); // Refresh to update active/history sections
                          }
                      })
                      .catch(error => {
                          console.error('Fetch error:', error);
                          alert('An error occurred while cancelling the post. Check console for details.');
                      });
              });
          });
      </script>

<!-- Session Manager -->
<script src="../../js/session_manager.js"></script>
<script src="../../js/dynamic_updates.js"></script>
<script>
    // Create session for restaurant
    sessionManager.createSession('restaurant', <?= $restaurant_id ?>, '<?= htmlspecialchars($profile['restaurant_name']) ?>');
</script>

</body>

</html>
