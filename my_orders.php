<?php
session_start();
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$curr_user = $_SESSION['user_name'];

// সরাসরি পেজের ভেতরেই অর্ডার বাতিল লজিক (যাতে কোনো Not Found এরর না আসে)
if (isset($_POST['btn_cancel_order'])) {
    $cancel_id = mysqli_real_escape_string($conn, trim($_POST['cancel_order_id']));

    $check = mysqli_query($conn, "SELECT * FROM orders WHERE (order_id = '$cancel_id' OR id = '$cancel_id') AND user_name = '$curr_user' AND status != 'Cancelled' LIMIT 1");
    
    if ($check && mysqli_num_rows($check) > 0) {
        $ord = mysqli_fetch_assoc($check);
        $real_id = !empty($ord['order_id']) ? $ord['order_id'] : $ord['id'];

        // স্টক ফিরিয়ে দেওয়া
        $items = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id = '$real_id'");
        if ($items && mysqli_num_rows($items) > 0) {
            while ($itm = mysqli_fetch_assoc($items)) {
                $pid = $itm['product_id'];
                $qty = intval($itm['quantity']);
                @mysqli_query($conn, "UPDATE products SET Quantity = Quantity + $qty WHERE ID = '$pid' OR id = '$pid'");
            }
        }

        mysqli_query($conn, "UPDATE orders SET status = 'Cancelled' WHERE order_id = '$real_id' OR id = '$real_id'");

        $_SESSION['msg'] = "অর্ডারটি সফলভাবে বাতিল করা হয়েছে এবং পণ্যগুলো সেলারের স্টকে যুক্ত হয়েছে।";
        $_SESSION['msg_type'] = "success";
    }
    header("Location: my_orders.php");
    exit();
}

include 'header.php';

$orders_query = mysqli_query($conn, "SELECT * FROM orders WHERE user_name = '$curr_user' ORDER BY 1 DESC");
$total_orders = ($orders_query) ? mysqli_num_rows($orders_query) : 0;
?>

