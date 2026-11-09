<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (strlen($_SESSION['sturecmsaid']==0)) {
  header('location:logout.php');
  } 
include('includes/dbconnection.php');

require('fpdf/fpdf.php'); // make sure FPDF is included
$con = new mysqli("localhost", "root", "root", "studentmsdb");

$student = null;
$successMsg = "";

// Search Student by StuID
if (isset($_POST['search'])) {
    $stuId = $_POST['StuID'];
    $sql = "SELECT * FROM tblstudent WHERE StuID = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $stuId);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
}

// Save Payment
// Save Payment
if (isset($_POST['save_payment'])) {
    $stuId = $_POST['StuID'];
    
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0;
    $payment_type = $_POST['payment_type'];
    $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : date("Y-m-d");
    $receipt_date = date("Y-m-d H:i:s");
    $month_year = date("Y-m", strtotime($payment_date)); // derive month-year

    // Generate unique receipt_no
    do {
        $random = mt_rand(1000, 9999);
        $datePart = date("Ymd");
        $receipt_no = "MDK" . $datePart . $random;

        $check = $con->prepare("SELECT COUNT(*) FROM tblreceipts WHERE receipt_no = ?");
        if (!$check) {
            die("Prepare failed (check): " . $con->error);
        }
        $check->bind_param("s", $receipt_no);
        $check->execute();
        $check->bind_result($exists);
        $check->fetch();
        $check->close();
    } while ($exists > 0);

    $total_amt = $amount;

    $sql = "INSERT INTO tblreceipts 
        (receipt_no, StuID, fee_id, month_year, paid_amt, late_fee, total_amt, receipt_date, payment_type)
        VALUES (?, ?, 0, ?, ?, 0, ?, ?, ?)";
$stmt = $con->prepare($sql);
if (!$stmt) {
    die("Prepare failed (insert): " . $con->error);
}

$stmt->bind_param(
    "sssddss", 
    $receipt_no,    // s
    $stuId,         // s
    $month_year,    // s
    $amount,        // d
    $total_amt,     // d
    $receipt_date,  // s
    $payment_type   // s
);

    $stmt->execute();
    $stmt->close();


    // Fetch student details for receipt
    $sql = "SELECT * FROM tblstudent WHERE StuID = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $stuId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stuData = $result->fetch_assoc();
    $stmt->close();

    $stuName = $stuData['StudentName'];
    $StudentClass = $stuData['StudentClass'];
    $fatherName = $stuData['FatherName'];
     $DateofAdmission       = $stuData['DateofAdmission'];
    $joindate          = $stuData['joindate'];
    $motherName   = $stuData['MotherName'];
    $address      = $stuData['Address'];
    $studentClass = $stuData['StudentClass'];
    $totalAmt     = $stuData['addAmt'];
    $StudentSection =$stuData['StudentSection'];
    

    // --- Generate PDF Receipt ---
    $pdf = new FPDF();
    $pdf->AddPage();

    // Header
    $pdf->Image('images/logo/mdk.png', 30, 10, 150);
    $pdf->Ln(25);

    $pdf->SetFont('Times','B',10);
    $pdf->Cell(0,6,'Plot no 3010, Cuttack - Puri By pass Near Sapta sati Temple in Palasuni Bhubaneswar, Odisha 751025',0,1,'C');
    $pdf->SetFont('Times','',10);
    $pdf->Cell(0,6,'MDKS TRUST NO-1081911189   |   PHONE NO-0674-3589228',0,1,'C');
    $pdf->Ln(9);

    $pdf->SetFont('Times','B',14);
    $pdf->Cell(0,10,'MONEY RECEIPT',0,1,'C');
    $pdf->Ln(5);

    $pdf->SetFont('Times','',12);
    $pdf->Cell(95,8,"Date: ".date("d-m-Y", strtotime($payment_date)),0,0,'L');
    $pdf->Cell(95,8,"Receipt No: $receipt_no",0,1,'R');
    $pdf->Ln(5);

    // Student Details
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
$pdf->Cell($colWidth,6,$stuName,0,0);
$pdf->Cell($colWidth,6,$fatherName,0,0);
$pdf->Cell($colWidth,6,$motherName,0,1);

$pdf->Ln(3);
// --- Header Row 2 ---
$pdf->SetFont('Times','B',11);
$pdf->Cell($colWidth,6,"Roll No",0,0);
$pdf->Cell($colWidth,6,"Class",0,0);
$pdf->Cell($colWidth,6,"Section",0,1);

// --- Data Row 2 ---
$pdf->SetFont('Times','',11);
$pdf->Cell($colWidth,6,$stuId,0,0);
$pdf->Cell($colWidth,6,$StudentClass,0,0);
$pdf->Cell($colWidth,6,$StudentSection,0,1);




    $pdf->Ln(3);
    $pdf->SetFont('Times','B',11);
    $pdf->Cell($colWidth,6,"Address",0,0);
    // $pdf->Cell($colWidth,6,"Amount Paid",0,0);
    $pdf->Cell($colWidth,6,"Date of Admission",0,0);
    // $pdf->Cell($colWidth,6,"Total Paid",0,1);
    $pdf->Cell($colWidth,6,"Joining date",0,1);

    $pdf->SetFont('Times','',11);
    $pdf->Cell($colWidth,6,$address,0,0);
   // Convert MySQL datetime or date string to dd-mm-yyyy
$DateofAdmission = date("d-m-Y", strtotime($DateofAdmission));
$joindate        = date("d-m-Y", strtotime($joindate));

// Now use in PDF
$pdf->Cell($colWidth,6,$DateofAdmission,0,0);
$pdf->Cell($colWidth,6,$joindate,0,1);

    $pdf->Ln(3);
    
    

    // Payment Details
    $pdf->SetFont('Times','B',12);
    $pdf->Cell(0,8,"Payment Details",0,1,'L');
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(5);

    $pdf->SetFont('Times','B',11);
    $pdf->Cell($colWidth,6,"Payment Type",0,0);
    $pdf->Cell($colWidth,6,"Amount Paid",0,0);
    $pdf->Cell($colWidth,6,"Total Amount",0,1);

    $pdf->SetFont('Times','',11);
    $pdf->Cell($colWidth,6,$payment_type,0,0);
    $pdf->Cell($colWidth,6,"Rs. ".number_format($amount,2),0,0);
    $pdf->Cell($colWidth,6,"Rs. ".number_format($total_amt,2),0,1);

    $pdf->Ln(15);
    $pdf->Cell(0,8,"Authorized Signature",0,1,'R');

    $monthName = date("F", strtotime($payment_date));
    $fileName = $stuName . "_" . $stuId . "_" . $monthName . ".pdf";
   
    $pdf->Output("D", $fileName);
exit;

    
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

   
    <title>Student  Management System|||Search Students</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="./vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="./vendors/chartist/chartist.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="./css/style.css">
    
    <!-- End layout styles -->
   
  </head>
  <body>
    <div class="container-scroller">
      <!-- partial:partials/_navbar.html -->
     <?php include_once('includes/header.php');?>
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.html -->
        <?php include_once('includes/sidebar.php');?>
        <!-- partial -->
        <div class="main-panel">
         <div class="content-wrapper">
        
        <div class="page-header">
                        <h3 class="page-title">Other Fee Payment</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Other Fee Payment</li>
                            </ol>
                        </nav>
                    </div>
          
        <?php if (!empty($successMsg)) { ?>
            <div class="alert alert-success"><?= $successMsg; ?></div>
        <?php } ?>
        <div class="card">
          <div class="card-body">
            <form method="post">
              <div class="form-group">
                <label>Enter Student ID</label>
                <input type="text" name="StuID" required class="form-control">
              </div>
              <button type="submit" name="search" class="btn btn-primary mt-2">Search</button>
            </form>

         </div>
        </div>
         <!-- Student Details -->
        <?php if (!empty($student)) { ?>
            <div class="card border-primary mb-4">
                <div class="card-body">
    <h5 class="card-title">Student Details</h5>
    <div class="row">
        <div class="col-4">
            <p><strong>Name:</strong> <?= htmlspecialchars($student['StudentName']); ?></p>
        </div>
        <div class="col-4">
            <p><strong>Father Name :</strong> <?= htmlspecialchars($student['FatherName']); ?></p>
        </div>
        <div class="col-4">
            <p><strong>Mother Name :</strong> <?= htmlspecialchars($student['MotherName']); ?></p>
        </div>
        
        <div class="col-4">
            <p><strong>Class:</strong> <?= htmlspecialchars($student['StudentClass']); ?></p>
        </div>
        <div class="col-4">
            <p><strong>Section:</strong> <?= htmlspecialchars($student['StudentSection']); ?></p>
        </div>
        <div class="col-4">
            <p><strong>Address :</strong> <?= htmlspecialchars($student['Address']); ?></p>
        </div>
    </div>
</div>

            </div>

            <!-- Payment Form -->
            <form method="post" class="card p-3 shadow-sm">
                <input type="hidden" name="StuID" value="<?= htmlspecialchars($student['StuID']); ?>">

                <div class="mb-3">
                    <label class="form-label">Payment Type</label>
                    <select name="payment_type" class="form-select" required>
                        <!-- Academic Fees -->
                        <option value="Tuition Fee">Tuition Fee</option>
                        <option value="Transport Fee">Transport Fee</option>
                        <option value="Hostel Fee">Hostel Fee</option>
                        <option value="Exam Fee">Exam Fee</option>
                        <option value="Library Fine">Library Fine</option>

                        <!-- Events -->
                        <option value="Annual Function">Annual Function</option>
                        <option value="Picnic">Picnic</option>

                        <!-- Materials -->
                        <option value="Bag">Bag</option>
                        <option value="Pant">Pant</option>
                        <option value="Shirt">Shirt</option>
                        <option value="Book">Book</option>
                    </select>
                </div>

               <div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Payment Date</label>
        <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Amount</label>
        <input type="number" step="0.01" name="amount" class="form-control" required>
    </div>
</div>


                <button type="submit" name="save_payment" class="btn btn-success w-100">Save Payment</button>
            </form>
        <?php } ?>

    
       
    
        
      </div>
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
         <?php include_once('includes/footer.php');?>
          <!-- partial -->
        </div>
        <!-- main-panel ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="./vendors/chart.js/Chart.min.js"></script>
    <script src="./vendors/moment/moment.min.js"></script>
    <script src="./vendors/daterangepicker/daterangepicker.js"></script>
    <script src="./vendors/chartist/chartist.min.js"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="./js/dashboard.js"></script>
    <!-- End custom js for this page -->
  </body>
</html><?php   ?>