<?php
session_start();
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

if (isset($_POST['upload_btn'])) {
    $product_id = intval($_POST['product_id']);

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
        $orig_name = $_FILES['product_image']['name'];
        $file_tmp  = $_FILES['product_image']['tmp_name'];
        
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
        if (empty($ext)) {
            $ext = "jpg";
        }

        // প্রতিটা প্রোডাক্টের জন্য ইউনিক নাম তৈরি যাতে ছবি মিক্স না হয়
        $unique_img_name = "product_" . $product_id . "_" . time() . "." . $ext;
        
        $target_dir = __DIR__ . "/image/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $destination = $target_dir . $unique_img_name;

        if (move_uploaded_file($file_tmp, $destination)) {
            $update_sql = "UPDATE products SET image = '$unique_img_name' WHERE id = '$product_id' OR ID = '$product_id'";
            mysqli_query($conn, $update_sql);

            $_SESSION['msg'] = "পণ্যটির ছবি সফলভাবে আপলোড হয়েছে!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "ছবি ফোল্ডারে সেভ হতে ব্যর্থ হয়েছে!";
            $_SESSION['msg_type'] = "danger";
        }
    } else {
        $_SESSION['msg'] = "কোনো ছবি সিলেক্ট করা হয়নি!";
        $_SESSION['msg_type'] = "warning";
    }
}

header("Location: index.php");
exit();