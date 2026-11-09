<?php
session_start();
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

// Inline MySQL connection
$host = "localhost";
$user = "root";
$pass = "root";
$db   = "studentmsdb";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Create tables if not exists
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
    student_regno VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Initialize message
$msg = '';
$msgTitle = '';
$msgType = 'info';

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

// Add Stock
if (isset($_POST['add_stock'])) {
    $item = trim($_POST['item']);
    $size = !empty($_POST['size']) ? trim($_POST['size']) : NULL;
    $remark = !empty($_POST['remark']) ? trim($_POST['remark']) : NULL;
    $qty = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];

    $sql = "SELECT * FROM items WHERE name='$item' AND (size <=> '$size') AND (remark <=> '$remark') LIMIT 1";
    $res = $conn->query($sql);

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $item_id = $row['id'];
        $conn->query("UPDATE items SET current_stock = current_stock + $qty WHERE id = $item_id");
    } else {
        $conn->query("INSERT INTO items (name, size, remark, current_stock) VALUES ('$item','$size','$remark',$qty)");
        $item_id = $conn->insert_id;
    }

    $total = $qty * $price;
    $conn->query("INSERT INTO stock_transactions (item_id, type, quantity, price_per_unit, total_price) 
                  VALUES ($item_id,'IN',$qty,$price,$total)");

    // Clean, elegant description formatting
    $specs = [];
    if (!empty($size)) $specs[] = "Size: " . htmlspecialchars($size);
    if (!empty($remark)) $specs[] = "Remark: " . htmlspecialchars($remark);
    $specStr = !empty($specs) ? ' (' . implode(', ', $specs) . ')' : '';
    
    $unitLabel = ($qty == 1) ? "unit" : "units";
    $formattedPrice = number_format($price, 2);
    $formattedTotal = number_format($total, 2);

    $msgTitle = "Stock Added Successfully";
    $msg = "Added <strong>$qty $unitLabel</strong> of <strong>" . htmlspecialchars($item) . "</strong>$specStr at <strong>₹$formattedPrice</strong> each (Total: <strong>₹$formattedTotal</strong>).";
    $msgType = 'success';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Add Stock | Student Management</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="./vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="./vendors/chartist/chartist.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/clean-theme.css">
    <style>
        .stock-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            transition: box-shadow 0.2s ease;
        }
        .stock-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        }
        .stock-header-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        #inventoryTable tbody tr:hover {
            background-color: #f8fafc;
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
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="page-title mb-1 font-weight-bold" style="color: #0f172a;">Stock Management</h3>
                        <p class="text-muted small mb-0">Record incoming stock supplies and monitor real-time inventory</p>
                    </div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0" style="background: transparent; padding: 0;">
                            <li class="breadcrumb-item"><a href="dashboard.php" style="color: #6366f1; text-decoration: none;">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="stock_report.php" style="color: #6366f1; text-decoration: none;">Inventory</a></li>
                            <li class="breadcrumb-item active text-muted" aria-current="page">Add Stock</li>
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
                // Fetch stock items for the right-side inventory table
                $items_sql = "SELECT i.*, 
                              (SELECT st.created_at FROM stock_transactions st WHERE st.item_id=i.id ORDER BY st.created_at DESC LIMIT 1) as last_update
                              FROM items i 
                              ORDER BY i.current_stock ASC, i.name ASC";
                $items_res = $conn->query($items_sql);
                $total_items = 0;
                $total_qty = 0;
                $low_stock_count = 0;
                $items_list = [];

                if ($items_res && $items_res->num_rows > 0) {
                    while ($r = $items_res->fetch_assoc()) {
                        $total_items++;
                        $total_qty += (int)$r['current_stock'];
                        if ((int)$r['current_stock'] < 10) {
                            $low_stock_count++;
                        }
                        $items_list[] = $r;
                    }
                }
                ?>

                <!-- Split Layout: Left Form, Right Inventory List -->
                <div class="row">
                    <!-- Left Side: Add Stock Form -->
                    <div class="col-lg-5 col-xl-4 mb-4">
                        <div class="card stock-card h-100">
                            <div class="card-header bg-white border-bottom py-3" style="border-radius: 12px 12px 0 0;">
                                <div class="d-flex align-items-center">
                                    <div class="stock-header-icon mr-3" style="background: rgba(99, 102, 241, 0.12); color: #4f46e5;">
                                        <i class="icon-plus"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 font-weight-bold" style="color: #0f172a; font-size: 1.05rem;">Add Inward Stock</h5>
                                        <p class="text-muted small mb-0">Record incoming stock batches</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <form method="post" id="addStockForm">
                                    <div class="form-group mb-3">
                                        <label class="form-label font-weight-600 text-dark small mb-1">Item Category <span class="text-danger">*</span></label>
                                        <select name="item" id="itemAdd" class="form-control" onchange="showOptions('Add')" required style="height: 44px; border-radius: 8px;">
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

                                    <div class="form-group mb-3" id="sizeDivAdd" style="display:none;">
                                        <label class="form-label font-weight-600 text-dark small mb-1">Size Option <span class="text-danger">*</span></label>
                                        <select name="size" id="sizeAdd" class="form-control" style="height: 44px; border-radius: 8px;"></select>
                                    </div>

                                    <div class="form-group mb-3" id="remarkDivAdd" style="display:none;">
                                        <label class="form-label font-weight-600 text-dark small mb-1">Title / Description <span class="text-danger">*</span></label>
                                        <input type="text" name="remark" id="remarkAdd" class="form-control" placeholder="e.g. Science Reader, Blue Bag" style="height: 44px; border-radius: 8px;">
                                    </div>

                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label font-weight-600 text-dark small mb-1">Quantity <span class="text-danger">*</span></label>
                                                <input type="number" id="qtyInput" name="quantity" class="form-control" min="1" placeholder="Qty" required oninput="calcTotal()" style="height: 44px; border-radius: 8px;">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label font-weight-600 text-dark small mb-1">Unit Price (₹) <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" id="priceInput" name="price" class="form-control" min="0" placeholder="Price" required oninput="calcTotal()" style="height: 44px; border-radius: 8px;">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Live Estimated Value Preview -->
                                    <div class="p-3 mb-4 rounded" style="background: #f8fafc; border: 1px dashed #cbd5e1; display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <span class="d-block small text-muted font-weight-500">Estimated Total Cost</span>
                                            <span class="small text-secondary">Units × Unit Price</span>
                                        </div>
                                        <span id="estTotalDisplay" class="font-weight-700" style="color: #4f46e5; font-size: 1.15rem;">₹0.00</span>
                                    </div>

                                    <button type="submit" name="add_stock" class="btn btn-primary btn-block py-2 font-weight-bold shadow-sm" style="border-radius: 8px; font-size: 0.95rem;">
                                        <i class="icon-plus mr-1"></i> Add Stock Entry
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Current Stock Inventory List -->
                    <div class="col-lg-7 col-xl-8 mb-4">
                        <div class="card stock-card h-100">
                            <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between" style="border-radius: 12px 12px 0 0; gap: 10px;">
                                <div class="d-flex align-items-center">
                                    <div class="stock-header-icon mr-3" style="background: rgba(16, 185, 129, 0.12); color: #059669;">
                                        <i class="icon-layers"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 font-weight-bold" style="color: #0f172a; font-size: 1.05rem;">Current Stock Inventory</h5>
                                        <p class="text-muted small mb-0">Live stock levels & variant availability</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center" style="gap: 8px;">
                                    <input type="text" id="inventorySearch" class="form-control form-control-sm" placeholder="Quick filter..." style="width: 170px; border-radius: 6px; height: 34px;">
                                    <a href="stock_report.php" class="btn btn-sm btn-outline-primary" style="border-radius: 6px; white-space: nowrap; height: 34px; display: inline-flex; align-items: center;">
                                        <i class="icon-chart mr-1"></i> Full Report
                                    </a>
                                </div>
                            </div>

                            <!-- Summary Metrics Bar -->
                            <div class="px-3 py-2 bg-light border-bottom d-flex flex-wrap align-items-center justify-content-between" style="font-size: 0.85rem; gap: 10px;">
                                <div class="d-flex align-items-center" style="gap: 12px;">
                                    <span><i class="icon-tag mr-1 text-primary"></i> SKUs: <strong class="text-dark"><?= $total_items ?></strong></span>
                                    <span><i class="icon-calculator mr-1 text-success"></i> Total Units: <strong class="text-dark"><?= number_format($total_qty) ?></strong></span>
                                    <?php if ($low_stock_count > 0): ?>
                                        <span class="badge" style="background: #fef3c7; color: #b45309; font-weight: 600;"><i class="icon-bell mr-1"></i> <?= $low_stock_count ?> Low Stock</span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-muted small d-none d-sm-inline">Real-time status</span>
                            </div>

                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                                    <table class="table table-hover align-middle mb-0" id="inventoryTable">
                                        <thead style="background: #f8fafc; position: sticky; top: 0; z-index: 5;">
                                            <tr style="border-bottom: 2px solid #e2e8f0; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">
                                                <th class="py-3 px-3">Item Name</th>
                                                <th class="py-3 px-3">Specification / Size</th>
                                                <th class="py-3 px-3 text-center">Status</th>
                                                <th class="py-3 px-3 text-right">In Stock</th>
                                                <th class="py-3 px-3 text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($items_list)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-5">
                                                        <div style="color: #94a3b8;">
                                                            <i class="icon-drawer" style="font-size: 38px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                                                            <h6 class="font-weight-600 text-secondary mb-1">No Inventory Items Recorded</h6>
                                                            <p class="small text-muted mb-0">Use the form on the left to add your first stock inward shipment.</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($items_list as $it): 
                                                    $qty = (int)$it['current_stock'];
                                                    $status_badge = '';
                                                    if ($qty <= 0) {
                                                        $status_badge = '<span class="badge" style="background: #fee2e2; color: #b91c1c; font-weight: 600; padding: 4px 8px; border-radius: 6px;">Out of Stock</span>';
                                                    } elseif ($qty < 10) {
                                                        $status_badge = '<span class="badge" style="background: #fef3c7; color: #b45309; font-weight: 600; padding: 4px 8px; border-radius: 6px;">Low Stock</span>';
                                                    } else {
                                                        $status_badge = '<span class="badge" style="background: #dcfce7; color: #15803d; font-weight: 600; padding: 4px 8px; border-radius: 6px;">In Stock</span>';
                                                    }

                                                    $icon = 'icon-bag';
                                                    $nm = strtolower($it['name']);
                                                    if (strpos($nm, 'book') !== false) $icon = 'icon-notebook';
                                                    elseif (strpos($nm, 'shirt') !== false || strpos($nm, 'pant') !== false) $icon = 'icon-layers';
                                                    elseif (strpos($nm, 'shoe') !== false) $icon = 'icon-compass';
                                                    elseif (strpos($nm, 'id') !== false) $icon = 'icon-badge';
                                                    elseif (strpos($nm, 'belt') !== false) $icon = 'icon-link';
                                                ?>
                                                    <tr class="stock-item-row" data-name="<?= strtolower(htmlspecialchars($it['name'] . ' ' . $it['size'] . ' ' . $it['remark'])) ?>">
                                                        <td class="py-3 px-3">
                                                            <div class="d-flex align-items-center">
                                                                <div class="mr-2" style="width: 32px; height: 32px; border-radius: 8px; background: #eef2ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0;">
                                                                    <i class="<?= $icon ?>"></i>
                                                                </div>
                                                                <div>
                                                                    <strong style="color: #0f172a; font-size: 0.92rem;"><?= htmlspecialchars($it['name']) ?></strong>
                                                                    <?php if (!empty($it['last_update'])): ?>
                                                                        <div class="text-muted" style="font-size: 0.72rem;">Last Inflow: <?= date('d M, h:i A', strtotime($it['last_update'])) ?></div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="py-3 px-3">
                                                            <?php if (!empty($it['size'])): ?>
                                                                <span class="badge badge-light border font-weight-600 text-dark">Size <?= htmlspecialchars($it['size']) ?></span>
                                                            <?php endif; ?>
                                                            <?php if (!empty($it['remark'])): ?>
                                                                <div class="text-muted small mt-1 font-italic"><?= htmlspecialchars($it['remark']) ?></div>
                                                            <?php endif; ?>
                                                            <?php if (empty($it['size']) && empty($it['remark'])): ?>
                                                                <span class="text-muted small">—</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="py-3 px-3 text-center">
                                                            <?= $status_badge ?>
                                                        </td>
                                                        <td class="py-3 px-3 text-right">
                                                            <span class="font-weight-700 <?= $qty <= 0 ? 'text-danger' : ($qty < 10 ? 'text-warning font-weight-bold' : 'text-dark') ?>" style="font-size: 1.05rem;">
                                                                <?= number_format($qty) ?>
                                                            </span>
                                                            <span class="text-muted small">pcs</span>
                                                        </td>
                                                        <td class="py-3 px-3 text-center">
                                                            <a href="issue_stock.php" class="btn btn-sm btn-light border py-1 px-2" title="Issue Stock" style="border-radius: 6px; font-size: 0.78rem;">
                                                                <i class="icon-arrow-up-circle text-primary"></i> Issue
                                                            </a>
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
    let remarkInput = document.getElementById("remark" + type);

    sizeDiv.style.display = "none";
    remarkDiv.style.display = "none";
    sizeSelect.innerHTML = "";
    if (remarkInput) remarkInput.value = "";

    if (item === "Shirt") {
        sizeDiv.style.display = "block";
        sizeSelect.innerHTML = "<option value=''>-- Select Size --</option><option value='S'>S (Small)</option><option value='M'>M (Medium)</option><option value='L'>L (Large)</option><option value='XL'>XL (Extra Large)</option>";
    } else if (item === "Pant") {
        sizeDiv.style.display = "block";
        sizeSelect.innerHTML = "<option value=''>-- Select Waist --</option><option value='28'>28</option><option value='30'>30</option><option value='32'>32</option><option value='34'>34</option><option value='36'>36</option>";
    } else if (item === "Shoes") {
        sizeDiv.style.display = "block";
        sizeSelect.innerHTML = "<option value=''>-- Select Size --</option><option value='3'>Size 3</option><option value='4'>Size 4</option><option value='5'>Size 5</option><option value='6'>Size 6</option><option value='7'>Size 7</option><option value='8'>Size 8</option><option value='9'>Size 9</option>";
    } else if (item === "Book" || item === "Others") {
        remarkDiv.style.display = "block";
    }
}

function calcTotal() {
    let q = parseFloat(document.getElementById('qtyInput')?.value) || 0;
    let p = parseFloat(document.getElementById('priceInput')?.value) || 0;
    let tot = (q * p).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    let el = document.getElementById('estTotalDisplay');
    if (el) el.textContent = '₹' + tot;
}

// Client-side quick search filter
document.getElementById('inventorySearch')?.addEventListener('keyup', function() {
    let q = this.value.toLowerCase().trim();
    let rows = document.querySelectorAll('#inventoryTable tbody tr.stock-item-row');
    rows.forEach(r => {
        let hay = r.getAttribute('data-name') || '';
        r.style.display = hay.includes(q) ? '' : 'none';
    });
});

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