<?php
session_start();
require_once('fpdf/fpdf.php');

if (!isset($_SESSION['admission_receipt_data'])) {
    die("No receipt data found!");
}

$data = $_SESSION['admission_receipt_data'];

// Start PDF
$pdf = new FPDF();
$pdf->AddPage();

// Logo and Header
$pdf->Image(__DIR__ . '/images/logo/mdk.png', 30, 10, 150);
$pdf->Ln(25);
$pdf->SetFont('Times','B',10);
$pdf->Cell(0,6,'Plot no 3010, Cuttack - Puri By pass Near Sapta sati Temple in Palasuni Bhubaneswar, Odisha 751025',0,1,'C');
$pdf->SetFont('Times','',10);
$pdf->Cell(0,6,'MDKS TRUST NO-1081911189 | PHONE NO-0674-3589228',0,1,'C');
$pdf->Ln(10);

$pdf->SetFont('Times','B',14);
$pdf->Cell(0,10,'ADMISSION FEE RECEIPT',0,1,'C');
$pdf->Ln(5);

$pdf->SetFont('Times','',12);
$pdf->Cell(95,8,"Date: ".date("d-m-Y", strtotime($data['payment_date'])),0,0,'L');
$pdf->Cell(95,8,"Receipt No: ".$data['receipt_no'],0,1,'R');
$pdf->Ln(5);

// Student Info Section
$pdf->SetFont('Times','B',12);
$pdf->Cell(0,8,"Student Details",0,1,'L');
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(5);

$colWidth = 70;
$pdf->SetFont('Times','B',11);
$pdf->Cell($colWidth,6,"Student Name",0,0);
$pdf->Cell($colWidth,6,"Father Name",0,0);
$pdf->Cell($colWidth,6,"Mother Name",0,1);

$pdf->SetFont('Times','',11);
$pdf->Cell($colWidth,6,$data['student_name'],0,0);
$pdf->Cell($colWidth,6,$data['father_name'],0,0);
$pdf->Cell($colWidth,6,$data['mother_name'],0,1);

$pdf->Ln(3);
$pdf->SetFont('Times','B',11);
$pdf->Cell($colWidth,6,"Class",0,0);
$pdf->Cell($colWidth,6,"Section",0,0);
$pdf->Cell($colWidth,6,"Admission Date",0,1);

$pdf->SetFont('Times','',11);
$pdf->Cell($colWidth,6,$data['student_class'],0,0);
$pdf->Cell($colWidth,6,$data['student_section'],0,0);
$pdf->Cell($colWidth,6,date("d-m-Y", strtotime($data['DateofAdmission'])),0,1);

$pdf->Ln(3);
$pdf->SetFont('Times','B',11);
$pdf->Cell($colWidth,6,"Address",0,0);
$pdf->Cell($colWidth,6,"Join Date",0,0);
$pdf->Cell($colWidth,6,"",0,1);

$pdf->SetFont('Times','',11);
$pdf->Cell($colWidth,6,$data['address'],0,0);
$pdf->Cell($colWidth,6,date("d-m-Y", strtotime($data['joindate'])),0,0);
$pdf->Cell($colWidth,6,"",0,1);

$pdf->Ln(10);

// Fee Details
$pdf->SetFont('Times','B',12);
$pdf->Cell(0,8,"Payment Details",0,1,'L');
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(5);

$pdf->SetFont('Times','B',11);
$pdf->Cell($colWidth,6,"Total Admission Fee",0,0);
$pdf->Cell($colWidth,6,"Now Paid Amount",0,0);
$pdf->Cell($colWidth,6,"Unpaid Amount",0,1);

$pdf->SetFont('Times','',11);
$pdf->Cell($colWidth,6,"Rs ".number_format($data['total_amount'],2),0,0);
$pdf->Cell($colWidth,6,"Rs ".number_format($data['paid_amount'],2),0,0);
$pdf->Cell($colWidth,6,"Rs ".number_format($data['unpaid_amount'],2),0,1);

$pdf->Ln(20);
$pdf->Cell(0,8,"Authorized Signature",0,1,'R');

// Output
$fileName = "Admission_Receipt_".$data['stu_id']."_".date("Ymd_His").".pdf";
unset($_SESSION['admission_receipt_data']);
$pdf->Output("D", $fileName);
exit();
?>
