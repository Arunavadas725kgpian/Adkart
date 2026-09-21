<?php
session_start();
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

// সেলার কার্ট পেজে ঢুকলে ড্যাশবোর্ডে রিডাইরেক্ট হবে
if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'seller' || $_SESSION['user_role'] === 'admin')) {
    $_SESSION['msg'] = "কার্ট ও চেকআউট শুধুমাত্র ক্রেতাদের জন্য সংরক্ষিত।";
    $_SESSION['msg_type'] = "info";
    header("Location: seller_dashboard.php");
    exit();
}

include 'db.php';
include 'header.php';

// কার্ট ইনিশিয়ালাইজেশন
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// প্রোডাক্ট রিমুভ লজিক
if (isset($_GET['remove'])) {
    $remove_id = $_GET['remove'];
    unset($_SESSION['cart'][$remove_id]);
    $_SESSION['msg'] = "পণ্যটি কার্ট থেকে সরানো হয়েছে।";
    $_SESSION['msg_type'] = "info";
    header("Location: cart.php");
    exit();
}

// পরিমাণ আপডেট লজিক
if (isset($_POST['update_qty'])) {
    foreach ($_POST['qty'] as $p_id => $quantity) {
        $quantity = intval($quantity);
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$p_id]);
        } else {
            if (isset($_SESSION['cart'][$p_id])) {
                $_SESSION['cart'][$p_id]['qty'] = $quantity;
            }
        }
    }
    $_SESSION['msg'] = "কার্ট আপডেট করা হয়েছে!";
    $_SESSION['msg_type'] = "success";
    header("Location: cart.php");
    exit();
}

$grand_total = 0;
?>

<!-- স্ট্যাটাস অ্যালার্ট -->
<?php if (isset($_SESSION['msg'])) { ?>
    <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i>
        <?php 
            echo $_SESSION['msg']; 
            unset($_SESSION['msg']);
            unset($_SESSION['msg_type']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php } ?>

<div class="row g-4 mb-5">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0"><i class="bi bi-cart3 text-warning"></i> শপিং কার্ট</h4>
                <span class="badge bg-light text-dark border"><?php echo count($_SESSION['cart']); ?> টি আইটেম</span>
            </div>

            <?php if (!empty($_SESSION['cart'])) { ?>
                <form action="cart.php" method="POST">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>পণ্য</th>
                                    <th>মূল্য</th>
                                    <th style="width: 120px;">পরিমাণ</th>
                                    <th>মোট</th>
                                    <th class="text-end">মুছুন</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($_SESSION['cart'] as $p_id => $item) { 
                                    $subtotal = $item['price'] * $item['qty'];
                                    $grand_total += $subtotal;
                                    $img_src = !empty($item['image']) ? "image/" . $item['image'] : "https://dummyimage.com/60x60/e2e8f0/475569&text=Item";
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?php echo $img_src; ?>" style="width: 50px; height: 50px; object-fit: contain; border-radius: 8px; border: 1px solid #ddd;">
                                            <div>
                                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>৳ <?php echo $item['price']; ?></td>
                                    <td>
                                        <input type="number" name="qty[<?php echo $p_id; ?>]" value="<?php echo $item['qty']; ?>" min="1" class="form-control form-control-sm text-center">
                                    </td>
                                    <td class="fw-bold text-success">৳ <?php echo $subtotal; ?></td>
                                    <td class="text-end">
                                        <a href="cart.php?remove=<?php echo $p_id; ?>" class="btn btn-outline-danger btn-sm rounded-circle"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <a href="index.php" class="btn btn-outline-secondary rounded-pill px-3"><i class="bi bi-arrow-left"></i> আরও পণ্য কিনুন</a>
                        <button type="submit" name="update_qty" class="btn btn-dark rounded-pill px-4">কার্ট আপডেট করুন</button>
                    </div>
                </form>
            <?php } else { ?>
                <div class="text-center py-5">
                    <i class="bi bi-cart-x display-1 text-muted"></i>
                    <h5 class="text-muted mt-3">আপনার কার্ট বর্তমানে খালি!</h5>
                    <a href="index.php" class="btn btn-warning rounded-pill mt-2 fw-semibold">কেনাকাটা শুরু করুন</a>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- অর্ডার সামারি -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
            <h5 class="fw-bold mb-3">অর্ডার সামারি</h5>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">পণ্য মূল্য</span>
                <span>৳ <?php echo $grand_total; ?></span>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <span class="text-muted">ডেলিভারি চার্জ</span>
                <span class="text-success fw-bold">ফ্রি (৳ 0)</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between fs-5 fw-bold mb-4">
                <span>সর্বমোট</span>
                <span class="text-success">৳ <?php echo $grand_total; ?></span>
            </div>

            <?php if (!empty($_SESSION['cart'])) { ?>
                <a href="checkout.php" class="btn btn-warning w-100 py-2 rounded-3 fw-bold shadow-sm">
                    চেকআউট করুন <i class="bi bi-arrow-right"></i>
                </a>
            <?php } ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>