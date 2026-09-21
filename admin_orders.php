<?php
session_start();
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'db.php';
include 'header.php';

// মালিকের স্ট্যাটাস পরিবর্তন লজিক
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    mysqli_query($conn, "UPDATE orders SET status = '$new_status' WHERE id = '$order_id' OR ID = '$order_id'");
    echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>
            অর্ডার #$order_id এর স্ট্যাটাস সফলভাবে '$new_status' করা হয়েছে!
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
          </div>";
}

$orders_res = mysqli_query($conn, "SELECT * FROM orders ORDER BY 1 DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold"><i class="bi bi-card-checklist"></i> সকল কাস্টমারের অর্ডার তালিকা (Owner Panel)</h3>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>অর্ডার আইডি</th>
                    <th>কাস্টমার ও যোগাযোগ</th>
                    <th>পণ্যের বিবরণ</th>
                    <th>মোট মূল্য</th>
                    <th>বর্তমান স্ট্যাটাস</th>
                    <th>স্ট্যাটাস আপডেট করুন</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders_res && mysqli_num_rows($orders_res) > 0) { 
                    while($row = mysqli_fetch_assoc($orders_res)) { 
                        $oid = reset($row);

                        $display_price = 0;
                        foreach ($row as $col => $val) {
                            if (stripos($col, 'price') !== false && !empty($val)) {
                                $display_price = $val;
                                break;
                            }
                        }

                        $status = 'Pending';
                        if (isset($row['status']) && !empty($row['status'])) {
                            $status = $row['status'];
                        } elseif (isset($row['Status']) && !empty($row['Status'])) {
                            $status = $row['Status'];
                        }

                        $user_display = isset($row['user_name']) ? $row['user_name'] : 'Customer';
                        $prod_display = isset($row['product_name']) ? $row['product_name'] : 'Product';
                        $phone = !empty($row['phone']) ? $row['phone'] : 'N/A';
                        $address = !empty($row['address']) ? $row['address'] : 'N/A';
                ?>
                <tr>
                    <td class="fw-bold">#<?php echo $oid; ?></td>
                    <td>
                        <div class="fw-bold"><?php echo $user_display; ?></div>
                        <small class="text-muted"><i class="bi bi-telephone"></i> <?php echo $phone; ?></small><br>
                        <small class="text-secondary"><i class="bi bi-geo-alt"></i> <?php echo $address; ?></small>
                    </td>
                    <td><?php echo $prod_display; ?></td>
                    <td class="fw-bold text-success">৳ <?php echo $display_price; ?></td>
                    <td>
                        <?php if($status == 'Delivered') { ?>
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Delivered</span>
                        <?php } elseif($status == 'Shipped') { ?>
                            <span class="badge bg-info text-dark"><i class="bi bi-truck"></i> Shipped</span>
                        <?php } elseif($status == 'Cancelled') { ?>
                            <span class="badge bg-danger">Cancelled</span>
                        <?php } else { ?>
                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Pending</span>
                        <?php } ?>
                    </td>
                    <td>
                        <form action="admin_orders.php" method="POST" class="d-flex gap-2">
                            <input type="hidden" name="order_id" value="<?php echo $oid; ?>">
                            <select name="status" class="form-select form-select-sm" style="width: 140px;">
                                <option value="Pending" <?php if($status=='Pending') echo 'selected'; ?>>Pending</option>
                                <option value="Shipped" <?php if($status=='Shipped') echo 'selected'; ?>>Shipped</option>
                                <option value="Delivered" <?php if($status=='Delivered') echo 'selected'; ?>>Delivered</option>
                                <option value="Cancelled" <?php if($status=='Cancelled') echo 'selected'; ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                        </form>
                    </td>
                </tr>
                <?php } } else { ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">কোনো অর্ডার পাওয়া যায়নি।</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>