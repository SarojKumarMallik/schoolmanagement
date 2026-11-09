<?php
session_start();
include('includes/dbconnection.php');

$student = null;
$msg = "";

if (isset($_POST['search'])) {
    $stuID = $_POST['StuID'];

    // ✅ Check if student exists
    $sql = "SELECT * FROM tblstudent WHERE StuID=:stuID";
    $query = $dbh->prepare($sql);
    $query->bindParam(':stuID', $stuID, PDO::PARAM_STR);
    $query->execute();
    $student = $query->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        $msg = "⚠️ Student ID not found!";
    } else {
        // ✅ Check if TC already exists
        $sql2 = "SELECT * FROM tbltc WHERE student_id=:sid LIMIT 1";
        $q2 = $dbh->prepare($sql2);
        $q2->bindParam(':sid', $student['ID'], PDO::PARAM_INT);
        $q2->execute();
        if ($q2->rowCount() > 0) {
            $msg = "TC has already been generated for this student!";
            $student = null; // Hide TC form
        }
    }
}

if (isset($_POST['generate_tc'])) {
    $student_id = $_POST['student_id'];
    $reason     = trim($_POST['Reason']);
    $leavingDate = $_POST['LeavingDate'];
    $remarks    = trim($_POST['Remarks']);

    // ✅ Extra safety: check TC not already generated
    $check = $dbh->prepare("SELECT 1 FROM tbltc WHERE student_id=:sid");
    $check->bindParam(':sid', $student_id, PDO::PARAM_INT);
    $check->execute();

    if ($check->rowCount() > 0) {
        $msg = "⚠️ TC already exists for this student!";
    } else {
        // Insert TC record
        $sql = "INSERT INTO tbltc (student_id, Reason, LeavingDate, Remarks, CreatedAt) 
                VALUES (:student_id, :reason, :leavingDate, :remarks, NOW())";
        $query = $dbh->prepare($sql);
        $query->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $query->bindParam(':reason', $reason, PDO::PARAM_STR);
        $query->bindParam(':leavingDate', $leavingDate, PDO::PARAM_STR);
        $query->bindParam(':remarks', $remarks, PDO::PARAM_STR);
        $query->execute();

        // ✅ Deactivate student
        $update = $dbh->prepare("UPDATE tblstudent SET isActive=0 WHERE ID=:sid");
        $update->bindParam(':sid', $student_id, PDO::PARAM_INT);
        $update->execute();

        header("Location: tc_certificate.php?StuID=" . $_POST['StuID']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Student Management System || Transfer Certificate</title>
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
                    <h3 class="page-title">Student Transfer Certificate</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Student Transfer Certificate</li>
                        </ol>
                    </nav>
                </div>

                <!-- Search Box -->
                <div class="card shadow-sm p-4 mb-4">
                    <h4 class="mb-3">Search Student</h4>
                    <form method="POST" class="row g-3" onsubmit="return validateSearchForm()">
                        <div class="col-md-6">
                            <input type="text" name="StuID" class="form-control" placeholder="Enter Student ID" required>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" name="search" class="btn btn-primary w-100">Search</button>
                        </div>
                    </form>
                    <?php if (!empty($msg)) { ?>
                        <p id="msgBox" class="text-danger mt-2"><?= $msg ?></p>
                    <?php } ?>
                </div>

                <!-- TC Form -->
                <?php if ($student) { ?>
                    <div class="card shadow-lg p-4">
                        <h3 class="mb-3 text-center">Transfer Certificate Form</h3>
                        <form method="POST" onsubmit="return validateTCForm()">
                            <input type="hidden" name="student_id" value="<?= $student['ID'] ?>">
                            <input type="hidden" name="StuID" value="<?= $student['StuID'] ?>">

                            <div class="mb-3">
                                <label class="form-label">Student Name</label>
                                <input type="text" class="form-control" value="<?= $student['StudentName'] ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Class</label>
                                <input type="text" class="form-control" value="<?= $student['StudentClass'] ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason for Leaving <span class="text-danger">*</span></label>
                                <textarea name="Reason" class="form-control" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date of Leaving <span class="text-danger">*</span></label>
                                <input type="date" name="LeavingDate" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea name="Remarks" class="form-control"></textarea>
                            </div>
                            <div class="text-center">
                                <button type="submit" name="generate_tc" class="btn btn-success px-4">Generate TC</button>
                            </div>
                        </form>
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

    // ✅ JS Validation
    function validateSearchForm() {
        let stuID = document.querySelector("input[name='StuID']").value.trim();
        if (stuID === "") {
            alert("Please enter Student ID");
            return false;
        }
        return true;
    }

    function validateTCForm() {
        let reason = document.querySelector("textarea[name='Reason']").value.trim();
        let date = document.querySelector("input[name='LeavingDate']").value;

        if (reason === "" || date === "") {
            alert("Reason and Date of Leaving are required!");
            return false;
        }
        
         // ✅ Confirmation alert
    let studentName = document.querySelector("input[readonly]").value; // Student Name
    return confirm("Do you want to generate TC for " + studentName + "?");
        return true;
    }
</script>
</body>
</html>
