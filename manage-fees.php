<?php
session_start();
include('includes/dbconnection.php');
require('fpdf/fpdf.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

// Database connection ($con, $conn, $dbh) provided by includes/dbconnection.php

// Handle search
$stuData = $feeData = null;
if (isset($_POST['search']) || isset($_POST['pay_admission'])) {
    $stu_id = $_POST['stu_id'];

    $stmt = $con->prepare("SELECT StudentName, addAmt, FatherName, ContactNumber, addDate, StudentClass, StudentSection FROM tblstudent WHERE StuID = ?");
    if (!$stmt) die("Prepare failed: (" . $con->errno . ") " . $con->error);

    $stmt->bind_param("s", $stu_id);
    $stmt->execute();
    $stmt->bind_result($stuName, $addAmt, $fatherName, $contactNumber, $addDate, $StudentClass, $StudentSection);
    if ($stmt->fetch()) {
        $stuData = [
            'StuID' => $stu_id,
            'StudentName' => $stuName,
            'FatherName' => $fatherName,
            'ContactNumber' => $contactNumber,
            'addDate' => $addDate,
            'StudentClass' => $StudentClass,
            'StudentSection' => $StudentSection
        ];
        $feeData['addAmt'] = $addAmt;
    }
    $stmt->close();

    if ($stuData) {
        // FIXED: Get total paid amount excluding zero payments from re-admission
        $stmt = $con->prepare("SELECT SUM(paid) FROM tbladmissionfee WHERE StuID = ? AND paid > 0");
        $stmt->bind_param("s", $stu_id);
        $stmt->execute();
        $stmt->bind_result($totalPaid);
        $stmt->fetch();
        $stmt->close();

        $feeData['paidAmt'] = $totalPaid ?: 0;
        $feeData['unpaidAmt'] = $feeData['addAmt'] - $feeData['paidAmt'];
    }
}

// Handle payment
if (isset($_POST['pay_admission'])) {
    $stu_id = $_POST['stu_id'];
    $pay_amt = floatval($_POST['pay_amt']);
    $pay_date = $_POST['paymentdate'];

    // Insert payment - only if amount is greater than 0
    if ($pay_amt > 0) {
        $stmt = $con->prepare("INSERT INTO tbladmissionfee (StuID, paid, paidDate) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $stu_id, $pay_amt, $pay_date);
        $stmt->execute();
        $stmt->close();
    }

    // Rest of your payment handling code remains the same...
    // Fetch student info
    $stmt = $con->prepare("SELECT * FROM tblstudent WHERE StuID = ?");
    $stmt->bind_param("s", $stu_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $stuName      = $row['StudentName'];
        $fatherName   = $row['FatherName'];
        $DateofAdmission = $row['addDate'];
        $joindate     = $row['joindate'];
        $motherName   = $row['MotherName'];
        $address      = $row['Address'];
        $studentClass = $row['StudentClass'];
        $totalAmt     = $row['addAmt'];
        $StudentSection = $row['StudentSection'];
    }
    $stmt->close();

    // Fetch total paid (excluding zero payments)
    $stmt = $con->prepare("SELECT SUM(paid) FROM tbladmissionfee WHERE StuID = ? AND paid > 0");
    $stmt->bind_param("s", $stu_id);
    $stmt->execute();
    $stmt->bind_result($totalPaid);
    $stmt->fetch();
    $stmt->close();

    $totalPaid = $totalPaid ?: 0;
    $unpaidAmt = $totalAmt - $totalPaid;

    // Generate receipt and store in session...
    // ... (your existing receipt generation code)

    echo "<script>window.open('managefee_receipt.php', '_blank');</script>";
    echo "<script>window.location.href='manage-fees.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Student Management System|||Search Students</title>
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        
        .sidebar::-webkit-scrollbar {
            display: none;
        }
        
        .payment-history {
            max-height: 400px;
            overflow-y: auto;
        }
        .zero-payment {
            background-color: #f8f9fa;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php');?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">Admission Fee Payment</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Admission Fee Payment</li>
                            </ol>
                        </nav>
                    </div>
          
                    <div class="card">
                        <div class="card-body">
                            <form method="post">
                                <div class="form-group">
                                    <label>Enter Student ID</label>
                                    <input type="text" name="stu_id" required class="form-control" 
                                           value="<?= isset($_POST['stu_id']) ? htmlspecialchars($_POST['stu_id']) : '' ?>">
                                </div>
                                <button type="submit" name="search" class="btn btn-primary mt-2">Search</button>
                            </form>
                        </div>
                    </div>
    
                    <?php if ($stuData && $feeData): ?>
                        <!-- Re-admission Information Section -->
                        <?php
                        // Get re-admission count
                        $stmtReAdmit = $con->prepare("SELECT COUNT(*) as reAdmitCount FROM tblreadmission WHERE StuID = ?");
                        $stmtReAdmit->bind_param("s", $stuData['StuID']);
                        $stmtReAdmit->execute();
                        $stmtReAdmit->bind_result($reAdmitCount);
                        $stmtReAdmit->fetch();
                        $stmtReAdmit->close();
                        ?>

                        <?php if ($reAdmitCount > 0): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="bi bi-arrow-repeat"></i> Re-Admission Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Total Re-Admissions:</strong> <?= $reAdmitCount ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Last Re-Admission:</strong> 
                                            <?php
                                            $stmtLast = $con->prepare("SELECT reAdmissionDate FROM tblreadmission WHERE StuID = ? ORDER BY reAdmissionDate DESC LIMIT 1");
                                            $stmtLast->bind_param("s", $stuData['StuID']);
                                            $stmtLast->execute();
                                            $stmtLast->bind_result($lastReAdmit);
                                            if ($stmtLast->fetch()) {
                                                echo date('d-m-Y', strtotime($lastReAdmit));
                                            } else {
                                                echo 'N/A';
                                            }
                                            $stmtLast->close();
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Student Info Card -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Student Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <p><strong>Student ID:</strong> <?= htmlspecialchars($stuData['StuID']) ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><strong>Name:</strong> <?= htmlspecialchars($stuData['StudentName']) ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><strong>Father's Name:</strong> <?= htmlspecialchars($stuData['FatherName']) ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><strong>Class/Section:</strong> <?= htmlspecialchars($stuData['StudentClass']) ?>/<?= htmlspecialchars($stuData['StudentSection']) ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><strong>Admission Date:</strong> <?= htmlspecialchars($stuData['addDate']) ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><strong>Contact Number:</strong> <?= htmlspecialchars($stuData['ContactNumber']) ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><strong>Total Admission Fee:</strong> ₹<?= number_format($feeData['addAmt'], 2) ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><strong>Paid:</strong> ₹<?= number_format($feeData['paidAmt'], 2) ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><strong>Unpaid:</strong> 
                                            <span class="<?= $feeData['unpaidAmt'] > 0 ? 'text-danger' : 'text-success' ?>">
                                                ₹<?= number_format($feeData['unpaidAmt'], 2) ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <?php if ($feeData['unpaidAmt'] > 0): ?>
                                    <!-- Payment Form -->
                                    <?php 
                                    $schoolName = "MDK School";
                                    $fatherName = $stuData['FatherName'];
                                    $dueAmount = number_format($feeData['unpaidAmt'], 2);
                                    $contactNumber = preg_replace('/\D/', '', $stuData['ContactNumber']);

                                    $message = rawurlencode(
                                        "Dear Parent,\n\n"
                                        . "This is a reminder from $schoolName regarding admission fee for " . $stuData['StudentName'] . ".\n"
                                        . "Total Fee: ₹" . number_format($feeData['addAmt'], 2) . "\n"
                                        . "Paid: ₹" . number_format($feeData['paidAmt'], 2) . "\n"
                                        . "Due Amount: ₹$dueAmount\n\n"
                                        . "Please clear the payment at your earliest convenience.\n"
                                        . "Thank you.\n$schoolName"
                                    );

                                    $whatsappUrl = "https://wa.me/91{$contactNumber}?text={$message}";
                                    ?>
                                    
                                    <form method="post" class="mt-4">
                                        <input type="hidden" name="stu_id" value="<?= $stuData['StuID'] ?>">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Amount to Pay</label>
                                                    <input type="number" name="pay_amt" class="form-control" 
                                                           min="1" max="<?= $feeData['unpaidAmt'] ?>" 
                                                           value="<?= $feeData['unpaidAmt'] ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Payment Date</label>
                                                    <input type="date" name="paymentdate" value="<?= date('Y-m-d') ?>" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end">
                                                <div class="form-group w-100">
                                                    <button type="submit" name="pay_admission" class="btn btn-success mr-2">
                                                        <i class="bi bi-credit-card"></i> Pay Now
                                                    </button>
                                                    <a href="<?= $whatsappUrl ?>" target="_blank" class="btn btn-success" style="background-color:#25D366; border:none;">
                                                        <i class="bi bi-whatsapp"></i> WhatsApp
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-success mt-3">
                                        <i class="bi bi-check-circle"></i> Admission Fee Fully Paid
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Payment History -->
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Payment History</h5>
                            </div>
                            <div class="card-body">
                                <div class="payment-history">
                                    <table class="table table-bordered table-striped">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Sl No</th>
                                                <th>Paid Amount</th>
                                                <th>Payment Date</th>
                                                <th>Receipt No</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // FIXED: Show all payment records but highlight zero payments as re-admission resets
                                            $stmt = $con->prepare("
                                                SELECT af.paid, DATE(af.paidDate) as paidDate, r.receipt_no 
                                                FROM tbladmissionfee af 
                                                LEFT JOIN tblreceipts r ON af.StuID = r.StuID AND af.paidDate = r.receipt_date
                                                WHERE af.StuID = ? 
                                                ORDER BY af.paidDate DESC, af.id DESC
                                            ");
                                            $stmt->bind_param("s", $stuData['StuID']);
                                            $stmt->execute();
                                            $stmt->bind_result($paid, $paidDate, $receipt_no);
                                            $sn = 1;
                                            $hasRecords = false;
                                            
                                            while ($stmt->fetch()) {
                                                $hasRecords = true;
                                                $rowClass = $paid == 0 ? 'zero-payment' : '';
                                                $remarks = $paid == 0 ? 'Re-admission Reset' : 'Payment';
                                                
                                                echo "<tr class='$rowClass'>
                                                        <td>{$sn}</td>
                                                        <td>₹" . number_format($paid, 2) . "</td>
                                                        <td>{$paidDate}</td>
                                                        <td>" . ($receipt_no ? $receipt_no : 'N/A') . "</td>
                                                        <td>{$remarks}</td>
                                                      </tr>";
                                                $sn++;
                                            }
                                            
                                            if (!$hasRecords) {
                                                echo "<tr><td colspan='5' class='text-center'>No payment records found</td></tr>";
                                            }
                                            $stmt->close();
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if ($reAdmitCount > 0): ?>
                                <div class="alert alert-info mt-3">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Note:</strong> Zero amount entries indicate re-admission events where the fee balance was reset.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif (isset($_POST['search'])): ?>
                        <div class="alert alert-danger mt-3">
                            <i class="bi bi-exclamation-triangle"></i> Student not found or admission data missing.
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php include_once('includes/footer.php');?>
            </div>
        </div>
    </div>

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
</body>
</html>