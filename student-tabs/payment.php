<?php
$stuID = $row->StuID ?? $_GET['stuID'] ?? null;

if ($stuID) {
    // Fetch student details
    $sql = "SELECT * FROM tblstudent WHERE StuID = :stuID";
    $query = $dbh->prepare($sql);
    $query->bindParam(':stuID', $stuID, PDO::PARAM_STR);
    $query->execute();
    $data = $query->fetch(PDO::FETCH_OBJ);

    if ($data) {
        // Fetch only paid monthly fees for this student
        $sqlFees = "SELECT * FROM tblmonthlyfees WHERE StuID = :stuID AND LOWER(status) = 'paid' ORDER BY month_year ASC";
        $queryFees = $dbh->prepare($sqlFees);
        $queryFees->bindParam(':stuID', $stuID, PDO::PARAM_STR);
        $queryFees->execute();
        $fees = $queryFees->fetchAll(PDO::FETCH_OBJ);
?>

<h4 class="mt-4 mb-3">Paid Monthly Fee Records</h4>
<table class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>Month</th>
            <th>Due Amount</th>
            <th>Paid Amount</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php
        if ($fees) {
            foreach ($fees as $row) {
                echo "<tr>";
                echo "<td>" . htmlentities($row->month_year) . "</td>";
                echo "<td>" . htmlentities(number_format($row->due, 2)) . "</td>";
                echo "<td>" . htmlentities(number_format($row->paid, 2)) . "</td>";
                echo "<td class='text-success'><strong>Paid ✅</strong></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4' class='text-center text-muted'>No paid fee records found.</td></tr>";
        }
    ?>
    </tbody>
</table>

<?php
    } else {
        echo "<p class='text-danger'>Student details not found.</p>";
    }
} else {
    echo "<p class='text-warning'>Invalid Student ID.</p>";
}
?>
