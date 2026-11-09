<?php
session_start();
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "studentmsdb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch current stock with last update date/time
$sql_items = "SELECT i.*, 
              (SELECT st.created_at FROM stock_transactions st WHERE st.item_id=i.id ORDER BY st.created_at DESC LIMIT 1) as last_update
              FROM items i
              ORDER BY i.name, i.size";
$result_items = $conn->query($sql_items);
if (!$result_items) {
    die("Items query failed: " . $conn->error);
}

// Fetch transactions history with student details
$sql_txn = "SELECT st.id, i.name as item_name, i.size, i.remark, st.type, st.quantity, st.price_per_unit, st.total_price, st.student_regno, st.created_at
            FROM stock_transactions st
            JOIN items i ON st.item_id = i.id
            ORDER BY st.created_at DESC";
$result_txn = $conn->query($sql_txn);
if (!$result_txn) {
    die("Transactions query failed: " . $conn->error);
}

// Fetch KPI summary metrics
$sku_count = 0;
$total_inventory = 0;
$low_stock_count = 0;
$items_arr = [];

if ($result_items && $result_items->num_rows > 0) {
    while ($r = $result_items->fetch_assoc()) {
        $sku_count++;
        $total_inventory += (int)$r['current_stock'];
        if ((int)$r['current_stock'] < 10) {
            $low_stock_count++;
        }
        $items_arr[] = $r;
    }
}

$txn_in_count = 0;
$txn_out_count = 0;
$txn_arr = [];

