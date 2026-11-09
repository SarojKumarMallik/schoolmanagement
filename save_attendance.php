<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']==0)) {
  header('location:logout.php');
}  

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "studentmsdb";
// $servername = "sql300.infinityfree.com";
// $username = "if0_39659266";
// $password = "wSbf7mjmMiXk7";
// $dbname = "if0_39659266_studentmsdb";
$feedback = "";

// ✅ Save attendance if form submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['students']) && isset($_POST['save_attendance'])) {
    $conn = new mysqli($servername, $username, $password, $dbname);

    $class_name = $_POST['class_name'];
    $month_year = $_POST['month_year'];
    $total_working_days = (int)$_POST['working_days'];

    $allSuccess = true;
    $message = "";

    // Get Class ID
    $classQry = $conn->prepare("SELECT id FROM tblclass WHERE ClassName=?");
    $classQry->bind_param("s", $class_name);
    $classQry->execute();
    $classQry->bind_result($class_id);
    $classQry->fetch();
    $classQry->close();

    foreach ($_POST['students'] as $student_id => $data) {
        $present = (int)$data['present'];
        $absent = $total_working_days - $present;

        // ✅ Check if already exists
        $check = $conn->prepare("SELECT id FROM tblattendance WHERE student_id=? AND ClassName=? AND month_year=?");
        $check->bind_param("iis", $student_id, $class_name, $month_year);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // Update existing record
            $row_id = 0;
            $check->bind_result($row_id);
            $check->fetch();
            $update = $conn->prepare("UPDATE tblattendance 
                                      SET total_present=?, total_absent=?, total_working_days=? 
                                      WHERE id=?");
            $update->bind_param("iiii", $present, $absent, $total_working_days, $row_id);
            if (!$update->execute()) {
                $allSuccess = false;
                $message .= "Error updating student ID $student_id: " . $update->error . "<br>";
            }
            $update->close();
        } else {
            // Insert new record
            $insert = $conn->prepare("INSERT INTO tblattendance (student_id, ClassName, month_year, total_present, total_absent, total_working_days) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
            $insert->bind_param("issiii", $student_id, $class_name, $month_year, $present, $absent, $total_working_days);
            if (!$insert->execute()) {
                $allSuccess = false;
                $message .= "Error inserting student ID $student_id: " . $insert->error . "<br>";
            }
            $insert->close();
        }
        $check->close();
    }

    $conn->close();

    if ($allSuccess) {
        $feedback = "<div class='success'>Monthly attendance saved successfully.</div>";
    } else {
        $feedback = "<div class='error'>Some entries failed:<br>$message</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Student Management System || Attendance</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css" />
    <style>
    .date-selector,
    .class-selector {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 1rem;
    }
    select, input {
        padding: 0.5rem;
        border-radius: 4px;
        border: 1px solid #ddd;
        font-family: inherit;
    }
    .attendance-sheet {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    .attendance-sheet table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    .attendance-sheet th {
        background-color: #3498db;
        color: white;
        padding: 12px;
    }
    .attendance-sheet td {
        padding: 12px;
        border-bottom: 1px solid #eee;
    }
    input[type="submit"], button[type="submit"] {
        background: #3498db;
        color: white;
        padding: 0.8rem 1.5rem;
        border: none;
        border-radius: 4px;
        font-weight: 600;
        margin-top: 1.5rem;
        cursor: pointer;
        transition: background 0.2s;
    }
    input[type="submit"]:hover, button[type="submit"]:hover {
        background: #2980b9;
    }
    tr:hover td { background-color: #f8f9fa; }
    #message { padding: 10px; margin: 10px 0; border-radius: 4px; text-align: center; }
    .success { background-color: #d4edda; color: #155724; }
    .error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php');?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title"> Attendance </h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page"> Attendance</li>
                            </ol>
                        </nav>
                    </div>
                    <form id="attendance-form" method="post">
                        <div class="class-selector">
                            <label for="class"><i class="fas fa-users"></i> Select Class:</label>
                            <select id="class" name="class_name" required onchange="document.getElementById('attendance-form').submit();">
                                <option value="">-- Select Class --</option>
                                <?php
                                $conn = new mysqli($servername, $username, $password, $dbname);
                                $sql = "SELECT DISTINCT ClassName FROM tblclass";
                                $result = $conn->query($sql);
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . $row['ClassName'] . "' " . (isset($_POST['class_name']) && $_POST['class_name'] == $row['ClassName'] ? 'selected' : '') . ">" . $row['ClassName'] . "</option>";
                                }
                                $conn->close();
                                ?>
                            </select>
                        </div>
                        <div class="date-selector">
                            <label for="month_year"><i class="fas fa-calendar"></i> Select Month:</label>
                            <input type="month" id="month_year" name="month_year" value="<?php echo $_POST['month_year'] ?? ''; ?>" required>
                        </div>
                        <div class="date-selector">
                            <label for="working_days"><i class="fas fa-clipboard-list"></i> Total Working Days:</label>
                            <input type="number" id="working_days" name="working_days" value="<?php echo $_POST['working_days'] ?? ''; ?>" min="1" required>
                        </div>
                        <div class="attendance-sheet">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Section</th>
                                        <th>Present Days</th>
                                        <th>Absent Days</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (isset($_POST['class_name'])) {
                                        $conn = new mysqli($servername, $username, $password, $dbname);
                                        $sql = "SELECT id as student_id, StudentName, StudentSection FROM tblstudent WHERE StudentClass = '" . $_POST['class_name'] . "'";
                                        $result = $conn->query($sql);
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<tr>
                                                <td>' . $row['StudentName'] . '</td>
                                                <td>' . $row['StudentSection']. '</td>
                                                <td>
                                                    <input type="number" name="students[' . $row['student_id'] . '][present]" min="0" placeholder="Present Days" oninput="updateAbsent(this, ' . ($_POST['working_days'] ?? 0) . ', \'absent_' . $row['student_id'] . '\')" required>
                                                    <input type="hidden" name="students[' . $row['student_id'] . '][name]" value="' . htmlspecialchars($row['StudentName']) . '">
                                                </td>
                                                <td><span id="absent_' . $row['student_id'] . '">0</span></td>
                                            </tr>';
                                        }
                                        $conn->close();
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="message"><?php if (isset($feedback)) echo $feedback; ?></div>
                        <button type="submit" name="save_attendance"><i class="fas fa-save"></i> Save Attendance</button>
                    </form>
                </div>
                <?php include_once('includes/footer.php');?>
            </div>
        </div>
    </div>
    <script>
    function updateAbsent(input, workingDays, absentId) {
        let present = parseInt(input.value) || 0;
        let absent = workingDays - present;
        if(absent < 0) absent = 0;
        document.getElementById(absentId).innerText = absent;
    }
    </script>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="vendors/select2/select2.min.js"></script>
    <script src="vendors/typeahead.js/typeahead.bundle.min.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script src="js/typeahead.js"></script>
    <script src="js/select2.js"></script>
</body>
</html>
