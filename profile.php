<?php
session_start();
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'header.php';

$user_name = $_SESSION['user_name'];
$msg = "";
$msg_type = "";

// বর্তমান ইউজারের তথ্য আনা
$query = mysqli_query($conn, "SELECT * FROM users WHERE name = '$user_name' OR Name = '$user_name' LIMIT 1");
$user_info = mysqli_fetch_assoc($query);

// পাসওয়ার্ড পরিবর্তনের লজিক
if (isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $db_pass = isset($user_info['password']) ? $user_info['password'] : $user_info['Password'];
    $user_id = reset($user_info);

    // বর্তমান পাসওয়ার্ড যাচাই (হ্যাশ বা প্লেইন টেক্সট)
    if (!password_verify($current_pass, $db_pass) && $current_pass !== $db_pass) {
        $msg = "বর্তমান পাসওয়ার্ডটি সঠিক নয়!";
        $msg_type = "danger";
    } elseif ($new_pass !== $confirm_pass) {
        $msg = "নতুন পাসওয়ার্ড দুটি মিলছে না!";
        $msg_type = "danger";
    } elseif (strlen($new_pass) < 4) {
        $msg = "নতুন পাসওয়ার্ড অন্তত ৪ অক্ষরের হতে হবে!";
        $msg_type = "warning";
    } else {
        $hashed_new_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password = '$hashed_new_pass' WHERE id = '$user_id' OR ID = '$user_id'");
        $msg = "পাসওয়ার্ড সফলভাবে পরিবর্তন ও এনক্রিপ্ট করা হয়েছে!";
        $msg_type = "success";
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        
        <?php if (!empty($msg)) { ?>
            <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php } ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-person-badge"></i> ব্যবহারকারী প্রোফাইল</h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <label class="text-muted small">পুরো নাম</label>
                    <p class="fw-bold fs-5 mb-0"><?php echo isset($user_info['name']) ? $user_info['name'] : (isset($user_info['Name']) ? $user_info['Name'] : $user_name); ?></p>
                </div>
                <div class="mb-3">
                    <label class="text-muted small">ইমেইল ঠিকানা</label>
                    <p class="fw-bold mb-0"><?php echo isset($user_info['email']) ? $user_info['email'] : (isset($user_info['Email']) ? $user_info['Email'] : 'N/A'); ?></p>
                </div>
                <div>
                    <label class="text-muted small">অ্যাকাউন্টের ধরন (Role)</label>
                    <br>
                    <span class="badge <?php echo (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? 'bg-danger' : 'bg-primary'; ?> fs-6">
                        <?php echo strtoupper(isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'USER'); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-light py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-key"></i> পাসওয়ার্ড পরিবর্তন করুন</h5>
            </div>
            <div class="card-body p-4">
                <form action="profile.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">বর্তমান পাসওয়ার্ড</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">নতুন পাসওয়ার্ড</label>
                        <input type="password" name="new_password" class="form-control" required minlength="4">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">নতুন পাসওয়ার্ড পুনরায় লিখুন</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="4">
                    </div>

                    <button type="submit" name="change_password" class="btn btn-warning w-100 fw-bold py-2">
                        পাসওয়ার্ড আপডেট করুন
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>