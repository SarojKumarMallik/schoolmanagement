<?php
session_start();
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

// Database connection is provided by includes/dbconnection.php ($conn, $dbh)

// Create tables if not exists (add student_regno field)
$conn->query("CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    size VARCHAR(50) NULL,
    remark VARCHAR(255) NULL,
    current_stock INT DEFAULT 0
)");

$conn->query("CREATE TABLE IF NOT EXISTS stock_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT,
    type ENUM('IN','OUT'),
    quantity INT,
    price_per_unit DECIMAL(10,2),
    total_price DECIMAL(10,2),
    student_regno VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Initialize message
$msg = '';
$msgType = 'info';

// Live availability check
if (isset($_GET['check_stock'])) {
    $item = $_GET['item'];
    $size = $_GET['size'] ?? NULL;
    $remark = $_GET['remark'] ?? NULL;

    $sql = "SELECT SUM(current_stock) as total FROM items WHERE name='$item'";
    if ($size) $sql .= " AND size='$size'";
    if ($remark) $sql .= " AND remark='$remark'";

    $res = $conn->query($sql);
    $row = $res->fetch_assoc();
    echo $row['total'] ?? 0;
    exit;
}

// Get remarks dynamically
if (isset($_GET['get_remarks'])) {
    $item = $_GET['item'];
    $sql = "SELECT DISTINCT remark FROM items WHERE name='$item' AND remark IS NOT NULL AND remark<>''";
    $res = $conn->query($sql);
    $remarks = [];
    while($row = $res->fetch_assoc()) {
        $remarks[] = $row['remark'];
    }
    echo json_encode($remarks);
    exit;
}

// Verify Student Registration Number
if (isset($_GET['verify_student'])) {
    $regno = $_GET['regno'];
    $sql = "SELECT StudentName, StudentClass, FatherName FROM tblstudent WHERE StuID = '$regno'";
    $res = $conn->query($sql);
    
    if ($res->num_rows > 0) {
        $student = $res->fetch_assoc();
        echo json_encode([
            'valid' => true,
            'name' => $student['StudentName'],
            'class' => $student['StudentClass'],
            'father' => $student['FatherName']
        ]);
    } else {
        echo json_encode(['valid' => false]);
    }
    exit;
}

// Issue Stock
if (isset($_POST['issue_stock'])) {
    $item = $_POST['item'];
    $size = $_POST['size'] ?? NULL;
    $remark = $_POST['remark'] ?? NULL;
    $qty = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $student_regno = $_POST['student_regno'];

    // Verify student exists
    $student_sql = "SELECT * FROM tblstudent WHERE StuID = '$student_regno'";
    $student_res = $conn->query($student_sql);
    
    if ($student_res->num_rows == 0) {
        $msgTitle = "Student Not Found";
        $msg = "No student record found with Registration ID <strong>" . htmlspecialchars($student_regno) . "</strong>.";
        $msgType = 'danger';
    } else {
        $student_result = $student_res->fetch_assoc();
        $student_name = $student_result['StudentName'];
        $father_name = $student_result['FatherName'];
        $mother_name = $student_result['MotherName'];
        $class_name = $student_result['StudentClass'];
        $student_section = $student_result['StudentSection'];
        $address = $student_result['Address'];
        $DateofAdmission = $student_result['DateofAdmission'];
        $joindate = $student_result['joindate'];
        
        $sql = "SELECT * FROM items WHERE name='$item' AND (size <=> '$size') AND (remark <=> '$remark') LIMIT 1";
        $res = $conn->query($sql);

        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $item_id = $row['id'];
            $current_stock = (int)$row['current_stock'];

            if ($current_stock >= $qty) {
                $conn->query("UPDATE items SET current_stock = current_stock - $qty WHERE id = $item_id");
                $total = $qty * $price;
                $conn->query("INSERT INTO stock_transactions (item_id, type, quantity, price_per_unit, total_price, student_regno) 
                              VALUES ($item_id,'OUT',$qty,$price,$total,'$student_regno')");
                
                $transaction_id = $conn->insert_id;
                
                $specs = [];
                if (!empty($size)) $specs[] = "Size: " . htmlspecialchars($size);
                if (!empty($remark)) $specs[] = "Remark: " . htmlspecialchars($remark);
                $specStr = !empty($specs) ? ' (' . implode(', ', $specs) . ')' : '';
                
                $unitLabel = ($qty == 1) ? "unit" : "units";
                $formattedPrice = number_format($price, 2);
                $formattedTotal = number_format($total, 2);

                $msgTitle = "Stock Issued Successfully";
                $msg = "Issued <strong>$qty $unitLabel</strong> of <strong>" . htmlspecialchars($item) . "</strong>$specStr to <strong>" . htmlspecialchars($student_name) . "</strong> (ID: $student_regno) at <strong>₹$formattedPrice</strong> each (Txn ID: <strong>#$transaction_id</strong>).";
                $msgType = 'success';
                
                // Generate receipt automatically
                $_SESSION['receipt_data'] = [
                    'transaction_id' => $transaction_id,
                    'student_regno' => $student_regno,
                    'student_name' => $student_name,
                    'father_name' => $father_name,
                    'mother_name' => $mother_name,
                    'class_name' => $class_name,
                    'student_section' => $student_section,
                    'address' => $address,
                    'DateofAdmission' => $DateofAdmission,
                    'joindate' => $joindate,
                    
                    'item' => $item,
                    'size' => $size,
                    'remark' => $remark,
                    'quantity' => $qty,
                    'price_per_unit' => $price,
                    'total_price' => $total,
                    'date' => date('Y-m-d H:i:s')
                ];
                
                echo "<script>window.open('generate-receipt.php', '_blank');</script>";
                
            } else {
                $msgTitle = "Insufficient Stock";
                $msg = "Cannot issue $qty units of " . htmlspecialchars($item) . ". Only <strong>$current_stock units</strong> available in inventory.";
                $msgType = 'danger';
            }
        } else {
            $msgTitle = "Item Not Found";
            $msg = "Selected item variant does not exist in inventory records.";
            $msgType = 'danger';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Issue Stock | Student Management</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="./vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="./vendors/chartist/chartist.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/clean-theme.css">
    <style>
        .student-info {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
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
                    <h3 class="page-title">Issue Stock</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="stock-management.php">Stock Management</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Issue Stock</li>
                        </ol>
                    </nav>
                </div>

                <!-- Modern System Alert -->
                <?php if (!empty($msg)): ?>
                    <div class="alert custom-stock-alert alert-<?= $msgType === 'success' ? 'success' : ($msgType === 'danger' ? 'danger' : 'info') ?> alert-dismissible fade show mb-4" id="msgAlert" role="alert">
                        <div class="d-flex align-items-center">
                            <div class="alert-icon-circle mr-3">
                                <?php if ($msgType === 'success'): ?>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                <?php elseif ($msgType === 'danger'): ?>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                <?php else: ?>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="16" x2="12" y2="12"></line>
                                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1 pr-3">
                                <div class="font-weight-bold" style="font-size: 0.95rem; letter-spacing: -0.2px;">
                                    <?= !empty($msgTitle) ? htmlspecialchars($msgTitle) : ($msgType === 'success' ? 'Success' : 'Notice') ?>
                                </div>
                                <div style="font-size: 0.88rem; margin-top: 2px; line-height: 1.45; opacity: 0.95;">
                                    <?= $msg ?>
                                </div>
                            </div>
                            <button type="button" class="btn-close" aria-label="Close" onclick="dismissMsgAlert()" style="background: none; border: none; font-size: 1.35rem; color: inherit; opacity: 0.6; line-height: 1; padding: 0 4px; cursor: pointer;">
                                &times;
                            </button>
                        </div>
                        <div class="alert-progress-bar"></div>
                    </div>
                <?php endif; ?>

                <?php
                // Query recent stock out transactions for the right-hand panel
                $recent_out_sql = "SELECT st.*, i.name as item_name, i.size, i.remark, 
                                   s.StudentName, s.StudentClass, s.StudentSection
                                   FROM stock_transactions st
                                   JOIN items i ON st.item_id = i.id
                                   LEFT JOIN tblstudent s ON st.student_regno = s.StuID
                                   WHERE st.type = 'OUT'
                                   ORDER BY st.created_at DESC
                                   LIMIT 15";
                $recent_res = $conn->query($recent_out_sql);
                $recent_issues = [];
                if ($recent_res && $recent_res->num_rows > 0) {
                    while ($r = $recent_res->fetch_assoc()) {
                        $recent_issues[] = $r;
                    }
                }
                ?>

                <!-- Split Layout: Left Issue Form, Right Recent Issues -->
                <div class="row">
                    <!-- Left Side: Issue Stock Form -->
                    <div class="col-lg-5 col-xl-5 mb-4">
                        <div class="card stock-card h-100">
                            <div class="card-header bg-white border-bottom py-3" style="border-radius: 12px 12px 0 0;">
                                <div class="d-flex align-items-center">
                                    <div class="stock-header-icon mr-3" style="background: rgba(99, 102, 241, 0.12); color: #4f46e5;">
                                        <i class="icon-share"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 font-weight-bold" style="color: #0f172a; font-size: 1.05rem;">Issue Stock to Student</h5>
                                        <p class="text-muted small mb-0">Allocate uniform & supplies with receipt</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <form method="post" id="issueStockForm">
                                    <!-- Step 1: Student Verification -->
                                    <div class="form-group mb-3">
                                        <label class="form-label font-weight-600 text-dark small mb-1">Student Registration ID <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" name="student_regno" id="student_regno" class="form-control" 
                                                   placeholder="Enter Student ID (e.g. 101)" required onblur="verifyStudent()" style="height: 44px; border-radius: 8px 0 0 8px;">
                                            <button type="button" class="btn btn-outline-primary px-3 font-weight-600" onclick="verifyStudent()" style="border-radius: 0 8px 8px 0; height: 44px;">
                                                <i class="icon-magnifier mr-1"></i> Verify
                                            </button>
                                        </div>
                                        <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">Type Student ID and click Verify to auto-fill details</small>
                                    </div>

                                    <!-- Student Information Banner (Auto-populated) -->
                                    <div id="studentInfo" class="p-3 mb-3 rounded" style="display:none; background: #f0fdf4; border: 1px solid #bbf7d0;">
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3" style="width: 38px; height: 38px; border-radius: 50%; background: #10b981; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: bold; flex-shrink: 0;">
                                                ✓
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0 font-weight-bold text-dark" id="studentName" style="font-size: 0.95rem;">-</h6>
                                                <div class="small text-muted" style="font-size: 0.8rem; margin-top: 2px;">
                                                    Class: <strong id="studentClass" class="text-dark">-</strong> • Father: <span id="studentFather">-</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 2: Item Selection -->
                                    <div class="form-group mb-3">
                                        <label class="form-label font-weight-600 text-dark small mb-1">Select Item <span class="text-danger">*</span></label>
                                        <select name="item" id="itemIssue" class="form-control" onchange="showOptions('Issue')" required style="height: 44px; border-radius: 8px;">
                                            <option value="">-- Choose Item Category --</option>
                                            <option value="Shirt">Shirt</option>
                                            <option value="Pant">Pant</option>
                                            <option value="Shoes">Shoes</option>
                                            <option value="Belt">Belt</option>
                                            <option value="ID Card">ID Card</option>
                                            <option value="Bag">Bag</option>
                                            <option value="Book">Book</option>
                                            <option value="Others">Others</option>
                                        </select>
                                    </div>

                                    <div class="form-group mb-3" id="sizeDivIssue" style="display:none;">
                                        <label class="form-label font-weight-600 text-dark small mb-1">Select Size <span class="text-danger">*</span></label>
                                        <select name="size" id="sizeIssue" class="form-control" onchange="updateAvailability()" style="height: 44px; border-radius: 8px;"></select>
                                    </div>

                                    <div class="form-group mb-3" id="remarkDivIssue" style="display:none;">
                                        <label class="form-label font-weight-600 text-dark small mb-1">Select Specification / Title <span class="text-danger">*</span></label>
                                        <select name="remark" id="remarkIssue" class="form-control" onchange="updateAvailability()" style="height: 44px; border-radius: 8px;">
                                            <option value="">-- Choose Specification --</option>
                                        </select>
                                    </div>

                                    <!-- Real-time Available Stock Strip -->
                                    <div class="p-2 px-3 mb-3 rounded d-flex align-items-center justify-content-between" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                        <span class="small font-weight-600 text-secondary">Warehouse Stock:</span>
                                        <span id="stockPill" class="badge" style="background: #eef2ff; color: #4338ca; font-size: 0.85rem; padding: 5px 12px; border-radius: 6px;">
                                            <span id="availableStock" class="font-weight-bold">0</span> units available
                                        </span>
                                    </div>

                                    <!-- Step 3: Quantity & Unit Price -->
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label font-weight-600 text-dark small mb-1">Quantity <span class="text-danger">*</span></label>
                                                <input type="number" id="qtyIssue" name="quantity" class="form-control" min="1" placeholder="Qty" required oninput="calcIssueTotal()" style="height: 44px; border-radius: 8px;">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label font-weight-600 text-dark small mb-1">Unit Price (₹) <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" id="priceIssue" name="price" class="form-control" min="0" placeholder="Price" required oninput="calcIssueTotal()" style="height: 44px; border-radius: 8px;">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Live Estimated Value Preview -->
                                    <div class="p-3 mb-4 rounded" style="background: #f8fafc; border: 1px dashed #cbd5e1; display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <span class="d-block small text-muted font-weight-500">Billable Amount</span>
                                            <span class="small text-secondary">Units × Unit Price</span>
                                        </div>
                                        <span id="issueTotalDisplay" class="font-weight-700" style="color: #4f46e5; font-size: 1.15rem;">₹0.00</span>
                                    </div>

                                    <button type="submit" name="issue_stock" class="btn btn-primary btn-block py-2 font-weight-bold shadow-sm" style="border-radius: 8px; font-size: 0.95rem;">
                                        <i class="icon-printer mr-2"></i> Issue Stock & Generate Receipt
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Recent Stock Issues & Receipts List -->
                    <div class="col-lg-7 col-xl-7 mb-4">
                        <div class="card stock-card h-100">
                            <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between" style="border-radius: 12px 12px 0 0; gap: 10px;">
                                <div class="d-flex align-items-center">
                                    <div class="stock-header-icon mr-3" style="background: rgba(16, 185, 129, 0.12); color: #059669;">
                                        <i class="icon-docs"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 font-weight-bold" style="color: #0f172a; font-size: 1.05rem;">Recent Issued Items</h5>
                                        <p class="text-muted small mb-0">Audit log of issued supplies & student receipts</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center" style="gap: 8px;">
                                    <input type="text" id="recentSearch" class="form-control form-control-sm" placeholder="Search issued..." style="width: 170px; border-radius: 6px; height: 34px;">
                                    <a href="stock_report.php" class="btn btn-sm btn-outline-primary" style="border-radius: 6px; white-space: nowrap; height: 34px; display: inline-flex; align-items: center;">
                                        <i class="icon-chart mr-1"></i> Reports
                                    </a>
                                </div>
                            </div>

                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 560px; overflow-y: auto;">
                                    <table class="table table-hover align-middle mb-0" id="recentTable">
                                        <thead style="background: #f8fafc; position: sticky; top: 0; z-index: 5;">
                                            <tr style="border-bottom: 2px solid #e2e8f0; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">
                                                <th class="py-3 px-3">Student Details</th>
                                                <th class="py-3 px-3">Item Issued</th>
                                                <th class="py-3 px-3 text-right">Amount</th>
                                                <th class="py-3 px-3 text-right">Issued At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recent_issues)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-5">
                                                        <div style="color: #94a3b8;">
                                                            <i class="icon-doc" style="font-size: 38px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                                                            <h6 class="font-weight-600 text-secondary mb-1">No Issued Stock Records</h6>
                                                            <p class="small text-muted mb-0">Use the form on the left to issue stock to a registered student.</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($recent_issues as $iss): 
                                                    $studentName = !empty($iss['StudentName']) ? htmlspecialchars($iss['StudentName']) : 'Student ID: ' . htmlspecialchars($iss['student_regno']);
                                                    $studentClass = !empty($iss['StudentClass']) ? htmlspecialchars($iss['StudentClass']) : '-';
                                                    $spec = $iss['size'] ?: $iss['remark'] ?: '-';
                                                ?>
                                                    <tr class="issue-item-row" data-search="<?= strtolower(htmlspecialchars($studentName . ' ' . $iss['student_regno'] . ' ' . $iss['item_name'] . ' ' . $spec)) ?>">
                                                        <td class="py-3 px-3">
                                                            <div class="d-flex align-items-center">
                                                                <div class="mr-2" style="width: 32px; height: 32px; border-radius: 8px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0;">
                                                                    <?= strtoupper(substr($studentName, 0, 1)) ?>
                                                                </div>
                                                                <div>
                                                                    <strong style="color: #0f172a; font-size: 0.92rem;"><?= $studentName ?></strong>
                                                                    <div class="text-muted" style="font-size: 0.72rem;">
                                                                        Reg: <?= htmlspecialchars($iss['student_regno']) ?> • Class: <?= $studentClass ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="py-3 px-3">
                                                            <strong style="color: #1e293b;"><?= htmlspecialchars($iss['item_name']) ?></strong>
                                                            <span class="badge badge-light border font-weight-600 text-dark ml-1"><?= htmlspecialchars($spec) ?></span>
                                                            <div class="text-muted small">Qty: <strong><?= (int)$iss['quantity'] ?></strong> @ ₹<?= number_format($iss['price_per_unit'], 2) ?></div>
                                                        </td>
                                                        <td class="py-3 px-3 text-right">
                                                            <span class="font-weight-700 text-primary" style="font-size: 0.98rem;">
                                                                ₹<?= number_format($iss['total_price'], 2) ?>
                                                            </span>
                                                        </td>
                                                        <td class="py-3 px-3 text-right text-muted" style="font-size: 0.75rem;">
                                                            <?= date('d M Y', strtotime($iss['created_at'])) ?><br>
                                                            <span class="text-secondary"><?= date('h:i A', strtotime($iss['created_at'])) ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <?php include_once('includes/footer.php'); ?>
        </div>
    </div>
</div>

<script>
function showOptions(type) {
    let item = document.getElementById("item" + type).value;
    let sizeDiv = document.getElementById("sizeDiv" + type);
    let sizeSelect = document.getElementById("size" + type);
    let remarkDiv = document.getElementById("remarkDiv" + type);
    let remarkInput = document.getElementById("remark" + type) || document.getElementById("remark" + type);

    sizeDiv.style.display = "none";
    remarkDiv.style.display = "none";
    sizeSelect.innerHTML = "";
    if (remarkInput) remarkInput.value = "";

    if (item === "Shirt") {
        sizeDiv.style.display = "block";
        sizeSelect.innerHTML = "<option value='S'>S</option><option value='M'>M</option><option value='L'>L</option>";
    } else if (item === "Pant") {
        sizeDiv.style.display = "block";
        sizeSelect.innerHTML = "<option value='28'>28</option><option value='30'>30</option><option value='32'>32</option>";
    } else if (item === "Shoes") {
        sizeDiv.style.display = "block";
        sizeSelect.innerHTML = "<option value='3'>3</option><option value='4'>4</option><option value='5'>5</option><option value='6'>6</option><option value='7'>7</option>";
    } else if (item === "Book" || item === "Others") {
        remarkDiv.style.display = "block";
        if (type === "Issue") {
            fetch("?get_remarks=1&item=" + item)
            .then(res => res.json())
            .then(data => {
                let remarkSelect = document.getElementById("remarkIssue");
                remarkSelect.innerHTML = "<option value=''>--Select Remark--</option>";
                data.forEach(r => {
                    let opt = document.createElement("option");
                    opt.value = r;
                    opt.text = r;
                    remarkSelect.appendChild(opt);
                });
            });
        }
    }

    if (type === "Issue") updateAvailability();
}

function updateAvailability() {
    let item = document.getElementById("itemIssue").value;
    let size = document.getElementById("sizeIssue") ? document.getElementById("sizeIssue").value : "";
    let remark = document.getElementById("remarkIssue") ? document.getElementById("remarkIssue").value : "";

    if (item !== "") {
        fetch("?check_stock=1&item=" + item + "&size=" + size + "&remark=" + remark)
        .then(res => res.text())
        .then(data => {
            let count = parseInt(data) || 0;
            let el = document.getElementById("availableStock");
            let pill = document.getElementById("stockPill");
            if (el) el.innerText = count;
            if (pill) {
                if (count <= 0) {
                    pill.style.background = "#fee2e2";
                    pill.style.color = "#dc2626";
                } else if (count < 10) {
                    pill.style.background = "#fef3c7";
                    pill.style.color = "#b45309";
                } else {
                    pill.style.background = "#dcfce7";
                    pill.style.color = "#15803d";
                }
            }
        });
    }
}

function calcIssueTotal() {
    let q = parseFloat(document.getElementById('qtyIssue')?.value) || 0;
    let p = parseFloat(document.getElementById('priceIssue')?.value) || 0;
    let tot = (q * p).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    let el = document.getElementById('issueTotalDisplay');
    if (el) el.textContent = '₹' + tot;
}

// Client-side quick filter for recent issues
document.getElementById('recentSearch')?.addEventListener('keyup', function() {
    let q = this.value.toLowerCase().trim();
    let rows = document.querySelectorAll('#recentTable tbody tr.issue-item-row');
    rows.forEach(r => {
        let hay = r.getAttribute('data-search') || '';
        r.style.display = hay.includes(q) ? '' : 'none';
    });
});

function verifyStudent() {
    let regno = document.getElementById("student_regno").value;
    if (regno.trim() === "") return;

    fetch("?verify_student=1&regno=" + regno)
    .then(res => res.json())
    .then(data => {
        let studentInfo = document.getElementById("studentInfo");
        if (data.valid) {
            document.getElementById("studentName").textContent = data.name;
            document.getElementById("studentClass").textContent = data.class;
            document.getElementById("studentFather").textContent = data.father;
            studentInfo.style.display = "block";
        } else {
            studentInfo.style.display = "none";
            alert("❌ Student with Registration Number '" + regno + "' not found!");
        }
    });
}

// Dismiss message alert smoothly
function dismissMsgAlert() {
    const alertEl = document.getElementById('msgAlert');
    if (alertEl) {
        alertEl.style.transition = "all 0.35s ease";
        alertEl.style.opacity = "0";
        alertEl.style.transform = "translateY(-8px)";
        setTimeout(() => {
            alertEl.remove();
        }, 350);
    }
}

// Auto-hide alert after 5 seconds
setTimeout(() => {
    dismissMsgAlert();
}, 5000);
</script>

<script src="vendors/js/vendor.bundle.base.js"></script>
<script src="./vendors/chart.js/Chart.min.js"></script>
<script src="./vendors/moment/moment.min.js"></script>
<script src="./vendors/daterangepicker/daterangepicker.js"></script>
<script src="./vendors/chartist/chartist.min.js"></script>
<script src="js/off-canvas.js"></script>
<script src="js/misc.js"></script>
<script src="./js/dashboard.js"></script>
</body>
</html>
<?php $conn->close(); ?>