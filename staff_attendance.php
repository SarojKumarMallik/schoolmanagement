<?php
session_start();

// Show all PHP errors for debugging (development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include('includes/dbconnection.php');
// Database connection is provided by includes/dbconnection.php ($conn, $dbh)

$message = "";
$type = "";

// Handle Save Attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_attendance'])) {
    $staff_id      = $_POST['staff_id'];
    $month_year    = $_POST['month_year'];
    $total_present = $_POST['total_present'];

    if (empty($staff_id) || empty($month_year) || $total_present === "") {
        $message = "All fields are required!";
        $type = "danger";
    } else {
        [$year, $month] = explode("-", $month_year);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        if ($total_present > $daysInMonth) {
            $message = "Present days cannot exceed total days ($daysInMonth)!";
            $type = "danger";
        } else {
            $total_absent  = $daysInMonth - $total_present;

            $sql = "INSERT INTO tblstaffattendance (staff_id, month_year, total_present, total_absent) 
                    VALUES ('$staff_id','$month_year','$total_present','$total_absent')";
            if ($conn->query($sql)) {
                $message = "Attendance saved successfully!";
                $type = "success";
            } else {
                $message = "Error saving attendance!";
                $type = "danger";
            }
        }
    }
}

// Handle Update Attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_attendance'])) {
    $staff_id      = $_POST['staff_id'];
    $month_year    = $_POST['month_year'];
    $total_present = $_POST['total_present_edit'];

    [$year, $month] = explode("-", $month_year);
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    if ($total_present > $daysInMonth) {
        $message = "Present days cannot exceed total days ($daysInMonth)!";
        $type = "danger";
    } else {
        $total_absent = $daysInMonth - $total_present;
        $sql = "UPDATE tblstaffattendance 
                SET total_present='$total_present', total_absent='$total_absent'
                WHERE staff_id='$staff_id' AND month_year='$month_year'";
        if ($conn->query($sql)) {
            $message = "Attendance updated successfully!";
            $type = "success";
        } else {
            $message = "Error updating attendance!";
            $type = "danger";
        }
    }
}

// Handle Search
$search_month = $_GET['search_month'] ?? "";
$attendanceData = [];
if ($search_month) {
    $attendanceData = $conn->query("
        SELECT s.name, s.designation, a.total_present, a.total_absent, a.month_year, a.staff_id 
        FROM tblstaffattendance a 
        JOIN tblstaff s ON s.staff_id = a.staff_id 
        WHERE a.month_year = '$search_month'
    ");
}

// Fetch staff list
$staff = $conn->query("SELECT * FROM tblstaff");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Staff Attendance | Student Management</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/clean-theme.css" />
    <script>
        function calculateAbsent() {
            let monthInput = document.getElementById("month_year").value;
            let present = parseInt(document.getElementById("total_present").value) || 0;
            let absentField = document.getElementById("total_absent");

            if (monthInput) {
                let [year, month] = monthInput.split("-");
                let daysInMonth = new Date(year, month, 0).getDate();
                if (present > daysInMonth) {
                    // remove alert(), just reset values
                    document.getElementById("total_present").value = "";
                    absentField.value = "";
                    return;
                }
                absentField.value = daysInMonth - present;
            }
        }

        function enableEdit(rowId) {
            document.querySelector("#present_"+rowId).removeAttribute("readonly");
            document.querySelector("#editBtn_"+rowId).classList.add("d-none");
            document.querySelector("#updateBtn_"+rowId).classList.remove("d-none");
        }

        function updateAbsent(rowId, daysInMonth) {
            let present = parseInt(document.querySelector("#present_"+rowId).value) || 0;
            if(present > daysInMonth){
                document.querySelector("#present_"+rowId).value="";
                document.querySelector("#absent_"+rowId).value="";
                return;
            }
            document.querySelector("#absent_"+rowId).value = daysInMonth - present;
        }
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
                    <h3 class="page-title">Staff Attendance</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Staff Attendance</li>
                        </ol>
                    </nav>
                </div>

               <!-- Bootstrap Alert -->
<?php if($message): ?>
    <div class="alert alert-<?= $type ?>" id="autoDismissAlert"><?= $message ?></div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(() => {
                let alertEl = document.getElementById("autoDismissAlert");
                if(alertEl){
                    alertEl.classList.add("fade");
                    setTimeout(()=> alertEl.remove(), 500); // Remove after fade
                }
            }, 3000); // 3 seconds
        });
    </script>
