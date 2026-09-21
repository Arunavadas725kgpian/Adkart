<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

// সেলার অ্যাকাউন্টে কার্ট নিষিদ্ধ করা
if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'seller' || $_SESSION['user_role'] === 'admin')) {
    $_SESSION['msg'] = "বিক্রেতা (Seller) অ্যাকাউন্ট থেকে কেনাকাটা করা যায় না। কার্ট ব্যবহারের জন্য ক্রেতা (Buyer) অ্যাকাউন্টে লগইন করুন।";
    $_SESSION['msg_type'] = "warning";
    header("Location: seller_dashboard.php");
    exit();
}

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);

    $result = mysqli_query($conn, "SELECT * FROM products WHERE ID = $product_id OR id = $product_id");
    $product = mysqli_fetch_assoc($result);

    if ($product) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }

        $prod_name  = !empty($product['Name']) ? $product['Name'] : $product['name'];
        $prod_price = !empty($product['Price']) ? $product['Price'] : $product['price'];
        $prod_image = !empty($product['image']) ? $product['image'] : '';

        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['qty'] += 1;
        } else {
            $_SESSION['cart'][$product_id] = array(
                'name'  => $prod_name,
                'price' => (int)$prod_price,
                'image' => $prod_image,
                'qty'   => 1
            );
        }

        $_SESSION['msg'] = "পণ্যটি সফলভাবে কার্টে যোগ হয়েছে!";
        $_SESSION['msg_type'] = "success";
    }
}

header("Location: cart.php");
exit();
?>