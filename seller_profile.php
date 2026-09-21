<?php
session_start();
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'seller' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

include 'db.php';

// Automatically add banking and profile columns to users table if missing
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(150) NULL");
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(150) NULL");
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS store_name VARCHAR(150) NULL");
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(30) NULL");
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS showroom_address TEXT NULL");
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS trade_license VARCHAR(100) NULL");
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS pan_gst_no VARCHAR(100) NULL");
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS bank_name VARCHAR(150) NULL");
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS account_holder VARCHAR(150) NULL");
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS account_number VARCHAR(100) NULL");
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS ifsc_code VARCHAR(50) NULL");
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS upi_id VARCHAR(100) NULL");

$curr_user = $_SESSION['user_name'];
$msg = "";
$msg_type = "";

// 1. Handle Profile & Business Information Update
if (isset($_POST['update_profile'])) {
    $full_name  = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email      = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone      = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $store_name = mysqli_real_escape_string($conn, trim($_POST['store_name']));
    $showroom   = mysqli_real_escape_string($conn, trim($_POST['showroom_address']));
    $license    = mysqli_real_escape_string($conn, trim($_POST['trade_license']));
    $pan_gst    = mysqli_real_escape_string($conn, trim($_POST['pan_gst_no']));

    $sql = "UPDATE users SET 
                full_name = '$full_name',
                email = '$email',
                phone = '$phone',
                store_name = '$store_name',
                showroom_address = '$showroom',
                trade_license = '$license',
                pan_gst_no = '$pan_gst'
            WHERE username = '$curr_user'";

    if (mysqli_query($conn, $sql)) {
        $msg = "Profile and business information updated successfully!";
        $msg_type = "success";
    } else {
        $msg = "Error updating profile: " . mysqli_error($conn);
        $msg_type = "danger";
    }
}

// 2. Handle Bank & Payout Information Update
if (isset($_POST['update_banking'])) {
    $bank_name   = mysqli_real_escape_string($conn, trim($_POST['bank_name']));
    $acc_holder  = mysqli_real_escape_string($conn, trim($_POST['account_holder']));
    $acc_number  = mysqli_real_escape_string($conn, trim($_POST['account_number']));
    $ifsc_code   = strtoupper(mysqli_real_escape_string($conn, trim($_POST['ifsc_code'])));
    $upi_id      = mysqli_real_escape_string($conn, trim($_POST['upi_id']));

    $sql = "UPDATE users SET 
                bank_name = '$bank_name',
                account_holder = '$acc_holder',
                account_number = '$acc_number',
                ifsc_code = '$ifsc_code',
                upi_id = '$upi_id'
            WHERE username = '$curr_user'";

    if (mysqli_query($conn, $sql)) {
        $msg = "Bank payout details saved securely!";
        $msg_type = "success";
    } else {
        $msg = "Error saving bank details: " . mysqli_error($conn);
        $msg_type = "danger";
    }
}

// Fetch current user details
$user_q = mysqli_query($conn, "SELECT * FROM users WHERE username = '$curr_user' LIMIT 1");
$u = mysqli_fetch_assoc($user_q);

include 'header.php';
?>