<?php endif; ?>


    <!-- Attendance Entry Form Card -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; background: #ffffff;">
        <div class="card-header bg-white border-bottom py-3" style="border-radius: 12px 12px 0 0;">
            <div class="d-flex align-items-center">
                <div class="mr-3" style="width: 38px; height: 38px; border-radius: 10px; background: rgba(99, 102, 241, 0.12); color: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="icon-calendar"></i>
                </div>
                <div>
                    <h5 class="mb-0 font-weight-bold" style="color: #0f172a; font-size: 1.05rem;">Mark Staff Attendance</h5>
                    <p class="text-muted small mb-0">Record monthly employee presence and absences</p>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <form method="post">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-600 text-dark small mb-1">Staff Member <span class="text-danger">*</span></label>
                        <select name="staff_id" class="form-control" required style="height: 44px; border-radius: 8px;">
                            <option value="">-- Choose Staff Member --</option>
                            <?php while($row = $staff->fetch_assoc()){ ?>
                                <option value="<?= $row['staff_id'] ?>"><?= htmlspecialchars($row['name']) ?> (<?= htmlspecialchars($row['designation']) ?>)</option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-600 text-dark small mb-1">Attendance Month <span class="text-danger">*</span></label>
                        <input type="month" id="month_year" name="month_year" class="form-control" onchange="calculateAbsent()" required style="height: 44px; border-radius: 8px;">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-600 text-dark small mb-1">Total Present Days <span class="text-danger">*</span></label>
                        <input type="number" id="total_present" name="total_present" class="form-control" min="0" placeholder="e.g. 24" onchange="calculateAbsent()" required style="height: 44px; border-radius: 8px;">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-600 text-dark small mb-1">Total Absent Days (Auto)</label>
                        <input type="number" id="total_absent" name="total_absent" class="form-control bg-light font-weight-bold" readonly placeholder="Auto-calculated" style="height: 44px; border-radius: 8px;">
                    </div>
                </div>

                <button type="submit" name="save_attendance" class="btn btn-primary py-2 px-4 font-weight-bold shadow-sm" style="border-radius: 8px;">
                    <i class="icon-check mr-2"></i> Save Attendance Record
                </button>
            </form>
        </div>
    </div>

    <!-- Search Form Card -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; background: #ffffff;">
        <div class="card-header bg-white border-bottom py-3" style="border-radius: 12px 12px 0 0;">
            <div class="d-flex align-items-center">
                <div class="mr-3" style="width: 38px; height: 38px; border-radius: 10px; background: rgba(16, 185, 129, 0.12); color: #059669; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="icon-magnifier"></i>
                </div>
                <div>
                    <h5 class="mb-0 font-weight-bold" style="color: #0f172a; font-size: 1.05rem;">Search Attendance by Month</h5>
                    <p class="text-muted small mb-0">View and update saved attendance records</p>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <form method="get">
                <div class="row align-items-end">
                    <div class="col-md-5 mb-3 mb-md-0">
                        <label class="form-label font-weight-600 text-dark small mb-1">Filter Month</label>
                        <input type="month" name="search_month" class="form-control" value="<?= htmlspecialchars($search_month) ?>" style="height: 44px; border-radius: 8px;">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary py-2 px-4 font-weight-bold shadow-sm w-100" style="border-radius: 8px; height: 44px;">
                            Search Records
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Attendance Table Card -->
    <?php if ($search_month && $attendanceData->num_rows > 0) { ?>
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; background: #ffffff;">
            <div class="card-header bg-white border-bottom py-3" style="border-radius: 12px 12px 0 0;">
                <h5 class="mb-0 font-weight-bold" style="color: #0f172a; font-size: 1.05rem;">
                    Attendance Report — <?= date("F Y", strtotime($search_month."-01")) ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background: #f8fafc;">
                            <tr style="border-bottom: 2px solid #e2e8f0; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">
                                <th class="py-3 px-3">Staff Name</th>
                                <th class="py-3 px-3">Designation</th>
                                <th class="py-3 px-3" style="width: 140px;">Total Present</th>
                                <th class="py-3 px-3" style="width: 140px;">Total Absent</th>
                                <th class="py-3 px-3 text-center" style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while($row = $attendanceData->fetch_assoc()) { 
                            [$year,$month] = explode("-", $row['month_year']);
                            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                        ?>
                            <tr>
                                <form method="post">
                                    <td class="py-3 px-3 font-weight-bold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                                    <td class="py-3 px-3 text-secondary"><?= htmlspecialchars($row['designation']) ?></td>
                                    <td class="py-3 px-3">
                                        <input type="number" id="present_<?= $row['staff_id'] ?>" 
                                            name="total_present_edit" 
                                            class="form-control form-control-sm font-weight-bold text-success" 
                                            value="<?= $row['total_present'] ?>" 
                                            readonly 
                                            onchange="updateAbsent(<?= $row['staff_id'] ?>,<?= $daysInMonth ?>)">
                                    </td>
                                    <td class="py-3 px-3">
                                        <input type="number" id="absent_<?= $row['staff_id'] ?>" 
                                            name="total_absent_edit" 
                                            class="form-control form-control-sm bg-light font-weight-bold text-danger" 
                                            value="<?= $row['total_absent'] ?>" readonly>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <input type="hidden" name="staff_id" value="<?= $row['staff_id'] ?>">
                                        <input type="hidden" name="month_year" value="<?= $row['month_year'] ?>">
                                        <button type="button" id="editBtn_<?= $row['staff_id'] ?>" class="btn btn-warning btn-sm font-weight-600 px-3" onclick="enableEdit(<?= $row['staff_id'] ?>)" style="border-radius: 6px;">Edit</button>
                                        <button type="submit" name="update_attendance" id="updateBtn_<?= $row['staff_id'] ?>" class="btn btn-success btn-sm font-weight-600 px-3 d-none" style="border-radius: 6px;">Save</button>
                                    </td>
                                </form>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php } elseif ($search_month) { ?>
        <div class="alert alert-info custom-stock-alert">No attendance records found for <?= date("F Y", strtotime($search_month."-01")) ?>.</div>
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
