<?php
session_start();
// Redirect if not logged in
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

include_once __DIR__ . '/../includes/db_con.php';
include_once __DIR__ . '/includes/header.php'; // navbar header

?>

<!-- ⭐ Main Content Wrapper -->
<div class="container-fluid mt-3 admin-content">

<?php
// Helper function
function single_value($connection, $sql) {
    $res = mysqli_query($connection, $sql);
    if ($res && $row = mysqli_fetch_row($res)) return $row[0];
    return 0;
}

// Stats
$totalCustomers = single_value($connection, "SELECT COUNT(*) FROM Users WHERE Role='Customer'");
$totalOrders = single_value($connection, "SELECT COUNT(*) FROM Orders");
$totalCategories = single_value($connection, "SELECT COUNT(*) FROM Category");
$totalSubcats = single_value($connection, "SELECT COUNT(*) FROM SubCategory");
$totalProducts = single_value($connection, "SELECT COUNT(*) FROM Product");
$totalStock = single_value($connection, "SELECT COALESCE(SUM(Stock),0) FROM Product");
$pendingOrders = single_value($connection, "SELECT COUNT(*) FROM Orders WHERE Status='Pending'");

// Recent Orders (latest 5)
$recentOrders = mysqli_query($connection, "
    SELECT o.OrderID, u.Name AS CustomerName, o.TotalAmount, o.Status 
    FROM Orders o
    JOIN Users u ON o.UserID=u.UserID
    ORDER BY o.OrderDate DESC 
    LIMIT 5
");

// Low Stock Products (<10 units)
$lowStock = mysqli_query($connection, "
    SELECT ProductID, ProductName, Stock 
    FROM Product 
    WHERE Stock < 10 AND IsActive=1 
    ORDER BY Stock ASC
    LIMIT 10
");
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold brand-color mb-0">Dashboard</h1>
    <span class="text-muted d-none d-md-block">Welcome back, <?= htmlspecialchars($_SESSION['Name']) ?>!</span>
</div>

<!-- Stat Cards -->
<div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div>
                    <h6 class="text-muted mb-1">Total Customers</h6>
                    <h3 class="fw-bold brand-color mb-0"><?= number_format($totalCustomers) ?></h3>
                </div>
                <i class="bi bi-people-fill stat-card-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div>
                    <h6 class="text-muted mb-1">Total Orders</h6>
                    <h3 class="fw-bold brand-color mb-0"><?= number_format($totalOrders) ?></h3>
                    <small class="text-muted"><?= number_format($pendingOrders) ?> pending</small>
                </div>
                <i class="bi bi-receipt-cutoff stat-card-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div>
                    <h6 class="text-muted mb-1">Products</h6>
                    <h3 class="fw-bold brand-color mb-0"><?= number_format($totalProducts) ?></h3>
                    <small class="text-muted"><?= number_format($totalStock) ?> in stock</small>
                </div>
                <i class="bi bi-box-seam-fill stat-card-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div>
                    <h6 class="text-muted mb-1">Categories</h6>
                    <h3 class="fw-bold brand-color mb-0"><?= number_format($totalCategories) ?></h3>
                    <small class="text-muted"><?= number_format($totalSubcats) ?> sub-categories</small>
                </div>
                <i class="bi bi-tags-fill stat-card-icon"></i>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Row: Tables -->
<div class="row g-4">
    <!-- Low Stock Alerts -->
    <div class="col-12 col-lg-6">
        <div class="card table-card h-100">
            <div class="card-header">
                <h5 class="fw-bold brand-color mb-0">Low Stock Alerts</h5>
            </div>
            <div class="card-body p-0">
                <?php if(!$lowStock || mysqli_num_rows($lowStock) == 0): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                        <p class="mb-0 mt-2">All products have sufficient stock.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Product</th>
                                    <th scope="col">Stock</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($p = mysqli_fetch_assoc($lowStock)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['ProductName']) ?></td>
                                    <td>
                                        <span class="badge bg-danger-subtle text-danger-emphasis rounded-pill">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                            <?= $p['Stock'] ?> units
                                        </span>
                                    </td>
                                    <td>
                                        <a href="products.php?action=edit&id=<?= $p['ProductID'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="col-12 col-lg-6">
        <div class="card table-card h-100">
            <div class="card-header">
                <h5 class="fw-bold brand-color mb-0">Recent Orders</h5>
            </div>
            <div class="card-body p-0">
                <?php if(!$recentOrders || mysqli_num_rows($recentOrders) == 0): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-cart-x-fill fs-3"></i>
                        <p class="mb-0 mt-2">No recent orders found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Order ID</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($r = mysqli_fetch_assoc($recentOrders)): ?>
                                <tr>
                                    <td>#<?= $r['OrderID'] ?></td>
                                    <td><?= htmlspecialchars($r['CustomerName']) ?></td>
                                    <td>$<?= number_format($r['TotalAmount'], 2) ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = 'bg-secondary-subtle text-secondary-emphasis';
                                        if ($r['Status'] == 'Pending') $statusClass = 'bg-warning-subtle text-warning-emphasis';
                                        elseif ($r['Status'] == 'Confirmed') $statusClass = 'bg-primary-subtle text-primary-emphasis';
                                        elseif ($r['Status'] == 'Delivered') $statusClass = 'bg-success-subtle text-success-emphasis';
                                        elseif ($r['Status'] == 'Cancelled') $statusClass = 'bg-danger-subtle text-danger-emphasis';
                                        ?>
                                        <span class="badge <?= $statusClass ?> rounded-pill"><?= htmlspecialchars($r['Status']) ?></span>
                                    </td>
                                    <td>
                                        <a href="order_details.php?id=<?= $r['OrderID'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</div> <!-- container-fluid -->

<?php include_once __DIR__ . '/includes/footer.php'; ?>