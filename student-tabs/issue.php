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
        // Item status stored as 'yes' or 'no'
        $items = [
            'Belt' => $data->belt ?? 'no',
            'ID Card' => $data->idCard ?? 'no',
            'Uniform' => $data->uniform ?? 'no',
            'Bag' => $data->bag ?? 'no',
            'Book' => $data->book ?? 'no'
        ];
?>
<div class="row">
    <div class="col-md-12">
        <h5 class="mb-3 text-center">Issued Items</h5>
        <div class="row text-center">
            <?php foreach($items as $itemName => $status) { ?>
                <div class="col-md-2 col-6 mb-3">
                    <div class="border p-2 rounded shadow-sm">
                        <p><strong><?php echo htmlentities($itemName); ?></strong></p>
                        <p>
                            <?php 
                                if (strtolower($status) === 'yes') {
                                    echo "<span class='text-success'>Issued ✅</span>";
                                } else {
                                    echo "<span class='text-danger'>Not Issued ❌</span>";
                                }
                            ?>
                        </p>
                    </div>
                </div>
            <?php } ?>
        </div>
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
