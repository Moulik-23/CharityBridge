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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

    <?php 
    $certFile = $ngo['certificate'] ?? ''; 
    if ($certFile && !empty(trim($certFile)) && strpos($certFile,'.') !== false): 
        $ext = strtolower(pathinfo($certFile, PATHINFO_EXTENSION));
        $certPath = '../../ngo/certificates/' . rawurlencode($certFile);
        $absolutePath = __DIR__ . '/../../ngo/certificates/' . $certFile;
        $fileExists = file_exists($absolutePath);
    ?>
    <div class="mt-6">
      <p class="text-sm text-gray-500 mb-2">Certificate</p>
      <?php if ($fileExists && in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])): ?>
        <img src="<?php echo $certPath; ?>" alt="Certificate" class="max-w-full max-h-96 border rounded" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" />
        <div style="display:none;" class="p-4 border rounded bg-gray-100">
          <p class="text-gray-600 mb-2">Image could not be loaded.</p>
          <a href="<?php echo $certPath; ?>" target="_blank" class="text-blue-600 hover:text-blue-800 underline inline-flex items-center">
            <i class="fas fa-file-download mr-2"></i>Download Certificate
          </a>
        </div>
      <?php elseif ($fileExists): ?>
        <div class="p-4 border rounded bg-gray-100">
          <p class="text-gray-600 mb-2">Certificate (<?php echo strtoupper($ext); ?>)</p>
          <a href="<?php echo $certPath; ?>" target="_blank" class="text-blue-600 hover:text-blue-800 underline inline-flex items-center">
            <i class="fas fa-file-download mr-2"></i>Download/View Certificate
          </a>
        </div>
      <?php else: ?>
        <div class="p-4 border rounded bg-yellow-50 border-yellow-200">
          <p class="text-yellow-800 mb-2">
            <i class="fas fa-exclamation-triangle mr-2"></i>Certificate file not found: <?php echo htmlspecialchars($certFile); ?>
          </p>
          <p class="text-sm text-yellow-700">Expected path: <?php echo htmlspecialchars($certPath); ?></p>
        </div>
      <?php endif; ?>
    </div>
    <?php elseif (!empty($certFile)): ?>
    <div class="mt-6">
      <div class="p-4 border rounded bg-yellow-50 border-yellow-200">
        <p class="text-yellow-800">
          <i class="fas fa-exclamation-triangle mr-2"></i>Certificate filename is invalid: <?php echo htmlspecialchars($certFile); ?>
        </p>
      </div>
    </div>
    <?php endif; ?>
  </div>
</body>
</html>


