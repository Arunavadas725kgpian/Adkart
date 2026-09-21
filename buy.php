<?php
session_start();
include 'db.php';

// লগইন চেক
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    $user_name = $_SESSION['user_name'];

    // ১. প্রোডাক্টের তথ্য নেওয়া
    $product_query = mysqli_query($conn, "SELECT * FROM products WHERE ID = $product_id");
    $product = mysqli_fetch_assoc($product_query);

    if ($product) {
        $p_name = $product['Name'];
        $total_price = $product['Price']; // ১টি পণ্যের মোট দাম

        // ২. orders টেবিলে Total_price কলামে ডাটা ইনসার্ট করা
        $order_sql = "INSERT INTO orders (user_name, product_name, Total_price) VALUES ('$user_name', '$p_name', '$total_price')";
        
        if (mysqli_query($conn, $order_sql)) {
            // ৩. স্টক থেকে ১টি কমানো
            mysqli_query($conn, "UPDATE products SET Quantity = Quantity - 1 WHERE ID = $product_id");

            header("Location: my_orders.php");
            exit();
        } else {
            echo "অর্ডার করতে সমস্যা হয়েছে: " . mysqli_error($conn);
        }
    }
}
?>