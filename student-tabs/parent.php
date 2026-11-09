<?php
if (!isset($dbh)) {
    include_once(__DIR__ . '/../includes/dbconnection.php');
}
if (!empty($stuID)) {
    $sql = "SELECT * FROM tblstudent WHERE StuID = :stuID";
    $query = $dbh->prepare($sql);
    $query->bindParam(':stuID', $stuID, PDO::PARAM_STR);
    $query->execute();
    $data = $query->fetch(PDO::FETCH_OBJ);

    if ($data) {
?>
<div class="row">
    <!-- Father's Details -->
    <div class="col-md-6 text-center border-right">
        <h5 class="mb-3">Father's Details</h5>
        <?php if (!empty($data->fatherImage)) { ?>
            <img src="images/<?php echo htmlentities($data->fatherImage); ?>" 
                 alt="Father Image" 
                 style="width:150px; height:150px; object-fit:cover; border-radius:8px; border:1px solid #ddd;">
        <?php } ?>
        <p class="mt-3"><strong>Name:</strong> <?php echo htmlentities($data->FatherName); ?></p>
        <p><strong>Aadhar:</strong> <?php echo htmlentities($data->fatherAddhar); ?></p>
    </div>

    <!-- Mother's Details -->
    <div class="col-md-6 text-center">
        <h5 class="mb-3">Mother's Details</h5>
        <?php if (!empty($data->motherImage)) { ?>
            <img src="images/<?php echo htmlentities($data->motherImage); ?>" 
                 alt="Mother Image" 
                 style="width:150px; height:150px; object-fit:cover; border-radius:8px; border:1px solid #ddd;">
        <?php } ?>
        <p class="mt-3"><strong>Name:</strong> <?php echo htmlentities($data->MotherName); ?></p>
        <p><strong>Aadhar:</strong> <?php echo htmlentities($data->motherAddhar); ?></p>
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
