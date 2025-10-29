<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: ../auth/volunteer_login.html");
    exit();
}

$volunteer_id = $_SESSION['volunteer_id'];

$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$volunteer = null;
$skills_array = [];
$error_message = "";
$success_message = "";

// Fetch volunteer details
if ($_SERVER["REQUEST_METHOD"] == "GET" || (isset($_POST['action']) && $_POST['action'] == 'fetch')) {
    $sql_vol = "SELECT name, email, phone, address, skills FROM volunteers WHERE volunteer_id = ?";
    $stmt = $conn->prepare($sql_vol);
    $stmt->bind_param("i", $volunteer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $volunteer = $result->fetch_assoc();
        if (!empty($volunteer['skills'])) {
            $skills_array = explode(",", $volunteer['skills']);
            $skills_array = array_map('trim', $skills_array);
        }
    } else {
        $error_message = "Volunteer not found.";
    }
    $stmt->close();
}

// Handle profile update with stronger validation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($address)) {
        $error_message = "All fields are required.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else if (!preg_match('/^[A-Za-z\s\-\']{2,100}$/', $name)) {
        $error_message = "Name can only contain letters, spaces or hyphens (2-100 chars).";
    } else {
        // Normalize and validate Indian mobile number (exactly 10 digits, starts 6-9)
        $normalized_phone = preg_replace('/\D/', '', $phone);
        if (!preg_match('/^[6-9]\d{9}$/', $normalized_phone)) {
            $error_message = "Enter a valid 10-digit Indian mobile number.";
        } elseif (strlen(trim($address)) < 5 || strlen($address) > 255) {
            $error_message = "Address must be between 5 and 255 characters.";
        } else {
            // Check if email already exists for another volunteer
            $check_email_stmt = $conn->prepare("SELECT volunteer_id FROM volunteers WHERE email = ? AND volunteer_id != ?");
            $check_email_stmt->bind_param("si", $email, $volunteer_id);
            $check_email_stmt->execute();
            $check_email_result = $check_email_stmt->get_result();
            if ($check_email_result->num_rows > 0) {
                $error_message = "Email already registered by another volunteer.";
            }
            $check_email_stmt->close();

            if (empty($error_message)) {
                $sql_update = "UPDATE volunteers SET name = ?, email = ?, phone = ?, address = ? WHERE volunteer_id = ?";
                $stmt_update = $conn->prepare($sql_update);
                // Save normalized 10-digit number
                $stmt_update->bind_param("ssssi", $name, $email, $normalized_phone, $address, $volunteer_id);

                if ($stmt_update->execute()) {
                    $success_message = "Profile updated successfully!";
                    // Re-fetch updated data
                    header("Location: manage_profile.php?status=success&message=" . urlencode($success_message));
                    exit();
                } else {
                    $error_message = "Error updating profile: " . $stmt_update->error;
                }
                $stmt_update->close();
            }
        }
    }
}

// Handle skill addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_skill') {
    $new_skill = trim($_POST['new_skill']);

    if (!empty($new_skill)) {
        // Fetch current skills
        $sql_fetch_skills = "SELECT skills FROM volunteers WHERE volunteer_id = ?";
        $stmt_fetch_skills = $conn->prepare($sql_fetch_skills);
        $stmt_fetch_skills->bind_param("i", $volunteer_id);
        $stmt_fetch_skills->execute();
        $current_skills_result = $stmt_fetch_skills->get_result()->fetch_assoc();
        $stmt_fetch_skills->close();

        $current_skills_str = $current_skills_result['skills'];
        $current_skills_array = !empty($current_skills_str) ? array_map('trim', explode(",", $current_skills_str)) : [];

        if (!in_array($new_skill, $current_skills_array)) {
            $current_skills_array[] = $new_skill;
            $updated_skills_str = implode(",", $current_skills_array);
            // If the skills array becomes empty, set the string to NULL for database storage
            $updated_skills_str = empty($updated_skills_str) ? NULL : $updated_skills_str;

            $sql_update_skills = "UPDATE volunteers SET skills = ? WHERE volunteer_id = ?";
            $stmt_update_skills = $conn->prepare($sql_update_skills);
            // Use "s" for string, but if $updated_skills_str is NULL, bind_param will handle it correctly
            $stmt_update_skills->bind_param("si", $updated_skills_str, $volunteer_id);

            if ($stmt_update_skills->execute()) {
                $success_message = "Skill added successfully!";
                header("Location: manage_profile.php?status=success&message=" . urlencode($success_message));
                exit();
            } else {
                $error_message = "Error adding skill: " . $stmt_update_skills->error;
            }
            $stmt_update_skills->close();
        } else {
            $error_message = "Skill already exists.";
        }
    } else {
        $error_message = "Skill cannot be empty.";
    }
}

