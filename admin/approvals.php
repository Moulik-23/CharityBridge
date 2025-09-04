<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>NGO Approvals - CharityBridge</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/charitybridge/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">

  <!-- Header -->
  <header class="bg-white shadow">
    <div class="container mx-auto px-6 py-4">
      <div class="flex items-center justify-between">
        <div>
          <a href="../index.html" class="text-2xl font-bold text-gray-800">CharityBridge - Admin</a>
        </div>
        <div class="flex items-center">
          <nav class="hidden md:flex">
            <a href="/charitybridge/admin/dashboard.php" class="text-gray-800 hover:text-blue-500 px-3 py-2">Dashboard</a>
            <a href="approvals.php" class="text-gray-800 hover:text-blue-500 px-3 py-2">Approvals</a>
            <a href="../index.html" class="text-gray-800 hover:text-blue-500 px-3 py-2">Logout</a>
          </nav>
        </div>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <main class="py-16">
    <div class="container mx-auto px-6">
      <h1 class="text-4xl font-bold mb-8">NGO Approval Queue</h1>

      <div class="bg-white p-8 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-6 flex items-center">
          <i class="fas fa-hands-helping mr-3 text-blue-500"></i> Pending NGO Registrations
        </h2>

        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-gray-50">
              <tr>
                <th class="py-3 px-4">NGO Name</th>
                <th class="py-3 px-4">Email</th>
                <th class="py-3 px-4">Darpan ID</th>
                <th class="py-3 px-4">Date Submitted</th>
                <th class="py-3 px-4">Certificate</th>
                <th class="py-3 px-4 text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $conn = new mysqli("localhost", "root", "", "charitybridge");
                if ($conn->connect_error) {
                  die("Connection failed: " . $conn->connect_error);
                }

                $sql = "SELECT id, name, email, darpan_id, created_at, certificate FROM ngos WHERE status='pending'";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                    echo "<tr id='ngo-row-{$row['id']}' class='border-b hover:bg-gray-50'>";
                    echo "<td class='py-3 px-4'>{$row['name']}</td>";
                    echo "<td class='py-3 px-4'>{$row['email']}</td>";
                    echo "<td class='py-3 px-4'>{$row['darpan_id']}</td>";
                    echo "<td class='py-3 px-4'>{$row['created_at']}</td>";
                    $certificate = $row['certificate'];
                    // Check if the certificate is a filename (printable string with an extension)
                    if ($certificate && ctype_print($certificate) && strpos($certificate, '.') !== false) {
                        // New file-based certificate: provide a download link
                        echo "<td class='py-3 px-4'><a href='../ngo/certificates/{$certificate}' download class='text-blue-600 hover:underline'><i class='fas fa-download mr-2'></i>Download</a></td>";
                    } elseif ($certificate) {
                        // Old BLOB-based certificate: provide a download link to the script
                        echo "<td class='py-3 px-4'><a href='../ngo/view_certificate.php?id={$row['id']}' class='text-blue-600 hover:underline'><i class='fas fa-download mr-2'></i>Download</a></td>";
                    } else {
                        // No certificate data
                        echo "<td class='py-3 px-4 text-gray-500'>No Certificate</td>";
                    }
                    echo "<td class='py-3 px-4 text-center'>
                            <button onclick=\"approveNgo({$row['id']}, document.getElementById('ngo-row-{$row['id']}'))\" class='bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600'>
                              <i class='fas fa-check mr-2'></i>Approve
                            </button>
                            <button onclick=\"rejectNgo({$row['id']}, document.getElementById('ngo-row-{$row['id']}'))\" class='bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 ml-2'>
                              <i class='fas fa-times mr-2'></i>Reject
                            </button>
                          </td>";
                    echo "</tr>";
                  }
                } else {
                  echo "<tr><td colspan='6' class='text-center py-4'>No pending NGOs</td></tr>";
                }

                $conn->close();
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-800 text-white py-8">
    <div class="container mx-auto text-center">
      <p>&copy; 2025 CharityBridge. All rights reserved.</p>
    </div>
  </footer>

  <!-- JS -->
  <script src="/charitybridge/js/admin.js"></script>
</body>
</html>
