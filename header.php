<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_seller = (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'seller' || $_SESSION['user_role'] === 'admin'));
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ad-Kart | Multi-Vendor Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-brand { font-size: 1.5rem; letter-spacing: 0.5px; }
        .product-card { transition: transform 0.2s, box-shadow 0.2s; border-radius: 14px; background: #ffffff; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important; }
        .cursor-pointer { cursor: pointer; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="index.php">
            <i class="bi bi-basket3-fill"></i> Ad-Kart
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php"><i class="bi bi-house-door"></i> Shop</a>
                </li>
                
                <?php if ($is_seller) { ?>
                    <!-- Seller Center Shortcut for Operations -->
                    <li class="nav-item">
                        <a class="nav-link text-warning fw-bold" href="seller_dashboard.php">
                            <i class="bi bi-speedometer2"></i> Seller Central
                        </a>
                    </li>
                <?php } else { ?>
                    <!-- Buyer Links -->
                    <li class="nav-item">
                        <a class="nav-link" href="my_orders.php">
                            <i class="bi bi-bag-check"></i> My Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="bi bi-cart3"></i> Cart
                        </a>
                    </li>
                <?php } ?>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <?php if (!empty($user_name)) { ?>
                    <!-- Clicking Seller Name opens the Seller Account Profile -->
                    <a href="<?php echo $is_seller ? 'seller_profile.php' : 'my_orders.php'; ?>" class="text-light text-decoration-none small border border-secondary px-3 py-1 rounded-pill bg-dark bg-opacity-50">
                        <i class="bi bi-person-circle fs-6 text-warning align-middle me-1"></i> 
                        <b class="text-white"><?php echo htmlspecialchars($user_name); ?></b>
                        <span class="badge bg-<?php echo $is_seller ? 'warning text-dark' : 'success'; ?> ms-1">
                            <?php echo $is_seller ? 'Seller' : 'Buyer'; ?>
                        </span>
                    </a>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Logout</a>
                <?php } else { ?>
                    <a href="login.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Login</a>
                    <a href="register.php" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold">Register</a>
                <?php } ?>
            </div>
        </div>
    </div>
</nav>

<div class="container">