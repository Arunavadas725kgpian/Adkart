<?php
session_start();
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'seller' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

include 'db.php';

$curr_user = $_SESSION['user_name'];
$active_tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'inventory';

// ১. কুপন তৈরি হ্যান্ডলার
if (isset($_POST['add_coupon'])) {
    $code      = strtoupper(mysqli_real_escape_string($conn, trim($_POST['code'])));
    $discount  = intval($_POST['discount']);
    $min_order = intval($_POST['min_order']);

    $check = mysqli_query($conn, "SELECT * FROM coupons WHERE code = '$code'");
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['msg'] = "Coupon code already exists!";
        $_SESSION['msg_type'] = "danger";
    } else {
        mysqli_query($conn, "INSERT INTO coupons (code, discount, min_order, status) VALUES ('$code', '$discount', '$min_order', 'Active')");
        $_SESSION['msg'] = "Coupon code '$code' created successfully!";
        $_SESSION['msg_type'] = "success";
    }
    header("Location: seller_dashboard.php?tab=coupons");
    exit();
}

// ২. কুপন ডিলিট
if (isset($_GET['delete_coupon'])) {
    $c_id = intval($_GET['delete_coupon']);
    mysqli_query($conn, "DELETE FROM coupons WHERE id = $c_id");
    $_SESSION['msg'] = "Coupon deleted successfully!";
    $_SESSION['msg_type'] = "info";
    header("Location: seller_dashboard.php?tab=coupons");
    exit();
}

// ৩. স্টোর সেটিংস আপডেট
if (isset($_POST['update_store_settings'])) {
    $store_name = mysqli_real_escape_string($conn, trim($_POST['store_name']));
    $phone      = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $showroom   = mysqli_real_escape_string($conn, trim($_POST['showroom_address']));
    $license    = mysqli_real_escape_string($conn, trim($_POST['trade_license']));

    $up_sql = "UPDATE users SET store_name='$store_name', phone='$phone', showroom_address='$showroom', trade_license='$license' WHERE username='$curr_user'";
    if (mysqli_query($conn, $up_sql)) {
        $_SESSION['msg'] = "Store & Showroom settings updated successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Failed to update settings!";
        $_SESSION['msg_type'] = "danger";
    }
    header("Location: seller_dashboard.php?tab=settings");
    exit();
}

// ৪. অর্ডার স্ট্যাটাস পরিবর্তন
if (isset($_POST['update_order_status'])) {
    $target_ord_id = mysqli_real_escape_string($conn, trim($_POST['order_id']));
    $new_status    = mysqli_real_escape_string($conn, trim($_POST['status']));

    mysqli_query($conn, "UPDATE orders SET status = '$new_status' WHERE order_id = '$target_ord_id' OR id = '$target_ord_id'");
    $_SESSION['msg'] = "Order #" . $target_ord_id . " updated to '" . $new_status . "'!";
    $_SESSION['msg_type'] = "success";
    header("Location: seller_dashboard.php?tab=orders");
    exit();
}

// ৫. রাইট ক্লিক / অ্যাকশন দিয়ে সরাসরি অর্ডার ডিলিট হ্যান্ডলার
if (isset($_GET['delete_order'])) {
    $del_ord_id = mysqli_real_escape_string($conn, trim($_GET['delete_order']));
    
    // ডাটাবেস থেকে সংশ্লিষ্ট অর্ডার ও আইটেম ডিলিট করা
    mysqli_query($conn, "DELETE FROM order_items WHERE order_id = '$del_ord_id'");
    mysqli_query($conn, "DELETE FROM orders WHERE order_id = '$del_ord_id' OR id = '$del_ord_id'");

    $_SESSION['msg'] = "Order #" . $del_ord_id . " permanently deleted from dashboard!";
    $_SESSION['msg_type'] = "success";
    header("Location: seller_dashboard.php?tab=orders");
    exit();
}

// ডাটা ফেচিং
$products_res = mysqli_query($conn, "SELECT * FROM products ORDER BY ID DESC");
$total_prods  = ($products_res) ? mysqli_num_rows($products_res) : 0;

$orders_res   = mysqli_query($conn, "SELECT * FROM orders ORDER BY 1 DESC");
$total_orders = ($orders_res) ? mysqli_num_rows($orders_res) : 0;

