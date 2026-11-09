<?php
session_start();

// Show all PHP errors for debugging (development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
include('includes/dbconnection.php');
// Database connection is provided by includes/dbconnection.php ($conn, $dbh)

$message = "";
$alert_class = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name        = trim($_POST['name']);
    $designation = trim($_POST['designation']);
    $joining     = $_POST['joining_date'];
    $aadhar      = trim($_POST['aadhar']);
    $address     = trim($_POST['address']);
    $salary      = $_POST['salary'];
    $card_no     = trim($_POST['card_no']);
    $phone_no    = trim($_POST['phone_no']);

    // Validation
    if (empty($name) || empty($card_no) || empty($phone_no) || empty($aadhar)) {
        $message = "Please fill in all required fields (Name, Staff ID, Phone No, Aadhar).";
        $alert_class = "alert-danger";
    } elseif (!preg_match("/^\d{12}$/", $aadhar)) {
        $message = "Aadhar must be exactly 12 digits.";
        $alert_class = "alert-danger";
    } elseif (!preg_match("/^\d{10}$/", $phone_no)) {
        $message = "Phone number must be exactly 10 digits.";
        $alert_class = "alert-danger";
    } elseif ($salary < 0) {
        $message = "Salary cannot be negative.";
        $alert_class = "alert-danger";
    } else {
        // Check if Aadhar already exists
        $stmt = $conn->prepare("SELECT staff_id FROM tblstaff WHERE aadhar = ?");
        $stmt->bind_param("s", $aadhar);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = "This Aadhar number is already registered!";
            $alert_class = "alert-danger";
            $stmt->close();
        } else {
            $stmt->close();
            // Check if Staff ID already exists
            $stmt = $conn->prepare("SELECT staff_id FROM tblstaff WHERE card_no = ?");
            $stmt->bind_param("s", $card_no);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $message = "This Staff ID is already registered!";
                $alert_class = "alert-danger";
                $stmt->close();
            } else {
                $stmt->close();
                // Upload photo
                $photo = "";
                if (!empty($_FILES['photo']['name'])) {
                    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $photo = md5($_FILES['photo']['name']).time().".".$ext;
                    move_uploaded_file($_FILES['photo']['tmp_name'], "images/".$photo);
                }

                $sql = "INSERT INTO tblstaff 
                        (name, designation, joining_date, aadhar, address, salary, photo, card_no, phone_no) 
                        VALUES 
                        ('$name','$designation','$joining','$aadhar','$address','$salary','$photo','$card_no','$phone_no')";

                if ($conn->query($sql)) {
                    $message = "Staff Registered Successfully!";
                    $alert_class = "alert-success";
                } else {
                    $message = "Error: " . $conn->error;
                    $alert_class = "alert-danger";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Student Management System || Add Staff</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/clean-theme.css" />

    <script>
        // Hide alert after 3 seconds
        function hideAlert() {
            const alertBox = document.getElementById('alert-box');
            if(alertBox) {
                setTimeout(() => { alertBox.style.display = 'none'; }, 3000);
            }
        }
        window.onload = hideAlert;
    </script>
</head>
<body>
<div class="container-scroller">
    <?php include_once('includes/header.php'); ?>
    <div class="container-fluid page-body-wrapper">
        <?php include_once('includes/sidebar.php'); ?>
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="page-title mb-1 font-weight-bold" style="color: #0f172a;">Staff Registration</h3>
                        <p class="text-muted small mb-0">Add new teaching and administrative staff members</p>
                    </div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0" style="background: transparent; padding: 0;">
                            <li class="breadcrumb-item"><a href="dashboard.php" style="color: #6366f1; text-decoration: none;">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="manage-staff.php" style="color: #6366f1; text-decoration: none;">Staff Directory</a></li>
                            <li class="breadcrumb-item active text-muted" aria-current="page">Add Staff</li>
                        </ol>
                    </nav>
                </div>

                <?php if (!empty($message)) : ?>
                    <div id="alert-box" class="alert <?php echo $alert_class; ?> custom-stock-alert mb-4">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm border-0" style="border-radius: 12px; background: #ffffff;">
                    <div class="card-header bg-white border-bottom py-3" style="border-radius: 12px 12px 0 0;">
                        <div class="d-flex align-items-center">
                            <div class="mr-3" style="width: 38px; height: 38px; border-radius: 10px; background: rgba(99, 102, 241, 0.12); color: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                <i class="icon-user-follow"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 font-weight-bold" style="color: #0f172a; font-size: 1.05rem;">Employee Profile Form</h5>
                                <p class="text-muted small mb-0">Fill in employee credentials and administrative details</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <form method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-600 text-dark small mb-1">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" placeholder="Enter staff full name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-600 text-dark small mb-1">Designation / Role</label>
                                    <input type="text" name="designation" class="form-control" placeholder="e.g. Mathematics Teacher / Lab Assistant">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-600 text-dark small mb-1">Joining Date</label>
                                    <input type="date" name="joining_date" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-600 text-dark small mb-1">Aadhar Number <span class="text-danger">*</span></label>
                                    <input type="text" name="aadhar" class="form-control" maxlength="12" placeholder="12-digit Aadhar number" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-600 text-dark small mb-1">Staff ID / Card No <span class="text-danger">*</span></label>
                                    <input type="text" name="card_no" class="form-control" placeholder="Unique Staff ID (e.g. EMP101)" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-600 text-dark small mb-1">Mobile Phone Number <span class="text-danger">*</span></label>
                                    <input type="text" name="phone_no" class="form-control" maxlength="10" placeholder="10-digit mobile number" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-600 text-dark small mb-1">Monthly Salary (₹)</label>
                                    <input type="number" name="salary" class="form-control" placeholder="e.g. 25000">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-600 text-dark small mb-1">Profile Photograph</label>
                                    <input type="file" name="photo" class="form-control">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label font-weight-600 text-dark small mb-1">Residential Address</label>
                                <textarea name="address" class="form-control" placeholder="Enter residential address" rows="3"></textarea>
                            </div>

                            <div class="d-flex align-items-center" style="gap: 12px;">
                                <button type="submit" class="btn btn-primary py-2 px-4 font-weight-bold shadow-sm" style="border-radius: 8px;">
                                    <i class="icon-check mr-2"></i> Register Staff Member
                                </button>
                                <a href="manage-staff.php" class="btn btn-light border py-2 px-3 text-secondary font-weight-600" style="border-radius: 8px;">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

               
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
