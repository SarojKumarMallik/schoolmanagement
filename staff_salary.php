<?php
session_start();

// Show all PHP errors for debugging (development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include('includes/dbconnection.php');
// Database connection is provided by includes/dbconnection.php ($conn, $dbh)
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
    <style>
        .alert {
            transition: opacity 0.5s ease-out;
        }
    </style>
    <script>
    setTimeout(function() {
        let box = document.getElementById("msgBox");
        if (box) {
            box.style.transition = "opacity 0.5s ease";
            box.style.opacity = "0";
            setTimeout(() => box.remove(), 500); // remove after fade-out
        }
    }, 3000);
</script>
    
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

             <?php
    // Pay salary
    if (isset($_POST['pay_salary'])) {
        $staff_id = $_POST['staff_id'];
        $month    = $_POST['month_year'];
        $amount   = $_POST['amount'];
        $paid     = $_POST['paid_date'];

        // Check total paid for this staff & month
        $check = $conn->query("SELECT IFNULL(SUM(amount),0) as total_paid FROM tblstaffsalary WHERE staff_id='$staff_id' AND month_year='$month'");
        $row_check = $check->fetch_assoc();
        $total_paid = $row_check['total_paid'];

        if ($total_paid == $amount) {
            echo "<div class='alert alert-warning' id='msgBox'>Salary already fully paid!</div>";
        } else {
            $sql = "INSERT INTO tblstaffsalary (staff_id, month_year, amount, paid_date) 
                    VALUES ('$staff_id','$month','$amount','$paid')";
            if ($conn->query($sql)) {
                echo "<div class='alert alert-success' id='msgBox'>Salary Paid Successfully!</div>";
            } else {
                echo "<div class='alert alert-danger' id='msgBox'>Error: ".$conn->error."</div>";
            }
        }
    }
    ?>

    <!-- Month Search Form -->
    <form method="get" class="card p-4 shadow-sm mb-4">
        <div class="mb-3">
            <label>Select Month</label>
            <input type="month" name="month_year" class="form-control" required value="<?= isset($_GET['month_year']) ? $_GET['month_year'] : '' ?>">
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <?php
    if (isset($_GET['month_year'])) {
        $month = $_GET['month_year'];
        $total_days = date("t", strtotime($month . "-01"));

        // Fetch staff with present count
        $sql = "SELECT s.staff_id, s.name, s.designation, s.salary,
                       IFNULL(SUM(a.total_present),0) as present_days
                FROM tblstaff s
                LEFT JOIN tblstaffattendance a 
                  ON s.staff_id = a.staff_id 
                  AND a.month_year = '$month'
                GROUP BY s.staff_id";

        $result = $conn->query($sql);

        if ($result === false) {
            echo "<div class='alert alert-danger'>Query Error: ".$conn->error."</div>";
        } elseif ($result->num_rows > 0) {
            echo "<table class='table table-bordered table-striped table-responsive'>";
            echo "<thead class='table-dark'>
                    <tr>
                        <th>Name</th>
                        <th>Designation</th>
                        <th>Salary</th>
                        <th>Present Days</th>
                        <th>Absent Days</th>
                        <th>Total Paid</th>
                        <th>Unpaid Amount</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Pay</th>
                    </tr>
                  </thead><tbody>";

            while ($row = $result->fetch_assoc()) {
                $present = $row['present_days'];
                $absent = $total_days - $present;
                $per_day = $row['salary'] / $total_days;
                $payable = floor($present * $per_day); // use floor to remove decimals

                // Get total paid
                $check = $conn->query("SELECT IFNULL(SUM(amount),0) as total_paid FROM tblstaffsalary WHERE staff_id='{$row['staff_id']}' AND month_year='$month'");
                $paid_row = $check->fetch_assoc();
                $total_paid = floor($paid_row['total_paid']); // floor

                $unpaid = $payable - $total_paid;
                if ($unpaid < 0) $unpaid = 0;

                echo "<tr>
        <td>{$row['name']}</td>
        <td>{$row['designation']}</td>
        <td>" . floor($row['salary']) . "</td>
        <td>{$present}</td>
        <td>{$absent}</td>
        <td>{$total_paid}</td>
        <td>{$unpaid}</td>";


                if ($unpaid > 0) {
                    echo "<form method='post'>
                            <input type='hidden' name='staff_id' value='{$row['staff_id']}'>
                            <input type='hidden' name='month_year' value='{$month}'>
                            <td><input type='text' name='amount' class='form-control amount-input' placeholder='Enter Amount' required min='1' max='{$unpaid}'></td>
                            <td><input type='date' name='paid_date' class='form-control' required></td>
                            <td><button type='submit' name='pay_salary' class='btn btn-success btn-sm'>Pay</button></td>
                          </form>";
                } else {
                    echo "<td colspan='3'><span class='badge bg-success'>Fully Paid</span></td>";
                }

                echo "</tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<div class='alert alert-warning'>No staff found!</div>";
        }
    }
    ?>

               
            </div>
            <?php include_once('includes/footer.php'); ?>
        </div>
    </div>
</div>
<style>
    .amount-input {
    width: 120px;       /* wider input */
    min-width: 100px;
    text-align: left;  /* aligns numbers nicely */
}
</style>
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
