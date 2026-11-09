<?php
include('includes/dbconnection.php');

if (!isset($_GET['StuID'])) { die("Invalid Request"); }
$stuID = $_GET['StuID'];

// Fetch student details
$sql = "SELECT * FROM tblstudent WHERE StuID=:stuID";
$query = $dbh->prepare($sql);
$query->bindParam(':stuID', $stuID, PDO::PARAM_STR);
$query->execute();
$student = $query->fetch(PDO::FETCH_ASSOC);

// Fetch latest TC details
$sql = "SELECT * FROM tbltc WHERE student_id=:sid ORDER BY id DESC LIMIT 1";
$query = $dbh->prepare($sql);
$query->bindParam(':sid', $student['ID'], PDO::PARAM_INT);
$query->execute();
$tc = $query->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <meta charset="UTF-8">
  <title>Transfer Certificate</title>
  <style>
    body { font-family: "Times New Roman", serif; background: #fff; }
    .certificate {
      width: 900px; margin: 20px auto; padding: 40px 60px;
      border: 2px solid #000; position: relative; background: #fff;
    }
    .school-header { text-align: center; margin-bottom: 10px; }
    .school-header img { max-height: 80px; }
    .school-header h1 { font-size: 32px; margin: 5px 0; font-weight: bold; }
    .school-header p { margin: 0; font-size: 14px; }
    .certificate-title {
      text-align: center; font-size: 22px; font-weight: bold;
      margin: 20px 0; text-decoration: underline;
    }
    .content { font-size: 18px; margin-top: 20px; line-height: 1.7; }
    .signature { margin-top: 60px; display: flex; justify-content: space-between; }
    .signature div { text-align: center; }
    .watermark {
      position: absolute; top: 45%; left: 10%;
      transform: translate(-50%, -50%);
      opacity: 0.10; pointer-events: none;
       transform: rotate(-45deg);
    }
    .watermark img { width: 800px; }
    @media print { .print-btn { display: none; } }
  </style>
</head>
<body>
<div class="certificate">
  <!-- Watermark -->
  <div class="watermark">
    <img src="images/logo/mdk.png" alt="School Logo">
  </div>

  <!-- School Header -->
  <div class="school-header">
    <img src="images/logo/mdk.png" alt="School Logo"><br>
    <p>Plot no 3010, Cuttack - Puri By pass Near Sapta sati Temple in Palasuni Bhubaneswar, Odisha 751025<br>
       MDKS TRUST NO- 1081911189 &nbsp; | &nbsp; PHONE NO- 0674-3589228</p>
  </div>

  <div class="certificate-title">SCHOOL LEAVING CERTIFICATE</div>

  <div class="content">
    <p><strong>1. Student’s Name:</strong> <?= $student['StudentName'] ?></p>
    <p><strong>2. Mother’s Name:</strong> <?= $student['MotherName'] ?></p>
    <p><strong>3. Father’s Name:</strong> <?= $student['FatherName'] ?></p>
    <p><strong>4. Residence Address:</strong> <?= $student['Address'] ?? '' ?></p>
    <p><strong>5. Admission No:</strong> <?= $student['StuID'] ?></p>
    <p><strong>6. Date of Admission:</strong> <?= $student['addDate'] ?? '' ?></p>

    <?php
function numberToWords($num) {
    $ones = array(
        0 => "Zero", 1 => "One", 2 => "Two", 3 => "Three", 4 => "Four", 5 => "Five",
        6 => "Six", 7 => "Seven", 8 => "Eight", 9 => "Nine", 10 => "Ten",
        11 => "Eleven", 12 => "Twelve", 13 => "Thirteen", 14 => "Fourteen",
        15 => "Fifteen", 16 => "Sixteen", 17 => "Seventeen", 18 => "Eighteen", 19 => "Nineteen",
        20 => "Twenty", 30 => "Thirty", 40 => "Forty", 50 => "Fifty",
        60 => "Sixty", 70 => "Seventy", 80 => "Eighty", 90 => "Ninety"
    );

    if ($num < 20) return $ones[$num];
    if ($num < 100) return $ones[10 * floor($num/10)] . (($num % 10) ? " " . $ones[$num % 10] : "");
    if ($num < 1000) return $ones[floor($num/100)] . " Hundred" . (($num % 100) ? " " . numberToWords($num % 100) : "");
    return $num;
}

function yearToWords($year) {
    if ($year >= 2000 && $year < 2100) {
        $thousands = "Two Thousand";
        $remainder = $year % 2000;
        if ($remainder == 0) return $thousands;
        return $thousands . " " . numberToWords($remainder);
    }
    return numberToWords($year);
}

function dobToWords($dob) {
    $day   = (int)date("d", strtotime($dob));
    $month = date("F", strtotime($dob));
    $year  = (int)date("Y", strtotime($dob));

    return numberToWords($day) . " " . $month . " " . yearToWords($year);
}
?>

    <p><strong>7. Date of Birth:</strong> <?= date("d-m-Y", strtotime($student['DOB'])) ?> 
   (In Words: <?= dobToWords($student['DOB']) ?>)
</p>

    <p><strong>8. Examination Passed:</strong> <?= $student['StudentClass'] ?? '' ?></p>
    <p><strong>9. Year of Exam Passing:</strong> <?= $tc['LeavingDate'] ?? '' ?></p>
    <p><strong>10. Character:</strong> <?= $tc['Character'] ?? 'Good' ?></p>
    <p><strong>11. Date of Leaving the School:</strong> <?= $tc['LeavingDate'] ?? '' ?></p>
    <p><strong>12. Date of Issue:</strong> <?= date("d-m-Y", strtotime($tc['CreatedAt'] ?? date("Y-m-d"))) ?></p>
  </div>

  <!-- Signatures -->
  <div class="signature">
    <div>
      <p>____________________</p>
      <p>Dealing Asst.</p>
    </div>
    <div>
      <p>____________________</p>
      <p>Principal</p>
    </div>
  </div>
</div>

<!-- Print Button -->
<div class="text-center mt-4 print-btn" style="text-align:center; margin-top:20px;">
  <button onclick="window.print()" 
          style="background:#007bff; color:white; border:none; padding:10px 20px; 
                 font-size:16px; border-radius:5px; cursor:pointer; margin-right:10px;">
    🖨 Print Certificate
  </button>
  
  <a href="tc_form.php" 
     style="background:#6c757d; color:white; text-decoration:none; padding:10px 20px; 
            font-size:16px; border-radius:5px; cursor:pointer;">
    ⬅ Back
  </a>
</div>

</body>
</html>
