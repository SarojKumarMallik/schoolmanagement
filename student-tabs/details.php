<?php
if (!isset($dbh)) {
    include_once(__DIR__ . '/../includes/dbconnection.php');
}
// Make sure $row is available globally if called directly OR fetch again here.
$stuID = $row->StuID ?? $_GET['stuID'] ?? null;

if ($stuID) {
    $sql = "SELECT * FROM tblstudent WHERE StuID = :stuID";
    $query = $dbh->prepare($sql);
    $query->bindParam(':stuID', $stuID, PDO::PARAM_STR);
    $query->execute();
    $data = $query->fetch(PDO::FETCH_OBJ);

    if ($data) {
?>
<div class="row">
    <div class="col-md-4 mb-3">
        <label><strong>Full Name:</strong></label>
        <div><?php echo htmlentities($data->StudentName); ?></div>
    </div>
    <div class="col-md-4 mb-3">
        <label><strong>Student ID:</strong></label>
        <div><?php echo htmlentities($data->StuID); ?></div>
    </div>
    <div class="col-md-4 mb-3">
        <label><strong>Student Class:</strong></label>
        <div><?php echo htmlentities($data->StudentClass); ?></div>
    </div>
    <div class="col-md-4 mb-3">
        <label><strong>Gender:</strong></label>
        <div><?php echo htmlentities($data->Gender); ?></div>
    </div>
    <div class="col-md-4 mb-3">
        <label><strong>Date of Birth:</strong></label>
        <div><?php echo htmlentities($data->DOB); ?></div>
    </div>
    <div class="col-md-4 mb-3">
        <label><strong>Email:</strong></label>
        <div><?php echo htmlentities($data->StudentEmail); ?></div>
    </div>
    <div class="col-md-4 mb-3">
        <label><strong>Contact Number:</strong></label>
        <div><?php echo htmlentities($data->ContactNumber); ?></div>
    </div>
    <div class="col-md-4 mb-3">
        <label><strong>Address:</strong></label>
        <div><?php echo htmlentities($data->Address); ?></div>
    </div>
    <div class="col-md-4 mb-3">
        <label><strong>Admission Date:</strong></label>
        <div><?php echo htmlentities($data->DateofAdmission); ?></div>
    </div>
</div>
<?php
    } else {
        echo "<p class='text-danger'>Student details not found.</p>";
    }
} else {
    echo "<p class='text-warning'>Invalid Student ID.</p>";
}
?>
