<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { die('Invalid NGO id'); }

$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) { die('DB error'); }

// Build a safe SELECT using only existing columns to avoid prepare() failure
$desiredColumns = ['id','name','email','mobile','org_pan','reg_number','ngo_type','state','district','darpan_id','owner_pan','owner_name','address','phone','acc_no','ifsc_code','created_at','certificate'];
$existingColumns = [];
if ($colsRes = $conn->query("SHOW COLUMNS FROM ngos")) {
  while ($col = $colsRes->fetch_assoc()) {
    $existingColumns[] = $col['Field'];
  }
  $colsRes->close();
}
$selectCols = array_values(array_intersect($desiredColumns, $existingColumns));
if (empty($selectCols)) { $selectCols = ['id','name','email']; }
$sql = "SELECT ".implode(", ", $selectCols)." FROM ngos WHERE id=?";
$stmt = $conn->prepare($sql);
if ($stmt) {
  $stmt->bind_param('i',$id);
  $stmt->execute();
  $result = $stmt->get_result();
  $ngo = $result ? $result->fetch_assoc() : null;
  $stmt->close();
} else {
  $ngo = null;
}
$conn->close();

if (!$ngo) { die('NGO not found'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NGO Report - <?php echo htmlspecialchars($ngo['name']); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
  <div class="max-w-4xl mx-auto bg-white shadow rounded-lg p-8 my-8">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">NGO Registration Report</h1>
      <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded">Print / Save PDF</button>
    </div>
    <hr class="my-4">
    <h2 class="text-lg font-semibold mb-2">Basic Information</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <div>
        <p class="text-sm text-gray-500">NGO Name</p>
        <p class="font-semibold"><?php echo htmlspecialchars($ngo['name']); ?></p>
      </div>
      <div>
        <p class="text-sm text-gray-500">Email</p>
        <p class="font-semibold"><?php echo htmlspecialchars($ngo['email']); ?></p>
      </div>
      <div>
        <p class="text-sm text-gray-500">Mobile</p>
        <p class="font-semibold"><?php echo htmlspecialchars($ngo['mobile'] ?? ''); ?></p>
      </div>
      <div>
        <p class="text-sm text-gray-500">Date of Registration</p>
        <p class="font-semibold"><?php echo htmlspecialchars($ngo['created_at']); ?></p>
      </div>
    </div>

    <h2 class="text-lg font-semibold mb-2">Registration Details</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <div>
        <p class="text-sm text-gray-500">Darpan ID</p>
        <p class="font-semibold"><?php echo htmlspecialchars($ngo['darpan_id']); ?></p>
      </div>
      <div>
        <p class="text-sm text-gray-500">NGO Type</p>
        <p class="font-semibold"><?php echo htmlspecialchars($ngo['ngo_type'] ?? ''); ?></p>
      </div>
      <div>
        <p class="text-sm text-gray-500">Organization PAN</p>
        <p class="font-semibold"><?php echo htmlspecialchars($ngo['org_pan'] ?? ''); ?></p>
      </div>
      <div>
        <p class="text-sm text-gray-500">Registration Number</p>
        <p class="font-semibold"><?php echo htmlspecialchars($ngo['reg_number'] ?? ''); ?></p>
      </div>
      <div>
        <p class="text-sm text-gray-500">State</p>
        <p class="font-semibold"><?php echo htmlspecialchars($ngo['state'] ?? ''); ?></p>
      </div>
      <div>
        <p class="text-sm text-gray-500">District</p>
        <p class="font-semibold"><?php echo htmlspecialchars($ngo['district'] ?? ''); ?></p>
      </div>
      <div class="md:col-span-2">
        <p class="text-sm text-gray-500">Address</p>
        <p class="font-semibold"><?php echo htmlspecialchars($ngo['address']); ?></p>
      </div>
    </div>

    <h2 class="text-lg font-semibold mb-2">Owner Details</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <div>
        <p class="text-sm text-gray-500">Owner Name (as per PAN)</p>
        <p class="font-semibold"><?php echo htmlspecialchars($ngo['owner_name'] ?? ''); ?></p>
      </div>
      <div>
        <p class="text-sm text-gray-500">Owner PAN</p>
        <p class="font-semibold"><?php echo htmlspecialchars($ngo['owner_pan'] ?? ''); ?></p>
      </div>
    </div>

    <h2 class="text-lg font-semibold mb-2">Banking Information</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <div>
        <p class="text-sm text-gray-500">Account Number</p>
        <p class="font-semibold"><?php echo htmlspecialchars($ngo['acc_no'] ?? ''); ?></p>
      </div>
      <div>
        <p class="text-sm text-gray-500">IFSC Code</p>
        <p class="font-semibold"><?php echo htmlspecialchars($ngo['ifsc_code'] ?? ''); ?></p>
      </div>
    </div>

    <?php $certFile = $ngo['certificate'] ?? ''; if ($certFile && ctype_print($certFile) && strpos($certFile,'.') !== false): ?>
    <div class="mt-6">
      <p class="text-sm text-gray-500 mb-2">Certificate</p>
      <?php $ext = strtolower(pathinfo($certFile, PATHINFO_EXTENSION)); if (in_array($ext,['png','jpg','jpeg','gif','webp'])): ?>
        <img src="<?php echo '../../ngo/certificates/'.rawurlencode($certFile); ?>" alt="Certificate" class="max-h-96 border rounded" />
      <?php else: ?>
        <a href="<?php echo '../../ngo/certificates/'.rawurlencode($certFile); ?>" target="_blank" class="text-primary-color underline">Open Certificate</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</body>
</html>


