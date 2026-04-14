<?php
session_start();
include_once __DIR__ . '/includes/db_con.php';
include_once __DIR__ . '/includes/header.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    echo "<div class='container py-5'><div class='alert alert-warning'>Product not specified.</div></div>";
    include_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch product info
$productRes = mysqli_query($connection, "SELECT * FROM Product WHERE ProductID=$product_id AND IsActive=1 LIMIT 1");
$product = mysqli_fetch_assoc($productRes);

if (!$product) {
    echo "<div class='container py-5'><div class='alert alert-danger'>Product not found.</div></div>";
    include_once __DIR__ . '/includes/footer.php';
    exit;
}

// Get image
$imageURL = !empty($product['ImageURL']) ? '/OnlineSupermarketDB/' . ltrim($product['ImageURL'], '/') : '/OnlineSupermarketDB/assets/images/placeholder.jpg';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-md-6">
            <img src="<?= $imageURL ?>" class="w-100 rounded shadow" style="object-fit:cover; max-height:500px;">
        </div>
        <div class="col-md-6">
            <h2><?= htmlspecialchars($product['ProductName']) ?></h2>
            <p class="text-muted"><?= htmlspecialchars($product['Description']) ?></p>
            <p class="fw-bold display-6">৳ <?= number_format($product['Price'], 2) ?></p>

            <?php
            $isInCart = false;
            $currentQty = 1;
            if (isset($_SESSION['UserID'])) {
                $uid = (int)$_SESSION['UserID'];
                $pid = (int)$product['ProductID'];
                $chk = mysqli_query($connection, "SELECT Quantity FROM Cart WHERE UserID=$uid AND ProductID=$pid");
                if (mysqli_num_rows($chk) > 0) {
                    $row = mysqli_fetch_assoc($chk);
                    $isInCart = true;
                    $currentQty = (int)$row['Quantity'];
                }
            }
            ?>
            <div class="d-flex gap-2 mt-3 align-items-center">
                <div class="input-group" style="width:120px;">
                    <button class="btn btn-outline-secondary qty-minus" type="button">−</button>
                    <input type="number" id="qtyInput" class="form-control text-center" value="<?= $currentQty ?>" min="1">
                    <button class="btn btn-outline-secondary qty-plus" type="button">+</button>
                </div>

                <?php if ($isInCart): ?>
                    <a href="cart.php" class="btn btn-custom">View Cart</a>
                <?php else: ?>
                    <button class="btn btn-custom addCartBtn" data-id="<?= $product['ProductID'] ?>">Add to Cart</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function updateCart(product_id, quantity, btn) {
    $.post("cart_actions.php", { product_id: product_id, quantity: quantity }, function (res) {
        if (res.status === "success" || res.status === "exists") {
            alert(res.message);
            btn.replaceWith(`<a href="cart.php" class="btn btn-custom">View Cart</a>`);
        } else if (res.status === "login") {
            alert("Please login first");
        } else {
            alert(res.message || "Something went wrong");
        }
    }, 'json').fail(function(){
        alert("Network error");
    });
}

$(document).on("click", ".addCartBtn", function () {
    let btn = $(this);
    let product_id = btn.data("id");
    let quantity = parseInt($("#qtyInput").val()) || 1;
    updateCart(product_id, quantity, btn);
});

$(document).on("click", ".qty-plus", function () {
    let input = $("#qtyInput");
    input.val(parseInt(input.val() || 0) + 1);
});

$(document).on("click", ".qty-minus", function () {
    let input = $("#qtyInput");
    let newVal = Math.max(1, parseInt(input.val() || 0) - 1);
    input.val(newVal);
});
</script>

<style>
.btn-custom {
    background-color: #34495e;
    color: white;
}
.input-group .btn {
    padding: 0.25rem 0.5rem;
}
</style>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
