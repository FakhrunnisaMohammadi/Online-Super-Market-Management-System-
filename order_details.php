<?php
include_once "includes/header.php";

include_once "../includes/db_con.php";

if (!isset($_GET['id'])) {
    echo "<script>window.location='orders.php';</script>";
    exit;
}

$orderID = intval($_GET['id']);

// Fetch order info
$orderRes = mysqli_query($connection, "
    SELECT o.*, u.Name AS CustomerName, u.Email, u.Phone, u.Address 
    FROM Orders o
    JOIN Users u ON o.UserID=u.UserID
    WHERE o.OrderID=$orderID
");
$order = mysqli_fetch_assoc($orderRes);

// Fetch order products
$orderItems = mysqli_query($connection, "
    SELECT od.*, p.ProductName 
    FROM OrderDetails od
    JOIN Product p ON od.ProductID=p.ProductID
    WHERE od.OrderID=$orderID
");
?>

<div class="main p-4">
    <h2 class="mb-4 fw-bold" style="color:#1D3557;">Order Details #<?= $order['OrderID'] ?></h2>

    <!-- Order & Customer Info -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm p-3">
                <h5>Order Info</h5>
                <p><strong>Date:</strong> <?= date("d M, Y", strtotime($order['OrderDate'])) ?></p>
                <p><strong>Status:</strong> 
                    <?php 
                    $statusColors = [
                        'Pending' => 'bg-warning text-dark',
                        'Confirmed' => 'bg-primary',
                        'Delivered' => 'bg-success',
                        'Cancelled' => 'bg-danger'
                    ];
                    ?>
                    <span class="badge <?= $statusColors[$order['Status']] ?>"><?= $order['Status'] ?></span>
                </p>
                <p><strong>Payment Status:</strong> 
                    <?php 
                    $paymentColors = [
                        'Pending' => 'bg-warning text-dark',
                        'Paid' => 'bg-success',
                        'Failed' => 'bg-danger'
                    ];
                    ?>
                    <span class="badge <?= $paymentColors[$order['PaymentStatus']] ?>"><?= $order['PaymentStatus'] ?></span>
                </p>
                <p><strong>Delivery Address:</strong> <?= $order['DeliveryAddress'] ?></p>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card shadow-sm p-3">
                <h5>Customer Info</h5>
                <p><strong>Name:</strong> <?= $order['CustomerName'] ?></p>
                <p><strong>Email:</strong> <?= $order['Email'] ?></p>
                <p><strong>Phone:</strong> <?= $order['Phone'] ?></p>
                <p><strong>Address:</strong> <?= $order['Address'] ?></p>
            </div>
        </div>
    </div>

    <!-- Order Products Table -->
    <div class="card shadow-sm p-3">
        <h5 class="mb-3">Products in Order</h5>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead style="background:#f1f3f5;">
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i=1; $total=0; while($item=mysqli_fetch_assoc($orderItems)): 
                        $subtotal = $item['Quantity'] * $item['PriceAtOrder'];
                        $total += $subtotal;
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= $item['ProductName'] ?></td>
                        <td><?= $item['Quantity'] ?></td>
                        <td>$<?= number_format($item['PriceAtOrder'],2) ?></td>
                        <td>$<?= number_format($subtotal,2) ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <tr>
                        <td colspan="4" class="text-end fw-bold">Total</td>
                        <td class="fw-bold">$<?= number_format($total,2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Back Button -->
    <div class="mt-3">
        <a href="orders.php" class="btn btn-secondary">← Back to Orders</a>
    </div>
</div>

<?php include_once "includes/footer.php"; ?>