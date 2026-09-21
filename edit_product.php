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

// Validate product ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: seller_dashboard.php");
    exit();
}

$product_id = intval($_GET['id']);

// Fetch existing product details
$query = mysqli_query($conn, "SELECT * FROM products WHERE ID = $product_id OR id = $product_id LIMIT 1");
if (!$query || mysqli_num_rows($query) === 0) {
    $_SESSION['msg'] = "Product not found!";
    $_SESSION['msg_type'] = "danger";
    header("Location: seller_dashboard.php");
    exit();
}

$product = mysqli_fetch_assoc($query);

// Collect field values
$p_name  = $product['Name'] ?? ($product['name'] ?? '');
$p_price = $product['Price'] ?? ($product['price'] ?? 0);
$p_qty   = $product['Quantity'] ?? ($product['quantity'] ?? 0);
$p_cat   = $product['category'] ?? 'Vegetables';
$p_unit  = $product['unit'] ?? 'kg';
$p_desc  = $product['description'] ?? '';
$p_img   = $product['image'] ?? '';

// Determine current image URL
$current_img_src = (!empty($p_img) && file_exists(__DIR__ . "/image/" . $p_img)) 
                    ? "image/" . rawurlencode($p_img) . "?v=" . time() 
                    : "https://dummyimage.com/300x250/e2e8f0/64748b&text=No+Image";

// Handle form submission
if (isset($_POST['update_product'])) {
    $new_name  = mysqli_real_escape_string($conn, trim($_POST['name']));
    $new_price = floatval($_POST['price']);
    $new_qty   = intval($_POST['quantity']);
    $new_cat   = mysqli_real_escape_string($conn, trim($_POST['category']));
    $new_unit  = mysqli_real_escape_string($conn, trim($_POST['unit']));
    $new_desc  = mysqli_real_escape_string($conn, trim($_POST['description']));

    $image_update_sql = "";

    // Upload new image if provided
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
        $file_name = $_FILES['product_image']['name'];
        $file_tmp  = $_FILES['product_image']['tmp_name'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $target_dir = __DIR__ . "/image/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $new_img_name = "item_" . $product_id . "_" . time() . "." . $ext;
        $destination  = $target_dir . $new_img_name;

        if (move_uploaded_file($file_tmp, $destination)) {
            // Remove previous image file
            if (!empty($p_img) && file_exists($target_dir . $p_img)) {
                @unlink($target_dir . $p_img);
            }
            $image_update_sql = ", image = '$new_img_name'";
        }
    }

    // Database update query
    $update_sql = "UPDATE products SET 
                    Name = '$new_name', 
                    Price = '$new_price', 
                    Quantity = '$new_qty', 
                    category = '$new_cat', 
                    unit = '$new_unit', 
                    description = '$new_desc' 
                    $image_update_sql 
                   WHERE ID = $product_id OR id = $product_id";

    if (mysqli_query($conn, $update_sql)) {
        $_SESSION['msg'] = "Product details updated successfully!";
        $_SESSION['msg_type'] = "success";
        echo "<script>window.location.href = 'seller_dashboard.php';</script>";
        exit();
    } else {
        $msg = "Update failed: " . mysqli_error($conn);
        $msg_type = "danger";
    }
}
?>

