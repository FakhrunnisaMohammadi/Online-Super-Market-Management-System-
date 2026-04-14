<?php
include_once __DIR__ . '/includes/db_con.php';
include_once __DIR__ . '/includes/header.php';

$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($category_id <= 0) {
    echo "<div class='container py-5'><div class='alert alert-warning'>No category selected.</div></div>";
    include_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch category info
$cat_sql = "SELECT * FROM Category WHERE CategoryID = $category_id LIMIT 1";
$cat_res = mysqli_query($connection, $cat_sql);
$category = $cat_res ? mysqli_fetch_assoc($cat_res) : false;

if (!$category) {
    echo "<div class='container py-5'><div class='alert alert-danger'>Invalid category.</div></div>";
    include_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch subcategories
$sub_sql = "SELECT * FROM SubCategory WHERE CategoryID = $category_id ORDER BY SubCategoryName";
$sub_res = mysqli_query($connection, $sub_sql);
?>

<!-- HERO / PAGE HEADER (INDEX STYLE) -->
<section class="hero-section text-center text-white" 
    style="
        height: 350px; 
        display: flex; 
        align-items: center; 
        justify-content: center;
        background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), 
                    url('<?= htmlspecialchars($category['ImageURL'] ?: 'assets/images/placeholder.jpg') ?>') center/cover no-repeat;
        border-bottom-left-radius: 30px;
        border-bottom-right-radius: 30px;
        overflow: hidden;
    ">
    <div class="container">
        <h1><?= htmlspecialchars($category['CategoryName']) ?></h1>
        <p>Explore subcategories under <?= htmlspecialchars($category['CategoryName']) ?></p>
    </div>
</section>

<!-- Subcategory Cards -->
<section class="container py-5">
  <div class="row g-4">
    <?php
    if ($sub_res && mysqli_num_rows($sub_res) > 0):
        while ($sub = mysqli_fetch_assoc($sub_res)):
            $img = !empty($sub['ImageURL']) ? $sub['ImageURL'] : 'assets/images/placeholder.jpg';
            $subName = htmlspecialchars($sub['SubCategoryName']);
            $subId = intval($sub['SubCategoryID']);
    ?>
    <div class="col-md-3 col-sm-6">
      <a href="product.php?subid=<?= $subId ?>" class="text-decoration-none text-dark">
        <div class="category-card text-center shadow-sm rounded overflow-hidden">
          <img src="<?= $img ?>" alt="<?= $subName ?>" class="w-100" style="height:200px; object-fit:cover;">
          <div class="p-3">
            <h5 class="fw-bold"><?= $subName ?></h5>
          </div>
        </div>
      </a>
    </div>
    <?php
        endwhile;
    else:
        echo "<div class='col-12'><div class='alert alert-info'>No subcategories found in this category.</div></div>";
    endif;
    ?>
  </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