<div class="container py-4 mb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-box-seam-fill text-warning me-2"></i>আমার অর্ডারসমূহ (My Orders)
            </h3>
            <p class="text-muted small mb-0">আপনার পূর্ববর্তী ও চলমান সকল অর্ডারের লাইভ ট্র্যাকিং ও বিবরণ</p>
        </div>
        <a href="index.php" class="btn btn-outline-dark rounded-pill px-4 shadow-sm fw-semibold">
            <i class="bi bi-bag-plus me-1"></i> নতুন কেনাকাটা করুন
        </a>
    </div>

    <!-- নোটিফিকেশন মেসেজ -->
    <?php if (isset($_SESSION['msg'])) { ?>
        <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i>
            <?php 
                echo $_SESSION['msg']; 
                unset($_SESSION['msg']);
                unset($_SESSION['msg_type']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <?php if ($total_orders > 0) { ?>
        <div class="row g-4">
            <?php while ($ord = mysqli_fetch_assoc($orders_query)) { 
                $raw_id     = $ord['id'] ?? ($ord['ID'] ?? 0);
                $order_id   = !empty($ord['order_id']) ? $ord['order_id'] : 'ADK-' . str_pad($raw_id, 6, '0', STR_PAD_LEFT);
                $order_date = !empty($ord['order_date']) ? date('d M Y, h:i A', strtotime($ord['order_date'])) : date('d M Y');
                $price      = isset($ord['total_price']) ? intval($ord['total_price']) : 0;
                $status     = !empty($ord['status']) ? trim($ord['status']) : 'Pending';
                $address    = $ord['address'] ?? 'ঠিকানা সংরক্ষিত নেই';
                
                $slot_txt = !empty($ord['delivery_slot']) ? $ord['delivery_slot'] : 'স্ট্যান্ডার্ড ডেলিভারি';
                if (!empty($ord['delivery_date'])) {
                    $slot_txt = $ord['delivery_date'] . " (" . $slot_txt . ")";
                }
                
                $pay_method = $ord['payment_method'] ?? 'Cash on Delivery';
                $is_active  = ($status !== 'Cancelled' && $status !== 'Delivered');
            ?>
                <div class="col-12">
                    <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
                        
                        <!-- কার্ড টপ বার -->
                        <div class="card-header bg-light border-0 p-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="d-flex flex-wrap gap-4 small text-secondary">
                                <div>
                                    <span class="text-uppercase text-muted d-block" style="font-size: 11px;">অর্ডার তারিখ:</span>
                                    <b class="text-dark"><?php echo $order_date; ?></b>
                                </div>
                                <div>
                                    <span class="text-uppercase text-muted d-block" style="font-size: 11px;">মোট মূল্য:</span>
                                    <b class="text-success fs-6">৳ <?php echo $price; ?></b>
                                </div>
                                <div>
                                    <span class="text-uppercase text-muted d-block" style="font-size: 11px;">ডেলিভারি ঠিকানা:</span>
                                    <b class="text-dark"><?php echo htmlspecialchars($address); ?></b>
                                </div>
                            </div>
                            <div>
                                <span class="text-uppercase text-muted d-block text-end" style="font-size: 11px;">অর্ডার আইডি:</span>
                                <span class="badge bg-dark text-warning px-3 py-2 rounded-pill fw-bold">#<?php echo htmlspecialchars($order_id); ?></span>
                            </div>
                        </div>

                        <!-- কার্ড বডি: লাইভ ট্র্যাকার -->
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-lg-8">
                                    <h6 class="fw-bold mb-3 text-dark">
                                        <i class="bi bi-clock-history text-primary me-2"></i>ডেলিভারি শিডিউল: <span class="text-primary"><?php echo htmlspecialchars($slot_txt); ?></span>
                                    </h6>

                                    <!-- প্রগ্রেস বার -->
                                    <div class="d-flex justify-content-between position-relative text-center small fw-semibold pt-2 mb-3">
                                        <div class="<?php echo ($status !== 'Cancelled') ? 'text-success' : 'text-danger'; ?>">
                                            <div class="rounded-circle <?php echo ($status !== 'Cancelled') ? 'bg-success' : 'bg-danger'; ?> text-white d-inline-flex align-items-center justify-content-center mb-1" style="width: 32px; height: 32px;">
                                                <i class="bi bi-check2"></i>
                                            </div>
                                            <div><?php echo ($status === 'Cancelled') ? 'অর্ডার বাতিল' : 'অর্ডার গৃহীত'; ?></div>
                                        </div>

                                        <div class="<?php echo ($status === 'Shipped' || $status === 'Delivered') ? 'text-success' : 'text-muted'; ?>">
                                            <div class="rounded-circle <?php echo ($status === 'Shipped' || $status === 'Delivered') ? 'bg-success text-white' : 'bg-light text-secondary border'; ?> d-inline-flex align-items-center justify-content-center mb-1" style="width: 32px; height: 32px;">
                                                <i class="bi bi-box-seam"></i>
                                            </div>
                                            <div>প্যাকিং সম্পন্ন</div>
                                        </div>

                                        <div class="<?php echo ($status === 'Out for Delivery' || $status === 'Delivered') ? 'text-success' : 'text-muted'; ?>">
                                            <div class="rounded-circle <?php echo ($status === 'Out for Delivery' || $status === 'Delivered') ? 'bg-success text-white' : 'bg-light text-secondary border'; ?> d-inline-flex align-items-center justify-content-center mb-1" style="width: 32px; height: 32px;">
                                                <i class="bi bi-truck"></i>
                                            </div>
                                            <div>ডেলিভারিতে বের হয়েছে</div>
                                        </div>

                                        <div class="<?php echo ($status === 'Delivered') ? 'text-success' : 'text-muted'; ?>">
                                            <div class="rounded-circle <?php echo ($status === 'Delivered') ? 'bg-success text-white' : 'bg-light text-secondary border'; ?> d-inline-flex align-items-center justify-content-center mb-1" style="width: 32px; height: 32px;">
                                                <i class="bi bi-house-door"></i>
                                            </div>
                                            <div>ডেলিভার্ড</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4 text-lg-end border-start-lg pt-3 pt-lg-0">
                                    <div class="d-flex flex-column gap-2">
                                        <div class="mb-1">
                                            <span class="badge bg-<?php echo ($status === 'Cancelled') ? 'danger' : (($status === 'Delivered') ? 'success' : 'warning text-dark'); ?> px-3 py-2 rounded-pill fs-6">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </div>
                                        <small class="text-muted d-block mb-2">পেমেন্ট: <b><?php echo htmlspecialchars($pay_method); ?></b></small>

                                        <?php if ($is_active) { ?>
                                            <!-- নিরাপদ ডাইরেক্ট পোস্ট ক্যানসেল বাটন -->
                                            <form action="my_orders.php" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিতভাবে এই অর্ডারটি বাতিল করতে চান? পণ্যটি সেলারের স্টকে ফেরত চলে যাবে।');">
                                                <input type="hidden" name="cancel_order_id" value="<?php echo htmlspecialchars($order_id); ?>">
                                                <button type="submit" name="btn_cancel_order" class="btn btn-outline-danger btn-sm rounded-pill py-2 w-100 fw-semibold">
                                                    <i class="bi bi-x-circle me-1"></i> অর্ডার বাতিল করুন
                                                </button>
                                            </form>
                                        <?php } ?>
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
            <div class="mb-3">
                <i class="bi bi-bag-x display-1 text-muted"></i>
            </div>
            <h4 class="fw-bold text-dark">আপনার কোনো পূর্ববর্তী অর্ডার পাওয়া যায়নি!</h4>
            <p class="text-muted">তাজা শাকসবজি ও ফলমূল কিনতে আমাদের শপ ভিজিট করুন।</p>
            <div class="mt-2">
                <a href="index.php" class="btn btn-warning rounded-pill px-4 py-2 fw-bold text-dark shadow-sm">
                    <i class="bi bi-basket me-1"></i> শপে যান
                </a>
            </div>
        </div>
    <?php } ?>

</div>

<?php include 'footer.php'; ?>