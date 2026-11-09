<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
} else {

    if (isset($_POST['submit'])) {
        $cname = $_POST['cname'];
        $section = $_POST['section'];
        $eid = $_GET['editid'];

        // Validation: same ClassName + Section exists (case-sensitive)
        $checksql = "SELECT ID FROM tblclass WHERE BINARY ClassName = :cname AND BINARY Section = :section AND ID != :eid";
        $checkquery = $dbh->prepare($checksql);
        $checkquery->bindParam(':cname', $cname, PDO::PARAM_STR);
        $checkquery->bindParam(':section', $section, PDO::PARAM_STR);
        $checkquery->bindParam(':eid', $eid, PDO::PARAM_STR);
        $checkquery->execute();

        if ($checkquery->rowCount() > 0) {
            // Redirect with error message
            header("Location: manage-class.php?error=" . urlencode("This Class with the same Section already exists."));
            exit();
        } else {
            // Update record
            $sql = "UPDATE tblclass SET ClassName = :cname, Section = :section WHERE ID = :eid";
            $query = $dbh->prepare($sql);
            $query->bindParam(':cname', $cname, PDO::PARAM_STR);
            $query->bindParam(':section', $section, PDO::PARAM_STR);
            $query->bindParam(':eid', $eid, PDO::PARAM_STR);
            $query->execute();

            // Redirect with success message
            header("Location: manage-class.php?msg=" . urlencode("Class updated successfully."));
            exit();
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Student Management System || Edit Class</title>
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
                    <h3 class="page-title">Edit Class</h3>
                </div>

                <div class="row">
                    <div class="col-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <!--<h4 class="card-title" style="text-align: center;">Edit Class</h4>-->

                                <form class="forms-sample" method="post">
                                    <?php
                                    $eid = $_GET['editid'];
                                    $sql = "SELECT * FROM tblclass WHERE ID = :eid";
                                    $query = $dbh->prepare($sql);
                                    $query->bindParam(':eid', $eid, PDO::PARAM_STR);
                                    $query->execute();
                                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                                    if ($query->rowCount() > 0) {
                                        foreach ($results as $row) {
                                    ?>
                                    <div class="form-group">
                                        <label>Class Name</label>
                                        <input type="text" name="cname" value="<?php echo htmlentities($row->ClassName); ?>" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Section</label>
                                        <select name="section" class="form-control" required>
                                            <option value="<?php echo htmlentities($row->Section); ?>"><?php echo htmlentities($row->Section); ?></option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                        </select>
                                    </div>
                                    <?php } } ?>
                                    <button type="submit" class="btn btn-primary mr-2" name="submit">Update</button>
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

</body>
</html>
<?php } ?>
