<?php
include_once __DIR__ . '/includes/db_con.php';
include_once __DIR__ . '/includes/header.php';

// Fetch categories
$sql = "SELECT * FROM Category ORDER BY CategoryName";
$result = mysqli_query($connection, $sql);
?>

<!-- HERO SECTION (UPDATED) -->
<section class="hero-section text-center text-white" 
style="
    height: 350px; 
    display: flex; 
    align-items: center; 
    justify-content: center;
    background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), 
                url('assets/images/supermart.jpg') center/cover no-repeat;
    border-bottom-left-radius: 30px;
    border-bottom-right-radius: 30px;
    overflow: hidden;
">
  <div class="container">
    <h1>Welcome to Online Supermarket</h1>
    <p>Shop fresh groceries, fashion, and home essentials in one place!</p>
  </div>
</section>

<section class="container py-5">
  <h2 class="text-center mb-4">Shop by Category</h2>
  <div class="row g-4">
    <?php
    if ($result && mysqli_num_rows($result) > 0) {
        while ($cat = mysqli_fetch_assoc($result)) {
            $img = !empty($cat['ImageURL']) ? $cat['ImageURL'] : 'assets/images/placeholder.jpg';
            echo "
            <div class='col-md-3 col-sm-6'>
              <a href='category.php?id={$cat['CategoryID']}' class='text-decoration-none text-dark'>
                <div class='category-card text-center shadow-sm rounded overflow-hidden'>
                  <img src='{$img}' alt='{$cat['CategoryName']}' class='w-100' style='height:200px; object-fit:cover;'>
                  <div class='p-3'>
                    <h5 class='fw-bold'>{$cat['CategoryName']}</h5>
                  </div>
                </div>
              </a>
            </div>";
        }
    } else {
        echo "<div class='col-12 text-center'><p>No categories available.</p></div>";
    }
    ?>
  </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>