<?php
session_start();
include_once __DIR__ . '/includes/db_con.php';

// Redirect if not logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['UserID'];
$error = "";

// Check if user is active
$user_stmt = $connection->prepare("SELECT IsActive FROM Users WHERE UserID=? LIMIT 1");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_res = $user_stmt->get_result();
$user_data = $user_res->fetch_assoc();
$user_stmt->close();

if (!$user_data['IsActive']) {
    $error = "Your account is inactive. You cannot place orders. Please contact support.";
}

// Handle POST (placing order)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $address = mysqli_real_escape_string($connection, trim($_POST['address']));
    $payment_mode = $_POST['payment_mode'] ?? 'CashOnDelivery';

    if (!$address) {
        $error = "Delivery address is required.";
    } else {
        // Fetch cart items
        $cart_stmt = $connection->prepare(
            "SELECT c.CartID, c.Quantity, p.ProductID, p.ProductName, p.Price, p.ImageURL
             FROM Cart c
             JOIN Product p ON c.ProductID=p.ProductID
             WHERE c.UserID=?"
        );
        $cart_stmt->bind_param("i", $user_id);
        $cart_stmt->execute();
        $cart_res = $cart_stmt->get_result();

        if ($cart_res->num_rows === 0) {
            $error = "Your cart is empty.";
        } else {
            $total = 0;
            $cart_items = [];
            while ($item = $cart_res->fetch_assoc()) {
                $cart_items[] = $item;
                $total += $item['Price'] * $item['Quantity'];
            }

            // Insert order
            $order_stmt = $connection->prepare("INSERT INTO Orders (UserID, TotalAmount, DeliveryAddress) VALUES (?,?,?)");
            $order_stmt->bind_param("ids", $user_id, $total, $address);
            $order_stmt->execute();
            $order_id = $connection->insert_id;
            $order_stmt->close();

            // Insert order details
            $detail_stmt = $connection->prepare(
                "INSERT INTO OrderDetails (OrderID, ProductID, Quantity, PriceAtOrder) VALUES (?,?,?,?)"
            );
            foreach ($cart_items as $item) {
                $detail_stmt->bind_param("iiid", $order_id, $item['ProductID'], $item['Quantity'], $item['Price']);
                $detail_stmt->execute();
            }
            $detail_stmt->close();

            // Insert payment
            $payment_stmt = $connection->prepare(
                "INSERT INTO Payment (OrderID, PaymentMode, Amount, PaymentStatus) VALUES (?,?,?,?)"
            );
            $status = 'Pending';
            $payment_stmt->bind_param("isds", $order_id, $payment_mode, $total, $status);
            $payment_stmt->execute();
            $payment_stmt->close();

            // Clear cart
            $clear_stmt = $connection->prepare("DELETE FROM Cart WHERE UserID=?");
            $clear_stmt->bind_param("i", $user_id);
            $clear_stmt->execute();
            $clear_stmt->close();

            header("Location: order_confirmation.php?order_id=$order_id");
            exit;
        }
    }
}

// Include header
include_once __DIR__ . '/includes/header.php';

// Fetch cart items for display
$cart_stmt = $connection->prepare(
    "SELECT c.CartID, c.Quantity, p.ProductID, p.ProductName, p.Price, p.ImageURL
     FROM Cart c
     JOIN Product p ON c.ProductID=p.ProductID
     WHERE c.UserID=?"
);
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_res = $cart_stmt->get_result();
?>

<div class="container py-5">
    <h3 class="mb-4">Checkout</h3>

    <?php if($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$error && $cart_res && $cart_res->num_rows > 0): ?>
        <form method="POST">
            <div class="mb-4">
                <label class="form-label fw-bold">Delivery Address <span class="text-danger">*</span></label>
                <textarea name="address" class="form-control" rows="3" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Payment Method <span class="text-danger">*</span></label>
                <select name="payment_mode" class="form-select" required>
                    <option value="CashOnDelivery" <?= (($_POST['payment_mode'] ?? '') === 'CashOnDelivery') ? 'selected' : '' ?>>Cash on Delivery</option>
                    <option value="Card" <?= (($_POST['payment_mode'] ?? '') === 'Card') ? 'selected' : '' ?>>Card</option>
                </select>
            </div>

            <h5 class="mb-3">Order Summary</h5>
            <div class="table-responsive">
            <table class="table table-bordered align-middle text-center">
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
                    while ($item = $cart_res->fetch_assoc()):
                        $total = $item['Price'] * $item['Quantity'];
                        $grand_total += $total;
                    ?>
                    <tr>
                        <td class="text-start">
                            <img src="<?= htmlspecialchars($item['ImageURL'] ?: '/OnlineSupermarketDB/assets/images/placeholder.jpg') ?>" 
                                 alt="<?= htmlspecialchars($item['ProductName']) ?>" 
                                 style="width:60px;height:60px;object-fit:cover;margin-right:10px;">
                            <?= htmlspecialchars($item['ProductName']) ?>
                        </td>
                        <td><?= number_format($item['Price'],2) ?> BDT</td>
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
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary" style="background:#1d3557;border:none;">Place Order</button>
            </div>
        </form>
    <?php elseif (!$error): ?>
        <div class="alert alert-info">Your cart is empty.</div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
