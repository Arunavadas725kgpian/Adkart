<?php
session_start();
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'header.php';

$is_seller = (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'seller' || $_SESSION['user_role'] === 'admin'));

// সার্চ ও ফিল্টার লজিক
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

$sql = "SELECT p.*, u.store_name, u.phone as seller_phone 
        FROM products p 
        LEFT JOIN users u ON p.seller_username = u.username 
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (p.Name LIKE '%$search%' OR p.name LIKE '%$search%')";
}
if (!empty($category)) {
    $sql .= " AND p.category = '$category'";
}

$result = mysqli_query($conn, $sql);
$cat_query = mysqli_query($conn, "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");
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

<!-- ব্যানার স্লাইডার -->
<div id="heroCarousel" class="carousel slide carousel-fade mb-4 shadow" data-bs-ride="carousel" data-bs-interval="4000">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
    </div>
    
    <div class="carousel-inner rounded-4 overflow-hidden">
        <div class="carousel-item active">
            <div class="d-flex align-items-center" style="min-height: 240px; background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.65)), url('https://images.unsplash.com/photo-1610348725531-843dff563e2c?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat; padding: 40px 30px;">
                <div class="text-white">
                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill mb-2 fw-bold shadow-sm">১০০% প্রাকৃতিক ও ফ্রেশ</span>
                    <h1 class="fw-bold display-6 text-white mb-1">তাজা শাকসবজি ও ফলমূল</h1>
                    <p class="fs-6 text-light mb-0">সেরা পাইকারি ও খুচরা মূল্যে সরাসরি বাগান থেকে সরবরাহ।</p>
                </div>
            </div>
        </div>

        <div class="carousel-item">
            <div class="d-flex align-items-center" style="min-height: 240px; background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.65)), url('https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat; padding: 40px 30px;">
                <div class="text-white">
                    <span class="badge bg-success text-white px-3 py-1 rounded-pill mb-2 fw-bold shadow-sm">সরাসরি ভেন্ডর সাপোর্ট</span>
                    <h1 class="fw-bold display-6 text-white mb-1">উন্নত মানের ফ্রেশ বাজার</h1>
                    <p class="fs-6 text-light mb-0">দৈনন্দিন টাটকা পণ্যের বিশ্বস্ত প্ল্যাটফর্ম।</p>
                </div>
            </div>
        </div>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>

