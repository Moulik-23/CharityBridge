<?php
session_start();

// Redirect to login if NGO is not logged in
if (!isset($_SESSION['ngo_id'])) {
    header("Location: login.php");
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

// Fetch volunteers who applied for this NGO
$sql = "SELECT v.id, v.fname, v.lname, v.email, v.phone, v.skills, v.availability, 
               m.status, m.created_at 
        FROM volunteers v
        JOIN volunteer_ngo_map m ON v.id = m.volunteer_id
        WHERE m.ngo_id = ?
        ORDER BY m.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error); // debug if query fails
}
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$result = $stmt->get_result();
$volunteers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Volunteers - CharityBridge</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light-color text-text-dark">

<header class="bg-white shadow-md py-4 px-6">
  <div class="container mx-auto flex justify-between items-center">
    <a href="dashboard.php" class="text-3xl font-bold text-primary-color">CharityBridge - NGO</a>
    <nav class="hidden md:flex">
      <a href="dashboard.php" class="px-3 py-2">Dashboard</a>
      <a href="requirements.php" class="px-3 py-2">Post Requirement</a>
      <a href="donations.php" class="px-3 py-2">Manage Donations</a>
      <a href="volunteers.php" class="px-3 py-2 font-bold text-secondary-color">Volunteers</a>
      <a href="../logout.php" class="px-3 py-2">Logout</a>
    </nav>
  </div>
</header>

<main class="py-16">
  <div class="container mx-auto px-6">
    <h1 class="text-3xl font-bold mb-6 text-primary-color">Manage Volunteers</h1>

    <div class="overflow-x-auto bg-white rounded-xl shadow-lg">
      <table class="min-w-full text-left">
        <thead>
          <tr class="bg-primary-color text-white">
            <th class="py-3 px-4">Name</th>
            <th class="py-3 px-4">Email</th>
            <th class="py-3 px-4">Phone</th>
            <th class="py-3 px-4">Skills</th>
            <th class="py-3 px-4">Availability</th>
            <th class="py-3 px-4">Status</th>
            <th class="py-3 px-4">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($volunteers) > 0): ?>
            <?php foreach ($volunteers as $volunteer): ?>
              <tr class="border-b hover:bg-gray-100">
                <td class="py-2 px-4"><?= htmlspecialchars($volunteer['fname'] . " " . $volunteer['lname']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($volunteer['email']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($volunteer['phone']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($volunteer['skills']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($volunteer['availability']) ?></td>
                <td class="py-2 px-4">
                  <?php if ($volunteer['status'] == 'Approved'): ?>
                    <span class="text-green-600 font-bold">Approved</span>
                  <?php else: ?>
                    <span class="text-yellow-600 font-bold">Pending</span>
                  <?php endif; ?>
                </td>
                <td class="py-2 px-4">
                  <?php if ($volunteer['status'] != 'Approved'): ?>
                    <a href="backend/approve_volunteer.php?id=<?= $volunteer['id'] ?>" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">Approve</a>
                  <?php endif; ?>
                  <a href="backend/delete_volunteer.php?id=<?= $volunteer['id'] ?>" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Remove</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center py-4 text-gray-500">No volunteers registered yet.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<footer class="bg-dark-color text-white py-6 text-center">
  <p>&copy; 2025 CharityBridge. All rights reserved.</p>
</footer>

</body>
</html>
