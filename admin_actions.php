<?php
session_start();
include_once __DIR__ . '/../includes/db_con.php';
header('Content-Type: application/json');

if(!isset($_SESSION['Role']) || $_SESSION['Role']!=='Admin') {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

if($action === 'toggle_user'){
    $user_id = (int)$_POST['user_id'];
    $row = mysqli_fetch_assoc(mysqli_query($connection, "SELECT IsActive FROM Users WHERE UserID=$user_id"));
    if($row){
        $new = $row['IsActive'] ? 0 : 1;
        mysqli_query($connection, "UPDATE Users SET IsActive=$new WHERE UserID=$user_id");
        echo json_encode(['success'=>true]);
        exit;
    }
}

if($action === 'delete_user'){
    $user_id = (int)$_POST['user_id'];
    mysqli_query($connection, "DELETE FROM Users WHERE UserID=$user_id");
    echo json_encode(['success'=>true]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid action']);