<!-- সার্চ ও ফিল্টার বার -->
<div class="card shadow-sm border-0 p-3 mb-4 rounded-4 bg-white">
    <form action="index.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="পণ্য খুঁজুন..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        <div class="col-md-4">
            <select name="category" class="form-select bg-light">
                <option value="">সকল ক্যাটাগরি (All Categories)</option>
                <?php while($cat_row = mysqli_fetch_assoc($cat_query)) { 
                    $cat_name = $cat_row['category'];
                ?>
                    <option value="<?php echo $cat_name; ?>" <?php if($category == $cat_name) echo 'selected'; ?>>
                        <?php echo $cat_name; ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill rounded-3 fw-semibold"><i class="bi bi-funnel"></i> ফিল্টার</button>
            <a href="index.php" class="btn btn-outline-secondary rounded-3"><i class="bi bi-arrow-clockwise"></i></a>
        </div>
    </form>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0 text-dark">পণ্য তালিকা</h4>
    <?php if ($is_seller) { ?>
        <a href="seller_dashboard.php" class="btn btn-warning rounded-pill fw-bold px-3 text-dark">
            <i class="bi bi-speedometer2"></i> সেলার ড্যাশবোর্ড
        </a>
    <?php } ?>
</div>

<!-- পণ্য গ্রিড -->
<div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4 mb-5">
    <?php if ($result && mysqli_num_rows($result) > 0) { 
        while($row = mysqli_fetch_assoc($result)) { 
            $p_id = isset($row['ID']) ? $row['ID'] : (isset($row['id']) ? $row['id'] : 0);
            $p_name = isset($row['Name']) ? $row['Name'] : (isset($row['name']) ? $row['name'] : 'Product');
            $p_price = isset($row['Price']) ? $row['Price'] : (isset($row['price']) ? $row['price'] : 0);
            $p_stock = isset($row['Quantity']) ? $row['Quantity'] : (isset($row['quantity']) ? $row['quantity'] : 0);
            $p_cat = !empty($row['category']) ? $row['category'] : 'General';
            
            $store_display = !empty($row['store_name']) ? $row['store_name'] : 'Ad-Kart Fresh Hub';
            $seller_contact = !empty($row['seller_phone']) ? $row['seller_phone'] : 'Support Available';

            $img_name = isset($row['image']) ? trim($row['image']) : '';
            if (!empty($img_name)) {
                $img_url = "image/" . $img_name;
            } else {
                $img_url = "https://dummyimage.com/400x300/e2e8f0/475569&text=" . urlencode($p_name);
            }
    ?>
    <div class="col">
        <div class="card product-card h-100 shadow-sm overflow-hidden border-0">
            
            <!-- ছবি ডিসপ্লে -->
            <div class="image-wrapper" style="height: 180px; background: #ffffff; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 10px;">
                <img src="<?php echo $img_url; ?>" 
                     style="max-height: 100%; max-width: 100%; object-fit: contain;" 
                     alt="<?php echo htmlspecialchars($p_name); ?>"
                     onerror="this.onerror=null; this.src='https://dummyimage.com/400x300/e2e8f0/475569&text=Item';">
            </div>
            
            <div class="card-body d-flex flex-column p-3">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <h5 class="card-title fw-bold mb-0 text-dark"><?php echo htmlspecialchars($p_name); ?></h5>
                    <span class="badge bg-light text-secondary border rounded-pill"><?php echo htmlspecialchars($p_cat); ?></span>
                </div>
                
                <div class="mb-2">
                    <small class="text-muted d-block" style="font-size: 12px;">
                        <i class="bi bi-shop text-warning"></i> বিক্রেতা: <b><?php echo htmlspecialchars($store_display); ?></b>
                    </small>
                    <small class="text-muted d-block" style="font-size: 11px;">
                        <i class="bi bi-telephone text-success"></i> হেল্পলাইন: <?php echo htmlspecialchars($seller_contact); ?>
                    </small>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="price-tag text-success fw-bold fs-5">৳ <?php echo $p_price; ?></span>
                    <div>
                        <?php if($p_stock > 0) { ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small"><?php echo $p_stock; ?> টি স্টক</span>
                        <?php } else { ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill small">স্টক শেষ</span>
                        <?php } ?>
                    </div>
                </div>
                
                <div class="mt-auto">
                    <?php if ($is_seller) { ?>
                        <!-- সেলার ভিউ: শুধুমাত্র পণ্য পরিচালনার বাটন -->
                        <a href="edit_product.php?id=<?php echo $p_id; ?>" class="btn btn-outline-dark w-100 rounded-3 fw-semibold py-2">
                            <i class="bi bi-pencil-square me-1"></i> পণ্যটি এডিট করুন
                        </a>
                    <?php } else { ?>
                        <!-- বায়ার ভিউ: কার্টে যোগ করার বাটন -->
                        <?php if($p_stock > 0) { ?>
                            <a href="add_to_cart.php?id=<?php echo $p_id; ?>" class="btn btn-primary w-100 rounded-3 fw-semibold py-2">
                                <i class="bi bi-cart-plus me-1"></i> কার্টে যোগ করুন
                            </a>
                        <?php } else { ?>
                            <button class="btn btn-secondary w-100 rounded-3 py-2" disabled>আউট অব স্টক</button>
                        <?php } ?>
                    <?php } ?>
                </div>

            </div>
        </div>
    </div>
    <?php } } else { ?>
        <div class="col-12 text-center py-5">
            <h4 class="text-muted">কোনো পণ্য খুঁজে পাওয়া যায়নি!</h4>
            <a href="index.php" class="btn btn-outline-primary mt-2">সব পণ্য দেখুন</a>
        </div>
    <?php } ?>
</div>

<?php include 'footer.php'; ?>