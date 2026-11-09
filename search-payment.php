<?php
session_start();
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
  header('location:logout.php');
  exit();
}


$con = new mysqli("localhost", "root", "root", "studentmsdb");
if ($con->connect_error) {
  die("Connection failed: " . $con->connect_error);
}

$receiptData = null;

if (isset($_GET['receipt_no'])) {
  $receipt_no = $_GET['receipt_no'];

  $sql = "SELECT r.receipt_no, r.StuID, r.fee_id, r.month_year, r.paid_amt, r.late_fee, 
                   r.total_amt, r.receipt_date,r.payment_type,
                   s.StudentName, s.StudentEmail, s.StudentClass, s.Gender, s.DOB, 
                   s.FatherName, s.MotherName, s.ContactNumber, s.AltenateNumber, 
                   s.Address
            FROM tblreceipts r
            INNER JOIN tblstudent s ON r.StuID = s.StuID
            WHERE r.receipt_no = ?";

  $stmt = $con->prepare($sql);
  if (!$stmt) {
    die("SQL Error: " . $con->error);
  }

  $stmt->bind_param("s", $receipt_no);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($row = $result->fetch_assoc()) {
    $receiptData = $row;
    $receiptData['receiptDate'] = date("d-m-Y", strtotime($row['receipt_date']));
    $receiptData['receiptMonth'] = date("F Y", strtotime($row['receipt_date']));

    // Decide Money Paid For based on fee_id
    if ($row['fee_id'] == 1) {
      $receiptData['moneyPaidFor'] = "Admission Fee";
    } elseif ($row['fee_id'] == 5) {
      $receiptData['moneyPaidFor'] = "Monthly Fee";
    } else {
      $receiptData['moneyPaidFor'] = "Other Fee";
    }
  }
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

  <!-- End layout styles -->

</head>

<body>
  <div class="container-scroller">
    <!-- partial:partials/_navbar.html -->
    <?php include_once('includes/header.php'); ?>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:partials/_sidebar.html -->
      <?php include_once('includes/sidebar.php'); ?>
      <!-- partial -->
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
              <form method="get">
                <div class="form-group">
                  <label>Search Receipt</label>
                  <input type="text" name="receipt_no" required class="form-control">
                </div>
                <button type="submit" name="search" class="btn btn-primary mt-2">Search</button>
              </form>

            </div>
            <?php if ($receiptData): ?>
              <div class="card mt-4 shadow">
                <div class="card-header bg-dark text-white d-flex justify-content-between">
                  <span><strong>Receipt No:</strong> <?= htmlspecialchars($receiptData['receipt_no']) ?></span>
                  <span><strong>Date:</strong> <?= $receiptData['receiptDate'] ?></span>
                </div>
                <div class="card-body">
                  <!-- Student Details -->
                  <h5 class="text-primary border-bottom pb-2">Student Details</h5>
                  <div class="row mb-3">
                    <div class="col-md-4 mb-2"><strong>Student ID:</strong> <?= htmlspecialchars($receiptData['StuID']) ?>
                    </div>
                    <div class="col-md-4 mb-2"><strong>Name:</strong> <?= htmlspecialchars($receiptData['StudentName']) ?>
                    </div>
                    <div class="col-md-4 mb-2"><strong>Class:</strong>
                      <?= htmlspecialchars($receiptData['StudentClass']) ?></div>
                    <div class="col-md-4 mb-2"><strong>Gender:</strong> <?= htmlspecialchars($receiptData['Gender']) ?>
                    </div>
                    <div class="col-md-4 mb-2"><strong>DOB:</strong> <?= date("d-m-Y", strtotime($receiptData['DOB'])) ?>
                    </div>
                    <div class="col-md-4 mb-2"><strong>Father's Name:</strong>
                      <?= htmlspecialchars($receiptData['FatherName']) ?></div>
                    <div class="col-md-4 mb-2"><strong>Mother's Name:</strong>
                      <?= htmlspecialchars($receiptData['MotherName']) ?></div>
                    <div class="col-md-4 mb-2"><strong>Contact:</strong>
                      <?= htmlspecialchars($receiptData['ContactNumber']) ?> /
                      <?= htmlspecialchars($receiptData['AltenateNumber']) ?></div>
                    <div class="col-md-4 mb-2"><strong>Address:</strong> <?= htmlspecialchars($receiptData['Address']) ?>
                    </div>

                  </div>

                  <!-- Payment Details -->
                  <h5 class="text-primary border-bottom pb-2">Payment Details</h5>
                  <div class="row">
                    <?php if ($receiptData['fee_id'] == 1 || $receiptData['fee_id'] == 5): ?>
                      <div class="col-md-4 mb-2">
                        <strong>Month (Receipt Date):</strong> <?= $receiptData['receiptMonth'] ?>
                      </div>
                    <?php endif; ?>
                    <div class="col-md-4 mb-2"><strong>Paid Amount:</strong>
                      ₹<?= number_format($receiptData['paid_amt'], 2) ?></div>

                    <?php if ($receiptData['fee_id'] == 0 || $receiptData['fee_id'] == 0): ?>
                      <div class="col-md-4 mb-2">
                        <strong>Payment For:</strong> <?= $receiptData['payment_type'] ?>
                      </div>
                    <?php endif; ?>
                    <div class="col-md-4 mb-2"><strong>Money Paid For:</strong>
                      <?= htmlspecialchars($receiptData['moneyPaidFor']) ?></div>
                  </div>
                </div>
              </div>
            <?php elseif (isset($_GET['receipt_no'])): ?>
              <div class="alert alert-danger mt-4">❌ No record found for receipt no:
                <?= htmlspecialchars($_GET['receipt_no']) ?></div>
            <?php endif; ?>
          </div>







        </div>
        <!-- content-wrapper ends -->
        <!-- partial:partials/_footer.html -->
        <?php include_once('includes/footer.php'); ?>
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

</html><?php ?>