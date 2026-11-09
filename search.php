<?php
session_start();
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}







?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

   
    <title>Student  Management System|||Search Students</title>
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
     <?php include_once('includes/header.php');?>
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.html -->
        <?php include_once('includes/sidebar.php');?>
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
            <form method="post">
              <div class="form-group">
                <label>Enter Student ID Or Name</label>
                <input type="text" name="searchdata" required class="form-control">
              </div>
              <button type="submit" name="search" class="btn btn-primary mt-2">Search</button>
            </form>

         </div>
        </div>
        <?php
if (isset($_POST['search'])) {
    $sdata = $_POST['searchdata'];

    $sql = "SELECT 
    tblstudent.StuID, 
    tblstudent.ID AS sid, 
    tblstudent.StudentName, 
    tblstudent.StudentEmail, 
    tblstudent.DateofAdmission, 
    tblstudent.Image,
    tblstudent.fatherImage,
    tblstudent.motherImage,
    tblstudent.Gender,
    tblstudent.DOB,
    tblstudent.ContactNumber,
    tblstudent.Address,
    tblstudent.StudentClass,
    tblstudent.StudentClass, 
    tblstudent.StudentSection
FROM tblstudent 
WHERE tblstudent.StuID LIKE :search 
   OR tblstudent.StudentName LIKE :search
";
    $query = $dbh->prepare($sql);
    $searchTerm = "$sdata%";
    $query->bindParam(':search', $searchTerm, PDO::PARAM_STR);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_OBJ);

    if ($query->rowCount() > 0) {
        foreach ($results as $row) {
?>

<div class="card mt-4 mb-3">
  <div class="card-body">
    <div class="row">
      <!-- Student Image -->
      <div class="col-md-3 text-center">
        <img src="images/<?php echo htmlentities($row->Image); ?>" 
             class="img-fluid rounded-circle" alt="Student Photo"
             style="width: 150px; height: 150px;">
        <p class="mt-2"><strong>ID:</strong> <?php echo htmlentities($row->StuID); ?></p>
      </div>

      <!-- Student Info -->
      <div class="col-md-9">
        <h4><?php echo htmlentities($row->StudentName); ?></h4>
        <p><strong>Class:</strong> <?php echo htmlentities($row->StudentClass . " " . $row->StudentSection); ?></p>
        <p><strong>Email:</strong> <?php echo htmlentities($row->StudentEmail); ?></p>
        <p><strong>Admission Date:</strong> <?php echo htmlentities($row->DateofAdmission); ?></p>
      </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mt-4" id="studentTabs" role="tablist">
      <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#details">Details</a></li>
      <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#payment">Payment</a></li>
      <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#attendance">Attendance</a></li>
      <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#parent">Parent Info</a></li>
      <!--<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#issue">Issued Items</a></li>-->
    </ul>

    <div class="tab-content p-3 border border-top-0">
      <div class="tab-pane fade show active" id="details"><?php include('student-tabs/details.php'); ?></div>
      <div class="tab-pane fade" id="payment"><?php include('student-tabs/payment.php'); ?></div>
      <div class="tab-pane fade" id="attendance"><?php include('student-tabs/attendance.php'); ?></div>
      <div class="tab-pane fade" id="parent"><?php include('student-tabs/parent.php'); ?></div>
      
    </div>
  </div>
</div>

<?php
        }
    } else {
        echo "<p class='mt-4 text-danger'>No student found for '$sdata'</p>";
    }
}
?>

    

        
    
        
      </div>
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
         <?php include_once('includes/footer.php');?>
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
</html><?php   ?>