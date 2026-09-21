<?php
session_start();
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

if (isset($_GET['order_id'])) {
    $target_order_id = mysqli_real_escape_string($conn, trim($_GET['order_id']));
    $curr_user = $_SESSION['user_name'];

    // অর্ডারটি খুঁজে বের করা
    $check_order = mysqli_query($conn, "SELECT * FROM orders WHERE (order_id = '$target_order_id' OR id = '$target_order_id') AND user_name = '$curr_user' AND status != 'Cancelled' LIMIT 1");

    if ($check_order && mysqli_num_rows($check_order) > 0) {
        $ord_data = mysqli_fetch_assoc($check_order);
        $real_order_id = !empty($ord_data['order_id']) ? $ord_data['order_id'] : $ord_data['id'];

        // ১. সেলারের পণ্য স্টকে পুনরায় ফিরিয়ে দেওয়া
        $items_q = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id = '$real_order_id'");
        if ($items_q && mysqli_num_rows($items_q) > 0) {
            while ($item = mysqli_fetch_assoc($items_q)) {
                $p_id = $item['product_id'];
                $qty  = intval($item['quantity']);
                @mysqli_query($conn, "UPDATE products SET Quantity = Quantity + $qty WHERE ID = '$p_id' OR id = '$p_id'");
            }
        }

        // ২. অর্ডার স্ট্যাটাস Cancelled করা
        mysqli_query($conn, "UPDATE orders SET status = 'Cancelled' WHERE order_id = '$real_order_id' OR id = '$real_order_id'");

        $_SESSION['msg'] = "অর্ডারটি সফলভাবে বাতিল করা হয়েছে এবং পণ্যগুলো স্টকে ফেরত গেছে।";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "অর্ডারটি বাতিল করা সম্ভব হয়নি!";
        $_SESSION['msg_type'] = "danger";
    }
}

header("Location: my_orders.php");
exit();
?>