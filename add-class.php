<?php
session_start();

// Show all PHP errors for debugging (development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include('includes/dbconnection.php');

// Message variables
$message = '';
$messageType = ''; // success or danger

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
} else {
    if (isset($_POST['submit'])) {
        $cname = trim($_POST['cname']);
        $section = trim($_POST['section']);

        // Case-insensitive checks using LOWER()
        // 1. Check if same ClassName & Section already exists
        $sqlExact = "SELECT COUNT(*) FROM tblclass 
                     WHERE LOWER(ClassName) = LOWER(:cname) 
                     AND (LOWER(Section) = LOWER(:section) OR (Section IS NULL AND :section = ''))";
        $queryExact = $dbh->prepare($sqlExact);
        $queryExact->bindParam(':cname', $cname, PDO::PARAM_STR);
        $queryExact->bindParam(':section', $section, PDO::PARAM_STR);
        $queryExact->execute();
        $existsExact = $queryExact->fetchColumn();

        // 2. Special rule: If class without section exists, block adding with section
        $sqlWithoutSec = "SELECT COUNT(*) FROM tblclass 
                          WHERE LOWER(ClassName) = LOWER(:cname) 
                          AND (Section IS NULL OR Section = '')";
        $queryWithoutSec = $dbh->prepare($sqlWithoutSec);
        $queryWithoutSec->bindParam(':cname', $cname, PDO::PARAM_STR);
        $queryWithoutSec->execute();
        $existsWithoutSec = $queryWithoutSec->fetchColumn();

        // 3. If class with section exists, block adding without section
        $sqlWithSec = "SELECT COUNT(*) FROM tblclass 
                       WHERE LOWER(ClassName) = LOWER(:cname) 
                       AND Section IS NOT NULL 
                       AND Section != ''";
        $queryWithSec = $dbh->prepare($sqlWithSec);
        $queryWithSec->bindParam(':cname', $cname, PDO::PARAM_STR);
        $queryWithSec->execute();
        $existsWithSec = $queryWithSec->fetchColumn();

        if ($existsExact > 0) {
            $message = "This Class and Section combination already exists.";
            $messageType = "danger";
        } elseif ($section !== '' && $existsWithoutSec > 0) {
            $message = "Class '$cname' already exists without section. You cannot add it with a section.";
            $messageType = "danger";
        } elseif ($section === '' && $existsWithSec > 0) {
            $message = "Class '$cname' already exists with a section. You cannot add it without a section.";
            $messageType = "danger";
        } else {
            // Insert record
            $sqlInsert = "INSERT INTO tblclass (ClassName, Section) VALUES (:cname, :section)";
            $queryInsert = $dbh->prepare($sqlInsert);
            $queryInsert->bindParam(':cname', $cname, PDO::PARAM_STR);
            $queryInsert->bindParam(':section', $section, PDO::PARAM_STR);
            $queryInsert->execute();

            $LastInsertId = $dbh->lastInsertId();
            if ($LastInsertId > 0) {
                $message = "Class has been added successfully.";
                $messageType = "success";
            } else {
                $message = "Something went wrong. Please try again.";
                $messageType = "danger";
            }
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
                    <h3 class="page-title">Add Class</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Add Class</li>
                        </ol>
                    </nav>
                </div>

                <?php if (!empty($message)) { ?>
                    <div class="alert alert-<?php echo $messageType; ?>" id="msgBox">
                        <?php echo htmlentities($message); ?>
                    </div>
                <?php } ?>

                <div class="row">
                    <div class="col-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <!--<h4 class="card-title" style="text-align: center;">Add Class</h4>-->
                                <form class="forms-sample" method="post">
                                    <div class="form-group">
                                        <label>Class Name</label>
                                        <input type="text" name="cname" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Section (Optional)</label>
                                        <select name="section" class="form-control">
                                            <option value="">No Section</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary mr-2" name="submit">Add</button>
                                </form>
                            </div>
                        </div>
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
