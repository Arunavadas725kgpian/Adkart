<?php
session_start();
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'seller' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

include 'db.php';
include 'header.php';

$msg = "";
$msg_type = "";

// নতুন কুপন তৈরি
if (isset($_POST['add_coupon'])) {
    $code      = strtoupper(mysqli_real_escape_string($conn, trim($_POST['code'])));
    $discount  = intval($_POST['discount']);
    $min_order = intval($_POST['min_order']);

    $check = mysqli_query($conn, "SELECT * FROM coupons WHERE code = '$code'");
    if (mysqli_num_rows($check) > 0) {
        $msg = "এই কুপন কোডটি আগেই তৈরি করা হয়েছে!";
        $msg_type = "danger";
    } else {
        $sql = "INSERT INTO coupons (code, discount, min_order, status) VALUES ('$code', '$discount', '$min_order', 'Active')";
        if (mysqli_query($conn, $sql)) {
            $msg = "কুপন কোড '$code' সফলভাবে তৈরি হয়েছে!";
            $msg_type = "success";
        }
    }
}

// কুপন ডিলিট
if (isset($_GET['delete'])) {
    $c_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM coupons WHERE id = $c_id");
    $msg = "কুপন মুছে ফেলা হয়েছে!";
    $msg_type = "info";
}

$all_coupons = mysqli_query($conn, "SELECT * FROM coupons ORDER BY id DESC");
?>

<div class="row g-4 mb-5">
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
            <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-ticket-perforated-fill text-warning me-2"></i>নতুন কুপন যোগ করুন</h5>
            <?php if (!empty($msg)) { ?>
                <div class="alert alert-<?php echo $msg_type; ?> py-2 small rounded-3"><?php echo $msg; ?></div>
            <?php } ?>
            <form action="manage_coupons.php" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">কুপন কোড (যেমন: SAVE50)</label>
                    <input type="text" name="code" class="form-control text-uppercase" placeholder="কুপন নাম লিখুন" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">ছাড়ের পরিমাণ (Discount in ৳)</label>
                    <input type="number" name="discount" class="form-control" placeholder="যেমন: 50" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">ন্যূনতম অর্ডারের পরিমাণ (Min Order)</label>
                    <input type="number" name="min_order" class="form-control" placeholder="যেমন: 200" required>
                </div>
                <button type="submit" name="add_coupon" class="btn btn-warning w-100 py-2 rounded-pill fw-bold text-dark">কুপন পাবলিশ করুন</button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
            <h5 class="fw-bold mb-3 text-dark">সক্রিয় কুপন তালিকা</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>কুপন কোড</th>
                            <th>ছাড় (৳)</th>
                            <th>মিনিমাম অর্ডার</th>
                            <th>স্ট্যাটাস</th>
                            <th class="text-end">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($c = mysqli_fetch_assoc($all_coupons)) { ?>
                        <tr>
                            <td><b class="text-primary"><?php echo $c['code']; ?></b></td>
                            <td class="text-success fw-bold">৳ <?php echo $c['discount']; ?></td>
                            <td>৳ <?php echo $c['min_order']; ?></td>
                            <td><span class="badge bg-success-subtle text-success border px-2 py-1"><?php echo $c['status']; ?></span></td>
                            <td class="text-end">
                                <a href="manage_coupons.php?delete=<?php echo $c['id']; ?>" class="btn btn-outline-danger btn-sm rounded-circle" onclick="return confirm('মুছে ফেলতে চান?');"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>