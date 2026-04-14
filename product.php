<?php
session_start();
include_once __DIR__ . '/includes/db_con.php';
include_once __DIR__ . '/includes/header.php'; // site header

// Get subcategory ID from URL
$subid = isset($_GET['subid']) ? intval($_GET['subid']) : 0;

if ($subid <= 0) {
    echo "<div class='container py-5'><div class='alert alert-warning'>Subcategory not specified.</div></div>";
    include_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch subcategory info
$subcatRes = mysqli_query($connection, "SELECT * FROM SubCategory WHERE SubCategoryID=$subid");
$subcat = mysqli_fetch_assoc($subcatRes);
if (!$subcat) {
    echo "<div class='container py-5'><div class='alert alert-danger'>Subcategory not found.</div></div>";
    include_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch products of this subcategory
$productsRes = mysqli_query($connection, "SELECT * FROM Product WHERE SubCategoryID=$subid AND IsActive=1 ORDER BY ProductName ASC");

// Helper to get product image from ImageURL
function getProductImage($product) {
    if (!empty($product['ImageURL'])) {
        $url = $product['ImageURL'];
        return '/OnlineSupermarketDB/' . ltrim($url, '/');
    }
    return '/OnlineSupermarketDB/assets/images/placeholder.jpg';
}
?>

<div class="container py-5">
    <h2 class="mb-4"><?= htmlspecialchars($subcat['SubCategoryName']) ?></h2>
    <div id="toastBox"></div>

    <?php if (mysqli_num_rows($productsRes) == 0): ?>
        <div class="alert alert-warning">No products found in this subcategory.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php while($p = mysqli_fetch_assoc($productsRes)) : 
                $img = getProductImage($p);
            ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="card shadow product-card h-100 d-flex flex-column">
                        <img src="<?= $img ?>" class="card-img-top" style="height:180px; object-fit:cover;">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <h5 class="card-title"><?= htmlspecialchars($p['ProductName']) ?></h5>
                                <p class="text-muted"><?= htmlspecialchars($p['Description']) ?></p>
                                <p class="fw-bold">৳ <?= number_format($p['Price'], 2) ?></p>
                            </div>

                            <?php
                            $isInCart = false;
                            if (isset($_SESSION['UserID'])) {
                                $uid = (int)$_SESSION['UserID'];
                                $pid = (int)$p['ProductID'];
                                $chk = mysqli_query($connection, "SELECT CartID FROM Cart WHERE UserID=$uid AND ProductID=$pid");
                                $isInCart = mysqli_num_rows($chk) > 0;
                            }
                            ?>
                            <div class="d-flex gap-2 flex-wrap">
                                <?php if ($isInCart): ?>
                                    <a href="cart.php" class="btn btn-custom">View Cart</a>
                                <?php else: ?>
                                    <button class="btn btn-custom addCartBtn" data-id="<?= $p['ProductID'] ?>">Add to Cart</button>
                                <?php endif; ?>
                                <!-- View button connected -->
                                <a href="product_details.php?id=<?= $p['ProductID'] ?>" class="btn btn-outline-dark">View</a>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function showToast(msg) {
    const box = document.getElementById("toastBox");
    const div = document.createElement("div");
    div.classList.add("myToast");
    div.textContent = msg;
    box.appendChild(div);
    setTimeout(() => div.remove(), 3000);
}

$(document).on("click", ".addCartBtn", function () {
    let btn = $(this);
    let product_id = btn.data("id");

    $.post("cart_actions.php", { product_id: product_id }, function (res) {
        if (res.status === "success" || res.status === "exists") {
            showToast(res.message);
            btn.replaceWith(`<a href="cart.php" class="btn btn-custom">View Cart</a>`);
        } else if (res.status === "login") {
            showToast("Please login first");
        } else {
            showToast(res.message || "Something went wrong");
        }
    }, 'json').fail(function(){
        showToast("Network error");
    });
});
</script>

<style>
#toastBox {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 99999;
}
.myToast {
    background: #34495e;
    color: white;
    padding: 12px 18px;
    margin-top: 10px;
    border-radius: 8px;
    animation: fadeInOut 3s forwards;
    font-size: 15px;
}
@keyframes fadeInOut {
    0% { opacity: 0; transform: translateY(-10px); }
    10% { opacity: 1; transform: translateY(0); }
    90% { opacity: 1; }
    100% { opacity: 0; transform: translateY(-10px); }
}

.product-card {
    height: 100%;
    display: flex;
    flex-direction: column;
}
.product-card img {
    height: 180px;
    object-fit: cover;
}

.btn-custom {
    background-color: #34495e;
    color: white;
    width: auto;
    flex: 1;
}
</style>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