// Handle skill deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_skill') {
    $skill_to_delete = trim($_POST['skill_to_delete']);

    if (!empty($skill_to_delete)) {
        // Fetch current skills
        $sql_fetch_skills = "SELECT skills FROM volunteers WHERE volunteer_id = ?";
        $stmt_fetch_skills = $conn->prepare($sql_fetch_skills);
        $stmt_fetch_skills->bind_param("i", $volunteer_id);
        $stmt_fetch_skills->execute();
        $current_skills_result = $stmt_fetch_skills->get_result()->fetch_assoc();
        $stmt_fetch_skills->close();

        $current_skills_str = $current_skills_result['skills'];
        $current_skills_array = !empty($current_skills_str) ? array_map('trim', explode(",", $current_skills_str)) : [];

        $updated_skills_array = array_diff($current_skills_array, [$skill_to_delete]);
        $updated_skills_str = implode(",", $updated_skills_array);
        // If the skills array becomes empty, set the string to NULL for database storage
        $updated_skills_str = empty($updated_skills_str) ? NULL : $updated_skills_str;

        $sql_update_skills = "UPDATE volunteers SET skills = ? WHERE volunteer_id = ?";
        $stmt_update_skills = $conn->prepare($sql_update_skills);
        // Use "s" for string, but if $updated_skills_str is NULL, bind_param will handle it correctly
        $stmt_update_skills->bind_param("si", $updated_skills_str, $volunteer_id);

        if ($stmt_update_skills->execute()) {
            $success_message = "Skill deleted successfully!";
            header("Location: manage_profile.php?status=success&message=" . urlencode($success_message));
            exit();
        } else {
            $error_message = "Error deleting skill: " . $stmt_update_skills->error;
        }
        $stmt_update_skills->close();
    } else {
        $error_message = "Skill to delete cannot be empty.";
    }
}

// Check for messages from redirect
if (isset($_GET['status']) && isset($_GET['message'])) {
    if ($_GET['status'] == 'success') {
        $success_message = htmlspecialchars($_GET['message']);
    } else if ($_GET['status'] == 'error') {
        $error_message = htmlspecialchars($_GET['message']);
    }
}

