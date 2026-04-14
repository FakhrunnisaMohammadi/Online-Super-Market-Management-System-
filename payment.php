<?php
session_start();
include_once __DIR__ . '/../includes/db_con.php';
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: /OnlineSupermarketDB/login.php");
    exit;
}
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <h3 class="fw-bold" style="color:#1d3557;">Payments</h3>
    <table class="table table-bordered align-middle mt-4">
        <thead class="table-light">
            <tr>
                <th>Payment ID</th>
                <th>Order ID</th>
                <th>User</th>
                <th>Amount</th>
                <th>Payment Mode</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="payment-body">
            <?php
            $payments = mysqli_query($connection, "
                SELECT p.*, o.UserID, u.Name, u.Email
                FROM Payment p
                JOIN Orders o ON p.OrderID=o.OrderID
                JOIN Users u ON o.UserID=u.UserID
                ORDER BY p.PaymentDate DESC
            ");
            while($p = mysqli_fetch_assoc($payments)):
            ?>
            <tr data-paymentid="<?= $p['PaymentID'] ?>">
                <td><?= $p['PaymentID'] ?></td>
                <td><?= $p['OrderID'] ?></td>
                <td><?= htmlspecialchars($p['Name']) ?> (<?= htmlspecialchars($p['Email']) ?>)</td>
                <td><?= number_format($p['Amount'],2) ?></td>
                <td><?= $p['PaymentMode'] ?></td>
                <td class="payment-status"><?= $p['PaymentStatus'] ?></td>
                <td><?= $p['PaymentDate'] ?></td>
                <td>
                    <select class="form-select form-select-sm update-payment-status" data-id="<?= $p['PaymentID'] ?>">
                        <option value="Pending" <?= $p['PaymentStatus']=='Pending'?'selected':'' ?>>Pending</option>
                        <option value="Paid" <?= $p['PaymentStatus']=='Paid'?'selected':'' ?>>Paid</option>
                        <option value="Failed" <?= $p['PaymentStatus']=='Failed'?'selected':'' ?>>Failed</option>
                    </select>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// AJAX update payment status
$(document).on('change', '.update-payment-status', function(){
    let paymentID = $(this).data('id');
    let status = $(this).val();

    $.post('admin_actions.php', {
        action: 'update_payment',
        payment_id: paymentID,
        status: status
    }, function(res){
        if(res.success){
            $('tr[data-paymentid="'+paymentID+'"] .payment-status').text(status);
            showToast(res.message);
        } else {
            alert(res.message);
        }
    }, 'json');
});

// Toast function
function showToast(msg){
    let toast = $('<div class="toast-msg">'+msg+'</div>');
    $('body').append(toast);
    setTimeout(()=>{ toast.fadeOut(400, ()=>{ toast.remove(); }); },1500);
}
</script>

<style>
.toast-msg {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #1d3557;
    color: white;
    padding: 10px 18px;
    border-radius: 6px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.3);
    z-index: 9999;
}
</style>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
