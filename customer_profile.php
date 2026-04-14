<?php
include_once "includes/header.php";
include_once "../includes/db_con.php";

$customer = null;
$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ------------------------------------
// Toggle Account Status
// ------------------------------------
if (isset($_GET['toggle_status']) && $customer_id > 0) {
    $res = mysqli_query($connection, "SELECT IsActive FROM Users WHERE UserID=$customer_id");
    if ($res && $row = mysqli_fetch_assoc($res)) {
        $newStatus = $row['IsActive'] ? 0 : 1;
        mysqli_query($connection, "UPDATE Users SET IsActive=$newStatus WHERE UserID=$customer_id");
        echo "<script>alert('Status updated successfully!'); window.location='customer_profile.php?id=$customer_id';</script>";
        exit;
    }
}

// ------------------------------------
// Fetch Customer Profile
// ------------------------------------
if ($customer_id > 0) {
    $result = mysqli_query($connection, "SELECT * FROM Users WHERE UserID=$customer_id AND Role='Customer'");
    if ($result && mysqli_num_rows($result) > 0) {
        $customer = mysqli_fetch_assoc($result);

        // Order statistics
        $orderStatsRes = mysqli_query($connection,
            "SELECT COUNT(*) AS totalOrders, COALESCE(SUM(TotalAmount),0) AS totalSpent, MAX(OrderDate) AS lastOrderDate
             FROM Orders
             WHERE UserID=$customer_id"
        );
        $orderStats = $orderStatsRes ? mysqli_fetch_assoc($orderStatsRes) : [];

        // Total products purchased
        $prodRes = mysqli_query($connection,
            "SELECT COALESCE(SUM(od.Quantity),0) AS totalProducts
             FROM Orders o
             JOIN OrderDetails od ON o.OrderID = od.OrderID
             WHERE o.UserID=$customer_id"
        );
        $prodInfo = $prodRes ? mysqli_fetch_assoc($prodRes) : [];
    }
}
?>

<main class="admin-content p-4">

<?php if (!$customer): ?>
    <div class="alert alert-danger text-center p-5 rounded-3 shadow-sm">
        <i class="bi bi-x-octagon-fill display-4 mb-3"></i>
        <h4 class="alert-heading">Customer Not Found</h4>
        <a href="customers.php" class="btn btn-primary mt-3">Back to List</a>
    </div>

<?php else:
    $isActive = (bool)$customer['IsActive'];
?>
    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h2 class="fw-bold brand-color m-0">
            Customer Profile: <?= htmlspecialchars($customer['Name']) ?>
        </h2>

        <a href="customers.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <!-- CUSTOMER INFORMATION -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="m-0"><i class="bi bi-person-lines-fill me-2"></i>Account Information</h5>
        </div>

        <div class="card-body">
<dl class="row mb-0">
    <dt class="col-sm-4 text-muted">Customer ID</dt>
    <dd class="col-sm-8 fw-bold"><?= $customer['UserID'] ?></dd>

    <dt class="col-sm-4 text-muted">Full Name</dt>
    <dd class="col-sm-8"><?= htmlspecialchars($customer['Name']) ?></dd>

    <dt class="col-sm-4 text-muted">Email</dt>
    <dd class="col-sm-8"><?= htmlspecialchars($customer['Email']) ?></dd>

    <dt class="col-sm-4 text-muted">Phone</dt>
    <dd class="col-sm-8"><?= htmlspecialchars($customer['Phone'] ?? 'N/A') ?></dd>

    <dt class="col-sm-4 text-muted">Address</dt>
    <dd class="col-sm-8"><?= nl2br(htmlspecialchars($customer['Address'] ?? 'No address provided.')) ?></dd>

    <dt class="col-sm-4 text-muted">Registered On</dt>
    <dd class="col-sm-8"><?= date("d M, Y (H:i)", strtotime($customer['RegistrationDate'])) ?></dd>

    <dt class="col-sm-4 text-muted">Last Order</dt>
    <dd class="col-sm-8">
        <?= $orderStats['lastOrderDate'] ? date("d M, Y (H:i)", strtotime($orderStats['lastOrderDate'])) : 'N/A' ?>
    </dd>

    <dt class="col-sm-4 text-muted">Total Orders</dt>
    <dd class="col-sm-8"><?= $orderStats['totalOrders'] ?></dd>

    <dt class="col-sm-4 text-muted">Total Products</dt>
    <dd class="col-sm-8"><?= $prodInfo['totalProducts'] ?></dd>

    <dt class="col-sm-4 text-muted">Total Spent</dt>
    <dd class="col-sm-8">$<?= number_format($orderStats['totalSpent'], 2) ?></dd>
</dl>

            <!-- ACCOUNT STATUS -->
            <hr>
            <h6 class="text-muted">Account Status</h6>
            <div class="d-flex align-items-center">
                <?php if ($isActive): ?>
                    <span class="badge bg-success me-3 p-2">
                        <i class="bi bi-check-circle"></i> Active
                    </span>
                    <a href="?id=<?= $customer_id ?>&toggle_status=1" class="btn btn-warning btn-sm">
                        Deactivate
                    </a>
                <?php else: ?>
                    <span class="badge bg-secondary me-3 p-2">
                        <i class="bi bi-lock-fill"></i> Inactive
                    </span>
                    <a href="?id=<?= $customer_id ?>&toggle_status=1" class="btn btn-success btn-sm">
                        Activate
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ALL ORDERS -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="m-0"><i class="bi bi-bag-check-fill me-2"></i>All Orders (Newest → Oldest)</h5>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Total</th>
                            <th>View</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $ordersRes = mysqli_query($connection, 
                            "SELECT * FROM Orders WHERE UserID=$customer_id ORDER BY OrderDate DESC"
                        );

                        if (!$ordersRes || mysqli_num_rows($ordersRes) == 0): ?>
                            <tr><td colspan="7" class="text-center p-4 text-muted">No orders found.</td></tr>

                        <?php else:
                            $i = 1;
                            while ($order = mysqli_fetch_assoc($ordersRes)): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= $order['OrderID'] ?></td>
                                    <td><?= date("d M, Y", strtotime($order['OrderDate'])) ?></td>
                                    <td><?= htmlspecialchars($order['Status']) ?></td>
                                    <td><?= htmlspecialchars($order['PaymentStatus']) ?></td>
                                    <td>$<?= number_format($order['TotalAmount'], 2) ?></td>
                                    <td>
                                        <a href="order_details.php?id=<?= $order['OrderID'] ?>" class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endif; ?>
</main>

<?php include_once "includes/footer.php"; ?>