// Re-fetch volunteer details after any POST operation to ensure displayed data is up-to-date
$sql_vol = "SELECT name, email, phone, address, skills FROM volunteers WHERE volunteer_id = ?";
$stmt = $conn->prepare($sql_vol);
$stmt->bind_param("i", $volunteer_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $volunteer = $result->fetch_assoc();
    if (!empty($volunteer['skills'])) {
        $skills_array = explode(",", $volunteer['skills']);
        $skills_array = array_map('trim', $skills_array);
    } else {
        $skills_array = [];
    }
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Profile - CharityBridge</title>
  <link
    href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css"
    rel="stylesheet"
  />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
  />
  <link rel="stylesheet" href="../../css/dashboard.css" />
  <link rel="stylesheet" href="../../css/style.css" />
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
        <a href="logistics.php"><i class="fas fa-truck"></i> Logistics</a>
        <a href="manage_profile.php" class="active"><i class="fas fa-user"></i> Manage Profile</a>
        <a href="#" id="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">

  <main style="padding: 0;">
    <div class="container">
      <h1 class="text-center mb-8">Manage Your Profile</h1>

      <?php if ($error_message): ?>
      <div class="message error mb-4" role="alert">
        <strong class="font-bold">Error!</strong>
        <span class="block sm:inline"><?= $error_message ?></span>
      </div>
      <?php endif; ?>

      <?php if ($success_message): ?>
      <div class="message success mb-4" role="alert">
        <strong class="font-bold">Success!</strong>
        <span class="block sm:inline"><?= $success_message ?></span>
      </div>
      <?php endif; ?>

      <?php if ($volunteer): ?>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Profile Details Section -->
        <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-light">
          <h2 class="mb-6">Your Details</h2>
          <form id="profile-form" action="manage_profile.php" method="POST" novalidate>
            <input type="hidden" name="action" value="update_profile" />
            <div class="form-group">
              <label for="name">Name:</label>
              <input
                type="text"
                id="name"
                name="name"
                value="<?= htmlspecialchars($volunteer['name']) ?>"
                required
                pattern="[A-Za-z\s\-']{2,100}"
                title="Only letters, spaces, hyphens or apostrophes (2-100 chars)"
              />
            </div>
            <div class="form-group">
              <label for="email">Email:</label>
              <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($volunteer['email']) ?>"
                required
              />
            </div>
            <div class="form-group">
              <label for="phone">Phone:</label>
              <input
                type="text"
                id="phone"
                name="phone"
                value="<?= htmlspecialchars($volunteer['phone']) ?>"
                required
                pattern="^[6-9]\d{9}$"
                inputmode="numeric"
                title="Enter a 10-digit Indian mobile number starting with 6-9"
              />
            </div>
            <div class="form-group">
              <label for="address">Address:</label>
              <textarea
                id="address"
                name="address"
                rows="3"
                required
                minlength="5"
                maxlength="255"
              ><?= htmlspecialchars($volunteer['address']) ?></textarea>
            </div>
            <div class="flex items-center justify-end mt-6 space-x-4">
              <button
                type="submit"
                id="save-btn"
                class="btn btn-primary"
              >
                Save
              </button>
              <button
                type="button"
                id="discard-btn"
                class="btn btn-secondary"
              >
                Discard
              </button>
            </div>
          </form>
        </div>

        <!-- Skills Section -->
        <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-light">
          <h2 class="mb-6">Your Skills</h2>
          <div class="mb-6">
            <?php if (!empty($skills_array)): ?>
            <div class="flex flex-wrap gap-2">
              <?php foreach ($skills_array as $skill): ?>
              <div
                class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full flex items-center"
              >
                <?= htmlspecialchars($skill) ?>
                <form
                  action="manage_profile.php"
                  method="POST"
                  class="ml-2 inline-block"
                >
                  <input type="hidden" name="action" value="delete_skill" />
                  <input
                    type="hidden"
                    name="skill_to_delete"
                    value="<?= htmlspecialchars($skill) ?>"
                  />
                  <button
                    type="submit"
                    class="skill-delete-btn text-blue-600 hover:text-blue-800 focus:outline-none"
                  >
                    <i class="fas fa-times-circle"></i>
                  </button>
                </form>
              </div>
              <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-gray-500">No skills added yet.</p>
            <?php endif; ?>
          </div>

          <h3 class="mb-4">Add New Skill</h3>
          <form action="manage_profile.php" method="POST">
            <input type="hidden" name="action" value="add_skill" />
            <div class="form-group">
              <input
                type="text"
                name="new_skill"
                placeholder="e.g., Web Development"
                required
              />
            </div>
            <div class="flex items-center justify-between">
              <button
                type="submit"
                class="btn btn-accent"
              >
                Add Skill
              </button>
            </div>
          </form>
        </div>
      </div>
      <?php else: ?>
      <p class="text-center text-xl text-red-500">Could not load volunteer profile.</p>
      <?php endif; ?>
    </div>
  </main>
</div>

  <footer>
    <div class="container">
      <p>&copy; 2025 CharityBridge. All rights reserved.</p>
    </div>
  </footer>

  <script>
    // Client-side validation guard
    (function(){
      const form = document.getElementById('profile-form');
      form.addEventListener('submit', function(e){
        // Enforce Indian number: 10 digits starting 6-9
        const phone = document.getElementById('phone').value.replace(/\D/g,'');
        if (!/^[6-9]\d{9}$/.test(phone)) {
          e.preventDefault();
          alert('Enter a valid 10-digit Indian mobile number starting with 6-9.');
          return false;
        }
      });
    })();
    document.getElementById("logout-link").addEventListener("click", function (event) {
      event.preventDefault();
      if (confirm("Are you sure you want to logout?")) {
        window.location.href = "../logout.php";
      }
    });

    // Discard button functionality - resets form fields to original values loaded on page
    const discardBtn = document.getElementById("discard-btn");
    const profileForm = document.getElementById("profile-form");

    // Store the original values when the page loads
    const originalValues = {
      name: profileForm.name.value,
      email: profileForm.email.value,
      phone: profileForm.phone.value,
      address: profileForm.address.value,
    };

    discardBtn.addEventListener("click", function () {
      profileForm.name.value = originalValues.name;
      profileForm.email.value = originalValues.email;
      profileForm.phone.value = originalValues.phone;
      profileForm.address.value = originalValues.address;
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
