<?php
session_start();
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'header.php';

$msg = "";
$msg_type = "";

// ডাটাবেসে প্রয়োজনীয় কলাম নিশ্চিত করা
@mysqli_query($conn, "ALTER TABLE products ADD COLUMN IF NOT EXISTS category VARCHAR(100) DEFAULT 'General'");
@mysqli_query($conn, "ALTER TABLE products ADD COLUMN IF NOT EXISTS image VARCHAR(255) NULL");
@mysqli_query($conn, "ALTER TABLE products ADD COLUMN IF NOT EXISTS unit VARCHAR(50) DEFAULT 'kg'");
@mysqli_query($conn, "ALTER TABLE products ADD COLUMN IF NOT EXISTS description TEXT NULL");

if (isset($_POST['save_product'])) {
    $p_name  = mysqli_real_escape_string($conn, trim($_POST['name']));
    $p_price = floatval($_POST['price']);
    $p_qty   = intval($_POST['quantity']);
    $p_cat   = mysqli_real_escape_string($conn, trim($_POST['category']));
    $p_unit  = mysqli_real_escape_string($conn, trim($_POST['unit']));
    $p_desc  = mysqli_real_escape_string($conn, trim($_POST['description']));

    $image_name = "";

    // ছবি আপলোড লজিক
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
        $file_name = $_FILES['product_image']['name'];
        $file_tmp  = $_FILES['product_image']['tmp_name'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $target_dir = __DIR__ . "/image/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $image_name = "prod_" . time() . "_" . rand(100, 999) . "." . $ext;
        move_uploaded_file($file_tmp, $target_dir . $image_name);
    }

    $insert_sql = "INSERT INTO products (Name, Price, Quantity, category, unit, description, image) 
                   VALUES ('$p_name', '$p_price', '$p_qty', '$p_cat', '$p_unit', '$p_desc', '$image_name')";

    if (mysqli_query($conn, $insert_sql)) {
        $_SESSION['msg'] = "Congratulation successfully new product add in the shop!";
        $_SESSION['msg_type'] = "success";
        echo "<script>window.location.href = 'seller_dashboard.php';</script>";
        exit();
    } else {
        $msg = "পণ্য সংরক্ষণে ত্রুটি: " . mysqli_error($conn);
        $msg_type = "danger";
    }
}
?>

<div class="container py-4">
    <!-- পেজ হেডার -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-box-seam-fill text-warning me-2"></i>Add New Product
            </h3>
            <p class="text-muted small mb-0">Describe your shop catalog items,prices & stocks</p>
        </div>
        <a href="seller_dashboard.php" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Return to the Dashbord
        </a>
    </div>

    <?php if (!empty($msg)) { ?>
        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-octagon-fill me-2"></i> <?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <form action="add_product.php" method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            
            <!-- বাঁদিকের সেকশন: মূল তথ্য -->
            <div class="col-lg-8">
                <!-- জেনারেল ইনফো কার্ড -->
                <div class="card shadow-sm border-0 rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle text-primary me-2"></i>Product primary description</h5>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Name of the product <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-lg fs-6 rounded-3" placeholder="Ex: Fresh Mango/Desi Onion" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-semibold text-secondary">Product Description</label>
                            <textarea name="description" rows="4" class="form-control rounded-3" placeholder="Write about the product's special qualities,freshness..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- মূল্য ও স্টক কার্ড -->
                <div class="card shadow-sm border-0 rounded-4 bg-white">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-currency-exchange text-success me-2"></i>Price & Investment</h5>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">বিক্রয় মূল্য (Price) <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light text-success fw-bold">৳</span>
                                    <input type="number" step="0.01" name="price" class="form-control fs-6" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">পরিমাপের একক (Unit) <span class="text-danger">*</span></label>
                                <select name="unit" class="form-select form-select-lg fs-6 rounded-3" required>
                                    <option value="kg" selected>প্রতি কেজি (Kg)</option>
                                    <option value="gm">প্রতি গ্রাম (Gram)</option>
                                    <option value="piece">প্রতি পিস / প্যাক (Piece)</option>
                                    <option value="dozen">প্রতি ডজন (Dozen)</option>
                                    <option value="liter">প্রতি লিটার (Liter)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Stock Quantity <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light"><i class="bi bi-layers"></i></span>
                                    <input type="number" name="quantity" class="form-control fs-6" placeholder="যেমন: 100" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Catagory <span class="text-danger">*</span></label>
                                <select name="category" class="form-select form-select-lg fs-6 rounded-3" required>
                                    <option value="" disabled selected>Select your category</option>
                                    <option value="Vegetables">শাকসবজি (Vegetables)</option>
                                    <option value="Fruits">ফলমূল (Fruits)</option>
                                    <option value="Grocery">মুদি ও ডাল (Grocery)</option>
                                    <option value="Spices">মসলাপাতি (Spices)</option>
                                    <option value="Organic">অর্গানিক খাবার (Organic)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ডানদিকের সেকশন: মিডিয়া আপলোড ও লাইভ প্রিভিউ -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-4 bg-white sticky-top" style="top: 85px;">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-image text-warning me-2"></i>Upload the product image</h5>
                    </div>
                    <div class="card-body p-4 pt-0 text-center">
                        
                        <!-- লাইভ প্রিভিউ বক্স -->
                        <div class="border rounded-4 p-3 bg-light mb-3 position-relative overflow-hidden" style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                            <img id="previewBox" src="https://dummyimage.com/300x250/e2e8f0/64748b&text=Live+Image+Preview" 
                                 class="img-fluid rounded-3" 
                                 style="max-height: 180px; object-fit: contain; background: #ffffff;">
                        </div>

                        <div class="mb-3">
                            <label for="imgUpload" class="btn btn-outline-primary w-100 py-2 rounded-3 fw-semibold">
                                <i class="bi bi-folder2-open me-2"></i>choose images
                            </label>
                            <input type="file" name="product_image" id="imgUpload" class="d-none" accept="image/*" onchange="loadPreview(this)">
                            <small class="text-muted d-block mt-2">JPG, PNG, WEBP ফরম্যাট সমর্থিত</small>
                        </div>

                        <hr>

                        <button type="submit" name="save_product" class="btn btn-warning w-100 py-3 rounded-pill fw-bold fs-5 shadow-sm text-dark">
                            <i class="bi bi-check-circle-fill me-2"></i>পণ্যটি পাবলিশ করুন
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
function loadPreview(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewBox').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'footer.php'; ?>