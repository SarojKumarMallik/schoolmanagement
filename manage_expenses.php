<?php
session_start();
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

// Database connection is provided by includes/dbconnection.php ($conn, $dbh)

$msg = "";

// Handle expense form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $expense_date = trim($_POST['expense_date']);
    $amount = floatval($_POST['amount']);
    $payment_mode = trim($_POST['payment_mode']);
    $vendor = trim($_POST['vendor']);
    $remarks = trim($_POST['remarks']);
    
    if ($expense_date && $amount > 0 && $payment_mode) {
        $stmt = $conn->prepare("INSERT INTO expenses (expense_date, amount, payment_mode, vendor, remarks, added_by) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdssss", $expense_date, $amount, $payment_mode, $vendor, $remarks, $_SESSION['sturecmsaid']);
        
        if ($stmt->execute()) {
            $msg = "✅ Expense added successfully!";
        } else {
            $msg = "❌ Error adding expense: " . $conn->error;
        }
    } else {
        $msg = "❌ Please fill all required fields correctly.";
    }
}

// Handle expense deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $msg = "✅ Expense deleted successfully!";
    } else {
        $msg = "❌ Error deleting expense.";
    }
    
    header("Location: manage_expenses.php?msg=" . urlencode($msg));
    exit();
}

// Fetch expenses with filtering
$where_conditions = [];
$params = [];
$types = "";

if (isset($_GET['filter'])) {
    if (!empty($_GET['start_date'])) {
        $where_conditions[] = "expense_date >= ?";
        $params[] = $_GET['start_date'];
        $types .= "s";
    }
    if (!empty($_GET['end_date'])) {
        $where_conditions[] = "expense_date <= ?";
        $params[] = $_GET['end_date'];
        $types .= "s";
    }
    if (!empty($_GET['payment_mode'])) {
        $where_conditions[] = "payment_mode = ?";
        $params[] = $_GET['payment_mode'];
        $types .= "s";
    }
}

$where_sql = "";
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// Total expenses calculation
$total_sql = "SELECT SUM(amount) as total_expenses FROM expenses $where_sql";
$total_stmt = $conn->prepare($total_sql);
if (!empty($params)) {
    $total_stmt->bind_param($types, ...$params);
}
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_expenses = $total_row['total_expenses'] ?? 0;

// Fetch expenses for display
$sql = "SELECT * FROM expenses $where_sql ORDER BY expense_date DESC, id DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$expenses_result = $stmt->get_result();

