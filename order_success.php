<?php
session_start();
if (!isset($_SESSION['user_name']) || !isset($_SESSION['latest_order_id'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';
include 'header.php';

$order_id = $_SESSION['latest_order_id'];

// অর্ডারের মূল তথ্য আনা
$order_res = mysqli_query($conn, "SELECT * FROM orders WHERE order_id = '$order_id' LIMIT 1");
$order = mysqli_fetch_assoc($order_res);

// অর্ডারের অন্তর্ভুক্ত পণ্য তালিকা আনা
$items_res = mysqli_query($conn, "SELECT oi.*, p.Name, p.name as p_name, p.image 
                                  FROM order_items oi 
                                  LEFT JOIN products p ON oi.product_id = p.ID OR oi.product_id = p.id 
                                  WHERE oi.order_id = '$order_id'");
?>

<div class="container py-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- সাকসেস ব্যানার কার্ড -->
            <div class="card shadow-sm border-0 rounded-4 text-center p-4 p-md-5 bg-white mb-4 position-relative overflow-hidden">
                <div class="mb-3">
                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center shadow" style="width: 80px; height: 80px; font-size: 2.5rem;">
                        <i class="bi bi-check-lg"></i>
                    </div>
                </div>
                
                <h2 class="fw-bold text-dark mb-1">🎉 ধন্যবাদ! আপনার অর্ডার গৃহীত হয়েছে</h2>
                <p class="text-muted mb-3">আমরা দ্রুত আপনার তাজা পণ্যগুলো প্রস্তুত করে নির্ধারিত সময়ে পৌঁছে দিচ্ছি।</p>
                
                <div class="d-inline-block bg-light border rounded-pill px-4 py-2 mb-2">
                    <span class="text-muted small">অর্ডার ট্র্যাকিং আইডি:</span> 
                    <b class="text-primary ms-1">#<?php echo htmlspecialchars($order['order_id']); ?></b>
                </div>
            </div>

            <!-- লাইভ ডেলিভারি স্ট্যাটাস ট্র্যাকার (Amazon / Blinkit Style) -->
            <div class="card shadow-sm border-0 rounded-4 p-4 bg-white mb-4">
                <h6 class="fw-bold text-dark mb-4"><i class="bi bi-truck text-warning me-2"></i>ডেলিভারি অগ্রগতি (Delivery Progress)</h6>
                
                <div class="d-flex justify-content-between position-relative text-center small fw-semibold">
                    <div class="text-success">
                        <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mb-1" style="width: 36px; height: 36px;">
                            <i class="bi bi-check2"></i>
                        </div>
                        <div>অর্ডার নিশ্চিত</div>
                        <div class="text-muted" style="font-size: 10px;"><?php echo date('h:i A'); ?></div>
                    </div>
                    <div class="text-primary">
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-1" style="width: 36px; height: 36px;">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div>প্যাকিং চলছে</div>
                        <div class="text-muted" style="font-size: 10px;">প্রক্রিয়াধীন</div>
                    </div>
                    <div class="text-muted">
                        <div class="rounded-circle bg-light text-secondary border d-inline-flex align-items-center justify-content-center mb-1" style="width: 36px; height: 36px;">
                            <i class="bi bi-bicycle"></i>
                        </div>
                        <div>ডেলিভারিতে বের হবে</div>
                        <div class="text-muted" style="font-size: 10px;">অপেক্ষারত</div>
                    </div>
                    <div class="text-muted">
                        <div class="rounded-circle bg-light text-secondary border d-inline-flex align-items-center justify-content-center mb-1" style="width: 36px; height: 36px;">
                            <i class="bi bi-house-door"></i>
                        </div>
                        <div>ডেলিভার্ড</div>
                        <div class="text-muted" style="font-size: 10px;">গন্তব্য</div>
                    </div>
                </div>
            </div>

            <!-- অর্ডার বিল ও আইটেম ইনভয়েস বিবরণ -->
            <div class="card shadow-sm border-0 rounded-4 p-4 bg-white mb-4">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-receipt text-secondary me-2"></i>অর্ডার ও ইনভয়েস বিবরণ</h5>
                    <button onclick="window.print();" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                        <i class="bi bi-printer me-1"></i> ইনভয়েস প্রিন্ট
                    </button>
                </div>

                <!-- কাস্টমার ও ডেলিভারি তথ্য -->
                <div class="row g-3 small mb-4">
                    <div class="col-md-6 border-end">
                        <span class="text-muted d-block">ডেলিভারির ঠিকানা:</span>
                        <b class="text-dark"><?php echo htmlspecialchars($order['customer_name']); ?></b><br>
                        <?php echo htmlspecialchars($order['address']); ?>, <?php echo htmlspecialchars($order['city']); ?><br>
                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($order['phone']); ?>
                    </div>
                    <div class="col-md-6 ps-md-4">
                        <span class="text-muted d-block">ডেলিভারি শিডিউল:</span>
                        <b class="text-primary"><?php echo htmlspecialchars($order['delivery_date']); ?> (<?php echo htmlspecialchars($order['delivery_slot']); ?>)</b><br>
                        <span class="text-muted d-block mt-2">পেমেন্ট মেথড:</span>
                        <b class="text-success"><?php echo htmlspecialchars($order['payment_method']); ?></b>
                    </div>
                </div>

                <!-- আইটেম টেবিল -->
                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light small">
                            <tr>
                                <th>পণ্য</th>
                                <th class="text-center">পরিমাণ</th>
                                <th class="text-end">দর</th>
                                <th class="text-end">মোট</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($items_res && mysqli_num_rows($items_res) > 0) {
                                while($item = mysqli_fetch_assoc($items_res)) { 
                                    $item_name = !empty($item['Name']) ? $item['Name'] : (!empty($item['p_name']) ? $item['p_name'] : 'Item');
                                    $item_total = $item['quantity'] * $item['price'];
                            ?>
                            <tr>
                                <td><b class="text-dark"><?php echo htmlspecialchars($item_name); ?></b></td>
                                <td class="text-center"><?php echo $item['quantity']; ?> টি</td>
                                <td class="text-end">৳ <?php echo $item['price']; ?></td>
                                <td class="text-end fw-bold text-dark">৳ <?php echo $item_total; ?></td>
                            </tr>
                            <?php } } else { ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted small">আইটেম ডাটা সংরক্ষিত হয়েছে।</td>
                            </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot class="table-light">
                            <?php if ($order['discount_amount'] > 0) { ?>
                            <tr>
                                <td colspan="3" class="text-end text-success fw-bold">কুপন ডিসকাউন্ট:</td>
                                <td class="text-end text-success fw-bold">- ৳ <?php echo $order['discount_amount']; ?></td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <td colspan="3" class="text-end fw-bold fs-6">সর্বমোট বিল:</td>
                                <td class="text-end fw-bold text-success fs-5">৳ <?php echo $order['total_price']; ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- বাটন গ্রুপ -->
                <div class="d-flex flex-wrap gap-2 justify-content-between pt-2">
                    <a href="my_orders.php" class="btn btn-warning rounded-pill px-4 fw-bold text-dark shadow-sm">
                        <i class="bi bi-box-seam me-1"></i> আমার সকল অর্ডার ট্র্যাক করুন
                    </a>
                    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold">
                        <i class="bi bi-cart-plus me-1"></i> আরও পণ্য কিনুন
                    </a>
                </div>

            </div>

        </div>
    </div>
</div>

<?php include 'footer.php'; ?>