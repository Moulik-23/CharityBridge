<?php
require_once __DIR__ . '/lib/fpdf.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
  http_response_code(400);
  echo 'Invalid NGO id';
  exit;
}

$conn = new mysqli('localhost', 'root', '', 'charitybridge');
if ($conn->connect_error) { die('DB error'); }

$stmt = $conn->prepare("SELECT id, name, email, darpan_id, address, phone, created_at, certificate FROM ngos WHERE id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$ngo = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$ngo) {
  http_response_code(404);
  echo 'NGO not found';
  exit;
}

$pdf = new FPDF();
$pdf->SetTitle('NGO Report');
$pdf->SetAuthor('CharityBridge');
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'NGO Registration Report',0,1);
$pdf->SetFont('Arial','',12);
$pdf->Ln(2);

function lineItem($pdf, $label, $value){
  $pdf->SetFont('Arial','B',12);
  $pdf->Cell(50,8,$label.':',0,0);
  $pdf->SetFont('Arial','',12);
  $pdf->Cell(0,8,(string)$value,0,1);
}

lineItem($pdf,'Name',$ngo['name'] ?? '');
lineItem($pdf,'Email',$ngo['email'] ?? '');
lineItem($pdf,'Darpan ID',$ngo['darpan_id'] ?? '');
lineItem($pdf,'Phone',$ngo['phone'] ?? '');
lineItem($pdf,'Address',$ngo['address'] ?? '');
lineItem($pdf,'Date of Registration',$ngo['created_at'] ?? '');

// Certificate image if present (stored as filename)
$certFile = $ngo['certificate'] ?? '';
if ($certFile && ctype_print($certFile) && strpos($certFile,'.') !== false) {
  $path = realpath(__DIR__ . '/../../ngo/certificates/' . $certFile);
  if ($path && is_file($path)) {
    $pdf->Ln(6);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8,'Attached Certificate:',0,1);
    // Attempt to place image (will skip if not supported)
    $pdf->Image($path, null, null, 120);
  }
}

$pdf->Output('D', 'ngo_'.$ngo['id'].'_report.pdf');
exit;
?>




