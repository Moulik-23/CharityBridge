<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Approvals - CharityBridge Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<!-- Mobile Header with Hamburger -->
<div class="mobile-header">
  <div class="mobile-logo">
    <i class="fas fa-user-shield"></i>
    <span>CharityBridge Admin</span>
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
    <i class="fas fa-user-shield"></i>
    <span>CharityBridge</span>
  </div>
  <nav class="nav">
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="approvals.php" class="active"><i class="fas fa-check-circle"></i> Approvals</a>
    <a href="#" onclick="confirmLogout(event)"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
</div>

<!-- Main Content -->
<div class="main-content">
  <div class="header">
    <h1>Approval Queue</h1>
    <div class="user-info">
      <i class="fas fa-user-circle"></i>
      <span>Administrator</span>
    </div>
  </div>

  <main style="padding: 0;">
    <div class="container">
      <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-light mb-8">
        <h2 class="text-xl font-bold mb-4 flex items-center text-primary-color">
          <i class="fas fa-hands-helping mr-3"></i> Pending NGO Registrations
        </h2>

        <div class="overflow-x-auto">
          <table class="min-w-full text-left table-auto">
            <thead>
              <tr class="bg-primary-color text-white uppercase text-sm">
                <th class="py-3 px-4 border-b-2 border-gray-200">NGO Name</th>
                <th class="py-3 px-4 border-b-2 border-gray-200">Email</th>
                <th class="py-3 px-4 border-b-2 border-gray-200">Darpan ID</th>
                <th class="py-3 px-4 border-b-2 border-gray-200">Date of Registration</th>
                <th class="py-3 px-4 border-b-2 border-gray-200">Report</th>
                <th class="py-3 px-4 text-center border-b-2 border-gray-200">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $conn = new mysqli('localhost', 'root', '', 'charitybridge');
                if ($conn->connect_error) {
                  die("Connection failed: " . $conn->connect_error);
                }

                $sql = "SELECT id, name, email, darpan_id, created_at, certificate FROM ngos WHERE status='pending'";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
              ?>
                    <tr id='ngo-row-<?php echo $row['id']; ?>' class='border-b border-gray-100 hover:bg-gray-50'>
                      <td class='py-2 px-4'><?php echo $row['name']; ?></td>
                      <td class='py-2 px-4'><?php echo $row['email']; ?></td>
                      <td class='py-2 px-4'><?php echo $row['darpan_id']; ?></td>
                      <td class='py-2 px-4'><?php echo $row['created_at']; ?></td>
                      <td class='py-2 px-4'>
                        <a href="backend/ngo_report.php?id=<?php echo $row['id']; ?>" class='btn btn-primary btn-sm inline-flex items-center'>
                          <i class='fas fa-file-alt mr-2'></i>View Report
                        </a>
                      </td>
                      <td class='py-2 px-4'>
                              <div class='flex items-center justify-center gap-2'>
                                <button onclick="approveNgo(<?php echo $row['id']; ?>, document.getElementById('ngo-row-<?php echo $row['id']; ?>'))" class='btn btn-success btn-sm'>
                                  <i class='fas fa-check mr-2'></i>Approve
                                </button>
                                <button onclick="rejectNgo(<?php echo $row['id']; ?>, document.getElementById('ngo-row-<?php echo $row['id']; ?>'))" class='btn btn-danger btn-sm'>
                                  <i class='fas fa-times mr-2'></i>Reject
                                </button>
                              </div>
                            </td>
                    </tr>
              <?php
                  }
                } else {
              ?>
                  <tr><td colspan='6' class='text-center py-4 text-gray-500'>No pending NGOs</td></tr>
              <?php
                }

                $conn->close();
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-light mt-10">
        <h2 class="text-xl font-bold mb-4 flex items-center text-primary-color">
          <i class="fas fa-utensils mr-3"></i> Pending Restaurant Registrations
        </h2>

        <div class="overflow-x-auto">
          <table class="min-w-full text-left table-auto">
            <thead>
              <tr class="bg-primary-color text-white uppercase text-sm">
                <th class="py-3 px-4 border-b-2 border-gray-200">Restaurant Name</th>
                <th class="py-3 px-4 border-b-2 border-gray-200">Owner Name</th>
                <th class="py-3 px-4 border-b-2 border-gray-200">Email</th>
                <th class="py-3 px-4 border-b-2 border-gray-200">Phone</th>
                <th class="py-3 px-4 border-b-2 border-gray-200">Pincode</th>
                <th class="py-3 px-4 border-b-2 border-gray-200">FSSAI License</th>
                <th class="py-3 px-4 border-b-2 border-gray-200">Date of Registration</th>
                <th class="py-3 px-4 border-b-2 border-gray-200">Report</th>
                <th class="py-3 px-4 text-center border-b-2 border-gray-200">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $conn = new mysqli('localhost', 'root', '', 'charitybridge');
                if ($conn->connect_error) {
                  die("Connection failed: " . $conn->connect_error);
                }

                $sql_restaurants = "SELECT id, restaurant_name, owner_name, email, phone, pincode, fssai_license, license_document, created_at FROM restaurants WHERE status='Pending'";
                $result_restaurants = $conn->query($sql_restaurants);

                if ($result_restaurants->num_rows > 0) {
                  while ($row_restaurant = $result_restaurants->fetch_assoc()) {
                    echo "<tr id='restaurant-row-{$row_restaurant['id']}' class='border-b border-gray-100 hover:bg-gray-50'>";
                    echo "<td class='py-2 px-4'>{$row_restaurant['restaurant_name']}</td>";
                    echo "<td class='py-2 px-4'>{$row_restaurant['owner_name']}</td>";
                    echo "<td class='py-2 px-4'>{$row_restaurant['email']}</td>";
                    echo "<td class='py-2 px-4'>{$row_restaurant['phone']}</td>";
                    echo "<td class='py-2 px-4'>{$row_restaurant['pincode']}</td>";
                    echo "<td class='py-2 px-4'>{$row_restaurant['fssai_license']}</td>";
                    echo "<td class='py-2 px-4'>{$row_restaurant['created_at']}</td>";
                    echo "<td class='py-2 px-4'><a href='backend/restaurant_report.php?id={$row_restaurant['id']}' class='btn btn-primary btn-sm inline-flex items-center'><i class='fas fa-file-alt mr-2'></i>View Report</a></td>";
                    echo "<td class='py-2 px-4'>
                            <div class='flex items-center justify-center gap-2'>
                              <button onclick=\"approveRestaurant({$row_restaurant['id']}, document.getElementById('restaurant-row-{$row_restaurant['id']}'))\" class='btn btn-success btn-sm'>
                                <i class='fas fa-check mr-2'></i>Approve
                              </button>
                              <button onclick=\"rejectRestaurant({$row_restaurant['id']}, document.getElementById('restaurant-row-{$row_restaurant['id']}'))\" class='btn btn-danger btn-sm'>
                                <i class='fas fa-times mr-2'></i>Reject
                              </button>
                            </div>
                          </td>";
                    echo "</tr>";
                  }
                } else {
                  echo "<tr><td colspan='9' class='text-center py-4 text-gray-500'>No pending restaurants</td></tr>";
                }

                $conn->close();
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

  <!-- Footer -->
  <footer>
    <div class="container">
      <p>&copy; 2025 CharityBridge. All rights reserved.</p>
    </div>
  </footer>

  <!-- JS -->
  <script src="../js/admin.js"></script>
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

    function confirmLogout(event) {
      event.preventDefault();
      if (confirm("Are you sure you want to logout?")) {
        window.location.href = "backend/logout.php";
      }
    }

    function approveRestaurant(id, rowElement) {
        console.log('Attempting to approve restaurant with ID:', id);
        fetch("backend/approve_restaurant.php", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success && rowElement) {
                rowElement.remove();
            }
        })
        .catch(err => console.error(err));
    }

    function rejectRestaurant(id, rowElement) {
        console.log('Attempting to reject restaurant with ID:', id);
        fetch("backend/reject_restaurant.php", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success && rowElement) {
                rowElement.remove();
            }
        })
        .catch(err => console.error(err));
    }
  </script>
</body>
</html>
