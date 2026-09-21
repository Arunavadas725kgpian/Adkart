<?php
session_start();
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'seller' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

include 'db.php';

// Handle Order Status Update
if (isset($_POST['update_status'])) {
    $ord_id = mysqli_real_escape_string($conn, trim($_POST['order_id']));
    $new_status = mysqli_real_escape_string($conn, trim($_POST['status']));

    mysqli_query($conn, "UPDATE orders SET status = '$new_status' WHERE order_id = '$ord_id' OR id = '$ord_id'");
    $_SESSION['msg'] = "Order #" . $ord_id . " status changed to '" . $new_status . "' successfully!";
    $_SESSION['msg_type'] = "success";
    header("Location: seller_orders.php");
    exit();
}

include 'header.php';

// Filter Parameter
$filter = isset($_GET['filter']) ? trim($_GET['filter']) : 'all';
$sql_filter = "";
if ($filter !== 'all') {
    $safe_filter = mysqli_real_escape_string($conn, $filter);
    $sql_filter = "WHERE status = '$safe_filter'";
}

// Fetch Orders
$all_orders = mysqli_query($conn, "SELECT * FROM orders $sql_filter ORDER BY 1 DESC");
$total_orders_count = ($all_orders) ? mysqli_num_rows($all_orders) : 0;

// Metric Calculations
$rev_q = mysqli_query($conn, "SELECT SUM(total_price) as total_rev FROM orders WHERE status != 'Cancelled'");
$rev_data = mysqli_fetch_assoc($rev_q);
$total_revenue = $rev_data['total_rev'] ?? 0;

$pending_q = mysqli_query($conn, "SELECT COUNT(*) as pending_count FROM orders WHERE status IN ('Order Placed', 'Pending', 'Shipped', 'Out for Delivery')");
$pending_data = mysqli_fetch_assoc($pending_q);
$active_orders = $pending_data['pending_count'] ?? 0;
?>

