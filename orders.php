<?php
// Include header and database connection
include_once "includes/header.php";
include_once "../includes/db_con.php";

// ----------------------------------------------
// UPDATE ORDER STATUS (when admin clicks dropdown)
// ----------------------------------------------
if (isset($_GET['update_status'])) {
    $orderID = intval($_GET['update_status']);
    $currentStatus = mysqli_real_escape_string($connection, $_GET['status']);

    // Update order status
    mysqli_query($connection, "UPDATE Orders SET Status='$currentStatus' WHERE OrderID=$orderID");

    // AUTO UPDATE PAYMENT STATUS
    if ($currentStatus === "Delivered") {
        mysqli_query($connection, "UPDATE Orders SET PaymentStatus='Paid' WHERE OrderID=$orderID");
    } else {
        mysqli_query($connection, "UPDATE Orders SET PaymentStatus='Pending' WHERE OrderID=$orderID");
    }

    // Refresh page
    echo "<script>window.location='orders.php';</script>";
}

// ------------------------------------------------
// FETCH ALL ORDERS + CUSTOMER NAME
// ------------------------------------------------
$orders = mysqli_query($connection, "
    SELECT o.*, u.Name as CustomerName 
    FROM Orders o 
    JOIN Users u ON o.UserID=u.UserID
    ORDER BY o.OrderDate DESC
");
?>

<style>
.btn-square { 
    display: inline-block; 
    padding: 6px 12px; 
    font-size: 0.85rem; 
    border: 1px solid #ccc; 
    background-color: #f8f9fa; 
    color: #495057; 
    text-align: center; 
    cursor: pointer; 
    transition: all 0.2s; 
    min-width: 75px;
}
.btn-square:hover { 
    background-color: #0d6efd; 
    color: white; 
}
.btn-square.view-btn { color: #0d6efd; }
.btn-square.view-btn:hover { background-color: #198754; color: white; }
.btn-square.delete-btn { color: #dc3545; }
.btn-square.delete-btn:hover { background-color: #dc3545; color: white; }
.status-badge { 
    display: inline-block; 
    padding: 0.25rem 0.5rem; 
    font-size: 0.75rem; 
    border: 1px solid #ccc; 
    background-color: #f8f9fa; 
    color: #495057; 
    text-align: center; 
    min-width: 60px; 
    transition: all 0.2s; 
}
.status-badge[data-active="1"]:hover { background-color: #198754; color: white; }
.status-badge[data-active="0"]:hover { background-color: #dc3545; color: white; }

@media (max-width: 768px) {
    .table td, .table th { font-size: 0.85rem; padding: 0.4rem; }
    .btn-square { font-size: 0.75rem; padding: 4px 8px; min-width: 65px; }
}

/* Main layout fix */
.main {
    width: 100%;
}
</style>

<!-- Flex container to fix footer -->
<div class="d-flex flex-column min-vh-100">
    <!-- MAIN CONTENT -->
    <div class="main flex-grow-1 p-4">
        <h2 class="mb-4 fw-bold" style="color: #34495e;">Manage Orders</h2>

        <div class="card shadow-sm" style="border-radius:10px;">
            <div class="card-body p-0">

                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead style="background: #e9ecef;">
                            <tr>
                                <th>#</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php 
                            $i = 1; 
                            while ($o = mysqli_fetch_assoc($orders)): 
                            ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= $o['OrderID'] ?></td>
                                <td><?= htmlspecialchars($o['CustomerName']) ?></td>
                                <td><?= date("d M, Y", strtotime($o['OrderDate'])) ?></td>
                                <td>$<?= number_format($o['TotalAmount'], 2) ?></td>

                                <td>
                                    <span class="status-badge"><?= $o['Status'] ?></span>
                                </td>

                                <td>
                                    <span class="status-badge"><?= $o['PaymentStatus'] ?></span>
                                </td>

                                <td class="d-flex gap-2 flex-wrap">

                                    <a href="order_details.php?id=<?= $o['OrderID'] ?>" 
                                       class="btn-square view-btn btn-sm">
                                        View
                                    </a>

                                    <div class="dropdown">
                                        <button class="btn-square btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                            Change
                                        </button>

                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="?update_status=<?= $o['OrderID'] ?>&status=Pending">Pending</a></li>
                                            <li><a class="dropdown-item" href="?update_status=<?= $o['OrderID'] ?>&status=Confirmed">Confirmed</a></li>
                                            <li><a class="dropdown-item" href="?update_status=<?= $o['OrderID'] ?>&status=Delivered">Delivered</a></li>
                                            <li><a class="dropdown-item" href="?update_status=<?= $o['OrderID'] ?>&status=Cancelled">Cancelled</a></li>
                                        </ul>
                                    </div>

                                </td>

                            </tr>
                            <?php endwhile; ?>
                        </tbody>

                    </table>
                </div>

            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <?php include_once "includes/footer.php"; ?>
</div>