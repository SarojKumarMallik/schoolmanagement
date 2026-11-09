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
// Get staff ID
if (!isset($_GET['id'])) {
    header("Location: manage-staff.php");
    exit;
}

$id = $_GET['id'];

// Fetch staff data
$res = $conn->query("SELECT * FROM tblstaff WHERE staff_id='$id'");
if (!$res || $res->num_rows == 0) {
    die("Staff not found!");
}
$staff = $res->fetch_assoc();

// Handle Update
if (isset($_POST['update'])) {
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
        $message = "Please fill in all required fields.";
        $alert_class = "alert-danger";
    } elseif (!preg_match("/^\d{12}$/", $aadhar)) {
        $message = "Aadhar must be exactly 12 digits.";
        $alert_class = "alert-danger";
    } elseif (!preg_match("/^\d{10}$/", $phone_no)) {
        $message = "Phone number must be exactly 10 digits.";
        $alert_class = "alert-danger";
    } else {
        // Check unique Aadhar except current staff
        $stmt = $conn->prepare("SELECT staff_id FROM tblstaff WHERE aadhar=? AND staff_id<>?");
        $stmt->bind_param("si", $aadhar, $id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = "This Aadhar is already registered with another staff!";
            $alert_class = "alert-danger";
        } else {
            $photo_sql = "";
            if (!empty($_FILES['photo']['name'])) {
                $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $photo = md5($_FILES['photo']['name']).time().".".$ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], "images/".$photo);
                // Delete old photo
                if (!empty($staff['photo']) && file_exists("images/".$staff['photo'])) unlink("images/".$staff['photo']);
                $photo_sql = ", photo='$photo'";
            }

            $sql = "UPDATE tblstaff SET 
                    name='$name', designation='$designation', joining_date='$joining',
                    aadhar='$aadhar', address='$address', salary='$salary',
                    card_no='$card_no', phone_no='$phone_no' $photo_sql
                    WHERE staff_id='$id'";

            if ($conn->query($sql)) {
                header("Location: manage-staff.php");
                exit;
            } else {
                $message = "Error: " . $conn->error;
                $alert_class = "alert-danger";
            }
        }
        $stmt->close();
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
    
    <script>
        function hideAlert() {
            const alertBox = document.getElementById('alert-box');
            if(alertBox) setTimeout(()=>{ alertBox.style.display='none'; }, 3000);
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
                <div class="page-header">
                    <h3 class="page-title">Staff Salary</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Staff Salary</li>
                        </ol>
                    </nav>
                </div>

             <?php if(!empty($message)) echo "<div id='alert-box' class='alert $alert_class'>$message</div>"; ?>

    <form method="post" enctype="multipart/form-data" class="card p-4 shadow-sm">
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo $staff['name']; ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Designation</label>
            <input type="text" name="designation" class="form-control" value="<?php echo $staff['designation']; ?>">
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Joining Date</label>
            <input type="date" name="joining_date" class="form-control" value="<?php echo $staff['joining_date']; ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Aadhar No</label>
            <input type="text" name="aadhar" class="form-control" maxlength="12" value="<?php echo $staff['aadhar']; ?>" required>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control"><?php echo $staff['address']; ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Salary</label>
            <input type="number" name="salary" class="form-control" value="<?php echo floor($staff['salary']); ?>">
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Staff ID</label>
            <input type="text" name="card_no" class="form-control" value="<?php echo $staff['card_no']; ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Phone No</label>
            <input type="text" name="phone_no" class="form-control" maxlength="10" value="<?php echo $staff['phone_no']; ?>" required>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Photo</label>
        <input type="file" name="photo" class="form-control">

        <?php if(!empty($staff['photo'])): ?>
            <div class="card mt-3 shadow-sm" style="width: 150px;">
                <img src="images/<?php echo $staff['photo']; ?>" class="card-img-top img-thumbnail" alt="Staff Photo">
                <div class="card-body p-2 text-center">
                    <small class="text-muted">Current Photo</small>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="d-flex justify-content-end gap-5 mt-4">
        <button type="submit" name="update" class="btn btn-success px-4 m-1">
            <i class="bi bi-save"></i> Update Staff
        </button>
        <a href="manage-staff.php" class="btn btn-outline-secondary px-4 m-1">
            <i class="bi bi-x-circle"></i> Cancel
        </a>
    </div>
</form>

               
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
