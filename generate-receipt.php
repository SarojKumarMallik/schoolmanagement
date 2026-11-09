<?php
session_start();
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

if (!isset($_SESSION['receipt_data'])) {
    die("No receipt data found!");
}

$receipt_data = $_SESSION['receipt_data'];

require_once('fpdf/fpdf.php');

// Generate PDF Receipt
$pdf = new FPDF();
$pdf->AddPage();

// School Header
$pdf->Image(__DIR__ . '/images/logo/mdk.png', 30, 10, 150);
$pdf->Ln(25);

$pdf->SetFont('Times','B',10);
$pdf->Cell(0,6,'Plot no 3010, Cuttack - Puri By pass Near Sapta sati Temple in Palasuni Bhubaneswar, Odisha 751025',0,1,'C');
$pdf->SetFont('Times','',10);
$pdf->Cell(0,6,'MDKS TRUST NO-1081911189   |   PHONE NO-0674-3589228',0,1,'C');
$pdf->Ln(9);

$pdf->SetFont('Times','B',14);
$pdf->Cell(0,10,'STOCK ISSUE RECEIPT',0,1,'C');
$pdf->Ln(5);

// Receipt Details
$pdf->SetFont('Times','',12);
$pdf->Cell(95,8,"Date: ".date("d-m-Y", strtotime($receipt_data['date'])),0,0,'L');
$pdf->Cell(95,8,"Transaction ID: STK".$receipt_data['transaction_id'],0,1,'R');
$pdf->Ln(5);


 $pdf->SetFont('Times','B',12);
    $pdf->Cell(0,8,"Student Details",0,1,'L');
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(5);

    $colWidth = 70;

// --- Header Row 1 ---
$pdf->SetFont('Times','B',11);
$pdf->Cell($colWidth,6,"Student Name",0,0);
$pdf->Cell($colWidth,6,"Father Name",0,0);
$pdf->Cell($colWidth,6,"Mother Name",0,1);

// --- Data Row 1 ---
$pdf->SetFont('Times','',11);
$pdf->Cell($colWidth,6,$receipt_data['student_name'],0,0);
$pdf->Cell($colWidth,6,$receipt_data['father_name'],0,0);
$pdf->Cell($colWidth,6,$receipt_data['mother_name'],0,1);

$pdf->Ln(3);

// --- Header Row 2 ---
$pdf->SetFont('Times','B',11);
$pdf->Cell($colWidth,6,"Roll No",0,0);
$pdf->Cell($colWidth,6,"Class",0,0);
$pdf->Cell($colWidth,6,"Section",0,1);

// --- Data Row 2 ---
$pdf->SetFont('Times','',11);
$pdf->Cell($colWidth,6,$receipt_data['student_regno'],0,0);
$pdf->Cell($colWidth,6,$receipt_data['class_name'],0,0);
$pdf->Cell($colWidth,6,$receipt_data['student_section'],0,1);

    $pdf->Ln(3);
    
     $pdf->SetFont('Times','B',11);
    $pdf->Cell($colWidth,6,"Address",0,0);
    $pdf->Cell($colWidth,6,"Date of Admission",0,0);
    $pdf->Cell($colWidth,6,"Joining date",0,1);

    $pdf->SetFont('Times','',11);
    $pdf->Cell($colWidth,6,$receipt_data['address'],0,0);
$DateofAdmission = date("d-m-Y", strtotime($receipt_data['DateofAdmission']));
$joindate        = date("d-m-Y", strtotime($receipt_data['joindate']));

$pdf->Cell($colWidth,6,$DateofAdmission,0,0);
$pdf->Cell($colWidth,6,$joindate,0,1);

 $pdf->Ln(3);
 
 

// Item Details
$pdf->SetFont('Times','B',12);
$pdf->Cell(0,8,"Item Details",0,1,'L');
$pdf->SetDrawColor(0,0,0);
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(5);


// --- Header Row ---
$pdf->SetFont('Times','B',11);
$pdf->Cell($colWidth,6,"Item Name",0,0);

if (!empty($receipt_data['size'])) {
    $pdf->Cell($colWidth,6,"Size",0,0);
    $pdf->Cell($colWidth,6,"Remark",0,1);
} else {
    $pdf->Cell($colWidth,6,"Remark",0,1);
}

// --- Data Row ---
$pdf->SetFont('Times','',11);
$pdf->Cell($colWidth,6,$receipt_data['item'],0,0);

if (!empty($receipt_data['size'])) {
    $pdf->Cell($colWidth,6,$receipt_data['size'],0,0);
    $pdf->Cell($colWidth,6,!empty($receipt_data['remark']) ? $receipt_data['remark'] : "-",0,1);
} else {
    $pdf->Cell($colWidth,6,!empty($receipt_data['remark']) ? $receipt_data['remark'] : "-",0,1);
}



$pdf->Ln(3);

$pdf->SetFont('Times','B',12);
    $pdf->Cell(0,8,"Payment Details",0,1,'L');
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(5);

    $colWidth = 70;

// --- Header Row 1 ---
$pdf->SetFont('Times','B',11);
$pdf->Cell($colWidth,6,"Quantity",0,0);
$pdf->Cell($colWidth,6,"Price/Unit",0,0);
$pdf->Cell($colWidth,6,"Total Amount",0,1);

// --- Data Row 1 ---
$pdf->SetFont('Times','',11);
// $pdf->Cell($colWidth,6,$receipt_data['student_name'],0,0);
$pdf->Cell($colWidth,6,$receipt_data['quantity'],0,0);
$pdf->Cell($colWidth,6,"Rs ".number_format($receipt_data['price_per_unit'], 2),0,0);
// $pdf->Cell($colWidth,6,$receipt_data['father_name'],0,0);
$pdf->Cell($colWidth,6,"Rs ".number_format($receipt_data['total_price'], 2),0,1);
// $pdf->Cell($colWidth,6,$receipt_data['mother_name'],0,1);





$pdf->Ln(15);
$pdf->Cell(0,8,"Authorized Signature",0,1,'R');

$fileName = "Stock_Issue_".$receipt_data['student_regno']."_".date("Ymd_His").".pdf";

// Clear the session data
unset($_SESSION['receipt_data']);

$pdf->Output("D", $fileName);
exit();
?>