// Get unique payment modes for filter
$payment_modes_result = $conn->query("SELECT DISTINCT payment_mode FROM expenses ORDER BY payment_mode");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Student Management System || Manage Expenses</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        .container {
            max-width: 100%;
            margin: 30px auto;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .summary-card .amount {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
        }
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .filter-section {
            background: #e9ecef;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .btn-action {
            padding: 5px 10px;
            margin: 2px;
            font-size: 12px;
        }
        .alert {
            margin: 15px 0;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: 500;
        }
        .expense-form .row {
            margin-bottom: 15px;
        }
        .payment-badge {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .bg-cash { background-color: #28a745; color: white; }
        .bg-card { background-color: #007bff; color: white; }
        .bg-upi { background-color: #6f42c1; color: white; }
        .bg-transfer { background-color: #fd7e14; color: white; }
    </style>
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php');?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">Manage Expenses</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Manage Expenses</li>
                            </ol>
                        </nav>
                    </div>
                    
                    <div class="container">
                        <?php if ($msg): ?>
                            <div class="alert alert-info" id="msgAlert"><?= htmlspecialchars($msg) ?></div>
                        <?php endif; ?>

                        <!-- Summary Cards -->
                        <div class="summary-cards">
                            <div class="summary-card">
                                <h3>Total Expenses</h3>
                                <p class="amount">₹<?= number_format($total_expenses, 2) ?></p>
                            </div>
                            <div class="summary-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                <h3>This Month</h3>
                                <p class="amount">
                                    <?php
                                    $month_sql = "SELECT SUM(amount) as monthly_total FROM expenses 
                                                 WHERE MONTH(expense_date) = MONTH(CURRENT_DATE()) 
                                                 AND YEAR(expense_date) = YEAR(CURRENT_DATE())";
                                    $month_result = $conn->query($month_sql);
                                    $month_row = $month_result->fetch_assoc();
                                    echo '₹' . number_format($month_row['monthly_total'] ?? 0, 2);
                                    ?>
                                </p>
                            </div>
                            <div class="summary-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                <h3>Today's Expenses</h3>
                                <p class="amount">
                                    <?php
                                    $today_sql = "SELECT SUM(amount) as daily_total FROM expenses 
                                                WHERE expense_date = CURDATE()";
                                    $today_result = $conn->query($today_sql);
                                    $today_row = $today_result->fetch_assoc();
                                    echo '₹' . number_format($today_row['daily_total'] ?? 0, 2);
                                    ?>
                                </p>
                            </div>
                        </div>

                        <!-- Expense Form -->
                        <div class="form-section">
                            <h4>➕ Add New Expense</h4>
                            <form method="POST" class="expense-form">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">Date *</label>
                                        <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Amount (₹) *</label>
                                        <input type="number" name="amount" class="form-control" step="0.01" min="0" placeholder="0.00" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Payment Mode *</label>
                                        <select name="payment_mode" class="form-control" required>
                                            <option value="">-- Select Payment Mode --</option>
                                            <option value="Cash">Cash</option>
                                            <option value="Credit Card">Credit Card</option>
                                            <option value="Debit Card">Debit Card</option>
                                            <option value="UPI">UPI</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Cheque">Cheque</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Vendor</label>
                                        <input type="text" name="vendor" class="form-control" placeholder="e.g., Amazon, Local Store">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label">Remarks</label>
                                        <textarea name="remarks" class="form-control" rows="3" placeholder="Enter remarks about this expense"></textarea>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12 text-end">
                                        <button type="submit" name="add_expense" class="btn btn-success px-4">Add Expense</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Filter Section -->
                        <div class="filter-section">
                            <h5>Filter Expenses</h5>
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Payment Mode</label>
                                    <select name="payment_mode" class="form-control">
                                        <option value="">All Payment Modes</option>
                                        <?php while ($mode = $payment_modes_result->fetch_assoc()): ?>
                                            <option value="<?= $mode['payment_mode'] ?>" <?= isset($_GET['payment_mode']) && $_GET['payment_mode'] == $mode['payment_mode'] ? 'selected' : '' ?>>
                                                <?= $mode['payment_mode'] ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" value="<?= $_GET['start_date'] ?? '' ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control" value="<?= $_GET['end_date'] ?? '' ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label><br>
                                    <button type="submit" name="filter" class="btn btn-primary">Apply Filter</button>
                                    <a href="manage_expenses.php" class="btn btn-secondary">Clear</a>
                                </div>
                            </form>
                        </div>

                        <!-- Expenses Table -->
                        <h4>📋 Expense History</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Payment Mode</th>
                                        <th>Vendor</th>
                                        <th>Remarks</th>
                                        <th>Added On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($expenses_result->num_rows > 0): ?>
                                        <?php while ($row = $expenses_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['expense_date']) ?></td>
                                                <td><strong>₹<?= number_format($row['amount'], 2) ?></strong></td>
                                                <td>
                                                    <?php
                                                    $badge_class = '';
                                                    switch($row['payment_mode']) {
                                                        case 'Cash': $badge_class = 'bg-cash'; break;
                                                        case 'Credit Card':
                                                        case 'Debit Card': $badge_class = 'bg-card'; break;
                                                        case 'UPI': $badge_class = 'bg-upi'; break;
                                                        case 'Bank Transfer': $badge_class = 'bg-transfer'; break;
                                                        default: $badge_class = 'bg-secondary';
                                                    }
                                                    ?>
                                                    <span class="badge payment-badge <?= $badge_class ?>">
                                                        <?= htmlspecialchars($row['payment_mode']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($row['vendor'] ?: 'N/A') ?></td>
                                                <td><?= htmlspecialchars($row['remarks'] ?: 'N/A') ?></td>
                                                <td><?= $row['added_on'] ?></td>
                                                <td>
                                                    <a href="manage_expenses.php?delete_id=<?= $row['id'] ?>" 
                                                       class="btn btn-danger btn-sm btn-action" 
                                                       onclick="return confirm('Are you sure you want to delete this expense?')">
                                                       Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                No expenses found. Start by adding your first expense above.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php include_once('includes/footer.php');?>
            </div>
        </div>
    </div>

    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    
    <script>
        // Auto-hide message alert
        setTimeout(() => {
            const alert = document.getElementById('msgAlert');
            if (alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 1000);
            }
        }, 5000);
    </script>
</body>
</html>