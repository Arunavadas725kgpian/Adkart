<?php
session_start();
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);

    // ১. ডিলিট করার আগে ছবিটির নাম বের করা
    $get_img = mysqli_query($conn, "SELECT image FROM products WHERE ID = $product_id OR id = $product_id LIMIT 1");
    if ($get_img && mysqli_num_rows($get_img) > 0) {
        $row = mysqli_fetch_assoc($get_img);
        $img_name = $row['image'];

        // ফোল্ডার থেকে ফাইল ডিলিট করা
        if (!empty($img_name)) {
            $file_path = __DIR__ . "/image/" . $img_name;
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
        }
    }

    // ২. ডাটাবেস থেকে পণ্যটি মুছে ফেলা
    $delete_sql = "DELETE FROM products WHERE ID = $product_id OR id = $product_id";
    if (mysqli_query($conn, $delete_sql)) {
        $_SESSION['msg'] = "পণ্যটি সফলভাবে ডিলিট করা হয়েছে!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "পণ্যটি ডিলিট করতে সমস্যা হয়েছে!";
        $_SESSION['msg_type'] = "danger";
    }
}

// যে পেজ থেকে ডিলিট করা হয়েছে সেখানে ফিরিয়ে দেওয়া
if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    header("Location: seller_dashboard.php");
}
exit();
?>