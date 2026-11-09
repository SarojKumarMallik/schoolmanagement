<?php
if (!isset($dbh)) {
    include_once(__DIR__ . '/../includes/dbconnection.php');
}

if (!empty($stuID)) {
    // Fetch student details (get both StuID and ID)
    $sql = "SELECT ID, StuID, StudentName, StudentClass, FatherName 
            FROM tblstudent 
            WHERE StuID = :stuID";
    $query = $dbh->prepare($sql);
    $query->bindParam(':stuID', $stuID, PDO::PARAM_STR);
    $query->execute();
    $student = $query->fetch(PDO::FETCH_OBJ);
    

    if ($student) {
        $studentID = $student->ID; // This ID is linked with tblattendance.student_id
        // Fetch attendance records using internal student_id
        $sqlAttendance = "SELECT month_year, total_present, total_absent, total_working_days 
                          FROM tblattendance 
                          WHERE student_id = :studentID
                          ORDER BY month_year ASC";
        $queryAtt = $dbh->prepare($sqlAttendance);
        $queryAtt->bindParam(':studentID', $studentID, PDO::PARAM_INT);
        $queryAtt->execute();
        $attendance = $queryAtt->fetchAll(PDO::FETCH_OBJ);
?>



<!-- Attendance Table -->
<h4 class="mt-4 mb-3">Monthly Attendance Records</h4>
<table class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>Month</th>
            <th>Total Working Days</th>
            <th>Present</th>
            <th>Absent</th>
            <th>Attendance %</th>
        </tr>
    </thead>
    <tbody>
    <?php
        if ($attendance) {
            foreach ($attendance as $row) {
                $percentage = ($row->total_working_days > 0) 
                              ? round(($row->total_present / $row->total_working_days) * 100, 2) 
                              : 0;
                echo "<tr>";
                echo "<td>" . htmlentities($row->month_year) . "</td>";
                echo "<td>" . htmlentities($row->total_working_days) . "</td>";
                echo "<td class='text-success'>" . htmlentities($row->total_present) . "</td>";
                echo "<td class='text-danger'>" . htmlentities($row->total_absent) . "</td>";
                echo "<td><strong>" . $percentage . "%</strong></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5' class='text-center text-muted'>No attendance records found.</td></tr>";
        }
    ?>
    </tbody>
</table>

<?php
    } else {
        echo "<p class='text-danger'>❌ Student details not found.</p>";
    }
} else {
    echo "<p class='text-warning'>⚠ Please provide a Student ID.</p>";
}
?>
