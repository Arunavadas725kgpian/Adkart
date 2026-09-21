<?php
session_start();
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// কোডের মাধ্যমেই স্বয়ংক্রিয়ভাবে টেবিল ও কলাম ঠিক নিশ্চিতকরণ
@mysqli_query($conn, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_name VARCHAR(100) NULL");
@mysqli_query($conn, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL");
@mysqli_query($conn, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS address TEXT NULL");
@mysqli_query($conn, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS city VARCHAR(100) NULL");
@mysqli_query($conn, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) NULL");
@mysqli_query($conn, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_date VARCHAR(50) NULL");
@mysqli_query($conn, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_slot VARCHAR(100) NULL");
@mysqli_query($conn, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS total_price INT NOT NULL DEFAULT 0");
@mysqli_query($conn, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount_amount INT DEFAULT 0");
@mysqli_query($conn, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS order_id VARCHAR(50) NULL");
@mysqli_query($conn, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'Order Placed'");

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price INT NOT NULL
)");

include 'header.php';

$curr_user = $_SESSION['user_name'];
$user_q = mysqli_query($conn, "SELECT * FROM users WHERE username = '$curr_user' LIMIT 1");
$u_info = mysqli_fetch_assoc($user_q);

// কার্ট আইটেম ও মূল্যের হিসাব
$cart_items = [];
$subtotal = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $p_id => $item) {
        $price = isset($item['price']) ? intval($item['price']) : 0;
        $qty   = isset($item['qty']) ? intval($item['qty']) : 1;
        $subtotal += ($price * $qty);
        $cart_items[$p_id] = $item;
    }
} else {
    echo "<script>window.location.href = 'index.php';</script>";
    exit();
}

// কুপন যাচাই
if (isset($_POST['apply_coupon'])) {
    $code = strtoupper(mysqli_real_escape_string($conn, trim($_POST['coupon_code'])));
    $cp_q = mysqli_query($conn, "SELECT * FROM coupons WHERE code = '$code' AND status = 'Active' LIMIT 1");

    if ($cp_q && mysqli_num_rows($cp_q) > 0) {
        $coupon = mysqli_fetch_assoc($cp_q);
        if ($subtotal >= $coupon['min_order']) {
            $_SESSION['coupon_discount'] = $coupon['discount'];
            $_SESSION['applied_code'] = $code;
            $_SESSION['coupon_msg'] = "🎉 অভিনন্দন! ৳" . $coupon['discount'] . " ডিসকাউন্ট যুক্ত হয়েছে।";
            $_SESSION['coupon_type'] = "success";
        } else {
            $_SESSION['coupon_msg'] = "এই কুপনের জন্য ন্যূনতম ৳" . $coupon['min_order'] . " কেনাকাটা করতে হবে!";
            $_SESSION['coupon_type'] = "warning";
        }
    } else {
        $_SESSION['coupon_msg'] = "অবৈধ বা মেয়াদোত্তীর্ণ কুপন কোড!";
        $_SESSION['coupon_type'] = "danger";
    }
    echo "<script>window.location.href='checkout.php';</script>";
    exit();
}

$discount = isset($_SESSION['coupon_discount']) ? intval($_SESSION['coupon_discount']) : 0;
$delivery_charge = ($subtotal >= 499) ? 0 : 30;
$grand_total = max(0, $subtotal + $delivery_charge - $discount);

// অর্ডার কনফার্মেশন প্রক্রিয়া
if (isset($_POST['place_order'])) {
    $c_name    = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $phone     = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $address   = mysqli_real_escape_string($conn, trim($_POST['address']));
    $city      = mysqli_real_escape_string($conn, trim($_POST['city']));
    $pay_type  = mysqli_real_escape_string($conn, trim($_POST['payment_method']));
    $del_date  = mysqli_real_escape_string($conn, trim($_POST['delivery_date']));
    $del_slot  = mysqli_real_escape_string($conn, trim($_POST['delivery_slot']));
    $order_id  = "ADK-" . date("Ymd") . "-" . rand(1000, 9999);

    // ১. মূল অর্ডার তৈরি
    $order_sql = "INSERT INTO orders (order_id, user_name, customer_name, phone, address, city, payment_method, delivery_date, delivery_slot, total_price, discount_amount, status) 
                  VALUES ('$order_id', '$curr_user', '$c_name', '$phone', '$address', '$city', '$pay_type', '$del_date', '$del_slot', '$grand_total', '$discount', 'Order Placed')";

    if (mysqli_query($conn, $order_sql)) {
        // ২. প্রতিটি আইটেম সংরক্ষণ ও স্টক হ্রাস
        foreach ($cart_items as $prod_id => $c_item) {
            $item_qty   = intval($c_item['qty']);
            $item_price = intval($c_item['price']);
            
            @mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES ('$order_id', '$prod_id', '$item_qty', '$item_price')");
            @mysqli_query($conn, "UPDATE products SET Quantity = GREATEST(0, Quantity - $item_qty) WHERE ID = '$prod_id' OR id = '$prod_id'");
        }

        unset($_SESSION['cart']);
        unset($_SESSION['coupon_discount']);
        unset($_SESSION['applied_code']);
        
        $_SESSION['msg'] = "🎉 আপনার অর্ডারটি সফলভাবে গৃহীত হয়েছে এবং 'আমার অর্ডার' তালিকায় যুক্ত হয়েছে!";
        $_SESSION['msg_type'] = "success";
        
        echo "<script>window.location.href = 'my_orders.php';</script>";
        exit();
    } else {
        echo "ডাটাবেস সংরক্ষণ ত্রুটি: " . mysqli_error($conn);
        exit();
    }
}
?>

<div class="container py-3">
    <form action="checkout.php" method="POST">
        <div class="row g-4">
            
            <!-- ফর্ম সেকশন -->
            <div class="col-lg-8">
                <!-- ডেলিভারির ঠিকানা -->
                <div class="card shadow-sm border-0 rounded-4 mb-4 bg-white p-4">
                    <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-geo-alt-fill text-danger me-1"></i> ডেলিভারির ঠিকানা</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">নাম <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($u_info['full_name'] ?? $curr_user); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">মোবাইল নম্বর <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($u_info['phone'] ?? ''); ?>" placeholder="+91 0000000000" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">ঠিকানা (বাড়ি/রাস্তা/এলাকা) <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($u_info['address'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">শহর / পিন <span class="text-danger">*</span></label>
                            <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($u_info['city'] ?? 'Kolkata'); ?>" required>
                        </div>
                    </div>
                </div>

                <!-- ডেলিভারি শিডিউল -->
                <div class="card shadow-sm border-0 rounded-4 mb-4 bg-white p-4">
                    <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-clock-history text-primary me-1"></i> ডেলিভারি শিডিউল</h5>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="border p-2 rounded-3 d-flex align-items-center w-100 bg-light">
                                <input type="radio" name="delivery_date" value="আজকেই ডেলিভারি (Today)" checked class="form-check-input me-2">
                                <b>আজকেই (Today)</b>
                            </label>
                        </div>
                        <div class="col-6">
                            <label class="border p-2 rounded-3 d-flex align-items-center w-100 bg-light">
                                <input type="radio" name="delivery_date" value="আগামীকাল (Tomorrow)" class="form-check-input me-2">
                                <b>আগামীকাল (Tomorrow)</b>
                            </label>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="border p-2 rounded-3 d-flex align-items-center w-100 bg-light">
                                <input type="radio" name="delivery_slot" value="সকাল (7 AM - 11 AM)" checked class="form-check-input me-2">
                                <span>সকাল (7 AM - 11 AM)</span>
                            </label>
                        </div>
                        <div class="col-6">
                            <label class="border p-2 rounded-3 d-flex align-items-center w-100 bg-light">
                                <input type="radio" name="delivery_slot" value="বিকাল (4 PM - 8 PM)" class="form-check-input me-2">
                                <span>বিকাল (4 PM - 8 PM)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- পেমেন্ট মেথড -->
                <div class="card shadow-sm border-0 rounded-4 bg-white p-4">
                    <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-credit-card-2-front-fill text-success me-1"></i> পেমেন্ট মেথড</h5>
                    <label class="border p-3 rounded-3 d-flex align-items-center justify-content-between bg-light mb-2">
                        <div>
                            <input type="radio" name="payment_method" value="Cash on Delivery" checked class="form-check-input me-2">
                            <b>ক্যাশ অন ডেলিভারি (Cash on Delivery)</b>
                        </div>
                        <span class="badge bg-success">জনপ্রিয়</span>
                    </label>
                    <label class="border p-3 rounded-3 d-flex align-items-center justify-content-between bg-light">
                        <div>
                            <input type="radio" name="payment_method" value="UPI / QR Code Scan" class="form-check-input me-2">
                            <b>UPI / QR কোড স্ক্যান (Google Pay, PhonePe, Paytm)</b>
                        </div>
                        <i class="bi bi-qr-code-scan text-primary"></i>
                    </label>
                </div>
            </div>

            <!-- সারাংশ ও কনফার্ম বাটন -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-4 bg-white p-3 mb-3">
                    <label class="form-label fw-bold small"><i class="bi bi-ticket-perforated-fill text-danger me-1"></i> কুপন কোড</label>
                    <?php if (isset($_SESSION['coupon_msg'])) { ?>
                        <div class="alert alert-<?php echo $_SESSION['coupon_type']; ?> py-1 px-2 small mb-2">
                            <?php echo $_SESSION['coupon_msg']; unset($_SESSION['coupon_msg']); unset($_SESSION['coupon_type']); ?>
                        </div>
                    <?php } ?>
                    <div class="input-group">
                        <input type="text" name="coupon_code" form="cpForm" class="form-control form-control-sm text-uppercase" placeholder="কুপন কোড দিন">
                        <button type="submit" name="apply_coupon" form="cpForm" class="btn btn-dark btn-sm fw-bold">প্রয়োগ</button>
                    </div>
                </div>

                <div class="card shadow-sm border-0 rounded-4 bg-white p-4 sticky-top" style="top: 85px;">
                    <h5 class="fw-bold mb-3 text-dark">অর্ডার সারাংশ</h5>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span>মোট মূল্য</span>
                        <b>৳ <?php echo $subtotal; ?></b>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span>ডেলিভারি চার্জ</span>
                        <b><?php echo ($delivery_charge === 0) ? '<span class="text-success">ফ্রি</span>' : '৳ ' . $delivery_charge; ?></b>
                    </div>
                    <?php if ($discount > 0) { ?>
                        <div class="d-flex justify-content-between mb-2 small text-success">
                            <span>কুপন ছাড়</span>
                            <b>- ৳ <?php echo $discount; ?></b>
                        </div>
                    <?php } ?>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <b>সর্বমোট প্রদেয়:</b>
                        <h4 class="fw-bold text-success mb-0">৳ <?php echo $grand_total; ?></h4>
                    </div>

                    <button type="submit" name="place_order" class="btn btn-warning w-100 py-3 rounded-pill fw-bold text-dark fs-5 shadow">
                        <i class="bi bi-shield-lock-fill me-1"></i> অর্ডার কনফার্ম করুন
                    </button>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted"><i class="bi bi-shield-check text-success"></i> ১০০% নিরাপদ ও সুরক্ষিত চেকআউট</small>
                    </div>
                </div>
            </div>

        </div>
    </form>
    <form action="checkout.php" method="POST" id="cpForm"></form>
</div>

<?php include 'footer.php'; ?>