<?php
session_start();

// Show all PHP errors for debugging (development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include('includes/dbconnection.php');
// Database connection is provided by includes/dbconnection.php ($conn, $dbh)

// Handle Delete
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $res = $conn->query("SELECT photo FROM tblstaff WHERE staff_id='$id'");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if (!empty($row['photo']) && file_exists("images/".$row['photo'])) unlink("images/".$row['photo']);
    }
    $conn->query("DELETE FROM tblstaff WHERE staff_id='$id'");
    header("Location: manage-staff.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Student Management System || Manage Staff</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css" />

    <!-- ✅ DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css"/>
    
    <script>
        function confirmDelete() {
            return confirm("Are you sure you want to delete this staff?");
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
                    <h3 class="page-title">Manage Staff</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Manage Staff</li>
                        </ol>
                    </nav>
                </div>

<div class="card p-3 shadow-sm">
    <div class="table-responsive">
        <table id="staffTable" 
               class="table table-bordered table-striped table-hover table-secondary align-middle mb-0">
            <thead class="table-dark text-center">
                <tr>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Aadhar</th>
                    <th>Card ID</th>
                    <th>Phone</th>
                    <th>Salary</th>
                    <th>Joining Date</th>
                    <th>Address</th>
                    <th style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody class="text-center">
            <?php
            $res = $conn->query("SELECT * FROM tblstaff ORDER BY staff_id DESC");
            if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['name']}</td>
                        <td>{$row['designation']}</td>
                        <td>{$row['aadhar']}</td>
                        <td>{$row['card_no']}</td>
                        <td>{$row['phone_no']}</td>
                        <td>₹{$row['salary']}</td>
                        <td>{$row['joining_date']}</td>
                        <td>{$row['address']}</td>
                        <td>
                            <a href='edit-staff.php?id={$row['staff_id']}' 
                               class='btn btn-sm btn-primary me-1'>Edit</a>
                            <a href='manage-staff.php?delete_id={$row['staff_id']}' 
                               class='btn btn-sm btn-danger' 
                               onclick='return confirmDelete()'>Delete</a>
                        </td>
                    </tr>";
                }
            } else {
            }
            ?>
            </tbody>
        </table>
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

<!-- ✅ DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $.fn.dataTable.ext.errMode = 'none';
    $('#staffTable').DataTable({
        language: { emptyTable: 'No staff found.' },
        "pageLength": 10,
        "lengthMenu": [5, 10, 25, 50, 100],
        "order": [[0, "asc"]],
        info:false,
        "columnDefs": [
            { "orderable": false, "targets": -1 } // Disable sorting on Actions column
        ]
    });
});
</script>

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
