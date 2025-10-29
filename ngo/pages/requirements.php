<?php
session_start();
if (!isset($_SESSION['ngo_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$ngo_id = $_SESSION['ngo_id'];
$message = "";

// Handle submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST['type'];

    if ($type == "volunteer") {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $skills = $_POST['skills'];
        $slots = intval($_POST['slots']);
        $event_date = $_POST['event_date'];
        $location = $_POST['location'];

        $sql = "INSERT INTO volunteer_requirements (ngo_id, title, description, required_skills, slots, event_date, location) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssiss", $ngo_id, $title, $description, $skills, $slots, $event_date, $location);
        if ($stmt->execute()) {
            $message = "✅ Volunteer requirement posted!";
        } else {
            $message = "❌ Error: " . $stmt->error;
        }
        $stmt->close();

    } elseif ($type == "general") {
        $title = $_POST['title'];
        $description = $_POST['description'];

        $sql = "INSERT INTO requirements (ngo_id, title, description) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $ngo_id, $title, $description);
        if ($stmt->execute()) {
            $message = "✅ General requirement posted!";
        } else {
            $message = "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch NGO requirements with proper error handling
$volunteer_reqs = [];
$general_reqs = [];

$volunteer_result = $conn->query("SELECT * FROM volunteer_requirements WHERE ngo_id=$ngo_id ORDER BY created_at DESC");
if ($volunteer_result) {
    $volunteer_reqs = $volunteer_result->fetch_all(MYSQLI_ASSOC);
}

$general_result = $conn->query("SELECT * FROM requirements WHERE ngo_id=$ngo_id ORDER BY created_at DESC");
if ($general_result) {
    $general_reqs = $general_result->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Post Requirement - CharityBridge</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../../css/dashboard.css">
</head>
<body>

<!-- Mobile Header with Hamburger -->
<div class="mobile-header">
  <div class="mobile-logo">
    <i class="fas fa-hands-helping"></i>
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
    <i class="fas fa-hands-helping"></i>
    <span>CharityBridge</span>
  </div>
  <nav class="nav">
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="requirements.php" class="active"><i class="fas fa-bullhorn"></i> Post Requirement</a>
    <a href="donations.php"><i class="fas fa-gift"></i> Manage Donations</a>
    <a href="volunteers.php"><i class="fas fa-users"></i> Volunteers</a>
    <a href="manage_profile.php"><i class="fas fa-user"></i> Manage Profile</a>
    <a href="../backend/logout.php" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
</div>

<!-- Main Content -->
<div class="main-content">
  <div class="header">
    <h1>Post a Requirement</h1>
    <div class="user-info">
      <i class="fas fa-user-circle"></i>
      <span>NGO User</span>
    </div>
  </div>

    <?php if ($message): ?>
      <div class="alert alert-success" style="margin: 1rem 0;">
        <i class="fas fa-check-circle mr-2"></i>
        <?= $message ?>
      </div>
    <?php endif; ?>

    <!-- Requirement Type Selection -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-plus-circle"></i> Post New Requirement</h2>
        </div>
        <div style="padding: 24px;">
            <p class="text-gray-600 mb-6">Choose the type of requirement you want to post.</p>
            
            <div class="flex flex-col sm:flex-row gap-4">
                <button id="volunteerTab" onclick="showVolunteerForm()" class="action-btn primary flex-1 text-center py-3 px-6">
                    <i class="fas fa-users mr-2"></i>
                    Volunteer Requirement
                </button>
                <button id="generalTab" onclick="showGeneralForm()" class="action-btn secondary flex-1 text-center py-3 px-6">
                    <i class="fas fa-gift mr-2"></i>
                    General Requirement
                </button>
            </div>
        </div>
    </div>

    <!-- Volunteer Requirement Form -->
    <div id="volunteerFormSection" class="requirement-section">
        <div class="card" style="max-width: 900px; margin: 0 auto;">
        <div class="card-header">
            <h2><i class="fas fa-users"></i> Post Volunteer Requirement</h2>
        </div>
        <div style="padding: 24px;">
        <form method="POST">
            <input type="hidden" name="type" value="volunteer">
            <div class="form-group">
                <label for="volunteer_title">Title</label>
                <input type="text" name="title" id="volunteer_title" required>
            </div>
            <div class="form-group">
                <label for="volunteer_description">Description</label>
                <textarea name="description" id="volunteer_description" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label for="volunteer_skills">Required Skills</label>
                <input type="text" name="skills" id="volunteer_skills" placeholder="e.g., Communication, Leadership">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="volunteer_slots">Number of Slots</label>
                    <input type="number" name="slots" id="volunteer_slots" min="1" required>
                </div>
                <div class="form-group">
                    <label for="volunteer_event_date">Event Date</label>
                    <input type="date" name="event_date" id="volunteer_event_date" required>
                </div>
            </div>
            <div class="form-group">
                <label for="volunteer_location">Location</label>
                <input type="text" name="location" id="volunteer_location" placeholder="e.g., Community Center, Surat">
            </div>

            <button type="submit" class="action-btn primary" style="width: 100%; margin-top: 1rem;">
                <i class="fas fa-plus mr-2"></i> Post Volunteer Requirement
            </button>
        </form>
        </div>
        </div>
    </div>

    <!-- General Requirement Form -->
    <div id="generalFormSection" class="requirement-section" style="display: none;">
        <div class="card" style="max-width: 900px; margin: 0 auto;">
        <div class="card-header">
            <h2><i class="fas fa-gift"></i> Post General Requirement</h2>
        </div>
        <div style="padding: 24px;">
        <form method="POST">
            <input type="hidden" name="type" value="general">
            <div class="form-group">
                <label for="general_title">Title</label>
                <input type="text" name="title" id="general_title" required>
            </div>
      <div class="form-group">
                <label for="general_description">Description</label>
                <textarea name="description" id="general_description" rows="4" required></textarea>
            </div>

            <button type="submit" class="action-btn primary" style="width: 100%; margin-top: 1rem;">
                <i class="fas fa-plus mr-2"></i> Post General Requirement
            </button>
        </form>
        </div>
        </div>
    </div>

    <!-- Separator -->
    <div class="my-12">
        <hr class="border-gray-300">
        <div class="text-center mt-4">
            <h2 class="text-2xl font-bold text-primary-color">Your Posted Requirements</h2>
            <p class="text-gray-600 mt-2">Manage your existing requirements below</p>
        </div>
    </div>

    <!-- Posted Requirements -->
    <div style="margin-top: 2rem;">
      <!-- Volunteer Requirements Section -->
      <div class="card" style="margin-bottom: 2rem;">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-primary-color flex items-center">
            <i class="fas fa-users mr-3 text-secondary-color"></i>
            Volunteer Requirements
            <span class="ml-3 bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded-full">
              <?= count($volunteer_reqs) ?>
            </span>
          </h2>
        </div>
        
        <?php if (empty($volunteer_reqs)): ?>
          <div class="text-center py-8 text-gray-600">
            <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
            <p class="text-lg">No volunteer requirements posted yet</p>
            <p class="text-sm">Post your first volunteer requirement above</p>
          </div>
        <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($volunteer_reqs as $req): ?>
              <div class="bg-light-color p-6 rounded-lg border border-gray-light hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-4">
                  <h3 class="text-lg font-semibold text-primary-color"><?= htmlspecialchars($req['title']) ?></h3>
                  <div class="flex space-x-2">
                    <button onclick="editVolunteerReq(<?= $req['req_id'] ?>)" class="text-blue-600 hover:text-blue-800" title="Edit">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteVolunteerReq(<?= $req['req_id'] ?>)" class="text-red-600 hover:text-red-800" title="Delete">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>
                
                <p class="text-gray-700 mb-4"><?= htmlspecialchars($req['description']) ?></p>
                
                <div class="space-y-2 text-sm">
                  <div class="flex items-center">
                    <i class="fas fa-user-friends text-blue-500 mr-2"></i>
                    <span class="font-medium"><?= $req['slots'] ?> slots available</span>
                  </div>
                  
                  <?php if (!empty($req['required_skills'])): ?>
                  <div class="flex items-center">
                    <i class="fas fa-tools text-green-500 mr-2"></i>
                    <span class="font-medium">Skills: <?= htmlspecialchars($req['required_skills']) ?></span>
                  </div>
                  <?php endif; ?>
                  
                  <div class="flex items-center">
                    <i class="fas fa-calendar text-purple-500 mr-2"></i>
                    <span class="font-medium"><?= date('M j, Y', strtotime($req['event_date'])) ?></span>
                  </div>
                  
                  <?php if (!empty($req['location'])): ?>
                  <div class="flex items-center">
                    <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                    <span class="font-medium"><?= htmlspecialchars($req['location']) ?></span>
                  </div>
                  <?php endif; ?>
                  
                  <div class="flex items-center text-gray-500">
                    <i class="fas fa-clock mr-2"></i>
                    <span>Posted <?= date('M j, Y', strtotime($req['created_at'])) ?></span>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- General Requirements Section -->
      <div class="card">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-primary-color flex items-center">
            <i class="fas fa-gift mr-3 text-accent-color"></i>
            General Requirements
            <span class="ml-3 bg-green-100 text-green-800 text-sm font-medium px-2.5 py-0.5 rounded-full">
              <?= count($general_reqs) ?>
            </span>
          </h2>
        </div>
        
        <?php if (empty($general_reqs)): ?>
          <div class="text-center py-8 text-gray-600">
            <i class="fas fa-gift text-4xl text-gray-300 mb-4"></i>
            <p class="text-lg">No general requirements posted yet</p>
            <p class="text-sm">Post your first general requirement above</p>
          </div>
        <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($general_reqs as $req): ?>
              <div class="bg-light-color p-6 rounded-lg border border-gray-light hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-4">
                  <h3 class="text-lg font-semibold text-primary-color"><?= htmlspecialchars($req['title']) ?></h3>
                  <div class="flex space-x-2">
                    <button onclick="editGeneralReq(<?= $req['id'] ?>)" class="text-blue-600 hover:text-blue-800" title="Edit">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteGeneralReq(<?= $req['id'] ?>)" class="text-red-600 hover:text-red-800" title="Delete">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>
                
                <p class="text-gray-700 mb-4"><?= htmlspecialchars($req['description']) ?></p>
                
                <div class="flex items-center text-gray-500 text-sm">
                  <i class="fas fa-clock mr-2"></i>
                  <span>Posted <?= date('M j, Y', strtotime($req['created_at'])) ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Edit Volunteer Requirement Modal -->
<div id="editVolunteerModal" class="fixed inset-0 bg-black bg-opacity-50 z-50" style="display: none; overflow-y: auto; padding: 2rem;">
  <div class="bg-white rounded-xl shadow-2xl mx-auto my-8" style="max-width: 42rem; width: 100%; max-height: calc(100vh - 4rem); overflow-y: auto;">
    <div class="p-6">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-bold text-primary-color">Edit Volunteer Requirement</h3>
        <button onclick="closeEditVolunteerModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form id="editVolunteerForm" class="space-y-4">
        <input type="hidden" id="edit_volunteer_req_id" name="req_id">
        
        <div class="form-group">
          <label for="edit_volunteer_title">Title</label>
          <input type="text" name="title" id="edit_volunteer_title" required>
        </div>
        
        <div class="form-group">
          <label for="edit_volunteer_description">Description</label>
          <textarea name="description" id="edit_volunteer_description" rows="3" required></textarea>
        </div>
        
        <div class="form-group">
          <label for="edit_volunteer_skills">Required Skills</label>
          <input type="text" name="skills" id="edit_volunteer_skills" placeholder="e.g., Communication, Leadership">
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="form-group">
            <label for="edit_volunteer_slots">Number of Slots</label>
            <input type="number" name="slots" id="edit_volunteer_slots" min="1" required>
          </div>
          
        <div class="form-group">
            <label for="edit_volunteer_event_date">Event Date</label>
            <input type="date" name="event_date" id="edit_volunteer_event_date" required>
          </div>
        </div>
        
        <div class="form-group">
          <label for="edit_volunteer_location">Location</label>
          <input type="text" name="location" id="edit_volunteer_location" placeholder="e.g., Community Center, Surat">
        </div>
        
        <div class="flex justify-end space-x-4 pt-4">
          <button type="button" onclick="closeEditVolunteerModal()" class="btn bg-gray-100 text-gray-700 hover:bg-gray-200">
            Cancel
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-2"></i> Update Requirement
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit General Requirement Modal -->
<div id="editGeneralModal" class="fixed inset-0 bg-black bg-opacity-50 z-50" style="display: none; overflow-y: auto; padding: 2rem;">
  <div class="bg-white rounded-xl shadow-2xl mx-auto my-8" style="max-width: 42rem; width: 100%; max-height: calc(100vh - 4rem); overflow-y: auto;">
    <div class="p-6">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-bold text-primary-color">Edit General Requirement</h3>
        <button onclick="closeEditGeneralModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form id="editGeneralForm" class="space-y-4">
        <input type="hidden" id="edit_general_req_id" name="id">
        
        <div class="form-group">
          <label for="edit_general_title">Title</label>
          <input type="text" name="title" id="edit_general_title" required>
        </div>
        
        <div class="form-group">
          <label for="edit_general_description">Description</label>
          <textarea name="description" id="edit_general_description" rows="4" required></textarea>
        </div>
        
        <div class="flex justify-end space-x-4 pt-4">
          <button type="button" onclick="closeEditGeneralModal()" class="btn bg-gray-100 text-gray-700 hover:bg-gray-200">
            Cancel
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-2"></i> Update Requirement
          </button>
      </div>
    </form>
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
    link.addEventListener('click', (e) => {
      if (window.innerWidth <= 768) {
        toggleMenu();
      }
      // Logout confirmation
      if (link.id === 'logoutBtn' && !confirm("Are you sure you want to logout?")) {
        e.preventDefault();
      }
    });
  });

  // Tab switching functions
  function showVolunteerForm() {
    // Hide general form section
    document.getElementById('generalFormSection').style.display = 'none';
    // Show volunteer form section
    document.getElementById('volunteerFormSection').style.display = 'block';
    
    // Update button styles
    document.getElementById('volunteerTab').className = 'action-btn primary flex-1 text-center py-3 px-6';
    document.getElementById('generalTab').className = 'action-btn secondary flex-1 text-center py-3 px-6';
  }

  function showGeneralForm() {
    // Hide volunteer form section
    document.getElementById('volunteerFormSection').style.display = 'none';
    // Show general form section
    document.getElementById('generalFormSection').style.display = 'block';
    
    // Update button styles
    document.getElementById('generalTab').className = 'action-btn primary flex-1 text-center py-3 px-6';
    document.getElementById('volunteerTab').className = 'action-btn secondary flex-1 text-center py-3 px-6';
  }

  document.addEventListener('DOMContentLoaded', function() {
    // Initialize with volunteer form shown by default
    showVolunteerForm();
  });

  // Delete Volunteer Requirement
  async function deleteVolunteerReq(reqId) {
    if (!confirm('Are you sure you want to delete this volunteer requirement?')) {
      return;
    }

    try {
      const response = await fetch('../backend/delete_volunteer_requirement.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ req_id: reqId })
      });

      if (response.ok) {
        const result = await response.json();
        if (result.success) {
          location.reload();
        } else {
          alert('Error: ' + (result.error || 'Could not delete the requirement.'));
        }
      } else {
        alert('An error occurred while trying to delete the requirement.');
      }
    } catch (error) {
      console.error('Deletion error:', error);
      alert('A network error occurred. Please try again.');
    }
  }

  // Delete General Requirement
  async function deleteGeneralReq(reqId) {
    if (!confirm('Are you sure you want to delete this general requirement?')) {
      return;
    }

    try {
      const response = await fetch('../backend/delete_requirement.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: reqId })
      });

      if (response.ok) {
        const result = await response.json();
        if (result.success) {
          location.reload();
        } else {
          alert('Error: ' + (result.error || 'Could not delete the requirement.'));
        }
      } else {
        alert('An error occurred while trying to delete the requirement.');
      }
    } catch (error) {
      console.error('Deletion error:', error);
      alert('A network error occurred. Please try again.');
    }
  }

  // Edit Volunteer Requirement
  async function editVolunteerReq(reqId) {
    try {
      // Fetch requirement data
      const response = await fetch(`../backend/get_volunteer_requirement.php?req_id=${reqId}`);
      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          // Populate form with existing data
          document.getElementById('edit_volunteer_req_id').value = data.requirement.req_id;
          document.getElementById('edit_volunteer_title').value = data.requirement.title;
          document.getElementById('edit_volunteer_description').value = data.requirement.description;
          document.getElementById('edit_volunteer_skills').value = data.requirement.required_skills;
          document.getElementById('edit_volunteer_slots').value = data.requirement.slots;
          document.getElementById('edit_volunteer_event_date').value = data.requirement.event_date;
          document.getElementById('edit_volunteer_location').value = data.requirement.location;
          
          // Show modal
          document.getElementById('editVolunteerModal').style.display = 'flex';
        } else {
          alert('Error: ' + data.error);
        }
      } else {
        alert('Failed to fetch requirement data');
      }
    } catch (error) {
      console.error('Error:', error);
      alert('An error occurred while fetching requirement data');
    }
  }

  // Edit General Requirement
  async function editGeneralReq(reqId) {
    try {
      // Fetch requirement data
      const response = await fetch(`../backend/get_requirement.php?id=${reqId}`);
      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          // Populate form with existing data
          document.getElementById('edit_general_req_id').value = data.requirement.id;
          document.getElementById('edit_general_title').value = data.requirement.title;
          document.getElementById('edit_general_description').value = data.requirement.description;
          
          // Show modal
          document.getElementById('editGeneralModal').style.display = 'flex';
        } else {
          alert('Error: ' + data.error);
        }
      } else {
        alert('Failed to fetch requirement data');
      }
    } catch (error) {
      console.error('Error:', error);
      alert('An error occurred while fetching requirement data');
    }
  }

  // Close Volunteer Edit Modal
  function closeEditVolunteerModal() {
    document.getElementById('editVolunteerModal').style.display = 'none';
  }

  // Close General Edit Modal
  function closeEditGeneralModal() {
    document.getElementById('editGeneralModal').style.display = 'none';
  }

  // Handle Volunteer Edit Form Submission
  document.getElementById('editVolunteerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    try {
      const response = await fetch('../backend/update_volunteer_requirement.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      });
      
      if (response.ok) {
        const result = await response.json();
        if (result.success) {
          closeEditVolunteerModal();
          location.reload();
        } else {
          alert('Error: ' + (result.error || 'Failed to update requirement'));
        }
      } else {
        alert('An error occurred while updating the requirement');
      }
    } catch (error) {
      console.error('Update error:', error);
      alert('A network error occurred. Please try again.');
    }
  });

  // Handle General Edit Form Submission
  document.getElementById('editGeneralForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    try {
      const response = await fetch('../backend/update_requirement.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      });
      
      if (response.ok) {
        const result = await response.json();
        if (result.success) {
          closeEditGeneralModal();
          location.reload();
        } else {
          alert('Error: ' + (result.error || 'Failed to update requirement'));
        }
      } else {
        alert('An error occurred while updating the requirement');
      }
    } catch (error) {
      console.error('Update error:', error);
      alert('A network error occurred. Please try again.');
    }
  });

  // Close modals when clicking outside
  document.getElementById('editVolunteerModal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeEditVolunteerModal();
    }
  });

  document.getElementById('editGeneralModal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeEditGeneralModal();
    }
  });
</script>
</body>
</html>
