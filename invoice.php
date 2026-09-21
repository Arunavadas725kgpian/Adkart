<?php
session_start();
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

if (!isset($_GET['id'])) {
    header("Location: my_orders.php");
    exit();
}

$order_id = $_GET['id'];
$user_name = $_SESSION['user_name'];
$is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');

// অর্ডার খোঁজা
if ($is_admin) {
    $res = mysqli_query($conn, "SELECT * FROM orders WHERE id = '$order_id' OR ID = '$order_id' LIMIT 1");
} else {
    $res = mysqli_query($conn, "SELECT * FROM orders WHERE (id = '$order_id' OR ID = '$order_id') AND user_name = '$user_name' LIMIT 1");
}

$order = mysqli_fetch_assoc($res);

if (!$order) {
    echo "অর্ডারটি পাওয়া যায়নি!";
    exit();
}

// দাম ও স্ট্যাটাস বের করা
$total_price = 0;
foreach ($order as $col => $val) {
    if (stripos($col, 'price') !== false && !empty($val)) {
        $total_price = $val;
        break;
    }
}
$status = isset($order['status']) ? $order['status'] : (isset($order['Status']) ? $order['Status'] : 'Pending');
$customer = isset($order['user_name']) ? $order['user_name'] : 'Customer';
$product_name = isset($order['product_name']) ? $order['product_name'] : 'Item';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $order_id; ?> - Ad Kart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            body { background-color: #fff; }
        }
        .invoice-box {
            max-width: 700px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body class="bg-light py-5">

<div class="invoice-box rounded">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
        <div>
            <h2 class="fw-bold text-warning mb-0">Ad-Kart</h2>
            <small class="text-muted">অনলাইন গ্রোসারি ও ফলমূলের দোকান</small>
        </div>
        <div class="text-end">
            <h4 class="fw-bold mb-0">INVOICE</h4>
            <span class="text-muted">অর্ডার নং: #<?php echo $order_id; ?></span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-6">
            <p class="mb-1 text-muted small">কাস্টমারের নাম:</p>
            <h5 class="fw-bold"><?php echo $customer; ?></h5>
        </div>
        <div class="col-6 text-end">
            <p class="mb-1 text-muted small">অর্ডার স্ট্যাটাস:</p>
            <span class="badge bg-secondary"><?php echo $status; ?></span>
        </div>
    </div>

    <table class="table table-bordered mb-4">
        <thead class="table-light">
            <tr>
                <th>আইটেমের বিবরণ</th>
                <th class="text-end">মোট মূল্য</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo $product_name; ?></td>
                <td class="text-end fw-bold">৳ <?php echo $total_price; ?></td>
            </tr>
            <tr>
                <td class="text-end fw-bold">সর্বমোট পরিশোধযোগ্য:</td>
                <td class="text-end fw-bold text-success fs-5">৳ <?php echo $total_price; ?></td>
            </tr>
        </tbody>
    </table>

    <div class="text-center text-muted small mb-4">
        <p>Ad-Kart থেকে কেনাকাটা করার জন্য ধন্যবাদ!</p>
    </div>

    <div class="d-flex justify-content-between no-print">
        <a href="my_orders.php" class="btn btn-outline-secondary">← ফিরে যান</a>
        <button onclick="window.print()" class="btn btn-primary">ইনভয়েস প্রিন্ট / PDF ডাউনলোড</button>
    </div>
</div>

</body>
</html>