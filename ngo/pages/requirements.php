<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['ngo_id'])) {
    header("Location: ../auth/login.html");
    exit();
}

$servername = "localhost";
$username   = "root";
$password   = ""; 
$dbname     = "charitybridge";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$ngo_id = $_SESSION['ngo_id'];
$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if (!empty($title) && !empty($description)) {
        $stmt = $conn->prepare("INSERT INTO requirements (ngo_id, title, description) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $ngo_id, $title, $description);

        if ($stmt->execute()) {
            $message = "✅ Requirement posted successfully!";
        } else {
            $message = "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "⚠️ Please fill in all fields.";
    }
}

// Fetch NGO requirements
$stmt = $conn->prepare("SELECT * FROM requirements WHERE ngo_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$result = $stmt->get_result();
$requirements = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Post Requirement - CharityBridge</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../css/style.css">
</head>
<body class="bg-light-color text-text-dark">

<header class="bg-white shadow-md py-4 px-6">
  <div class="container mx-auto flex justify-between items-center">
    <a href="dashboard.php" class="text-3xl font-bold text-primary-color">CharityBridge - NGO</a>
    <nav class="hidden md:flex">
      <a href="dashboard.php" class="px-3 py-2">Dashboard</a>
      <a href="requirements.php" class="px-3 py-2 font-bold text-secondary-color">Post Requirement</a>
      <a href="donations.php" class="px-3 py-2">Manage Donations</a>
      <a href="volunteers.php" class="px-3 py-2">Volunteers</a>
      <a href="manage_profile.html" class="px-3 py-2">Manage Profile</a>
      <a href="../backend/logout.php" class="px-3 py-2">Logout</a>
    </nav>
  </div>
</header>

<main class="py-16">
  <div class="container mx-auto px-6">
    <h1 class="text-3xl font-bold mb-6 text-primary-color">Post a New Requirement</h1>

    <?php if ($message): ?>
      <p class="mb-4 text-center font-semibold text-red-500"><?= $message ?></p>
    <?php endif; ?>

    <!-- Requirement Form -->
    <form method="POST" class="bg-white shadow-lg rounded-xl p-6 mb-10">
      <div class="mb-4">
        <label class="block text-gray-700 font-semibold">Title</label>
        <input type="text" name="title" class="w-full border rounded-lg px-4 py-2" placeholder="e.g. Need blankets for winter" required>
      </div>
      <div class="mb-4">
        <label class="block text-gray-700 font-semibold">Description</label>
        <textarea name="description" class="w-full border rounded-lg px-4 py-2" rows="4" placeholder="Provide detailed description..." required></textarea>
      </div>
      <button type="submit" class="btn btn-primary px-6 py-2 rounded-lg">Post Requirement</button>
    </form>

    <!-- List of Requirements -->
    <h2 class="text-2xl font-bold mb-4 text-primary-color">Your Posted Requirements</h2>
    <div class="grid gap-4" id="requirements-list">
      <?php if (count($requirements) > 0): ?>
        <?php foreach ($requirements as $req): ?>
          <div class="p-4 bg-white rounded-lg shadow" id="requirement-<?= $req['id'] ?>">
            <div class="flex justify-between items-start">
              <div>
                <h3 class="font-bold text-lg"><?= htmlspecialchars($req['title']) ?></h3>
                <p class="text-gray-600"><?= htmlspecialchars($req['description']) ?></p>
                <p class="text-sm text-gray-400">Posted on <?= $req['created_at'] ?></p>
              </div>
              <button onclick="deleteRequirement(<?= $req['id'] ?>)" class="text-red-500 hover:text-red-700 font-semibold">
                <i class="fas fa-trash-alt"></i> Delete
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-gray-600" id="no-requirements-message">No requirements posted yet.</p>
      <?php endif; ?>
    </div>
  </div>
</main>

<footer class="bg-dark-color text-white py-6 text-center">
  <p>&copy; 2025 CharityBridge. All rights reserved.</p>
</footer>

<script>
  async function deleteRequirement(id) {
    if (!confirm("Are you sure you want to delete this requirement?")) {
      return;
    }

    try {
      const response = await fetch('../backend/delete_requirement.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
      });

      if (response.ok) {
        const result = await response.json();
        if (result.success) {
          // Remove the requirement from the DOM
          const requirementElement = document.getElementById('requirement-' + id);
          if (requirementElement) {
            requirementElement.remove();
          }

          // Check if there are any requirements left
          const requirementsList = document.getElementById('requirements-list');
          if (requirementsList.children.length === 1 && requirementsList.children[0].id === 'no-requirements-message') {
            // This was the last one, show the "no requirements" message
          } else if (requirementsList.children.length === 0) {
             const noReqMessage = document.createElement('p');
             noReqMessage.className = 'text-gray-600';
             noReqMessage.id = 'no-requirements-message';
             noReqMessage.textContent = 'No requirements posted yet.';
             requirementsList.appendChild(noReqMessage);
          }
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
</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</body>
</html>
