<?php
// cart.php
session_start();
include_once __DIR__ . '/includes/db_con.php';
include_once __DIR__ . '/includes/header.php';

if (!isset($_SESSION['UserID'])) {
    echo "<div class='container py-5'><div class='alert alert-warning'>Please login to view your cart.</div></div>";
    include_once __DIR__ . '/includes/footer.php';
    exit;
}

$user_id = (int)$_SESSION['UserID'];

$sql = "SELECT c.CartID, c.Quantity, p.ProductID, p.ProductName, p.Price, p.ImageURL
        FROM Cart c
        JOIN Product p ON c.ProductID = p.ProductID
        WHERE c.UserID = $user_id";
$res = mysqli_query($connection, $sql);
?>

<div class="container py-5">
    <h3>Your Cart</h3>
    <?php if ($res && mysqli_num_rows($res) > 0): ?>
        <table class="table table-bordered mt-4 align-middle text-center">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody id="cart-body">
            <?php 
            $grand_total = 0;
            while ($item = mysqli_fetch_assoc($res)):
                $total = (float)$item['Price'] * (int)$item['Quantity'];
                $grand_total += $total;
                $img = !empty($item['ImageURL']) ? $item['ImageURL'] : 'assets/images/placeholder.jpg';
            ?>
                <tr data-cartid="<?= $item['CartID'] ?>">
                    <td class="text-start">
                        <img src="<?= htmlspecialchars($img) ?>" style="width:60px;height:60px;object-fit:cover;" alt="">
                        <?= htmlspecialchars($item['ProductName']) ?>
                    </td>

                    <td class="price"><?= number_format($item['Price'],2) ?></td>

                    <td>
                        <div class="input-group input-group-sm justify-content-center" style="width:120px;">
                            <button class="btn btn-outline-secondary qty-minus" data-cartid="<?= $item['CartID'] ?>">−</button>
                            <input type="number" 
                                   class="form-control text-center quantity-input" 
                                   data-cartid="<?= $item['CartID'] ?>" 
                                   value="<?= (int)$item['Quantity'] ?>" 
                                   min="1">
                            <button class="btn btn-outline-secondary qty-plus" data-cartid="<?= $item['CartID'] ?>">+</button>
                        </div>
                    </td>

                    <td class="row-total"><?= number_format($total,2) ?></td>

                    <td>
                        <button class="btn btn-sm btn-danger remove-item" data-cartid="<?= $item['CartID'] ?>">Remove</button>
                    </td>
                </tr>
            <?php endwhile; ?>

                <tr class="table-secondary fw-bold">
                    <td colspan="3" class="text-end">Grand Total:</td>
                    <td id="grand-total"><?= number_format($grand_total,2) ?></td>
                    <td></td>
                </tr>

            </tbody>
        </table>

        <div class="text-end mt-3">
            <a href="checkout.php" class="btn btn-success" style="background:#1d3557;border:none;">Place Order</a>
        </div>

    <?php else: ?>
        <div class="alert alert-info mt-4">Your cart is empty.</div>
    <?php endif; ?>
</div>

<!-- AJAX SCRIPT -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
function showToast(msg){
    const box = document.getElementById('toastBoxGlobal') || createGlobalToastBox();
    const note = document.createElement('div');
    note.className = 'toast-msg';
    note.textContent = msg;
    box.appendChild(note);
    setTimeout(()=>{ note.style.opacity = '0'; setTimeout(()=> note.remove(),300); }, 1400);
}

function createGlobalToastBox(){
    const box = document.createElement('div');
    box.id = 'toastBoxGlobal';
    box.style.position = 'fixed';
    box.style.top = '20px';
    box.style.right = '20px';
    box.style.zIndex = 9999;
    document.body.appendChild(box);
    return box;
}

/* FIXED: remove commas before converting to number */
function recalcGrand() {
    let grand = 0;
    document.querySelectorAll('.row-total').forEach(function(el){
        const clean = el.textContent.replace(/,/g, ''); 
        const v = parseFloat(clean) || 0;
        grand += v;
    });
    document.getElementById('grand-total').textContent = grand.toFixed(2);
}

// quantity input -> AJAX update
$(document).on('input', '.quantity-input', function(){
    const cartID = $(this).data('cartid');
    let qty = parseInt($(this).val(), 10);
    if (isNaN(qty) || qty < 1) { qty = 1; $(this).val(qty); }

    $.post('cart_actions.php', { action: 'update', cart_id: cartID, quantity: qty }, function(resp){
        if (resp && resp.success) {

            /* FIXED: use server values directly (not parseFloat) */
            $('tr[data-cartid="'+cartID+'"] .row-total').text(resp.new_total);
            $('#grand-total').text(resp.grand_total);

            showToast('Cart updated');
        } else {
            showToast(resp.message || 'Update failed');
        }
    }, 'json').fail(function(){ showToast('Network error'); });
});

// plus button
$(document).on('click', '.qty-plus', function(){
    const input = $(this).siblings('.quantity-input');
    input.val(parseInt(input.val() || 0) + 1).trigger('input');
});

// minus button
$(document).on('click', '.qty-minus', function(){
    const input = $(this).siblings('.quantity-input');
    const newVal = Math.max(1, (parseInt(input.val() || 0) - 1));
    input.val(newVal).trigger('input');
});

// remove item
$(document).on('click', '.remove-item', function(){
    const cartID = $(this).data('cartid');

    $.post('cart_actions.php', { action: 'remove', cart_id: cartID }, function(resp){
        if (resp && resp.success) {
            $('tr[data-cartid="'+cartID+'"]').remove();

            $('#grand-total').text(resp.grand_total);

            showToast('Product removed from cart');
        } else {
            showToast(resp.message || 'Remove failed');
        }
    }, 'json').fail(function(){ showToast('Network error'); });
});
</script>

<style>
.toast-msg{
  background:#1d3557;
  color:#fff;
  padding:10px 14px;
  border-radius:6px;
  margin-top:8px;
  box-shadow:0 4px 12px rgba(0,0,0,0.12);
  transition: opacity .25s ease;
}
</style>

<?php include_once __DIR__ . '/includes/footer.php'; ?>