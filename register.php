<?php
session_start();
include 'db.php';

$msg = "";
$msg_type = "";

if (isset($_POST['register_btn'])) {
    $username  = mysqli_real_escape_string($conn, trim($_POST['username']));
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $phone     = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $address   = mysqli_real_escape_string($conn, trim($_POST['address']));
    $password  = trim($_POST['password']);
    $role      = mysqli_real_escape_string($conn, $_POST['role']); // buyer অথবা seller

    // ডাটাবেসে role, full_name ইত্যাদি কলাম নিশ্চিত করা
    @mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(20) DEFAULT 'buyer'");
    @mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(100) NULL");
    @mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL");
    @mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT NULL");

    // ইউজারনেম আগে থেকে আছে কি না যাচাই
    $check = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check) > 0) {
        $msg = "এই ইউজারনেমটি ইতিমধ্যে ব্যবহৃত হয়েছে! দয়া করে অন্য একটি নাম দিন।";
        $msg_type = "danger";
    } else {
        $sql = "INSERT INTO users (username, password, full_name, phone, address, role) 
                VALUES ('$username', '$password', '$full_name', '$phone', '$address', '$role')";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['msg'] = "অ্যাকাউন্ট সফলভাবে তৈরি হয়েছে! এখন লগইন করুন।";
            $_SESSION['msg_type'] = "success";
            header("Location: login.php");
            exit();
        } else {
            $msg = "রেজিস্ট্রেশন সম্পন্ন হতে সমস্যা হয়েছে!";
            $msg_type = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>রেজিস্ট্রেশন | Ad-Kart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f1f5f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            
            <?php if (!empty($msg)) { ?>
                <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i> <?php echo $msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php } ?>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white text-center py-4">
                    <h4 class="fw-bold mb-1 text-warning"><i class="bi bi-person-plus-fill"></i> নতুন অ্যাকাউন্ট তৈরি করুন</h4>
                    <p class="small text-white-50 mb-0">Ad-Kart ফ্রেশ গ্রোসারি শপে স্বাগতম</p>
                </div>
                
                <div class="card-body p-4 bg-white">
                    <form action="register.php" method="POST">
                        
                        <!-- রোল সিলেকশন (ক্রেতা নাকি বিক্রেতা) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">অ্যাকাউন্টের ধরন <span class="text-danger">*</span></label>
                            <select name="role" class="form-select bg-light border-1" required>
                                <option value="buyer" selected>🛍️ ক্রেতা / কাস্টমার (Buyer)</option>
                                <option value="seller">🏪 বিক্রেতা / সেলার (Seller)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">পুরো নাম <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" placeholder="আপনার সম্পূর্ণ নাম লিখুন" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">ইউজারনেম <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" placeholder="লগইন করার ইউনিক নাম" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">মোবাইল নম্বর <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" placeholder="যেমন: +91 0000000000" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">ডেলিভারির ঠিকানা</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="বাড়ি নং, রাস্তা, এলাকা, জেলা"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-secondary">পাসওয়ার্ড <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="গোপন পাসওয়ার্ড দিন" required>
                        </div>

                        <button type="submit" name="register_btn" class="btn btn-warning w-100 py-2 fw-bold rounded-3 shadow-sm text-dark">
                            অ্যাকাউন্ট তৈরি করুন
                        </button>
                    </form>

                    <div class="text-center mt-4 border-top pt-3">
                        <span class="text-muted small">আগে থেকেই অ্যাকাউন্ট আছে?</span> 
                        <a href="login.php" class="text-decoration-none fw-bold text-primary">এখানে লগইন করুন</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>