<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<?php
session_start();
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

$con = new mysqli("localhost", "root", "root", "studentmsdb");
$stuData = $feeData = $monthlyData = null;
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

if (isset($_POST['search'])) {
    $stu_id = $_POST['stu_id'];

    // Fetch student data
    $stmt = $con->prepare("SELECT StudentName, dueAmt, addDate, FatherName, joindate, ContactNumber, StudentClass, StudentSection, MotherName, Address FROM tblstudent WHERE StuID = ?");
    $stmt->bind_param("s", $stu_id);
    $stmt->execute();
    $stmt->bind_result($stuName, $dueAmt, $addDate, $fatherName, $joindate, $ContactNumber, $StudentClass, $StudentSection, $MotherName, $Address);
    
    if ($stmt->fetch()) {
        $stuData = [
            'StuID' => $stu_id,
            'StudentName' => $stuName,
            'FatherName' => $fatherName,
            'JoiningDate' => $joindate,
            'ContactNumber' => $ContactNumber,
            'StudentClass' => $StudentClass,
            'StudentSection' => $StudentSection,
            'MotherName' => $MotherName,
            'Address' => $Address,
            'AddDate' => $addDate
        ];
        $feeData = [
            'dueAmt' => $dueAmt,
            'addDate' => $addDate
        ];
    }
    $stmt->close();

    // Smart monthly fee synchronization - PRESERVE existing payments
    if ($stuData && $dueAmt > 0) {
        $currentJoinDate = new DateTime($stuData['JoiningDate']);
        
        // Session calculation - March to February based on CURRENT joining date
        $sessionStart = new DateTime(($currentJoinDate->format("Y")) . "-03-01");
        if ($currentJoinDate < $sessionStart) {
            $sessionStart->modify("-1 year");
        }
        
        $sessionEnd = clone $sessionStart;
        $sessionEnd->modify("+1 year")->modify("-1 month"); // Feb next year
        
        // Generate all required months for current session
        $requiredMonths = [];
        $current = new DateTime($currentJoinDate->format("Y-m-01"));
        while ($current <= $sessionEnd) {
            $requiredMonths[] = $current->format("Y-m");
            $current->modify("+1 month");
        }
        
        // Get existing months from database
        $existingMonths = [];
        $existingMonthsData = [];
        $stmt = $con->prepare("SELECT ID, month_year, due, paid, status FROM tblmonthlyfees WHERE StuID = ?");
        $stmt->bind_param("s", $stu_id);
        $stmt->execute();
        $stmt->bind_result($existingId, $existingMonth, $existingDue, $existingPaid, $existingStatus);
        
        while ($stmt->fetch()) {
            $existingMonths[] = $existingMonth;
            $existingMonthsData[$existingMonth] = [
                'id' => $existingId,
                'due' => $existingDue,
                'paid' => $existingPaid,
                'status' => $existingStatus
            ];
        }
        $stmt->close();
        
        // Find months that need to be added (new months after readmission)
        $monthsToAdd = array_diff($requiredMonths, $existingMonths);
        
        // Insert missing months with CURRENT due amount
        foreach ($monthsToAdd as $monthYear) {
            $monthDate = new DateTime($monthYear . "-01");
            $joinDate = new DateTime($stuData['JoiningDate']);
            
            // Only add months that are on or after the new joining date
            if ($monthDate >= $joinDate) {
                $ins = $con->prepare("INSERT INTO tblmonthlyfees (StuID, month_year, due, paid, status, late_fee) 
                                      VALUES (?, ?, ?, 0, 'Unpaid', 0)");
                $ins->bind_param("ssd", $stu_id, $monthYear, $dueAmt);
                $ins->execute();
                $ins->close();
            }
        }
        
        // Update due amount for existing unpaid months that are after new joining date
        foreach ($existingMonthsData as $monthYear => $data) {
            $monthDate = new DateTime($monthYear . "-01");
            $joinDate = new DateTime($stuData['JoiningDate']);
            
            // Update due amount only for unpaid months after new joining date
            if ($monthDate >= $joinDate && $data['status'] === 'Unpaid') {
                $update = $con->prepare("UPDATE tblmonthlyfees SET due = ? WHERE ID = ?");
                $update->bind_param("di", $dueAmt, $data['id']);
                $update->execute();
                $update->close();
            }
        }
    }

    // Fetch ALL monthly fee data (both old and new) with payment dates
    if ($stuData) {
        $stmt = $con->prepare("SELECT ID, month_year, due, paid, late_fee, status, paidDate 
                               FROM tblmonthlyfees 
                               WHERE StuID = ? 
                               ORDER BY month_year DESC");
        $stmt->bind_param("s", $stu_id);
        $stmt->execute();
        $stmt->bind_result($id, $month_year, $due, $paid, $late_fee, $status, $paidDate);

        $monthlyData = []; 
        while ($stmt->fetch()) {
            $monthlyData[] = [
                'id' => $id,
                'month' => $month_year,
                'due' => $due,
                'paid' => $paid,
                'late_fee' => $late_fee,
                'status' => $status,
                'paidDate' => $paidDate
            ];
        }
        $stmt->close();
    }
}

require_once('fpdf/fpdf.php');

// Handle Payment
if (isset($_POST['pay_now'])) {
    $fee_id = intval($_POST['fee_id']);
    $pay_amt = floatval($_POST['pay_amt']);
    $pay_date = $_POST['paymentdate'];

    if ($pay_amt <= 0) {
        echo "<script>alert('Amount must be greater than 0'); window.history.back();</script>";
        exit;
    }

    // Fetch current due/paid
    $stmt = $con->prepare("SELECT StuID, paid, due, month_year, late_fee FROM tblmonthlyfees WHERE ID = ?");
    $stmt->bind_param("i", $fee_id);
    $stmt->execute();
    $stmt->bind_result($stu_id, $existing_paid, $due_amt, $month_year, $existing_late_fee);
    if ($stmt->fetch()) {
        $unpaid = $due_amt - $existing_paid;
        if ($pay_amt > $unpaid) {
            echo "<script>alert('Payment exceeds unpaid amount'); window.history.back();</script>";
            exit;
        }
        $stmt->close();

        // Late fee check
        $due_date = date("Y-m-10", strtotime($month_year));
        $current_date = date("Y-m-d");
        $late_fee = $existing_late_fee;
        if ($current_date > $due_date && $existing_late_fee == 0) {
            $late_fee = 50; // fixed late fee
        }

        $new_paid = $existing_paid + $pay_amt;
        $status = ($new_paid + $late_fee >= $due_amt) ? 'Paid' : 'Unpaid';

        // Update fee table
        $update = $con->prepare("UPDATE tblmonthlyfees SET paid = ?, late_fee = ?, status = ?, paidDate = ? WHERE ID = ?");
        $update->bind_param("ddssi", $new_paid, $late_fee, $status, $pay_date, $fee_id);
        $update->execute();
        $update->close();

        // Generate unique receipt number (MDK + YYYYMMDD + 4-digit random)
        do {
            $random = mt_rand(1000, 9999);
            $datePart = date("Ymd"); // YYYYMMDD
            $receipt_no = "MDK" . $datePart . $random;

            $check = $con->prepare("SELECT COUNT(*) FROM tblreceipts WHERE receipt_no = ?");
            $check->bind_param("s", $receipt_no);
            $check->execute();
            $check->bind_result($exists);
            $check->fetch();
            $check->close();
        } while ($exists > 0);

        $fee_id_new = $fee_id;

        // Save receipt
        $total_amt = $pay_amt + $late_fee;
        $ins = $con->prepare("INSERT INTO tblreceipts (receipt_no, StuID, fee_id, month_year, paid_amt, late_fee, total_amt) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("ssissdd", $receipt_no, $stu_id, $fee_id_new, $month_year, $pay_amt, $late_fee, $total_amt);
        $ins->execute();
        $ins->close();

        // Fetch student details for receipt
        $stmt = $con->prepare("SELECT StudentName, FatherName, StudentClass, StudentSection, MotherName, Address, addDate, joindate FROM tblstudent WHERE StuID = ?");
        $stmt->bind_param("s", $stu_id);
        $stmt->execute();
        $stmt->bind_result($stuName, $fatherName, $StudentClass, $StudentSection, $MotherName, $Address, $addDate, $joindate);
        $stmt->fetch();
        $stmt->close();

        // Generate PDF Receipt
        $pdf = new FPDF();
        $pdf->AddPage();

        $pdf->Image(__DIR__ . '/images/logo/mdk.png', 30, 10, 150);
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
        $pdf->Cell(95,8,"Date: ".date("d-m-Y"),0,0,'L');
        $pdf->Cell(95,8,"Receipt No: $receipt_no",0,1,'R');
        $pdf->Ln(5);

        // Add Student Details Heading
        $pdf->SetFont('Times','B',12);
        $pdf->Cell(0,8,"Student Details",0,1,'L');
        $pdf->SetDrawColor(0,0,0);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY()); // HR
        $pdf->Ln(5);

        // Set font for details
        $pdf->SetFont('Times','',11);

        // Column width for three columns
        $colWidth = 70;

        // First row: Student Name | Father's Name | Mother's Name
        $pdf->SetFont('Times','B',11);
        $pdf->Cell($colWidth,6,"Student Name",0,0);
        $pdf->Cell($colWidth,6,"Father's Name",0,0);
        $pdf->Cell($colWidth,6,"Mother's Name",0,1);

        $pdf->SetFont('Times','',11);
        $pdf->Cell($colWidth,6,$stuName,0,0);
        $pdf->Cell($colWidth,6,$fatherName,0,0);
        $pdf->Cell($colWidth,6,$MotherName,0,1);

        $pdf->Ln(3); // small spacing

        // Second row: Roll No | Class | Section
        $pdf->SetFont('Times','B',11);
        $pdf->Cell($colWidth,6,"Roll No",0,0);
        $pdf->Cell($colWidth,6,"Class",0,0);
        $pdf->Cell($colWidth,6,"Section",0,1);

        $pdf->SetFont('Times','',11);
        $pdf->Cell($colWidth,6,$stu_id,0,0);
        $pdf->Cell($colWidth,6,$StudentClass,0,0);
        $pdf->Cell($colWidth,6,$StudentSection,0,1);

        $pdf->Ln(3); // small spacing

        // Third row: Address | Date Of Admission | Joining Date
        $pdf->SetFont('Times','B',11);
        $pdf->Cell($colWidth,6,"Address",0,0);
        $pdf->Cell($colWidth,6,"Date Of Admission",0,0);
        $pdf->Cell($colWidth,6,"Joining Date",0,1);

        $pdf->SetFont('Times','',11);
        $pdf->Cell($colWidth,6,$Address,0,0);
        $pdf->Cell($colWidth,6, (!empty($addDate) ? date("d-m-Y", strtotime($addDate)) : ''),0,0);
        $pdf->Cell($colWidth,6,date("d-m-Y", strtotime($joindate)),0,1);

        $pdf->Ln(9); // spacing

        // Add Payment Details Heading
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,8,"Payment Details",0,1,'L');
        $pdf->SetDrawColor(0,0,0);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY()); // HR
        $pdf->Ln(5);

        // Column width for three columns
        $colWidth = 70;

        // First row: Month | Monthly Due | Amount Paid Now
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell($colWidth,6,"Month",0,0);
        $pdf->Cell($colWidth,6,"Monthly Due",0,0);
        $pdf->Cell($colWidth,6,"Amount Paid Now",0,1);

        $pdf->SetFont('Arial','',11);
        $pdf->Cell($colWidth,6,date("F Y", strtotime($month_year)),0,0);
        $pdf->Cell($colWidth,6,"Rs. ".number_format($due_amt,2),0,0);
        $pdf->Cell($colWidth,6,"Rs. ".number_format($pay_amt,2),0,1);

        $pdf->Ln(3); // spacing

        // Second row: Total Paid Till Now | Remaining Due | Late Fee
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell($colWidth,6,"Total Paid Till Now",0,0);
        $pdf->Cell($colWidth,6,"Remaining Due",0,0);
        $pdf->Cell($colWidth,6,"Late Fee",0,1);

        $pdf->SetFont('Arial','',11);
        $pdf->Cell($colWidth,6,"Rs. ".number_format($new_paid,2),0,0);
        $pdf->Cell($colWidth,6,"Rs. ".number_format(($due_amt + $late_fee) - $new_paid,2),0,0);
        $pdf->Cell($colWidth,6,($late_fee > 0 ? "Rs. ".number_format($late_fee,2) : "-"),0,1);

        $pdf->Ln(10);
        $pdf->Cell(0,8,"Authorized Signature",0,1,'R');

        $monthName = date("F", strtotime($month_year . "-01")); 
        $fileName = $stuName . "" . $stu_id ."".$monthName. ".pdf";
        
        ob_end_clean(); // clear previous output
        $pdf->Output("D", $fileName);

        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Student Management System | Monthly Fee</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="./vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="./vendors/chartist/chartist.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
    

    .sidebar::-webkit-scrollbar {
        display: none;
    }

    

    .badge {
        font-size: 0.85em;
        padding: 0.4em 0.6em;
    }

    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    .form-control-sm {
        max-width: 120px;
    }

    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    .old-record {
        background-color: #fff3cd !important;
    }

    .new-record {
        background-color: #d1ecf1 !important;
    }

    .payment-date {
        font-size: 0.85em;
        color: #28a745;
        font-weight: 500;
    }
    
    .payment-form {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 5px;
        border: 1px solid #dee2e6;
    }
    
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .whatsapp-btn {
        background: #25D366 !important;
        border: none;
        white-space: nowrap;
    }
    
    .paid-status {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .payment-section {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .payment-inputs {
        display: flex;
        gap: 5px;
        align-items: center;
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
                        <h3 class="page-title">Monthly Fee Payment</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Monthly Fee Payment</li>
                            </ol>
                        </nav>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <form method="post">
                                <div class="form-group">
                                    <label>Enter Student ID:</label>
                                    <input type="text" name="stu_id" required class="form-control"
                                        placeholder="e.g. STU001"
                                        value="<?php echo isset($_POST['stu_id']) ? htmlspecialchars($_POST['stu_id']) : ''; ?>">
                                </div>
                                <button type="submit" name="search" class="btn btn-primary mt-2">Search</button>
                            </form>
                        </div>
                    </div>

                    <?php if ($stuData): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Student Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-md-3">
                                    <p><strong>Student ID:</strong> <?= htmlspecialchars($stuData['StuID']) ?></p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Name:</strong> <?= htmlspecialchars($stuData['StudentName']) ?></p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Father's Name:</strong> <?= htmlspecialchars($stuData['FatherName']) ?>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Class:</strong> <?= htmlspecialchars($stuData['StudentClass']) ?> -
                                        <?= htmlspecialchars($stuData['StudentSection']) ?></p>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-3">
                                    <p><strong>Current Monthly Fee:</strong>
                                        ₹<?= number_format($feeData['dueAmt'], 0) ?></p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Admission Date:</strong>
                                        <?= date("d-m-Y", strtotime($stuData['AddDate'])) ?></p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Current Joining Date:</strong>
                                        <?= date("d-m-Y", strtotime($stuData['JoiningDate'])) ?></p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Contact:</strong> <?= htmlspecialchars($stuData['ContactNumber']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Monthly Fee Payments (All Records)</h5>
                            <small class="text-white">Yellow = Previous Session, Blue = Current Session</small>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Month</th>
                                            <th>Due Amount</th>
                                            <th>Paid Amount</th>
                                            <th>Unpaid Amount</th>
                                            <th>Status</th>
                                            <!-- <th>Session</th> -->
                                            <th>Payment Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                $totalDue = $totalPaid = 0;
                                $currentJoinDate = new DateTime($stuData['JoiningDate']);
                                
                                foreach ($monthlyData as $i => $row):
                                    $monthDate = new DateTime($row['month'] . "-01");
                                    $joinDate = new DateTime($stuData['JoiningDate']);
                                    
                                    $unpaid = $row['due'] - $row['paid'];
                                    $totalDue += $row['due'];
                                    $totalPaid += $row['paid'];
                                    
                                    // Check if this month belongs to current session (after readmission)
                                    $isCurrentSession = ($monthDate >= $joinDate);
                                    $rowClass = $isCurrentSession ? 'new-record' : 'old-record';
                                    $sessionLabel = $isCurrentSession ? 'Current' : 'Previous';
                                    
                                    // Format payment date if available
                                    $paymentDateDisplay = '';
                                    if (!empty($row['paidDate']) && $row['paidDate'] != '0000-00-00') {
                                        $paymentDateDisplay = date("d-m-Y", strtotime($row['paidDate']));
                                    }
                                    
                                    // Build WhatsApp message for this specific month
                                    $fatherName = $stuData['FatherName'];
                                    $studentName = $stuData['StudentName'];
                                    $contactNumber = preg_replace('/\D/', '', $stuData['ContactNumber'] ?? '');
                                    $message = rawurlencode(
                                        "Dear Parent,\n\n"
                                        . "This is a reminder from MDK School regarding pending fee for $studentName.\n\n"
                                        . "Month: " . date("F Y", strtotime($row['month'])) . "\n"
                                        . "Pending Amount: ₹" . number_format($unpaid, 2) . "\n\n"
                                        . "Kindly clear the payment at your earliest convenience.\n"
                                        . "If already paid, please ignore this message.\n\n"
                                        . "Thank you,\nMDK School"
                                    );
                                    $whatsappUrl = "https://wa.me/91{$contactNumber}?text={$message}";
                            ?>
                                        <tr class="<?= $rowClass ?>">
                                            <td><strong><?= date("F Y", strtotime($row['month'])) ?></strong></td>
                                            <td>₹<?= number_format($row['due'], 0) ?></td>
                                            <td>₹<?= number_format($row['paid'], 0) ?></td>
                                            <td>₹<?= number_format($unpaid, 0) ?></td>
                                            <td>
                                                <?php if ($row['status'] === 'Paid'): ?>
                                                <div class="paid-status">
                                                    <i class="bi bi-check-circle-fill text-success"></i>
                                                    <span class="badge bg-success">Paid</span>
                                                </div>
                                                <?php else: ?>
                                                <span class="badge bg-danger">Unpaid</span>
                                                <?php endif; ?>
                                            </td>
                                            <!-- <td>
                                                <span class="badge <?= $isCurrentSession ? 'bg-info' : 'bg-warning' ?>">
                                                    <?= $sessionLabel ?>
                                                </span>
                                            </td> -->
                                            <td>
                                                <?php if ($row['status'] === 'Paid' && !empty($paymentDateDisplay)): ?>
                                                <span class="payment-date">
                                                    <i class="bi bi-calendar-check"></i> <?= $paymentDateDisplay ?>
                                                </span>
                                                <?php else: ?>
                                                <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($unpaid > 0): ?>
                                                    <div class="payment-section">
                                                        <!-- Payment Form -->
                                                        <form method="post" class="payment-inputs">
                                                            <input type="hidden" name="fee_id" value="<?= $row['id'] ?>">
                                                            <input type="date" name="paymentdate" class="form-control form-control-sm" required style="width: 110px;">
                                                            <input type="number" name="pay_amt" class="form-control form-control-sm" 
                                                                   min="1" max="<?= $unpaid ?>" step="1" required 
                                                                   placeholder="Amount" style="width: 80px;">
                                                            <button type="submit" name="pay_now" class="btn btn-sm btn-primary">
                                                                <i class="bi bi-credit-card"></i> Pay
                                                            </button>
                                                        </form>
                                                        <!-- WhatsApp Reminder for this specific month -->
                                                        <a href="<?= $whatsappUrl ?>" target="_blank"
                                                            class="btn btn-sm btn-success whatsapp-btn"
                                                            title="Send WhatsApp reminder for <?= date("F Y", strtotime($row['month'])) ?>">
                                                            <i class="bi bi-whatsapp"></i> Remind
                                                        </a>
                                                    </div>
                                                    <?php else: ?>
                                                    <span class="text-success"><i class="bi bi-check-circle"></i> Paid</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="2" class="text-end">Total</th>
                                            <th>₹<?= number_format($totalDue, 0) ?></th>
                                            <th>₹<?= number_format($totalPaid, 0) ?></th>
                                            <th colspan="4">₹<?= number_format($totalDue - $totalPaid, 0) ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php elseif (isset($_POST['search'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> No student found with the given Student ID.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                </div>
                <?php include_once('includes/footer.php');?>
            </div>
        </div>
    </div>

    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="./vendors/chart.js/Chart.min.js"></script>
    <script src="./vendors/moment/moment.min.js"></script>
    <script src="./vendors/daterangepicker/daterangepicker.js"></script>
    <script src="./vendors/chartist/chartist.min.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script src="./js/dashboard.js"></script>

    <script>
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
    // Set default payment date to today
    document.addEventListener('DOMContentLoaded', function() {
        var today = new Date().toISOString().split('T')[0];
        var dateInputs = document.querySelectorAll('input[name="paymentdate"]');
        dateInputs.forEach(function(input) {
            input.value = today;
        });
    });
    </script>
</body>

</html>