<div class="container py-4 mb-5">
    
    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-pencil-square text-warning me-2"></i>Edit Product
            </h3>
            <p class="text-muted small mb-0">Modify product pricing, stock inventory, category, or media</p>
        </div>
        <a href="seller_dashboard.php" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <?php if (!empty($msg)) { ?>
        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <form action="edit_product.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            
            <!-- Left Column: Primary Details -->
            <div class="col-lg-8">
                
                <!-- General Information Card -->
                <div class="card shadow-sm border-0 rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-info-circle text-primary me-2"></i>Product Information
                        </h5>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-lg fs-6 rounded-3" value="<?php echo htmlspecialchars($p_name); ?>" placeholder="e.g., Fresh Organic Banana" required>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-semibold text-secondary">Description</label>
                            <textarea name="description" rows="4" class="form-control rounded-3" placeholder="Provide quality, origin, and freshness details..."><?php echo htmlspecialchars($p_desc); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Pricing & Inventory Card -->
                <div class="card shadow-sm border-0 rounded-4 bg-white">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-currency-exchange text-success me-2"></i>Pricing & Inventory
                        </h5>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Price (in ৳) <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light text-success fw-bold">৳</span>
                                    <input type="number" step="0.01" name="price" class="form-control fs-6" value="<?php echo $p_price; ?>" placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Unit Measurement <span class="text-danger">*</span></label>
                                <select name="unit" class="form-select form-select-lg fs-6 rounded-3" required>
                                    <option value="kg" <?php if($p_unit == 'kg') echo 'selected'; ?>>Per Kilogram (Kg)</option>
                                    <option value="piece" <?php if($p_unit == 'piece') echo 'selected'; ?>>Per Piece / Pack (Piece)</option>
                                    <option value="dozen" <?php if($p_unit == 'dozen') echo 'selected'; ?>>Per Dozen (Dozen)</option>
                                    <option value="gm" <?php if($p_unit == 'gm') echo 'selected'; ?>>Per Gram (Gm)</option>
                                    <option value="liter" <?php if($p_unit == 'liter') echo 'selected'; ?>>Per Liter (Liter)</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Stock Available <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light"><i class="bi bi-layers"></i></span>
                                    <input type="number" name="quantity" class="form-control fs-6" value="<?php echo $p_qty; ?>" placeholder="0" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-select form-select-lg fs-6 rounded-3" required>
                                    <option value="Vegetables" <?php if($p_cat == 'Vegetables') echo 'selected'; ?>>Vegetables</option>
                                    <option value="Fruits" <?php if($p_cat == 'Fruits') echo 'selected'; ?>>Fruits</option>
                                    <option value="Grocery" <?php if($p_cat == 'Grocery') echo 'selected'; ?>>Grocery & Pulses</option>
                                    <option value="Spices" <?php if($p_cat == 'Spices') echo 'selected'; ?>>Spices & Herbs</option>
                                    <option value="Organic" <?php if($p_cat == 'Organic') echo 'selected'; ?>>Organic Products</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Media Preview & Actions -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-4 bg-white sticky-top" style="top: 85px;">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-image text-warning me-2"></i>Product Media
                        </h5>
                    </div>
                    <div class="card-body p-4 pt-0 text-center">
                        
                        <!-- Realtime Image Preview -->
                        <div class="border rounded-4 p-3 bg-light mb-3 position-relative overflow-hidden" style="min-height: 220px; display: flex; align-items: center; justify-content: center;">
                            <img id="editImagePreview" src="<?php echo $current_img_src; ?>" 
                                 class="img-fluid rounded-3 shadow-sm" 
                                 style="max-height: 190px; object-fit: contain; background: #ffffff;">
                        </div>

                        <div class="mb-3">
                            <label for="imgFileInput" class="btn btn-outline-primary w-100 py-2 rounded-3 fw-semibold">
                                <i class="bi bi-camera me-2"></i>Choose New Image
                            </label>
                            <input type="file" name="product_image" id="imgFileInput" class="d-none" accept="image/*" onchange="previewEditImage(this)">
                            <small class="text-muted d-block mt-2" style="font-size: 12px;">Leave empty to keep the existing image</small>
                        </div>

                        <hr class="my-3">

                        <!-- Update Button -->
                        <button type="submit" name="update_product" class="btn btn-warning w-100 py-3 rounded-pill fw-bold text-dark fs-5 shadow-sm">
                            <i class="bi bi-check2-circle me-2"></i>Save Changes
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
function previewEditImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('editImagePreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'footer.php'; ?>