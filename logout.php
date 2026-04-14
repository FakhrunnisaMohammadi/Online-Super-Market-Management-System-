<?php
session_start();
session_destroy();
header("Location: /OnlineSupermarketDB/login.php");
exit;
