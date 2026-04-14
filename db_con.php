<?php
// includes/db_con.php
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'OnlineSupermarketDB');

$connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($connection, "utf8");
?>
