<?php
session_start();

// Show all PHP errors for debugging (development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include('includes/dbconnection.php');

$host = "localhost";
$user = "root";
$pass = "root";
$db   = "studentmsdb";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$salaryRecords = [];
$error = '';
$staff = [];
$card_no = '';
$filter_month = '';

if (isset($_POST['card_no'])) {
    $card_no = trim($conn->real_escape_string($_POST['card_no']));
    if (!empty($_POST['filter_month'])) {
        $filter_month = $_POST['filter_month'];
    }

    if (empty($card_no)) {
        $error = "Please enter a Card No.";
    } else {
        // Fetch staff info
        $staffResult = $conn->query("SELECT * FROM tblstaff WHERE TRIM(card_no)='$card_no'");
        if ($staffResult && $staffResult->num_rows > 0) {
            $staff = $staffResult->fetch_assoc();
            $staff_id = $staff['staff_id'];

            // Fetch salary records sorted by paid_date DESC
            $salaryResult = $conn->query("SELECT * FROM tblstaffsalary WHERE staff_id=$staff_id ORDER BY STR_TO_DATE(paid_date, '%Y-%m-%d') DESC");
            if ($salaryResult && $salaryResult->num_rows > 0) {
                while ($row = $salaryResult->fetch_assoc()) {
                    $salaryRecords[] = $row;
                }
            }
        } else {
            $error = "No staff found with Card No: $card_no";
        }
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Student Management System || Add Class</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css" />
    
   
</head>
<body>
<div class="container-scroller">
    <?php include_once('includes/header.php'); ?>
    <div class="container-fluid page-body-wrapper">
        <?php include_once('includes/sidebar.php'); ?>
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h3 class="page-title">Staff Salary</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Staff Salary</li>
                        </ol>
                    </nav>
                </div>

             <!-- Search Form -->
    <div class="card shadow-sm mb-4 p-4">
        <!-- <h3 class="text-center mb-4">Staff Salary Lookup</h3> -->
        <form method="post" class="row g-3">
            <div class="col-md-9">
                <input type="text" name="card_no" class="form-control form-control-lg" placeholder="Enter Staff ID No" value="<?= htmlspecialchars($card_no) ?>" required>
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Search</button>
            </div>
        </form>
    </div>

    <!-- Error Message -->
    <?php if (!empty($error)) { ?>
        <div class="alert alert-warning text-center"><?= $error ?></div>
    <?php } ?>

    <!-- Staff Info Card -->
    <?php if (!empty($staff)) { ?>
        <div class="card shadow-sm mb-4 p-4">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <img src="images/<?= htmlspecialchars($staff['photo']) ?>" alt="Staff Photo" class="img-fluid rounded-0" style="height:180px;width:80%">
                </div>
                <div class="col-md-10">
                    <h4><?= htmlspecialchars($staff['name']) ?></h4>
                    <p class="mb-0">
                        <strong>Designation:</strong> <?= htmlspecialchars($staff['designation']) ?> |
                        <strong>Joining Date:</strong> <?= htmlspecialchars($staff['joining_date']) ?> |
                        <strong>Aadhar:</strong> <?= htmlspecialchars($staff['aadhar']) ?> |
                        <strong>Salary:</strong> ₹<?= htmlspecialchars($staff['salary']) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Salary Table Card with Month Filter -->
        <div class="card shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Salary Transactions</h5>
                <form method="post" class="d-flex gap-2">
                    <input type="hidden" name="card_no" value="<?= htmlspecialchars($card_no) ?>">
                    <select name="filter_month" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Months</option>
                        <?php
                        // Fetch distinct months for dropdown
                        $monthsResult = $conn->query("SELECT DISTINCT month_year FROM tblstaffsalary WHERE staff_id=".$staff['staff_id']." ORDER BY STR_TO_DATE(month_year, '%Y-%m') DESC");
                        while($m = $monthsResult->fetch_assoc()){
                            $monthValue = $m['month_year']; // e.g., 2025-08
                            $selected = ($filter_month == $monthValue) ? 'selected' : '';
                            $formattedMonth = date("F Y", strtotime($monthValue . "-01")); // e.g., August 2025
                            echo "<option value='".$monthValue."' $selected>".$formattedMonth."</option>";
                        }
                        ?>
                    </select>
                </form>
            </div>

            <?php
            // Apply month filter if selected
            $filteredRecords = $salaryRecords;
            if (!empty($filter_month)) {
                $filteredRecords = array_filter($salaryRecords, function($s) use ($filter_month){
                    return $s['month_year'] == $filter_month;
                });
            }
            ?>

            <?php if (!empty($filteredRecords)) { ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover text-center">
                        <thead class="table-dark">
                            <tr>
                           
                                <th>Month-Year</th>
                                <th>Amount (₹)</th>
                                <th>Paid Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filteredRecords as $index => $s) { 
                                $formattedMonth = date("F Y", strtotime($s['month_year']."-01"));
                                $formattedPaidDate = date("d-M-Y", strtotime($s['paid_date']));
                            ?>
                                <tr>
                                    
                                    <td><?= $formattedMonth ?></td>
                                    <td><?= $s['amount'] ?></td>
                                    <td><?= $formattedPaidDate ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="alert alert-info text-center">No salary records found for this selection.</div>
            <?php } ?>
        </div>
    <?php } ?>

               
            </div>
            <?php include_once('includes/footer.php'); ?>
        </div>
    </div>
</div>
<script src="vendors/js/vendor.bundle.base.js"></script>
<script src="vendors/select2/select2.min.js"></script>
<script src="vendors/typeahead.js/typeahead.bundle.min.js"></script>
<script src="js/off-canvas.js"></script>
<script src="js/misc.js"></script>
<script src="js/typeahead.js"></script>
<script src="js/select2.js"></script>

<!-- Auto-hide message after 5 seconds -->
<script>
    setTimeout(function(){
        var msgBox = document.getElementById('msgBox');
        if(msgBox){
            msgBox.style.transition = "opacity 0.5s ease";
            msgBox.style.opacity = "0";
            setTimeout(function(){
                msgBox.remove();
            }, 500);
        }
    }, 5000);
</script>

</body>
</html>
