<?php
session_start();

// ✅ Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// ✅ Fix session check
if (!isset($_SESSION['sturecmsaid']) || strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

// ✅ DB connection
// $conn = new mysqli("sql300.infinityfree.com", "if0_39659266", "wSbf7mjmMiXk7", "if0_39659266_studentmsdb");
$conn = new mysqli("localhost", "root", "root", "studentmsdb");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ Get all classes for dropdown
$classResult = $conn->query("SELECT DISTINCT ClassName FROM tblclass ORDER BY ClassName ASC");
if (!$classResult) {
    die("Class query failed: " . $conn->error);
}

// ✅ Get selected class and month
$selectedClass = $_GET['class'] ?? '';
$selectedMonth = $_GET['month'] ?? date('Y-m'); // Default: current month

$attendanceData = [];

if ($selectedClass) {
    // ✅ Get attendance summary (joining with student & class table)
    $attQuery = "
        SELECT DISTINCT s.StudentName,s.StudentSection, 
               a.total_present, 
               a.total_absent, 
               a.total_working_days
        FROM tblattendance a
        JOIN tblstudent s ON a.student_id = s.id
        JOIN tblclass c ON a.ClassName = c.ClassName
        WHERE a.ClassName = '" . $conn->real_escape_string($selectedClass) . "' 
          AND a.month_year = '" . $conn->real_escape_string($selectedMonth) . "'
        ORDER BY s.StudentName ASC
    ";

    $attResult = $conn->query($attQuery);

    if (!$attResult) {
        die("Attendance query failed: " . $conn->error . "<br>Query: " . $attQuery);
    }

    while ($row = $attResult->fetch_assoc()) {
        $attendanceData[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Student Management System || View Attendance</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css" />
    <style>
        h2, h3 { color: #2c3e50; text-align: center; margin-bottom: 2rem; }
        .selector { background: white; padding: 1.5rem; border-radius: 8px; 
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 1rem; }
        select, input[type="month"] { padding: 0.5rem; border-radius: 4px; border: 1px solid #ddd; margin-right: 1rem; }
        button { background: #3498db; color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 4px;
                 font-weight: 600; cursor: pointer; transition: background 0.2s; }
        button:hover { background: #2980b9; }
        .table-container { overflow-x: auto; margin-top: 1rem; border-radius: 8px;
                           box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; background: white; }
        th { background-color: #3498db; color: white; padding: 12px; }
        td { padding: 12px; border-bottom: 1px solid #eee; text-align: center; }
        tr:hover td { background-color: #e9ecf0ff; }
    </style>
</head>

<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php'); ?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php'); ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">View Attendance</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">View Attendance</li>
                            </ol>
                        </nav>
                    </div>

                    <!-- ✅ Class & Month Selector -->
                    <form method="get" class="selector">
                        <label for="class">Select Class:</label>
                        <select name="class" id="class" required>
                            <option value="">--Select--</option>
                            <?php
                            // Reset pointer for classes result
                            $classResult->data_seek(0);
                            while($row = $classResult->fetch_assoc()): ?>
                            <option value="<?= $row['ClassName'] ?>" <?= ($selectedClass == $row['ClassName']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['ClassName']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>

                        <label for="month">Select Month:</label>
                        <input type="month" name="month" value="<?= htmlspecialchars($selectedMonth) ?>" required>

                        <button type="submit">View</button>
                    </form>

                    <?php if ($selectedClass): ?>
                    <h3>Attendance for (<?= date("F Y", strtotime($selectedMonth)) ?>)</h3>
                    <div class="table-container">
                        <table>
                            <tr>
                                <th>Student Name</th>
                                <th>Student Section</th>
                                <th>Total Working Days</th>
                                <th>Present Days</th>
                                <th>Absent Days</th>
                            </tr>
                            <?php if (count($attendanceData) > 0): ?>
                                <?php foreach ($attendanceData as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['StudentName']) ?></td>
                                    <td><?= htmlspecialchars($row['StudentSection']) ?></td>
                                    <td><?= $row['total_working_days'] ?></td>
                                    <td><?= $row['total_present'] ?></td>
                                    <td><?= $row['total_absent'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">No attendance records found for this class and month.</td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    <?php endif; ?>

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
