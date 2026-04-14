<?php
session_start();
include_once __DIR__ . '/includes/db_con.php';

header('Content-Type: application/json');

if (!isset($_SESSION['UserID'])) {
    echo json_encode(['status'=>'login','message'=>'Please login first.']);
    exit;
}

$user_id = (int)$_SESSION['UserID'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? max(1,(int)$_POST['quantity']) : 1;

if ($product_id <= 0) {
    echo json_encode(['status'=>'error','message'=>'Invalid product.']);
    exit;
}

// check existing
$check_sql = "SELECT CartID, Quantity FROM Cart WHERE UserID=$user_id AND ProductID=$product_id LIMIT 1";
$check_res = mysqli_query($connection, $check_sql);

if($check_res && mysqli_num_rows($check_res) > 0) {
    $row = mysqli_fetch_assoc($check_res);
    mysqli_query($connection, "UPDATE Cart SET Quantity=$quantity WHERE CartID=".$row['CartID']);
    echo json_encode(['status'=>'exists','message'=>'Cart updated']);
    exit;
}

// insert
$insert_sql = "INSERT INTO Cart (UserID, ProductID, Quantity) VALUES ($user_id, $product_id, $quantity)";
$ok = mysqli_query($connection, $insert_sql);

if($ok){
    echo json_encode(['status'=>'success','message'=>'Product added to cart.']);
}else{
    echo json_encode(['status'=>'error','message'=>'Could not add to cart.']);
}
exit;
