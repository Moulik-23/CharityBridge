<?php
require_once __DIR__ . '/lib/fpdf.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
  http_response_code(400);
  echo 'Invalid restaurant id';
  exit;
}

$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) { die('DB error'); }

$stmt = $conn->prepare("SELECT id, restaurant_name, owner_name, email, phone, pincode, address, fssai_license, license_document, created_at FROM restaurants WHERE id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$rest = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$rest) {
  http_response_code(404);
  echo 'Restaurant not found';
  exit;
}

$pdf = new FPDF();
$pdf->SetTitle('Restaurant Report');
$pdf->SetAuthor('CharityBridge');
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Restaurant Registration Report',0,1);
$pdf->SetFont('Arial','',12);
$pdf->Ln(2);

function rlineItem($pdf, $label, $value){
  $pdf->SetFont('Arial','B',12);
  $pdf->Cell(60,8,$label.':',0,0);
  $pdf->SetFont('Arial','',12);
  $pdf->Cell(0,8,(string)$value,0,1);
}

rlineItem($pdf,'Restaurant Name',$rest['restaurant_name'] ?? '');
rlineItem($pdf,'Owner Name',$rest['owner_name'] ?? '');
rlineItem($pdf,'Email',$rest['email'] ?? '');
rlineItem($pdf,'Phone',$rest['phone'] ?? '');
rlineItem($pdf,'Address',$rest['address'] ?? '');
rlineItem($pdf,'Pincode',$rest['pincode'] ?? '');
rlineItem($pdf,'FSSAI License',$rest['fssai_license'] ?? '');
rlineItem($pdf,'Date of Registration',$rest['created_at'] ?? '');

// License document image if present (stored as filename)
$docFile = $rest['license_document'] ?? '';
if ($docFile && ctype_print($docFile) && strpos($docFile,'.') !== false) {
  $path = realpath(__DIR__ . '/../../restaurant/pge/uploads/' . $docFile);
  if ($path && is_file($path)) {
    $pdf->Ln(6);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8,'Attached License Document:',0,1);
    $pdf->Image($path, null, null, 120);
  }
}

$pdf->Output('D', 'restaurant_'.$rest['id'].'_report.pdf');
exit;
?>




