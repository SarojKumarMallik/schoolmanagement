<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid'] == 0)) {
    header('location:logout.php');
    exit();
}

// Search for student
$student = null;
$readmissionHistory = [];
$reAdmissionStats = [];
$oldAdmissionAmount = 0;
if (isset($_POST['search'])) {
    $stuid = trim($_POST['stuid']);
    $sql = "SELECT * FROM tblstudent WHERE StuID = :stuid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':stuid', $stuid, PDO::PARAM_STR);
    $query->execute();
    $student = $query->fetch(PDO::FETCH_OBJ);

    if ($student) {
        // Store old admission amount before any changes
        $oldAdmissionAmount = $student->addAmt;
        
        // Get re-admission history
        $sqlHistory = "SELECT * FROM tblreadmission WHERE StuID = :stuid ORDER BY reAdmissionDate DESC";
        $queryHistory = $dbh->prepare($sqlHistory);
        $queryHistory->bindParam(':stuid', $stuid, PDO::PARAM_STR);
        $queryHistory->execute();
        $readmissionHistory = $queryHistory->fetchAll(PDO::FETCH_OBJ);
        
        // Get re-admission statistics
        $sqlStats = "SELECT 
                    COUNT(*) as total_re_admissions,
                    MIN(reAdmissionDate) as first_re_admission,
                    MAX(reAdmissionDate) as last_re_admission,
                    GROUP_CONCAT(DISTINCT newClass ORDER BY reAdmissionDate) as class_journey
                    FROM tblreadmission 
                    WHERE StuID = :stuid";
        $queryStats = $dbh->prepare($sqlStats);
        $queryStats->bindParam(':stuid', $stuid, PDO::PARAM_STR);
        $queryStats->execute();
        $reAdmissionStats = $queryStats->fetch(PDO::FETCH_OBJ);
    } else {
        echo "<script>alert('Student not found!');</script>";
    }
}

