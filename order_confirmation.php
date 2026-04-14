<?php
session_start();
include_once __DIR__ . '/includes/db_con.php';
include_once __DIR__ . '/includes/header.php';

if (!isset($_SESSION['UserID'])) {
    echo "<div class='container py-5'><div class='alert alert-warning'>Please login to view your orders.</div></div>";
    include_once __DIR__ . '/includes/footer.php';
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    echo "<div class='container py-5'><div class='alert alert-danger'>Invalid order ID.</div></div>";
    include_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch order
$order_sql = "SELECT * FROM Orders WHERE OrderID=$order_id AND UserID=".$_SESSION['UserID'];
$order_res = mysqli_query($connection, $order_sql);
$order = mysqli_fetch_assoc($order_res);

if (!$order) {
    echo "<div class='container py-5'><div class='alert alert-danger'>Order not found.</div></div>";
    include_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch order details
$details_sql = "SELECT od.*, p.ProductName, p.ImageURL
                FROM OrderDetails od
                JOIN Product p ON od.ProductID=p.ProductID
                WHERE od.OrderID=$order_id";
$details_res = mysqli_query($connection, $details_sql);
?>

<section class="container py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold" style="color:#1d3557;">🎉 Order Placed Successfully!</h2>
        <p>Your order ID is <strong>#<?= $order['OrderID'] ?></strong></p>
        <p>We have received your order and it is now <strong><?= htmlspecialchars($order['Status']) ?></strong>.</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Order Summary</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered text-center align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_total = 0;
                    while ($item = mysqli_fetch_assoc($details_res)):
                        $total = $item['PriceAtOrder'] * $item['Quantity'];
                        $grand_total += $total;
                    ?>
                    <tr>
                        <td class="text-start">
                            <img src="<?= htmlspecialchars($item['ImageURL'] ?: '/OnlineSupermarketDB/assets/images/placeholder.jpg') ?>" 
                                 style="width:60px;height:60px;object-fit:cover;" alt="<?= htmlspecialchars($item['ProductName']) ?>">
                            <?= htmlspecialchars($item['ProductName']) ?>
                        </td>
                        <td><?= number_format($item['PriceAtOrder'],2) ?> BDT</td>
                        <td><?= (int)$item['Quantity'] ?></td>
                        <td><?= number_format($total,2) ?> BDT</td>
                    </tr>
                    <?php endwhile; ?>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Grand Total:</td>
                        <td class="fw-bold"><?= number_format($grand_total,2) ?> BDT</td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-4 text-center">
                <p>Delivery Address:</p>
                <p><?= nl2br(htmlspecialchars($order['DeliveryAddress'])) ?></p>
                <a href="/OnlineSupermarketDB/index.php" class="btn btn-primary" style="background:#1d3557;border:none;">Back to Home</a>
            </div>
        </div>
    </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