$coupons_res  = mysqli_query($conn, "SELECT * FROM coupons ORDER BY id DESC");

$user_res     = mysqli_query($conn, "SELECT * FROM users WHERE username = '$curr_user' LIMIT 1");
$seller_info  = mysqli_fetch_assoc($user_res);

include 'header.php';
?>

<!-- রাইট ক্লিক কাস্টম কনটেক্সট মেন্যু স্টাইলিং -->
<style>
.custom-context-menu {
    display: none;
    position: absolute;
    z-index: 10000;
    min-width: 200px;
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
    border: 1px solid #e2e8f0;
    padding: 6px 0;
}
.custom-context-menu a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    text-decoration: none;
    transition: background 0.15s ease;
    cursor: pointer;
}
.custom-context-menu a:hover {
    background-color: #f1f5f9;
    color: #0f172a;
}
.custom-context-menu a.text-danger:hover {
    background-color: #fee2e2;
    color: #dc2626 !important;
}
.custom-context-menu .divider {
    height: 1px;
    background-color: #e2e8f0;
    margin: 4px 0;
}
.order-interactive-card {
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    user-select: none;
}
.order-interactive-card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08) !important;
}
</style>

<div class="container py-3 mb-5">

    <!-- ড্যাশবোর্ড হেডার -->
    <div class="card bg-dark text-white rounded-4 border-0 shadow-sm p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-2">Seller Control Center</span>
                <h3 class="fw-bold mb-0 text-white"><?php echo htmlspecialchars($seller_info['store_name'] ?? 'My Store'); ?></h3>
                <small class="text-white-50">Manage inventory, monitor customer orders, configure coupons & private showroom</small>
            </div>
            <a href="add_product.php" class="btn btn-warning rounded-pill px-4 fw-bold text-dark shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Add New Product
            </a>
        </div>
    </div>

    <!-- নোটিফিকেশন অ্যালার্ট -->
    <?php if (isset($_SESSION['msg'])) { ?>
        <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i>
            <?php echo $_SESSION['msg']; unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <!-- ড্যাশবোর্ড ট্যাব বার -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white mb-4">
        <div class="card-header bg-white p-2 border-0">
            <ul class="nav nav-pills nav-fill gap-2 p-1 bg-light rounded-pill">
                <li class="nav-item">
                    <a class="nav-link rounded-pill fw-semibold <?php echo ($active_tab === 'inventory') ? 'active bg-warning text-dark' : 'text-secondary'; ?>" href="seller_dashboard.php?tab=inventory">
                        <i class="bi bi-boxes me-1"></i> Inventory (<?php echo $total_prods; ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill fw-semibold <?php echo ($active_tab === 'orders') ? 'active bg-warning text-dark' : 'text-secondary'; ?>" href="seller_dashboard.php?tab=orders">
                        <i class="bi bi-card-checklist me-1"></i> Customer Orders (<?php echo $total_orders; ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill fw-semibold <?php echo ($active_tab === 'coupons') ? 'active bg-warning text-dark' : 'text-secondary'; ?>" href="seller_dashboard.php?tab=coupons">
                        <i class="bi bi-ticket-perforated-fill me-1"></i> Coupons & Offers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill fw-semibold <?php echo ($active_tab === 'settings') ? 'active bg-warning text-dark' : 'text-secondary'; ?>" href="seller_dashboard.php?tab=settings">
                        <i class="bi bi-shop-window me-1"></i> Store & Showroom Settings
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- ট্যাব ১: ইনভেন্টরি ক্যাটালগ -->
    <?php if ($active_tab === 'inventory') { ?>
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-box-seam text-primary me-2"></i>Product Catalog & Live Stock</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-4">Image</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_prods > 0) { 
                                while ($row = mysqli_fetch_assoc($products_res)) {
                                    $p_id = isset($row['ID']) ? $row['ID'] : ($row['id'] ?? 0);
                                    $p_name = $row['Name'] ?? ($row['name'] ?? 'Product');
                                    $p_price = $row['Price'] ?? ($row['price'] ?? 0);
                                    $p_qty = $row['Quantity'] ?? ($row['quantity'] ?? 0);
                                    $p_cat = !empty($row['category']) ? $row['category'] : 'General';
                                    $img_name = isset($row['image']) ? trim($row['image']) : '';
                                    $img_src = (!empty($img_name)) ? "image/" . $img_name : "https://dummyimage.com/100x100/e2e8f0/475569&text=No+Img";
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <img src="<?php echo $img_src; ?>" style="width: 50px; height: 50px; object-fit: contain; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff;" onerror="this.onerror=null; this.src='https://dummyimage.com/100x100/e2e8f0/475569&text=No+Img';">
                                </td>
                                <td><b class="text-dark"><?php echo htmlspecialchars($p_name); ?></b></td>
                                <td><span class="badge bg-light text-secondary border"><?php echo htmlspecialchars($p_cat); ?></span></td>
                                <td><span class="text-success fw-bold">৳ <?php echo $p_price; ?></span></td>
                                <td>
                                    <?php if ($p_qty > 0) { ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><?php echo $p_qty; ?> in stock</span>
                                    <?php } else { ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Out of Stock</span>
                                    <?php } ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="edit_product.php?id=<?php echo $p_id; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 me-1">Edit</a>
                                    <a href="delete.php?id=<?php echo $p_id; ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                                </td>
                            </tr>
                            <?php } } else { ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No products available in your inventory.</td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php } ?>

    <!-- ট্যাব ২: কাস্টমার অর্ডার তালিকা (রাইট ক্লিক সাপোর্টসহ) -->
    <?php if ($active_tab === 'orders') { ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <small class="text-muted"><i class="bi bi-mouse me-1"></i> Tip: <b>Right-click</b> on any order card for quick actions (Delete, View, Print).</small>
        </div>
        <div class="row g-4">
            <?php if ($total_orders > 0) {
                $m_index = 0;
                while ($ord = mysqli_fetch_assoc($orders_res)) {
                    $m_index++;
                    $raw_id     = $ord['id'] ?? ($ord['ID'] ?? 0);
                    $order_id   = !empty($ord['order_id']) ? $ord['order_id'] : 'ADK-' . str_pad($raw_id, 6, '0', STR_PAD_LEFT);
                    $order_date = !empty($ord['order_date']) ? date('d M Y, h:i A', strtotime($ord['order_date'])) : date('d M Y');
                    $status     = !empty($ord['status']) ? trim($ord['status']) : 'Order Placed';
                    $c_name     = $ord['customer_name'] ?? ($ord['user_name'] ?? 'Guest Customer');
                    $phone      = $ord['phone'] ?? 'N/A';
                    $address    = $ord['address'] ?? 'Address not specified';
                    $city       = $ord['city'] ?? 'Kolkata';
                    $slot_txt   = $ord['delivery_slot'] ?? ($ord['delivery_date'] ?? 'Standard Delivery');
                    $pay_method = $ord['payment_method'] ?? 'Cash on Delivery';
                    $discount   = isset($ord['discount_amount']) ? intval($ord['discount_amount']) : 0;

                    // আইটেম ফেচ
                    $items_q = mysqli_query($conn, "SELECT oi.*, p.Name, p.name as p_name, p.Price, p.price as p_price 
                                                     FROM order_items oi 
                                                     LEFT JOIN products p ON oi.product_id = p.ID OR oi.product_id = p.id 
                                                     WHERE oi.order_id = '$order_id' OR oi.order_id = '$raw_id'");
                    
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

                    $db_total = isset($ord['total_price']) ? intval($ord['total_price']) : 0;
                    $delivery_fee = ($items_total >= 499 || $items_total == 0) ? 0 : 30;
                    $grand_total = ($db_total > 0) ? $db_total : max(0, $items_total + $delivery_fee - $discount);
                    $modal_id = "orderModal_" . $m_index;
            ?>
            <div class="col-12">
                <!-- অর্ডারের কার্ডটিতে রাইট ক্লিক ডেটা বাইন্ড করা হলো -->
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white order-interactive-card" 
                     data-order-id="<?php echo htmlspecialchars($order_id); ?>" 
                     data-modal-id="<?php echo $modal_id; ?>"
                     data-print-id="print-area-<?php echo $modal_id; ?>">
                    
                    <div class="card-header bg-dark text-white p-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <span class="text-white-50 small">ORDER ID:</span>
                            <b class="text-warning fs-6 ms-1">#<?php echo htmlspecialchars($order_id); ?></b>
                            <span class="text-white-50 ms-3 small"><i class="bi bi-calendar3 me-1"></i><?php echo $order_date; ?></span>
                        </div>
                        <span class="badge bg-<?php 
                            echo ($status === 'Delivered') ? 'success' : (($status === 'Cancelled') ? 'danger' : 'warning text-dark'); 
                        ?> px-3 py-2 rounded-pill fw-bold">
                            <?php echo htmlspecialchars($status); ?>
                        </span>
                    </div>

                    <div class="card-body p-4">
                        <div class="row align-items-center g-3">
                            <div class="col-md-5">
                                <h6 class="fw-bold text-dark mb-1">
                                    <i class="bi bi-person-fill text-primary me-1"></i><?php echo htmlspecialchars($c_name); ?> 
                                    (<a href="tel:<?php echo $phone; ?>" class="text-success text-decoration-none fw-semibold"><?php echo htmlspecialchars($phone); ?></a>)
                                </h6>
                                <p class="small text-muted mb-2 text-truncate" style="max-width: 380px;">
                                    <i class="bi bi-geo-alt text-danger me-1"></i><?php echo htmlspecialchars($address); ?>, <?php echo htmlspecialchars($city); ?>
                                </p>
                                <span class="badge bg-light text-primary border"><i class="bi bi-clock-history me-1"></i><?php echo htmlspecialchars($slot_txt); ?></span>
                            </div>

                            <div class="col-md-3">
                                <span class="text-muted small d-block">Bill Amount:</span>
                                <h4 class="fw-bold text-success mb-0">৳ <?php echo $grand_total; ?></h4>
                                <small class="text-muted" style="font-size: 11px;">Payment: <?php echo htmlspecialchars($pay_method); ?></small>
                            </div>

                            <div class="col-md-4 text-md-end">
                                <button type="button" class="btn btn-outline-dark rounded-pill px-4 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#<?php echo $modal_id; ?>">
                                    <i class="bi bi-receipt-cutoff me-1"></i> Manage Order Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- প্রতিটি অর্ডারের ফুলস্ক্রিন ইনভয়েস ও স্ট্যাটাস মডাল -->
            <div class="modal fade" id="<?php echo $modal_id; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header bg-dark text-white p-4 border-0">
                            <div>
                                <h5 class="modal-title fw-bold text-warning mb-0">
                                    <i class="bi bi-bag-check-fill me-2"></i>Order Fulfillment & Invoice
                                </h5>
                                <small class="text-white-50">Invoice #<?php echo htmlspecialchars($order_id); ?> | Placed on <?php echo $order_date; ?></small>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body p-4" id="print-area-<?php echo $modal_id; ?>">
                            <div class="row g-3 p-3 bg-light rounded-4 mb-4">
                                <div class="col-md-6 border-end">
                                    <span class="text-muted small d-block">Customer Shipping Info:</span>
                                    <b class="text-dark fs-6"><?php echo htmlspecialchars($c_name); ?></b><br>
                                    <i class="bi bi-telephone text-success"></i> <?php echo htmlspecialchars($phone); ?><br>
                                    <i class="bi bi-geo-alt text-danger"></i> <?php echo htmlspecialchars($address); ?>, <?php echo htmlspecialchars($city); ?>
                                </div>
                                <div class="col-md-6 ps-md-4">
                                    <span class="text-muted small d-block">Delivery & Payment Mode:</span>
                                    <b>Schedule:</b> <span class="text-primary"><?php echo htmlspecialchars($slot_txt); ?></span><br>
                                    <b>Payment:</b> <span class="badge bg-success-subtle text-success border"><?php echo htmlspecialchars($pay_method); ?></span><br>
                                    <b>Current Status:</b> <span class="badge bg-dark text-warning"><?php echo htmlspecialchars($status); ?></span>
                                </div>
                            </div>

                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-cart3 me-2"></i>Purchased Items</h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light small">
                                        <tr>
                                            <th>Product Description</th>
                                            <th class="text-center">Quantity</th>
                                            <th class="text-end">Unit Price</th>
                                            <th class="text-end">Line Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        <?php if (!empty($items_list)) { 
                                            foreach ($items_list as $itm_row) { ?>
                                            <tr>
                                                <td><b class="text-dark"><?php echo htmlspecialchars($itm_row['name']); ?></b></td>
                                                <td class="text-center"><?php echo $itm_row['qty']; ?> pcs</td>
                                                <td class="text-end">৳ <?php echo $itm_row['unit_price']; ?></td>
                                                <td class="text-end fw-semibold">৳ <?php echo $itm_row['total']; ?></td>
                                            </tr>
                                        <?php } } else { ?>
                                            <tr><td colspan="4" class="text-center text-muted">Item details saved with order</td></tr>
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
                                            <th colspan="3" class="text-end fs-6 text-dark">Grand Payable Total:</th>
                                            <th class="text-end fs-6 text-success fw-bold">৳ <?php echo $grand_total; ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <form action="seller_dashboard.php?tab=orders" method="POST" class="p-3 border rounded-3 bg-light">
                                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
                                <div class="row align-items-center g-2">
                                    <div class="col-md-7">
                                        <label class="small fw-bold text-secondary mb-1">Update Delivery Progress:</label>
                                        <select name="status" class="form-select rounded-3">
                                            <option value="Order Placed" <?php if($status == 'Order Placed' || $status == 'Pending') echo 'selected'; ?>>1. Order Placed</option>
                                            <option value="Shipped" <?php if($status == 'Shipped') echo 'selected'; ?>>2. Packed & Shipped</option>
                                            <option value="Out for Delivery" <?php if($status == 'Out for Delivery') echo 'selected'; ?>>3. Out for Delivery</option>
                                            <option value="Delivered" <?php if($status == 'Delivered') echo 'selected'; ?>>4. Delivered</option>
                                            <option value="Cancelled" <?php if($status == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5 pt-md-4">
                                        <button type="submit" name="update_order_status" class="btn btn-warning w-100 py-2 rounded-pill fw-bold text-dark shadow-sm">
                                            <i class="bi bi-check2-circle me-1"></i> Update Status
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="modal-footer bg-white border-0 p-4 pt-0 d-flex justify-content-between">
                            <button type="button" onclick="printModalInvoice('print-area-<?php echo $modal_id; ?>')" class="btn btn-outline-dark rounded-pill px-4 fw-semibold">
                                <i class="bi bi-printer me-1"></i> Print Packing Slip
                            </button>
                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php } } else { ?>
            <div class="col-12 text-center py-5 bg-white rounded-4 shadow-sm">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h5 class="text-muted mt-3 mb-0">No customer orders received yet.</h5>
            </div>
            <?php } ?>
        </div>
    <?php } ?>

    <!-- ট্যাব ৩: কুপন -->
    <?php if ($active_tab === 'coupons') { ?>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                    <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-plus-circle text-warning me-2"></i>Create New Coupon</h5>
                    <form action="seller_dashboard.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Coupon Code (e.g. SAVE50)</label>
                            <input type="text" name="code" class="form-control text-uppercase rounded-3" placeholder="COUPON50" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Discount Amount (৳)</label>
                            <input type="number" name="discount" class="form-control rounded-3" placeholder="50" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Minimum Order Value (৳)</label>
                            <input type="number" name="min_order" class="form-control rounded-3" placeholder="200" required>
                        </div>
                        <button type="submit" name="add_coupon" class="btn btn-warning w-100 py-2 rounded-pill fw-bold text-dark shadow-sm">Publish Coupon</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                    <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-ticket-perforated text-success me-2"></i>Active Coupons</h5>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Discount</th>
                                    <th>Min Order</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($coupons_res && mysqli_num_rows($coupons_res) > 0) { 
                                    while ($c = mysqli_fetch_assoc($coupons_res)) { ?>
                                    <tr>
                                        <td><b class="text-primary"><?php echo htmlspecialchars($c['code']); ?></b></td>
                                        <td class="text-success fw-bold">৳ <?php echo $c['discount']; ?></td>
                                        <td>৳ <?php echo $c['min_order']; ?></td>
                                        <td><span class="badge bg-success-subtle text-success border px-2 py-1"><?php echo $c['status']; ?></span></td>
                                        <td class="text-end">
                                            <a href="seller_dashboard.php?delete_coupon=<?php echo $c['id']; ?>" class="btn btn-outline-danger btn-sm rounded-circle" onclick="return confirm('Delete this coupon?');"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php } } else { ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No coupons created yet.</td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <!-- ট্যাব ৪: স্টোর সেটিংস -->
    <?php if ($active_tab === 'settings') { ?>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                    <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-shop text-warning me-2"></i>Store & Private Showroom Configuration</h5>
                    <form action="seller_dashboard.php" method="POST">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-eye-fill me-1"></i> Public Store Information (Visible to Buyers)</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Store Display Name <span class="text-danger">*</span></label>
                                <input type="text" name="store_name" class="form-control rounded-3" value="<?php echo htmlspecialchars($seller_info['store_name'] ?? 'Ad-Kart Official Store'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Customer Support Phone <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control rounded-3" value="<?php echo htmlspecialchars($seller_info['phone'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="fw-bold text-danger mb-3"><i class="bi bi-lock-fill me-1"></i> Private Showroom & Logistics Details (Internal Only)</h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold text-secondary">Trade License / Vendor Reg. ID</label>
                                <input type="text" name="trade_license" class="form-control rounded-3" value="<?php echo htmlspecialchars($seller_info['trade_license'] ?? ''); ?>" placeholder="TRD-2026-XXXX">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold text-secondary">Warehouse / Dispatch Showroom Full Address</label>
                                <textarea name="showroom_address" class="form-control rounded-3" rows="3" placeholder="Warehouse Unit, Street, City"><?php echo htmlspecialchars($seller_info['showroom_address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" name="update_store_settings" class="btn btn-warning px-4 py-2 rounded-pill fw-bold text-dark shadow-sm">
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>

</div>

<!-- কাস্টম রাইট ক্লিক কনটেক্সট মেন্যু বক্স -->
<div id="orderContextMenu" class="custom-context-menu">
    <a id="ctxOpenModal"><i class="bi bi-receipt text-primary"></i> View / Manage Order</a>
    <a id="ctxPrintSlip"><i class="bi bi-printer text-dark"></i> Print Packing Slip</a>
    <div class="divider"></div>
    <a id="ctxDeleteOrder" class="text-danger"><i class="bi bi-trash3 text-danger"></i> Delete Order Record</a>
</div>

<!-- রাইট ক্লিক ও প্রিন্ট স্ক্রিপ্ট -->
<script>
let currentTargetOrderId = null;
let currentTargetModalId = null;
let currentTargetPrintId = null;

const contextMenu = document.getElementById('orderContextMenu');

document.querySelectorAll('.order-interactive-card').forEach(card => {
    card.addEventListener('contextmenu', function(e) {
        e.preventDefault();

        currentTargetOrderId = this.getAttribute('data-order-id');
        currentTargetModalId = this.getAttribute('data-modal-id');
        currentTargetPrintId = this.getAttribute('data-print-id');

        // মাউসের সঠিক পজিশন সেট করা
        contextMenu.style.top = `${e.pageY}px`;
        contextMenu.style.left = `${e.pageX}px`;
        contextMenu.style.display = 'block';
    });
});

// স্ক্রিনের অন্য কোথাও ক্লিক করলে মেনু লুকানো
document.addEventListener('click', function(e) {
    if (contextMenu.style.display === 'block') {
        contextMenu.style.display = 'none';
    }
});

// ১. কনটেক্সট মেনু থেকে মডাল ওপেন
document.getElementById('ctxOpenModal').addEventListener('click', function() {
    if (currentTargetModalId) {
        const modalElement = document.getElementById(currentTargetModalId);
        const modalInstance = new bootstrap.Modal(modalElement);
        modalInstance.show();
    }
});

// ২. কনটেক্সট মেনু থেকে প্রিন্ট
document.getElementById('ctxPrintSlip').addEventListener('click', function() {
    if (currentTargetPrintId) {
        printModalInvoice(currentTargetPrintId);
    }
});

// ৩. কনটেক্সট মেনু থেকে স্থায়ীভাবে ডিলিট
document.getElementById('ctxDeleteOrder').addEventListener('click', function() {
    if (currentTargetOrderId) {
        if (confirm("Are you sure you want to delete order #" + currentTargetOrderId + " permanently?")) {
            window.location.href = "seller_dashboard.php?tab=orders&delete_order=" + encodeURIComponent(currentTargetOrderId);
        }
    }
});

// প্রিন্ট ফাংশন
function printModalInvoice(elementId) {
    var printContents = document.getElementById(elementId).innerHTML;
    var originalContents = document.body.innerHTML;

    document.body.innerHTML = "<div class='container p-4'>" + printContents + "</div>";
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}
</script>

<?php include 'footer.php'; ?>