<div class="container py-3 mb-5">
    
    <!-- Profile Page Header -->
    <div class="card bg-dark text-white rounded-4 border-0 shadow-sm p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center fw-bold fs-3" style="width: 65px; height: 65px;">
                    <?php echo strtoupper(substr($curr_user, 0, 1)); ?>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-white"><?php echo htmlspecialchars($u['full_name'] ?? $curr_user); ?></h3>
                    <div class="small text-white-50">
                        Username: <b>@<?php echo htmlspecialchars($curr_user); ?></b> | Role: <span class="badge bg-warning text-dark">Verified Merchant</span>
                    </div>
                </div>
            </div>
            <a href="seller_dashboard.php" class="btn btn-outline-light rounded-pill px-4 fw-semibold shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> Go to Seller Central
            </a>
        </div>
    </div>

    <!-- Alert Notification -->
    <?php if (!empty($msg)) { ?>
        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> <?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <div class="row g-4">
        
        <!-- Left Column: Business & Owner Profile -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 rounded-4 bg-white p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-person-lines-fill text-primary me-2"></i>Owner & Business Details
                    </h5>
                    <span class="badge bg-light text-secondary border">Merchant Profile</span>
                </div>

                <form action="seller_profile.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Owner Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control rounded-3" value="<?php echo htmlspecialchars($u['full_name'] ?? ''); ?>" placeholder="e.g. Arunava Das" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Business Email</label>
                            <input type="email" name="email" class="form-control rounded-3" value="<?php echo htmlspecialchars($u['email'] ?? ''); ?>" placeholder="vendor@adkart.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Primary Contact Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control rounded-3" value="<?php echo htmlspecialchars($u['phone'] ?? ''); ?>" placeholder="+91 0000000000" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Public Store Name <span class="text-danger">*</span></label>
                            <input type="text" name="store_name" class="form-control rounded-3" value="<?php echo htmlspecialchars($u['store_name'] ?? 'Ad-Kart Fresh Hub'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Trade License / Reg. ID</label>
                            <input type="text" name="trade_license" class="form-control rounded-3" value="<?php echo htmlspecialchars($u['trade_license'] ?? ''); ?>" placeholder="TRD-WB-2026-XXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">PAN / GSTIN (Tax ID)</label>
                            <input type="text" name="pan_gst_no" class="form-control rounded-3 text-uppercase" value="<?php echo htmlspecialchars($u['pan_gst_no'] ?? ''); ?>" placeholder="19AAAAA0000A1Z5">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Showroom / Warehouse Address</label>
                            <textarea name="showroom_address" rows="3" class="form-control rounded-3" placeholder="Full warehouse dispatch location, unit number, market area, city"><?php echo htmlspecialchars($u['showroom_address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" name="update_profile" class="btn btn-dark rounded-pill px-4 fw-semibold shadow-sm">
                            <i class="bi bi-save me-1"></i> Save Profile Details
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Banking, Payouts & Verification -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 rounded-4 bg-white p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-bank text-success me-2"></i>Bank & Payout Details
                    </h5>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Direct Settlement</span>
                </div>
                <p class="small text-muted mb-3">Add your official bank account details to receive daily order settlements.</p>

                <form action="seller_profile.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" class="form-control rounded-3" value="<?php echo htmlspecialchars($u['bank_name'] ?? ''); ?>" placeholder="e.g. State Bank of India / HDFC" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Account Holder Name <span class="text-danger">*</span></label>
                        <input type="text" name="account_holder" class="form-control rounded-3" value="<?php echo htmlspecialchars($u['account_holder'] ?? ''); ?>" placeholder="Name as per Bank records" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Account Number <span class="text-danger">*</span></label>
                        <input type="text" name="account_number" class="form-control rounded-3" value="<?php echo htmlspecialchars($u['account_number'] ?? ''); ?>" placeholder="XXXX XXXX XXXX" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">IFSC Code <span class="text-danger">*</span></label>
                        <input type="text" name="ifsc_code" class="form-control text-uppercase rounded-3" value="<?php echo htmlspecialchars($u['ifsc_code'] ?? ''); ?>" placeholder="SBIN000XXXX" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">UPI ID for Instant Payouts</label>
                        <input type="text" name="upi_id" class="form-control rounded-3" value="<?php echo htmlspecialchars($u['upi_id'] ?? ''); ?>" placeholder="username@upi / mobile@okaxis">
                    </div>

                    <button type="submit" name="update_banking" class="btn btn-warning w-100 py-2 rounded-pill fw-bold text-dark shadow-sm">
                        <i class="bi bi-shield-lock-fill me-1"></i> Update Banking Details
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>

<?php include 'footer.php'; ?>