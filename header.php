<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/db_con.php';

// Fetch categories and subcategories
$cats = [];
$cat_sql = "SELECT * FROM Category ORDER BY CategoryName";
$cat_res = mysqli_query($connection, $cat_sql);
while ($row = mysqli_fetch_assoc($cat_res)) {
    $cats[$row['CategoryID']] = [
        'CategoryID' => $row['CategoryID'],
        'Name' => $row['CategoryName'],
        'Sub' => []
    ];
}

$sub_sql = "SELECT * FROM SubCategory ORDER BY SubCategoryName";
$sub_res = mysqli_query($connection, $sub_sql);
while ($s = mysqli_fetch_assoc($sub_res)) {
    if (isset($cats[$s['CategoryID']])) {
        $cats[$s['CategoryID']]['Sub'][] = $s;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Online Supermarket</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap CSS & JS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Custom CSS -->
  <link href="/OnlineSupermarketDB/assets/css/style.css?v=1.3" rel="stylesheet">

  <!-- Modern Header & Sidebar Styles -->
  <style>
    .navbar { background-color: #1d3557 !important; padding: 0.8rem 1rem; }
    .navbar .navbar-brand { font-weight: 700; font-size: 1.7rem; color: #f1faee; transition: 0.3s; }
    .navbar-brand:hover { color: #a8dadc; }
    .search-form { position: relative; flex: 1; max-width: 500px; margin: 0 10px; }
    .search-form input { width: 100%; padding: 10px 45px 10px 15px; border-radius: 50px; border: none; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
    .search-form button { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 1.3rem; color: #1d3557; border: none; background: none; }
    .navbar-toggler { border: none !important; box-shadow: none !important; }
    .toggle-icon { font-size: 1.8rem; color: #f1faee; transition: 0.2s; }

    .offcanvas { background-color: #1d3557; color: white; }
    .sidebar-link { padding: 12px 20px; color: #f1faee; font-weight: 600; display: block; border-radius: 6px; }
    .sidebar-link:hover { background: #457b9d; }
    .sidebar-sublink { padding: 8px 25px; display: block; color: #f1faee; border-radius: 6px; }
    .sidebar-sublink:hover { background: #a8dadc; color: #1d3557; }

    @media (max-width: 992px) { .search-form { max-width: 300px; margin: 5px auto; } }
    .sidebar-link i { transition: transform 0.3s; }
    .sidebar-link.collapsed i { transform: rotate(0deg); }
    .sidebar-link:not(.collapsed) i { transform: rotate(180deg); }
  </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">

    <!-- Sidebar Toggle -->
    <button class="btn btn-outline-light me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
      <i class="bi bi-list"></i>
    </button>

    <!-- Logo -->
    <a class="navbar-brand" href="/OnlineSupermarketDB/index.php">SuperMart</a>

    <!-- RIGHT NAVBAR TOGGLER -->
    <button class="navbar-toggler collapsed" type="button" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
        <i class="bi bi-list toggle-icon"></i>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <form class="d-flex mx-auto my-2 search-form" action="/OnlineSupermarketDB/search.php">
        <input type="search" name="q" placeholder="Search products..." required>
        <button><i class="bi bi-search"></i></button>
      </form>

      <div class="d-flex align-items-center ms-lg-3 flex-wrap justify-content-center">
        <?php if(isset($_SESSION['UserID'])): ?>
          <span class="text-light me-2"><?= htmlspecialchars($_SESSION['Name']) ?></span>
          <a class="btn btn-outline-light me-2" href="/OnlineSupermarketDB/logout.php">Logout</a>
        <?php else: ?>
          <a class="btn btn-outline-light me-2" href="/OnlineSupermarketDB/login.php">
            <i class="bi bi-person"></i> Login
          </a>
        <?php endif; ?>
        <a class="btn btn-outline-light" href="/OnlineSupermarketDB/cart.php">
          <i class="bi bi-cart"></i> Cart
        </a>
      </div>
    </div>
  </div>
</nav>

<!-- SIDEBAR -->
<div class="offcanvas offcanvas-start" id="sidebar" style="width: 280px;">
  <div class="offcanvas-header">
    <h5 class="text-light">Shop by Categories</h5>
    <button class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>

  <div class="offcanvas-body">
    <a href="/OnlineSupermarketDB/index.php" class="sidebar-link">🏠 Home</a>

    <div class="accordion" id="sidebarAccordion">
      <?php foreach ($cats as $cat): ?>
        <div class="sidebar-item">
          <?php if(count($cat['Sub']) > 0): ?>
            <a class="sidebar-link collapsed" data-bs-toggle="collapse"
               href="#cat<?= $cat['CategoryID'] ?>" role="button"
               aria-expanded="false" aria-controls="cat<?= $cat['CategoryID'] ?>">
               <?= htmlspecialchars($cat['Name']) ?> <i class="bi bi-chevron-down float-end"></i>
            </a>
            <div class="collapse ms-3" id="cat<?= $cat['CategoryID'] ?>" data-bs-parent="#sidebarAccordion">
              <?php foreach ($cat['Sub'] as $sub): ?>
                <!-- FIXED: subcategory now goes to product.php -->
                <a class="sidebar-sublink"
                   href="/OnlineSupermarketDB/product.php?subid=<?= $sub['SubCategoryID'] ?>">
                   <?= htmlspecialchars($sub['SubCategoryName']) ?>
                </a>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <a href="#" class="sidebar-link"><?= htmlspecialchars($cat['Name']) ?></a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- JS FIX FOR PERFECT OPEN/CLOSE -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggler = document.querySelector('.navbar-toggler');
    const icon    = toggler.querySelector('.toggle-icon');
    const navMain = document.getElementById('navMain');

    const bsCollapse = bootstrap.Collapse.getOrCreateInstance(navMain, { toggle: false });

    navMain.addEventListener('shown.bs.collapse', () => {
        icon.classList.remove('bi-list'); icon.classList.add('bi-x');
        toggler.classList.remove('collapsed'); toggler.setAttribute('aria-expanded', 'true');
    });
    navMain.addEventListener('hidden.bs.collapse', () => {
        icon.classList.remove('bi-x'); icon.classList.add('bi-list');
        toggler.classList.add('collapsed'); toggler.setAttribute('aria-expanded', 'false');
    });

    toggler.addEventListener('click', () => {
        const isOpen = navMain.classList.contains('show');
        if (isOpen) { bsCollapse.hide(); } else { bsCollapse.show(); }
    });
});
</script>

</body>
</html>