// Handle re-admission update
if (isset($_POST['readmit'])) {
    $stuid = $_POST['stuid'];
    $newClass = $_POST['newclass'];
    $newSection = $_POST['newsection'];
    $reDate = $_POST['readdate'];
    $joinDate = $_POST['joindate'];
    $notes = $_POST['notes'];
    $readFee = $_POST['readfee'];
    $academicYear = $_POST['academic_year'];
    $semester = $_POST['semester'];

    // Get old admission amount before update
    $sqlOldAmt = "SELECT addAmt FROM tblstudent WHERE StuID = :stuid";
    $queryOldAmt = $dbh->prepare($sqlOldAmt);
    $queryOldAmt->bindParam(':stuid', $stuid, PDO::PARAM_STR);
    $queryOldAmt->execute();
    $oldAdmissionData = $queryOldAmt->fetch(PDO::FETCH_OBJ);
    $oldAdmissionAmount = $oldAdmissionData ? $oldAdmissionData->addAmt : 0;

    // Get current re-admission count for this student
    $sqlCount = "SELECT COUNT(*) as re_admit_count FROM tblreadmission WHERE StuID = :stuid";
    $queryCount = $dbh->prepare($sqlCount);
    $queryCount->bindParam(':stuid', $stuid, PDO::PARAM_STR);
    $queryCount->execute();
    $countData = $queryCount->fetch(PDO::FETCH_OBJ);
    $currentReAdmitCount = $countData ? $countData->re_admit_count + 1 : 1;

    // Validation
    $errors = [];
    
    if (empty($newClass)) {
        $errors[] = "Class is required";
    }
    if (empty($newSection)) {
        $errors[] = "Section is required";
    }
    if (empty($reDate)) {
        $errors[] = "Re-admission date is required";
    }
    if (empty($readFee) || !is_numeric($readFee) || $readFee < 0) {
        $errors[] = "Valid re-admission fee is required";
    }
    if (empty($academicYear)) {
        $errors[] = "Academic year is required";
    }
    
    // Check if re-admission date is not in the future
    if (strtotime($reDate) > time()) {
        $errors[] = "Re-admission date cannot be in the future";
    }
    
    // Check if joining date is valid
    if (!empty($joinDate) && strtotime($joinDate) > time()) {
        $errors[] = "Joining date cannot be in the future";
    }

    if (empty($errors)) {
        // Get old class/section
        $sqlOld = "SELECT StudentClass, StudentSection FROM tblstudent WHERE StuID = :stuid";
        $queryOld = $dbh->prepare($sqlOld);
        $queryOld->bindParam(':stuid', $stuid, PDO::PARAM_STR);
        $queryOld->execute();
        $old = $queryOld->fetch(PDO::FETCH_OBJ);

        if ($old) {
            $oldClass = $old->StudentClass;
            $oldSection = $old->StudentSection;

            // Insert into re-admission log with enhanced tracking
            $sqlLog = "INSERT INTO tblreadmission (StuID, oldClass, newClass, oldSection, newSection, reAdmissionDate, notes, oldAdmissionAmount, newAdmissionAmount, academic_year, semester, re_admission_count, cumulative_re_admissions)
                       VALUES (:stuid, :oldClass, :newClass, :oldSection, :newSection, :reDate, :notes, :oldAmount, :newAmount, :academicYear, :semester, :reAdmitCount, :cumulativeCount)";
            $queryLog = $dbh->prepare($sqlLog);
            $queryLog->bindParam(':stuid', $stuid);
            $queryLog->bindParam(':oldClass', $oldClass);
            $queryLog->bindParam(':newClass', $newClass);
            $queryLog->bindParam(':oldSection', $oldSection);
            $queryLog->bindParam(':newSection', $newSection);
            $queryLog->bindParam(':reDate', $reDate);
            $queryLog->bindParam(':notes', $notes);
            $queryLog->bindParam(':oldAmount', $oldAdmissionAmount);
            $queryLog->bindParam(':newAmount', $readFee);
            $queryLog->bindParam(':academicYear', $academicYear);
            $queryLog->bindParam(':semester', $semester);
            $queryLog->bindParam(':reAdmitCount', $currentReAdmitCount);
            $queryLog->bindParam(':cumulativeCount', $currentReAdmitCount);
            $queryLog->execute();

            // Update student record
            $sqlUpdate = "UPDATE tblstudent SET StudentClass=:newClass, StudentSection=:newSection, addDate=:reDate, joindate=:joinDate, addAmt=:readFee WHERE StuID=:stuid";
            $queryUpdate = $dbh->prepare($sqlUpdate);
            $queryUpdate->bindParam(':newClass', $newClass);
            $queryUpdate->bindParam(':newSection', $newSection);
            $queryUpdate->bindParam(':reDate', $reDate);
            $queryUpdate->bindParam(':joinDate', $joinDate);
            $queryUpdate->bindParam(':readFee', $readFee);
            $queryUpdate->bindParam(':stuid', $stuid);
            
            if ($queryUpdate->execute()) {
                // Update admission fee record
                $sqlCheckFee = "SELECT COUNT(*) FROM tbladmissionfee WHERE StuID = :stuid";
                $queryCheckFee = $dbh->prepare($sqlCheckFee);
                $queryCheckFee->bindParam(':stuid', $stuid);
                $queryCheckFee->execute();
                $feeExists = $queryCheckFee->fetchColumn();
                
                if ($feeExists) {
                    $sqlUpdateFee = "UPDATE tbladmissionfee SET paid = 0 WHERE StuID = :stuid";
                    $queryUpdateFee = $dbh->prepare($sqlUpdateFee);
                    $queryUpdateFee->bindParam(':stuid', $stuid);
                    $queryUpdateFee->execute();
                } else {
                    $sqlInsertAddFee = "INSERT INTO tbladmissionfee (StuID, paid, paidDate) VALUES (:stuid, 0, :reDate)";
                    $queryInsertAddFee = $dbh->prepare($sqlInsertAddFee);
                    $queryInsertAddFee->bindParam(':stuid', $stuid);
                    $queryInsertAddFee->bindParam(':reDate', $reDate);
                    $queryInsertAddFee->execute();
                }
                
                // Refresh all data
                $sqlStudent = "SELECT * FROM tblstudent WHERE StuID = :stuid";
                $queryStudent = $dbh->prepare($sqlStudent);
                $queryStudent->bindParam(':stuid', $stuid, PDO::PARAM_STR);
                $queryStudent->execute();
                $student = $queryStudent->fetch(PDO::FETCH_OBJ);
                
                $sqlHistory = "SELECT * FROM tblreadmission WHERE StuID = :stuid ORDER BY reAdmissionDate DESC";
                $queryHistory = $dbh->prepare($sqlHistory);
                $queryHistory->bindParam(':stuid', $stuid, PDO::PARAM_STR);
                $queryHistory->execute();
                $readmissionHistory = $queryHistory->fetchAll(PDO::FETCH_OBJ);
                
                $sqlStats = "SELECT 
                            COUNT(*) as total_re_admissions,
                            MIN(reAdmissionDate) as first_re_admission,
                            MAX(reAdmissionDate) as last_re_admission,
                            GROUP_CONCAT(DISTINCT newClass ORDER BY reAdmissionDate) as class_journey
                            FROM tblreadmission 
                            WHERE StuID = :stuid";
                $queryStats = $dbh->prepare($sqlStats);
                $queryStats->bindParam(':stuid', $stuid, PDO::PARAM_STR);
                $queryStats->execute();
                $reAdmissionStats = $queryStats->fetch(PDO::FETCH_OBJ);
                
                echo "<script>alert('Student re-admitted successfully!\\\\nRe-admission #{$currentReAdmitCount}\\\\nOld Amount: ₹" . number_format($oldAdmissionAmount, 2) . "\\\\nNew Amount: ₹" . number_format($readFee, 2) . "\\\\nAdmission fee status updated.');</script>";
            } else {
                echo "<script>alert('Error updating student record!');</script>";
            }
        }
    } else {
        $errorMessage = implode("\\n", $errors);
        echo "<script>alert('Please fix the following errors:\\n$errorMessage');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Re-Admission</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        
        .sidebar::-webkit-scrollbar {
            display: none;
        }
        
        .history-table {
            max-height: 400px;
            overflow-y: auto;
        }
        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .journey-timeline {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        .timeline-item {
            display: inline-block;
            margin-right: 10px;
            padding: 5px 10px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            font-size: 14px;
        }
        .timeline-arrow {
            color: rgba(255,255,255,0.7);
            margin: 0 5px;
        }
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
                        <h3 class="page-title">Student Re-Admission</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Re-Admission</li>
                            </ol>
                        </nav>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label>Enter Student ID</label>
                                    <input type="text" name="stuid" class="form-control" required 
                                           value="<?= isset($_POST['stuid']) ? htmlentities($_POST['stuid']) : '' ?>">
                                </div>
                                <button type="submit" name="search" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php if ($student): ?>
                    <!-- Re-admission Statistics -->
                    <?php if ($reAdmissionStats && $reAdmissionStats->total_re_admissions > 0): ?>
                    <div class="stats-card">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="stat-item">
                                    <div class="stat-label">Total Re-Admissions</div>
                                    <div class="stat-value" style="font-size: 2.5rem; font-weight: bold;">
                                        <?= $reAdmissionStats->total_re_admissions ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-item">
                                    <div class="stat-label">First Re-Admission</div>
                                    <div class="stat-value">
                                        <?= date('d M Y', strtotime($reAdmissionStats->first_re_admission)) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-item">
                                    <div class="stat-label">Last Re-Admission</div>
                                    <div class="stat-value">
                                        <?= date('d M Y', strtotime($reAdmissionStats->last_re_admission)) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-item">
                                    <div class="stat-label">This will be</div>
                                    <div class="stat-value" style="font-size: 1.8rem; font-weight: bold;">
                                        Re-admission #<?= ($reAdmissionStats->total_re_admissions + 1) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Class Journey Timeline -->
                        <?php if ($reAdmissionStats->class_journey): ?>
                        <div class="journey-timeline">
                            <div class="text-center mb-2">
                                <strong>Class Journey:</strong>
                            </div>
                            <div class="text-center">
                                <?php
                                $classes = explode(',', $reAdmissionStats->class_journey);
                                foreach ($classes as $index => $class) {
                                    echo '<span class="timeline-item">' . trim($class) . '</span>';
                                    if ($index < count($classes) - 1) {
                                        echo '<i class="bi bi-arrow-right timeline-arrow"></i>';
                                    }
                                }
                                ?>
                                <i class="bi bi-arrow-right timeline-arrow"></i>
                                <span class="timeline-item" style="background: rgba(255,255,255,0.3);">
                                    <strong>New Class</strong>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Student Information -->
                    <div class="card mt-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Student Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <p><strong>Student ID:</strong> <?= htmlentities($student->StuID) ?></p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Name:</strong> <?= htmlentities($student->StudentName) ?></p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Current Class:</strong> <?= htmlentities($student->StudentClass) ?></p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Current Section:</strong> <?= htmlentities($student->StudentSection) ?></p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Current Admission Amount:</strong> ₹<?= number_format($student->addAmt, 2) ?></p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Total Re-Admissions:</strong> 
                                        <?= $reAdmissionStats ? $reAdmissionStats->total_re_admissions : '0' ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Re-admission Form -->
                    <div class="card mt-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                Re-Admission Form 
                                <?php if ($reAdmissionStats && $reAdmissionStats->total_re_admissions > 0): ?>
                                <span class="float-right">
                                    Re-admission #<?= ($reAdmissionStats->total_re_admissions + 1) ?>
                                </span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="readmissionForm">
                                <input type="hidden" name="stuid" value="<?= htmlentities($student->StuID) ?>">

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Academic Year <span class="text-danger">*</span></label>
                                            <select name="academic_year" class="form-control" required>
                                                <option value="">Select Academic Year</option>
                                                <?php
                                                $currentYear = date('Y');
                                                for ($year = $currentYear - 2; $year <= $currentYear + 2; $year++) {
                                                    $academicYear = $year . '-' . ($year + 1);
                                                    $selected = (isset($_POST['academic_year']) && $_POST['academic_year'] == $academicYear) ? 'selected' : '';
                                                    echo "<option value='$academicYear' $selected>$academicYear</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Semester/Term</label>
                                            <select name="semester" class="form-control">
                                                <option value="">Select Semester</option>
                                                <option value="1st Semester" <?= (isset($_POST['semester']) && $_POST['semester'] == '1st Semester') ? 'selected' : '' ?>>1st Semester</option>
                                                <option value="2nd Semester" <?= (isset($_POST['semester']) && $_POST['semester'] == '2nd Semester') ? 'selected' : '' ?>>2nd Semester</option>
                                                <option value="Annual" <?= (isset($_POST['semester']) && $_POST['semester'] == 'Annual') ? 'selected' : '' ?>>Annual</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>New Admission Amount <span class="text-danger">*</span></label>
                                            <input type="number" name="readfee" class="form-control" required 
                                                   min="0" step="0.01" placeholder="0.00"
                                                   value="<?= isset($_POST['readfee']) ? htmlentities($_POST['readfee']) : $student->addAmt ?>">
                                            <small class="form-text text-muted">
                                                Current: ₹<?= number_format($student->addAmt, 2) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>New Class <span class="text-danger">*</span></label>
                                            <select id="classDropdown" name="newclass" class="form-control" required>
                                                <option value="">Select Class</option>
                                                <?php
                                                $sql1 = "SELECT DISTINCT ClassName FROM tblclass ORDER BY ClassName";
                                                $result1 = $conn->query($sql1);
                                                while ($row = $result1->fetch_assoc()) {
                                                    $selected = (isset($_POST['newclass']) && $_POST['newclass'] == $row['ClassName']) ? 'selected' : '';
                                                    echo '<option value="' . $row['ClassName'] . '" ' . $selected . '>' . $row['ClassName'] . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>New Section <span class="text-danger">*</span></label>
                                            <select id="sectionDropdown" name="newsection" class="form-control" required>
                                                <option value="">Select Section</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Joining Date</label>
                                            <input type="date" id="joindate" name="joindate" class="form-control"
                                                   value="<?= isset($_POST['joindate']) ? htmlentities($_POST['joindate']) : '' ?>">        
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Re-Admission Date <span class="text-danger">*</span></label>
                                            <input type="date" name="readdate" class="form-control" required
                                                   value="<?= isset($_POST['readdate']) ? htmlentities($_POST['readdate']) : date('Y-m-d') ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Notes</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Any additional notes..."><?= isset($_POST['notes']) ? htmlentities($_POST['notes']) : '' ?></textarea>
                                </div>

                                <div class="form-group">
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <strong>Note:</strong> After re-admission, the admission fee payment status will be reset to unpaid, but previous payment history will be preserved.
                                    </div>
                                </div>

                                <button type="submit" name="readmit" class="btn btn-success" onclick="return confirmReAdmission()">
                                    <i class="bi bi-person-check"></i> 
                                    <?php if ($reAdmissionStats && $reAdmissionStats->total_re_admissions > 0): ?>
                                        Process Re-admission #<?= ($reAdmissionStats->total_re_admissions + 1) ?>
                                    <?php else: ?>
                                        Confirm Re-Admission
                                    <?php endif; ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Re-admission History -->
                    <?php if (!empty($readmissionHistory)): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Complete Re-Admission History</h5>
                        </div>
                        <div class="card-body">
                            <div class="history-table">
                                <table class="table table-bordered table-striped">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Re-Admission Date</th>
                                            <th>Academic Year</th>
                                            <th>Old Class</th>
                                            <th>New Class</th>
                                            <th>Old Section</th>
                                            <th>New Section</th>
                                            <th>Old Amount</th>
                                            <th>New Amount</th>
                                            <th>Change</th>
                                            <th>Re-admission #</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($readmissionHistory as $index => $history): ?>
                                        <?php 
                                        $change = $history->newAdmissionAmount - $history->oldAdmissionAmount;
                                        $changeClass = $change > 0 ? 'text-success' : ($change < 0 ? 'text-danger' : 'text-warning');
                                        $changeIcon = $change > 0 ? 'bi-arrow-up' : ($change < 0 ? 'bi-arrow-down' : 'bi-dash');
                                        ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= date('d-m-Y', strtotime($history->reAdmissionDate)) ?></td>
                                            <td><?= htmlentities($history->academic_year) ?: 'N/A' ?></td>
                                            <td><?= htmlentities($history->oldClass) ?></td>
                                            <td><?= htmlentities($history->newClass) ?></td>
                                            <td><?= htmlentities($history->oldSection) ?></td>
                                            <td><?= htmlentities($history->newSection) ?></td>
                                            <td>₹<?= number_format($history->oldAdmissionAmount, 2) ?></td>
                                            <td>₹<?= number_format($history->newAdmissionAmount, 2) ?></td>
                                            <td class="<?= $changeClass ?>">
                                                <i class="bi <?= $changeIcon ?>"></i>
                                                ₹<?= number_format(abs($change), 2) ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary">#<?= $history->re_admission_count ?></span>
                                            </td>
                                            <td><?= htmlentities($history->notes) ?: 'N/A' ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card mt-4">
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                No re-admission history found for this student.
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php endif; ?>

                </div>
                <?php include_once('includes/footer.php'); ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Initialize section dropdown if class is already selected
        var initialClass = $('#classDropdown').val();
        if (initialClass) {
            loadSections(initialClass);
        }

        $('#classDropdown').change(function() {
            var className = $(this).val();
            loadSections(className);
        });

        function loadSections(className) {
            $('#sectionDropdown').html('<option value="">Select Section</option>');
            if (className !== "") {
                $.ajax({
                    url: 'get_sections.php',
                    type: 'POST',
                    data: { class_name: className },
                    dataType: 'json',
                    success: function(response) {
                        if(response.length > 0) {
                            $.each(response, function(index, section) {
                                $('#sectionDropdown').append('<option value="'+section.Section+'">'+section.Section+'</option>');
                            });
                        } else {
                            $('#sectionDropdown').append('<option value="">No Section Found</option>');
                        }
                    },
                    error: function() {
                        $('#sectionDropdown').append('<option value="">Error loading sections</option>');
                    }
                });
            }
        }

        // Set maximum date for date fields to today
        var today = new Date().toISOString().split('T')[0];
        $('input[type="date"]').attr('max', today);
    });

    function confirmReAdmission() {
        var newClass = document.getElementById('classDropdown').value;
        var newSection = document.getElementById('sectionDropdown').value;
        var readDate = document.querySelector('input[name="readdate"]').value;
        var readFee = document.querySelector('input[name="readfee"]').value;
        var academicYear = document.querySelector('select[name="academic_year"]').value;
        var currentAmount = <?= $student ? $student->addAmt : 0 ?>;
        var reAdmissionNumber = <?= $reAdmissionStats ? ($reAdmissionStats->total_re_admissions + 1) : 1 ?>;

        if (!newClass || !newSection || !readDate || !readFee || !academicYear) {
            alert('Please fill all required fields marked with *');
            return false;
        }

        var change = parseFloat(readFee) - currentAmount;
        var changeText = change > 0 ? 'Increase' : (change < 0 ? 'Decrease' : 'No Change');
        
        return confirm('RE-ADMISSION #' + reAdmissionNumber + '\n\n' +
                      'Academic Year: ' + academicYear + '\n' +
                      'Current Admission Amount: ₹' + currentAmount.toFixed(2) + '\n' +
                      'New Admission Amount: ₹' + parseFloat(readFee).toFixed(2) + '\n' +
                      'Amount Change: ' + changeText + ' of ₹' + Math.abs(change).toFixed(2) + '\n\n' +
                      'This will:\n- Update student class/section\n- Update admission amount\n- Reset current admission fee balance to unpaid\n- Preserve previous payment history\n- Create re-admission record #' + reAdmissionNumber);
    }
    </script>

    <!-- plugins:js -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="vendors/select2/select2.min.js"></script>
    <script src="vendors/typeahead.js/typeahead.bundle.min.js"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <!-- endinject -->
</body>
</html>
