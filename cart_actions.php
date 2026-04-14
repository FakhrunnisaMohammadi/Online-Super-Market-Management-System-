<?php
session_start();
include_once __DIR__ . '/includes/db_con.php';

header('Content-Type: application/json');

if (!isset($_SESSION['UserID'])) {
    echo json_encode(['status'=>'login','message'=>'Please login first']);
    exit;
}

$user_id = (int)$_SESSION['UserID'];
$action = $_POST['action'] ?? ($_POST['product_id'] ? 'add' : '');

function compute_grand_total($conn, $user_id) {
    $sql = "SELECT c.Quantity, p.Price FROM Cart c JOIN Product p ON c.ProductID=p.ProductID WHERE c.UserID = $user_id";
    $res = mysqli_query($conn, $sql);
    $grand = 0.0;
    while ($r = mysqli_fetch_assoc($res)) {
        $grand += ((float)$r['Price'] * (int)$r['Quantity']);
    }
    return $grand;
}

// REMOVE
if ($action === 'remove') {
    $cart_id = (int)($_POST['cart_id'] ?? 0);
    mysqli_query($connection, "DELETE FROM Cart WHERE CartID=$cart_id AND UserID=$user_id");
    $grand = compute_grand_total($connection, $user_id);

    echo json_encode([
        'success'=>true,
        'removed'=>true,
        'cart_id'=>$cart_id,
        'grand_total'=>number_format($grand,2)
    ]);
    exit;
}

// UPDATE
if ($action === 'update') {
    $cart_id = (int)($_POST['cart_id'] ?? 0);
    $qty = max(1, (int)($_POST['quantity'] ?? 1));

    mysqli_query($connection, "UPDATE Cart SET Quantity=$qty WHERE CartID=$cart_id AND UserID=$user_id");

    $row = mysqli_fetch_assoc(mysqli_query($connection,
        "SELECT c.Quantity, p.Price FROM Cart c JOIN Product p ON c.ProductID=p.ProductID WHERE c.CartID=$cart_id AND c.UserID=$user_id LIMIT 1"
    ));

    $item_total = (float)$row['Quantity'] * (float)$row['Price'];
    $grand = compute_grand_total($connection, $user_id);

    echo json_encode([
        'success'=>true,
        'new_total'=>number_format($item_total,2),
        'grand_total'=>number_format($grand,2)
    ]);
    exit;
}

// ADD
if ($action === 'add') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    $chk = mysqli_query($connection, "SELECT CartID, Quantity FROM Cart WHERE UserID=$user_id AND ProductID=$product_id LIMIT 1");

    if (mysqli_num_rows($chk) > 0) {
        $row = mysqli_fetch_assoc($chk);
        $new_qty = $row['Quantity'] + $quantity;
        mysqli_query($connection, "UPDATE Cart SET Quantity=$new_qty WHERE CartID=".$row['CartID']);
        echo json_encode(['status'=>'exists','message'=>'Quantity updated in cart']);
        exit;
    } else {
        mysqli_query($connection, "INSERT INTO Cart (UserID, ProductID, Quantity) VALUES ($user_id, $product_id, $quantity)");
        echo json_encode(['status'=>'success','message'=>'Product added to cart']);
        exit;
    }
}

echo json_encode(['status'=>'error','message'=>'Invalid action']);