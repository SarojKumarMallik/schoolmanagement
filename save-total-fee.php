<?php
include_once(__DIR__ . '/includes/dbconnection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stu_id = $_POST['stu_id'];
    $total = floatval($_POST['total_fee']);
    $discount = floatval($_POST['discount'] ?? 0);
    $net = $total - $discount;
    $monthly = round($net / 12, 2);

    // ✅ Save Total Fee
    $stmt = $con->prepare("INSERT INTO tblfees (StuID, TotalAmount, Discount, NetTotal) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        die("❌ Prepare failed for tblfees: " . $con->error);
    }
    $stmt->bind_param("sddd", $stu_id, $total, $discount, $net);
    $stmt->execute();

    // ✅ Add Monthly Records
    for ($i = 0; $i < 12; $i++) {
        $month = date("Y-m", strtotime("+$i month"));

        $stmt2 = $con->prepare("INSERT INTO tblmonthlyfees (StuID, MonthYear, Amount, Status) VALUES (?, ?, ?, 'Unpaid')");
        if (!$stmt2) {
            die("❌ Prepare failed for tblmonthlyfees: " . $con->error);
        }
        $stmt2->bind_param("ssd", $stu_id, $month, $monthly);
        $stmt2->execute();
    }

    header("Location: manage-fees.php");
    exit();
}
?>