if ($result_txn && $result_txn->num_rows > 0) {
    while ($r = $result_txn->fetch_assoc()) {
        if (strtoupper($r['type']) === 'IN') {
            $txn_in_count++;
        } else {
            $txn_out_count++;
        }
        $txn_arr[] = $r;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Stock Report & Inventory Analytics | Student Management</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="./vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="./vendors/chartist/chartist.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/clean-theme.css">
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <style>
        .stock-report-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            margin-bottom: 24px;
        }
        .report-header-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .kpi-stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            height: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .kpi-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
        }
        .kpi-icon-pill {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        table.dataTable thead th {
            border-bottom: 2px solid #e2e8f0 !important;
            font-size: 0.78rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            color: #64748b !important;
            background: #f8fafc !important;
            padding: 12px 14px !important;
        }
        table.dataTable tbody td {
            padding: 12px 14px !important;
            vertical-align: middle !important;
            border-bottom: 1px solid #f1f5f9 !important;
        }
        table.dataTable tbody tr:hover {
            background-color: #f8fafc !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #6366f1 !important;
            color: #ffffff !important;
            border-color: #6366f1 !important;
            border-radius: 6px !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #eef2ff !important;
            color: #4f46e5 !important;
            border-color: #c7d2fe !important;
            border-radius: 6px !important;
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
                    <!-- Page Header -->
                    <div class="page-header d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h3 class="page-title mb-1 font-weight-bold" style="color: #0f172a;">Stock & Inventory Analytics</h3>
                            <p class="text-muted small mb-0">Monitor warehouse balances, batches received, and student issues</p>
                        </div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0" style="background: transparent; padding: 0;">
                                <li class="breadcrumb-item"><a href="dashboard.php" style="color: #6366f1; text-decoration: none;">Dashboard</a></li>
                                <li class="breadcrumb-item active text-muted" aria-current="page">Stock Report</li>
                            </ol>
                        </nav>
                    </div>

                    <!-- 4 Summary Metric KPI Cards -->
                    <div class="row mb-4">
                        <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
                            <div class="kpi-stat-card">
                                <div class="kpi-icon-pill" style="background: rgba(99, 102, 241, 0.12); color: #4f46e5;">
                                    <i class="icon-tag"></i>
                                </div>
                                <div>
                                    <div class="text-muted small font-weight-600 text-uppercase" style="letter-spacing: 0.5px;">Item SKUs</div>
                                    <h3 class="mb-0 font-weight-bold" style="color: #0f172a;"><?= $sku_count ?></h3>
                                    <span class="small text-muted">Distinct Variants</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
                            <div class="kpi-stat-card">
                                <div class="kpi-icon-pill" style="background: rgba(16, 185, 129, 0.12); color: #059669;">
                                    <i class="icon-layers"></i>
                                </div>
                                <div>
                                    <div class="text-muted small font-weight-600 text-uppercase" style="letter-spacing: 0.5px;">Units in Stock</div>
                                    <h3 class="mb-0 font-weight-bold" style="color: #0f172a;"><?= number_format($total_inventory) ?></h3>
                                    <span class="small text-success font-weight-600">On-hand Warehouse</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
                            <div class="kpi-stat-card">
                                <div class="kpi-icon-pill" style="background: rgba(14, 165, 233, 0.12); color: #0284c7;">
                                    <i class="icon-arrow-down-circle"></i>
                                </div>
                                <div>
                                    <div class="text-muted small font-weight-600 text-uppercase" style="letter-spacing: 0.5px;">Inward Batches</div>
                                    <h3 class="mb-0 font-weight-bold" style="color: #0f172a;"><?= $txn_in_count ?></h3>
                                    <span class="small text-muted">Procurements (IN)</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-lg-3">
                            <div class="kpi-stat-card">
                                <div class="kpi-icon-pill" style="background: rgba(245, 158, 11, 0.12); color: #d97706;">
                                    <i class="icon-arrow-up-circle"></i>
                                </div>
                                <div>
                                    <div class="text-muted small font-weight-600 text-uppercase" style="letter-spacing: 0.5px;">Student Issues</div>
                                    <h3 class="mb-0 font-weight-bold" style="color: #0f172a;"><?= $txn_out_count ?></h3>
                                    <span class="small text-muted">Allocations (OUT)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 1: Current Stock Inventory -->
                    <div class="card stock-report-card">
                        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between" style="border-radius: 12px 12px 0 0; gap: 10px;">
                            <div class="d-flex align-items-center">
                                <div class="report-header-icon mr-3" style="background: rgba(99, 102, 241, 0.12); color: #4f46e5;">
                                    <i class="icon-layers"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 font-weight-bold" style="color: #0f172a; font-size: 1.05rem;">Current Inventory Stock</h5>
                                    <p class="text-muted small mb-0">On-hand quantities & stock availability by variant</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center" style="gap: 8px;">
                                <a href="add_stock.php" class="btn btn-sm btn-primary font-weight-600" style="border-radius: 6px; padding: 7px 14px;">
                                    <i class="icon-plus mr-1"></i> Add Stock
                                </a>
                                <a href="issue_stock.php" class="btn btn-sm btn-outline-secondary font-weight-600" style="border-radius: 6px; padding: 7px 14px;">
                                    <i class="icon-share mr-1"></i> Issue Stock
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="stockTable" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Item Details</th>
                                            <th>Variant / Remark</th>
                                            <th class="text-center">Availability Status</th>
                                            <th class="text-right">Current Stock</th>
                                            <th class="text-right">Last Update</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($items_arr)): ?>
                                            <?php foreach ($items_arr as $row): 
                                                $qty = (int)$row['current_stock'];
                                                $sizeOrRemark = $row['size'] ?: $row['remark'] ?: '—';
                                                $lastUpdate = $row['last_update'] ? date("d M Y, h:i A", strtotime($row['last_update'])) : '—';
                                                
                                                $statusBadge = '';
                                                if ($qty <= 0) {
                                                    $statusBadge = '<span class="badge" style="background: #fee2e2; color: #dc2626; font-weight: 600; padding: 5px 10px; border-radius: 6px;">Out of Stock</span>';
                                                } elseif ($qty < 10) {
                                                    $statusBadge = '<span class="badge" style="background: #fef3c7; color: #b45309; font-weight: 600; padding: 5px 10px; border-radius: 6px;">Low Stock</span>';
                                                } else {
                                                    $statusBadge = '<span class="badge" style="background: #dcfce7; color: #15803d; font-weight: 600; padding: 5px 10px; border-radius: 6px;">In Stock</span>';
                                                }

                                                $icon = 'icon-bag';
                                                $nm = strtolower($row['name']);
                                                if (strpos($nm, 'book') !== false) $icon = 'icon-notebook';
                                                elseif (strpos($nm, 'shirt') !== false || strpos($nm, 'pant') !== false) $icon = 'icon-layers';
                                                elseif (strpos($nm, 'shoe') !== false) $icon = 'icon-compass';
                                                elseif (strpos($nm, 'id') !== false) $icon = 'icon-badge';
                                                elseif (strpos($nm, 'belt') !== false) $icon = 'icon-link';
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="mr-2" style="width: 32px; height: 32px; border-radius: 8px; background: #eef2ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0;">
                                                                <i class="<?= $icon ?>"></i>
                                                            </div>
                                                            <strong style="color: #0f172a; font-size: 0.92rem;"><?= htmlspecialchars($row['name']) ?></strong>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['size']): ?>
                                                            <span class="badge badge-light border font-weight-600 text-dark">Size: <?= htmlspecialchars($row['size']) ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($row['remark']): ?>
                                                            <span class="text-muted small ml-1 font-italic"><?= htmlspecialchars($row['remark']) ?></span>
                                                        <?php endif; ?>
                                                        <?php if (!$row['size'] && !$row['remark']): ?>
                                                            <span class="text-muted small">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center"><?= $statusBadge ?></td>
                                                    <td class="text-right">
                                                        <strong class="<?= $qty <= 0 ? 'text-danger' : ($qty < 10 ? 'text-warning font-weight-bold' : 'text-dark') ?>" style="font-size: 1.05rem;">
                                                            <?= number_format($qty) ?>
                                                        </strong>
                                                        <span class="text-muted small">units</span>
                                                    </td>
                                                    <td class="text-right text-muted small"><?= $lastUpdate ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Transactions History -->
                    <div class="card stock-report-card">
                        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between" style="border-radius: 12px 12px 0 0; gap: 10px;">
                            <div class="d-flex align-items-center">
                                <div class="report-header-icon mr-3" style="background: rgba(16, 185, 129, 0.12); color: #059669;">
                                    <i class="icon-docs"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 font-weight-bold" style="color: #0f172a; font-size: 1.05rem;">Stock Transactions Audit Log</h5>
                                    <p class="text-muted small mb-0">History of stock receipts and student issuances</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center" style="gap: 10px;">
                                <div class="d-flex align-items-center">
                                    <label for="monthFilter" class="small text-muted font-weight-600 mb-0 mr-2" style="white-space: nowrap;">Month Filter:</label>
                                    <input type="month" id="monthFilter" class="form-control form-control-sm" style="width: 155px; height: 34px; border-radius: 6px;">
                                </div>
                                <button type="button" class="btn btn-sm btn-light border" onclick="$('#monthFilter').val('').trigger('change');" title="Reset filter" style="border-radius: 6px; height: 34px;">
                                    Reset
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="txnTable" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Item Details</th>
                                            <th>Variant / Remark</th>
                                            <th class="text-center">Quantity</th>
                                            <th class="text-right">Price / Unit</th>
                                            <th class="text-right">Total Price</th>
                                            <th class="text-right">Transaction Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($txn_arr)): ?>
                                            <?php foreach ($txn_arr as $row): 
                                                $sizeOrRemark = $row['size'] ?: $row['remark'] ?: '—';
                                                $formattedDate = date("d-m-Y H:i:s", strtotime($row['created_at']));
                                                $displayDate = date("d M Y, h:i A", strtotime($row['created_at']));
                                                $isOut = (strtoupper($row['type']) === 'OUT');
                                            ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($isOut): ?>
                                                            <span class="badge" style="background: #e0e7ff; color: #4338ca; font-weight: 600; padding: 5px 10px; border-radius: 6px;">
                                                                <i class="icon-arrow-up mr-1"></i> OUT (Issued)
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge" style="background: #dcfce7; color: #15803d; font-weight: 600; padding: 5px 10px; border-radius: 6px;">
                                                                <i class="icon-arrow-down mr-1"></i> IN (Purchased)
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong style="color: #0f172a; font-size: 0.92rem;"><?= htmlspecialchars($row['item_name']) ?></strong>
                                                        <?php if (!empty($row['student_regno'])): ?>
                                                            <div class="text-muted small">Student ID: <strong><?= htmlspecialchars($row['student_regno']) ?></strong></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['size']): ?>
                                                            <span class="badge badge-light border font-weight-600 text-dark">Size: <?= htmlspecialchars($row['size']) ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($row['remark']): ?>
                                                            <span class="text-muted small ml-1"><?= htmlspecialchars($row['remark']) ?></span>
                                                        <?php endif; ?>
                                                        <?php if (!$row['size'] && !$row['remark']): ?>
                                                            <span class="text-muted small">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center font-weight-bold" style="color: #1e293b;">
                                                        <?= (int)$row['quantity'] ?>
                                                    </td>
                                                    <td class="text-right text-muted small">
                                                        ₹<?= number_format((float)$row['price_per_unit'], 2) ?>
                                                    </td>
                                                    <td class="text-right font-weight-bold <?= $isOut ? 'text-primary' : 'text-success' ?>" style="font-size: 0.95rem;">
                                                        ₹<?= number_format((float)$row['total_price'], 2) ?>
                                                    </td>
                                                    <td class="text-right text-muted small" data-raw-date="<?= $formattedDate ?>">
                                                        <?= $displayDate ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- jQuery & DataTables JS -->
                    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
                    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
                    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
                    <script>
                        $.fn.dataTable.ext.errMode = 'none';

                        $('#stockTable').DataTable({
                            info: true,
                            pageLength: 10,
                            order: [
                                [3, 'desc']
                            ],
                            language: {
                                emptyTable: "No items available in stock",
                                search: "Search Stock:",
                                lengthMenu: "Show _MENU_ entries"
                            }
                        });

                        var txnTable = $('#txnTable').DataTable({
                            info: true,
                            pageLength: 10,
                            order: [
                                [6, 'desc']
                            ],
                            language: {
                                emptyTable: "No transactions found",
                                search: "Filter Audit Log:",
                                lengthMenu: "Show _MENU_ entries"
                            }
                        });

                        // Robust Month Filter for txnTable
                        $.fn.dataTable.ext.search.push(
                            function(settings, data, dataIndex) {
                                if (settings.nTable.id !== 'txnTable') return true;
                                var selectedMonth = $('#monthFilter').val(); // YYYY-MM
                                if (!selectedMonth) return true;

                                var cellNode = txnTable.cell(dataIndex, 6).node();
                                var rawDate = $(cellNode).attr('data-raw-date') || ''; // format: dd-mm-yyyy hh:ii:ss
                                if (rawDate) {
                                    var parts = rawDate.split(' ')[0].split('-'); // [dd, mm, yyyy]
                                    if (parts.length >= 3) {
                                        var rowMonth = parts[2] + '-' + parts[1];
                                        return rowMonth === selectedMonth;
                                    }
                                }
                                return true;
                            }
                        );

                        $('#monthFilter').on('change', function() {
                            txnTable.draw();
                        });
                    </script>

                </div>

                <?php include_once('includes/footer.php'); ?>
            </div>
        </div>
    </div>

    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="vendors/chart.js/Chart.min.js"></script>
    <script src="vendors/moment/moment.min.js"></script>
    <script src="vendors/daterangepicker/daterangepicker.js"></script>
    <script src="vendors/chartist/chartist.min.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script src="js/dashboard.js"></script>
</body>

</html>
<?php $conn->close(); ?>