<div class="container py-3 mb-5">
    
    <!-- Top Metrics Overview Bar -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 bg-white p-3 d-flex flex-row align-items-center gap-3">
                <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 55px; height: 55px; font-size: 1.5rem;">
                    <i class="bi bi-currency-exchange"></i>
                </div>
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Total Revenue</span>
                    <h4 class="fw-bold text-dark mb-0">৳ <?php echo number_format($total_revenue, 2); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 bg-white p-3 d-flex flex-row align-items-center gap-3">
                <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 55px; height: 55px; font-size: 1.5rem;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Active Orders</span>
                    <h4 class="fw-bold text-dark mb-0"><?php echo $active_orders; ?> Orders</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 bg-white p-3 d-flex flex-row align-items-center gap-3">
                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 55px; height: 55px; font-size: 1.5rem;">
                    <i class="bi bi-box-seam-fill"></i>
                </div>
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Total Recorded</span>
                    <h4 class="fw-bold text-dark mb-0"><?php echo $total_orders_count; ?> Orders</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Header & Filter Tabs -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-card-checklist text-warning me-2"></i>Customer Orders Management
            </h3>
            <p class="text-muted small mb-0">Track order billing, item breakdown, customer shipping details, and status updates</p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <a href="seller_dashboard.php" class="btn btn-outline-dark rounded-pill px-4 shadow-sm fw-semibold btn-sm">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Quick Status Filter Bar -->
    <div class="card shadow-sm border-0 rounded-pill bg-white p-2 mb-4 overflow-auto">
        <ul class="nav nav-pills gap-1 flex-nowrap">
            <li class="nav-item">
                <a class="nav-link rounded-pill py-1 px-3 small fw-semibold <?php echo ($filter === 'all') ? 'active bg-dark text-white' : 'text-secondary'; ?>" href="seller_orders.php?filter=all">All Orders</a>
            </li>
            <li class="nav-item">
                <a class="nav-link rounded-pill py-1 px-3 small fw-semibold <?php echo ($filter === 'Order Placed') ? 'active bg-warning text-dark' : 'text-secondary'; ?>" href="seller_orders.php?filter=Order Placed">New Orders</a>
            </li>
            <li class="nav-item">
                <a class="nav-link rounded-pill py-1 px-3 small fw-semibold <?php echo ($filter === 'Shipped') ? 'active bg-primary text-white' : 'text-secondary'; ?>" href="seller_orders.php?filter=Shipped">Packed/Shipped</a>
            </li>
            <li class="nav-item">
                <a class="nav-link rounded-pill py-1 px-3 small fw-semibold <?php echo ($filter === 'Out for Delivery') ? 'active bg-info text-white' : 'text-secondary'; ?>" href="seller_orders.php?filter=Out for Delivery">In Transit</a>
            </li>
            <li class="nav-item">
                <a class="nav-link rounded-pill py-1 px-3 small fw-semibold <?php echo ($filter === 'Delivered') ? 'active bg-success text-white' : 'text-secondary'; ?>" href="seller_orders.php?filter=Delivered">Delivered</a>
            </li>
            <li class="nav-item">
                <a class="nav-link rounded-pill py-1 px-3 small fw-semibold <?php echo ($filter === 'Cancelled') ? 'active bg-danger text-white' : 'text-secondary'; ?>" href="seller_orders.php?filter=Cancelled">Cancelled</a>
            </li>
        </ul>
    </div>

    <!-- Notification Alert -->
    <?php if (isset($_SESSION['msg'])) { ?>
        <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i>
            <?php echo $_SESSION['msg']; unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <!-- Orders Feed -->
    <?php if ($total_orders_count > 0) { ?>
        <div class="row g-4">
            <?php while ($ord = mysqli_fetch_assoc($all_orders)) { 
                $raw_id     = $ord['id'] ?? ($ord['ID'] ?? 0);
                $order_id   = !empty($ord['order_id']) ? $ord['order_id'] : 'ADK-' . str_pad($raw_id, 6, '0', STR_PAD_LEFT);
                $order_date = !empty($ord['order_date']) ? date('d M Y, h:i A', strtotime($ord['order_date'])) : date('d M Y');
                $status     = !empty($ord['status']) ? trim($ord['status']) : 'Order Placed';
                $c_name     = $ord['customer_name'] ?? ($ord['user_name'] ?? 'Guest Customer');
                $phone      = $ord['phone'] ?? 'N/A';
                $address    = $ord['address'] ?? 'Delivery address not provided';
                $city       = $ord['city'] ?? 'Kolkata';
                $slot_txt   = $ord['delivery_slot'] ?? ($ord['delivery_date'] ?? 'Standard Delivery');
                $pay_method = $ord['payment_method'] ?? 'Cash on Delivery';
                $discount   = isset($ord['discount_amount']) ? intval($ord['discount_amount']) : 0;

                // Fetch Items for this specific order
                $items_q = mysqli_query($conn, "SELECT oi.*, p.Name, p.name as p_name, p.Price, p.price as p_price 
                                                 FROM order_items oi 
                                                 LEFT JOIN products p ON oi.product_id = p.ID OR oi.product_id = p.id 
                                                 WHERE oi.order_id = '$order_id' OR oi.order_id = '$raw_id'");
                
                // Calculate Subtotal and Grand Total reliably
                $items_total = 0;
                $items_list = [];
                if ($items_q && mysqli_num_rows($items_q) > 0) {
                    while ($itm = mysqli_fetch_assoc($items_q)) {
                        $p_name = !empty($itm['Name']) ? $itm['Name'] : (!empty($itm['p_name']) ? $itm['p_name'] : 'Ordered Item');
                        $qty = intval($itm['quantity']);
                        $price = ($itm['price'] > 0) ? intval($itm['price']) : (intval($itm['Price'] ?? ($itm['p_price'] ?? 0)));
                        $line_total = $qty * $price;
                        $items_total += $line_total;

                        $items_list[] = [
                            'name' => $p_name,
                            'qty' => $qty,
                            'unit_price' => $price,
                            'total' => $line_total
                        ];
                    }
                }

                // If total_price in table is > 0 use it, else calculate from items + delivery - discount
                $db_total = isset($ord['total_price']) ? intval($ord['total_price']) : 0;
                $delivery_fee = ($items_total >= 499 || $items_total == 0) ? 0 : 30;
                $grand_total = ($db_total > 0) ? $db_total : max(0, $items_total + $delivery_fee - $discount);
            ?>
                <div class="col-12" id="order-card-<?php echo htmlspecialchars($order_id); ?>">
                    <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
                        
                        <!-- Header Top Bar -->
                        <div class="card-header bg-dark text-white p-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div class="d-flex flex-wrap gap-4 small align-items-center">
                                <div>
                                    <span class="text-white-50 d-block" style="font-size: 11px;">ORDER ID</span>
                                    <b class="text-warning fs-6">#<?php echo htmlspecialchars($order_id); ?></b>
                                </div>
                                <div>
                                    <span class="text-white-50 d-block" style="font-size: 11px;">PLACED ON</span>
                                    <span><?php echo $order_date; ?></span>
                                </div>
                                <div>
                                    <span class="text-white-50 d-block" style="font-size: 11px;">PAYMENT METHOD</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><?php echo htmlspecialchars($pay_method); ?></span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-<?php 
                                    echo ($status === 'Delivered') ? 'success' : (($status === 'Cancelled') ? 'danger' : 'warning text-dark'); 
                                ?> px-3 py-2 rounded-pill fw-bold">
                                    Status: <?php echo htmlspecialchars($status); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Card Content -->
                        <div class="card-body p-4">
                            <div class="row g-4">
                                
                                <!-- Customer & Dispatch Address -->
                                <div class="col-lg-4 border-end-lg">
                                    <h6 class="fw-bold text-primary mb-3"><i class="bi bi-person-badge-fill me-1"></i> Shipping & Delivery Details</h6>
                                    <div class="bg-light p-3 rounded-3 mb-2 small">
                                        <div class="mb-2">
                                            <span class="text-muted d-block">Recipient Name:</span>
                                            <b class="text-dark fs-6"><?php echo htmlspecialchars($c_name); ?></b>
                                        </div>
                                        <div class="mb-2">
                                            <span class="text-muted d-block">Contact Phone:</span>
                                            <a href="tel:<?php echo $phone; ?>" class="fw-bold text-success text-decoration-none">
                                                <i class="bi bi-telephone-fill me-1"></i><?php echo htmlspecialchars($phone); ?>
                                            </a>
                                        </div>
                                        <div class="mb-2">
                                            <span class="text-muted d-block">Delivery Address:</span>
                                            <span class="text-dark"><?php echo htmlspecialchars($address); ?>, <?php echo htmlspecialchars($city); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-muted d-block">Scheduled Slot:</span>
                                            <b class="text-primary"><i class="bi bi-clock-history me-1"></i><?php echo htmlspecialchars($slot_txt); ?></b>
                                        </div>
                                    </div>
                                </div>

                                <!-- Itemized Billing Breakdown -->
                                <div class="col-lg-5">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-receipt me-1"></i> Order Items & Billing</h6>
                                        <span class="badge bg-light text-secondary border"><?php echo count($items_list); ?> item(s)</span>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered align-middle mb-2">
                                            <thead class="table-light small">
                                                <tr>
                                                    <th>Product</th>
                                                    <th class="text-center">Qty</th>
                                                    <th class="text-end">Unit Price</th>
                                                    <th class="text-end">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody class="small">
                                                <?php if (!empty($items_list)) { 
                                                    foreach ($items_list as $item) { ?>
                                                    <tr>
                                                        <td><b class="text-dark"><?php echo htmlspecialchars($item['name']); ?></b></td>
                                                        <td class="text-center"><?php echo $item['qty']; ?> pcs</td>
                                                        <td class="text-end">৳ <?php echo $item['unit_price']; ?></td>
                                                        <td class="text-end fw-semibold">৳ <?php echo $item['total']; ?></td>
                                                    </tr>
                                                <?php } } else { ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">Items archived</td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                            <tfoot class="table-light small">
                                                <tr>
                                                    <td colspan="3" class="text-end text-muted">Items Subtotal:</td>
                                                    <td class="text-end fw-bold">৳ <?php echo $items_total; ?></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="3" class="text-end text-muted">Delivery Fee:</td>
                                                    <td class="text-end"><?php echo ($delivery_fee === 0) ? '<span class="text-success fw-bold">Free</span>' : '৳ ' . $delivery_fee; ?></td>
                                                </tr>
                                                <?php if ($discount > 0) { ?>
                                                <tr>
                                                    <td colspan="3" class="text-end text-success">Coupon Discount:</td>
                                                    <td class="text-end text-success fw-bold">- ৳ <?php echo $discount; ?></td>
                                                </tr>
                                                <?php } ?>
                                                <tr>
                                                    <th colspan="3" class="text-end fs-6 text-dark">Grand Total:</th>
                                                    <th class="text-end fs-6 text-success fw-bold">৳ <?php echo $grand_total; ?></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>

                                <!-- Actions & Status Controller -->
                                <div class="col-lg-3 text-lg-end d-flex flex-column justify-content-between">
                                    <div>
                                        <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-sliders me-1"></i> Order Workflow</h6>
                                        <form action="seller_orders.php" method="POST" class="d-flex flex-column gap-2">
                                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
                                            
                                            <label class="small text-muted text-start">Update Fulfillment Status:</label>
                                            <select name="status" class="form-select form-select-sm rounded-3">
                                                <option value="Order Placed" <?php if($status == 'Order Placed' || $status == 'Pending') echo 'selected'; ?>>1. Order Placed</option>
                                                <option value="Shipped" <?php if($status == 'Shipped') echo 'selected'; ?>>2. Packed / Shipped</option>
                                                <option value="Out for Delivery" <?php if($status == 'Out for Delivery') echo 'selected'; ?>>3. Out for Delivery</option>
                                                <option value="Delivered" <?php if($status == 'Delivered') echo 'selected'; ?>>4. Delivered</option>
                                                <option value="Cancelled" <?php if($status == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                                            </select>

                                            <button type="submit" name="update_status" class="btn btn-warning btn-sm rounded-pill fw-bold text-dark mt-1 shadow-sm">
                                                <i class="bi bi-check-circle-fill me-1"></i> Save Status
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Print Invoice Button -->
                                    <div class="mt-3">
                                        <button type="button" onclick="printReceipt('order-card-<?php echo htmlspecialchars($order_id); ?>')" class="btn btn-outline-secondary btn-sm rounded-pill w-100">
                                            <i class="bi bi-printer me-1"></i> Print Invoice
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div class="card shadow-sm border-0 rounded-4 p-5 text-center bg-white my-4">
            <div class="mb-3"><i class="bi bi-inbox display-1 text-muted"></i></div>
            <h4 class="fw-bold text-dark">No Orders Found!</h4>
            <p class="text-muted">There are no customer orders matching the selected filter criteria.</p>
            <div>
                <a href="seller_orders.php?filter=all" class="btn btn-outline-primary rounded-pill px-4 btn-sm">Clear Filter</a>
            </div>
        </div>
    <?php } ?>

</div>

<!-- Printable Script -->
<script>
function printReceipt(cardId) {
    var printContents = document.getElementById(cardId).innerHTML;
    var originalContents = document.body.innerHTML;

    document.body.innerHTML = "<div class='container p-4'>" + printContents + "</div>";
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}
</script>

<?php include 'footer.php'; ?>