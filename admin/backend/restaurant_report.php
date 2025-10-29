<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { die('Invalid restaurant id'); }

$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) { die('DB error'); }

$stmt = $conn->prepare("SELECT id, restaurant_name, owner_name, email, phone, address, pincode, fssai_license, restaurant_type, license_document, created_at FROM restaurants WHERE id=?");
$stmt->bind_param('i',$id);
$stmt->execute();
$result = $stmt->get_result();
$rest = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$rest) { die('Restaurant not found'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restaurant Report - <?php echo htmlspecialchars($rest['restaurant_name']); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
  <div class="max-w-4xl mx-auto bg-white shadow rounded-lg p-8 my-8">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">Restaurant Registration Report</h1>
      <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded">Print / Save PDF</button>
    </div>
    <hr class="my-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <p class="text-sm text-gray-500">Restaurant Name</p>
        <p class="font-semibold"><?php echo htmlspecialchars($rest['restaurant_name']); ?></p>
      </div>
      <div>
        <p class="text-sm text-gray-500">Owner Name</p>
        <p class="font-semibold"><?php echo htmlspecialchars($rest['owner_name']); ?></p>
      </div>
      <div>
        <p class="text-sm text-gray-500">Email</p>
        <p class="font-semibold"><?php echo htmlspecialchars($rest['email']); ?></p>
      </div>
      <div>
        <p class="text-sm text-gray-500">Phone</p>
        <p class="font-semibold"><?php echo htmlspecialchars($rest['phone']); ?></p>
      </div>
      <div>
        <p class="text-sm text-gray-500">Restaurant Type</p>
        <p class="font-semibold"><?php echo htmlspecialchars($rest['restaurant_type'] ?? ''); ?></p>
      </div>
      <div class="md:col-span-2">
        <p class="text-sm text-gray-500">Address</p>
        <p class="font-semibold"><?php echo htmlspecialchars($rest['address']); ?></p>
      </div>
      <div>
        <p class="text-sm text-gray-500">Pincode</p>
        <p class="font-semibold"><?php echo htmlspecialchars($rest['pincode']); ?></p>
      </div>
      <div>
        <p class="text-sm text-gray-500">FSSAI License</p>
        <p class="font-semibold"><?php echo htmlspecialchars($rest['fssai_license']); ?></p>
      </div>
      <div>
        <p class="text-sm text-gray-500">Date of Registration</p>
        <p class="font-semibold"><?php echo htmlspecialchars($rest['created_at']); ?></p>
      </div>
    </div>

    <?php $docFile = $rest['license_document'] ?? ''; if ($docFile && ctype_print($docFile) && strpos($docFile,'.') !== false): ?>
    <div class="mt-6">
      <p class="text-sm text-gray-500 mb-2">License Document</p>
      <img src="<?php echo '../../restaurant/pge/uploads/'.rawurlencode($docFile); ?>" alt="License Document" class="max-h-96 border rounded" />
    </div>
    <?php endif; ?>
  </div>
</body>
</html>


