<?php
session_start();
include 'db.php';

// ডাটাবেস টেবিল স্ট্রাকচার নিশ্চিত করা (যাতে কোনো এরর না আসে)
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS username VARCHAR(100) NULL");
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS password VARCHAR(255) NULL");
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(20) DEFAULT 'buyer'");

// ইতিমধ্যে লগইন থাকলে রোলের ভিত্তিতে পাঠানো
if (isset($_SESSION['user_name'])) {
    if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'seller' || $_SESSION['user_role'] === 'admin')) {
        header("Location: seller_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$msg = "";
$msg_type = "";

if (isset($_POST['login_btn'])) {
    $input_user = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password   = mysqli_real_escape_string($conn, trim($_POST['password']));

    if (!empty($input_user) && !empty($password)) {
        // সব সম্ভাব্য ফিল্ডে চেক করা (username, name, email)
        $sql = "SELECT * FROM users WHERE (username = '$input_user' OR name = '$input_user' OR email = '$input_user') LIMIT 1";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);

            // পাসওয়ার্ড ফিল্ড চেক (password বা pass কলাম)
            $db_pass = isset($user['password']) ? $user['password'] : (isset($user['pass']) ? $user['pass'] : '');

            if ($db_pass === $password) {
                // ইউজারের নাম সেট
                $display_name = !empty($user['username']) ? $user['username'] : (!empty($user['name']) ? $user['name'] : $input_user);
                $_SESSION['user_name'] = $display_name;

                // রোল সেট (seller / admin / buyer)
                $user_role = !empty($user['role']) ? strtolower(trim($user['role'])) : 'buyer';
                $_SESSION['user_role'] = $user_role;

                $_SESSION['msg'] = "Welcome " . htmlspecialchars($display_name) . "! Login successfully";
                $_SESSION['msg_type'] = "success";

                if ($user_role === 'seller' || $user_role === 'admin') {
                    header("Location: seller_dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $msg = "Wrong password!";
                $msg_type = "danger";
            }
        } else {
            $msg = "Enter a valid username!";
            $msg_type = "danger";
        }
    } else {
        $msg = "Fill all the boxes correctly!";
        $msg_type = "warning";
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>লগইন | Ad-Kart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f1f5f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">

            <!-- অ্যালার্ট মেসেজ -->
            <?php if (isset($_SESSION['msg'])) { ?>
                <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show rounded-3 shadow-sm mb-3" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <?php 
                        echo $_SESSION['msg']; 
                        unset($_SESSION['msg']);
                        unset($_SESSION['msg_type']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php } ?>

            <?php if (!empty($msg)) { ?>
                <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show rounded-3 shadow-sm mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php } ?>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white text-center py-4">
                    <h4 class="fw-bold mb-1 text-warning"><i class="bi bi-box-arrow-in-right"></i> Ad-Kart Login</h4>
                    <p class="small text-white-50 mb-0">Buyer and Seller Account login</p>
                </div>

                <div class="card-body p-4 bg-white">
                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">User name / Name / Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person text-muted"></i></span>
                                <input type="text" name="username" class="form-control" placeholder="Enter your Userid" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-secondary">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock text-muted"></i></span>
                                <input type="password" name="password" class="form-control" placeholder="Enter the password" required>
                            </div>
                        </div>

                        <button type="submit" name="login_btn" class="btn btn-warning w-100 py-2 fw-bold rounded-3 shadow-sm text-dark">
                            Log in
                        </button>
                    </form>

                    <div class="text-center mt-4 border-top pt-3">
                        <span class="text-muted small">New user?</span> 
                        <a href="register.php" class="text-decoration-none fw-bold text-primary">Create a new account</a>
                    </div>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left"></i> Return to the Home page</a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>