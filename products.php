<?php
// Start session properly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protect admin pages
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Load DB and Admin UI includes
include_once "../includes/db_con.php";
include_once "includes/header.php";


// ================= Messages =================
$alert_msg = "";

/* ================= ADD PRODUCT ================= */
if (isset($_POST['add_product'])) {

    $category     = intval($_POST['category']);
    $subcategory  = intval($_POST['subcategory']);
    $name         = mysqli_real_escape_string($connection, $_POST['product_name']);
    $price        = floatval($_POST['price']);
    $stock        = intval($_POST['stock']);
    $status       = isset($_POST['is_active']) ? 1 : 0;
    $description  = mysqli_real_escape_string($connection, $_POST['description'] ?? '');

    $imgURL = "";

    // Handle Image Upload
    if (!empty($_FILES['product_image']['name'])) {
        $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $newName = 'product_' . time() . '.' . $ext;
        $targetDir = "../assets/images/products/";   // actual filesystem folder

        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetDir . $newName)) {
            // Path stored in DB (relative to site root)
            $imgURL = "assets/images/products/" . $newName;
        }
    }

    $stmt = mysqli_prepare($connection,
        "INSERT INTO Product (SubCategoryID, ProductName, Price, Stock, Description, IsActive, ImageURL)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isdisis",
            $subcategory,
            $name,
            $price,
            $stock,
            $description,
            $status,
            $imgURL
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $alert_msg = "<div class='alert alert-success'>Product added successfully!</div>";
    } else {
        $alert_msg = "<div class='alert alert-danger'>Database error: " . mysqli_error($connection) . "</div>";
    }
}

/* ================= DELETE PRODUCT ================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($connection, "DELETE FROM Product WHERE ProductID=$id");
    $_SESSION['flash_msg'] = "<div class='alert alert-success'>Product deleted successfully!</div>";
    header("Location: products.php");
    exit();
}

// Show flash message
if (isset($_SESSION['flash_msg'])) {
    $alert_msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

/* ================= UPDATE PRODUCT ================= */
if (isset($_POST['update_product'])) {

    $id          = intval($_POST['edit_id']);
    $subcategory = intval($_POST['edit_subcategory']);
    $name        = mysqli_real_escape_string($connection, $_POST['edit_name']);
    $price       = floatval($_POST['edit_price']);
    $stock       = intval($_POST['edit_stock']);
    $status      = isset($_POST['edit_status']) ? 1 : 0;
    $description = mysqli_real_escape_string($connection, $_POST['edit_description'] ?? '');

    $imgSQL = "";
    if (!empty($_FILES['edit_image']['name'])) {
        $ext = pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION);
        $newName = 'product_' . time() . '.' . $ext;

        $targetDir = "../assets/images/products/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

        if (move_uploaded_file($_FILES['edit_image']['tmp_name'], $targetDir . $newName)) {
            // Store only relative path
            $dbPath = "assets/images/products/" . $newName;
            $imgSQL = ", ImageURL='" . mysqli_real_escape_string($connection, $dbPath) . "'";
        }
    }

    $query = "
        UPDATE Product 
        SET 
            SubCategoryID='$subcategory',
            ProductName='" . mysqli_real_escape_string($connection, $name) . "',
            Price='$price',
            Stock='$stock',
            Description='$description',
            IsActive='$status'
            $imgSQL
        WHERE ProductID=$id
    ";

    mysqli_query($connection, $query);
    $alert_msg = "<div class='alert alert-success'>Product updated successfully!</div>";
}

/* ================= FETCH DATA ================= */
$categories    = [];
$subcategories = [];
$productList   = [];

// Categories
$resCat = mysqli_query($connection, "SELECT * FROM Category ORDER BY CategoryName ASC");
while ($c = mysqli_fetch_assoc($resCat)) $categories[] = $c;

// SubCategories
$resSub = mysqli_query($connection, "SELECT * FROM SubCategory ORDER BY SubCategoryName ASC");
while ($s = mysqli_fetch_assoc($resSub)) $subcategories[] = $s;

// Products + SubCategory + Category
$productsRes = mysqli_query($connection, "
    SELECT 
        p.*, 
        s.SubCategoryName, 
        s.CategoryID, 
        c.CategoryName
    FROM Product p
    JOIN SubCategory s ON p.SubCategoryID = s.SubCategoryID
    JOIN Category c ON s.CategoryID = c.CategoryID
    ORDER BY c.CategoryName, s.SubCategoryName, p.ProductName ASC
");

while ($row = mysqli_fetch_assoc($productsRes)) {
    $productList[] = $row;
}
?>

<div class="main p-4">
    <h2 class="mb-4 fw-bold" style="color:#34495e;">Manage Products</h2>
    <?= $alert_msg ?>

    <!-- ADD PRODUCT CARD -->
    <div class="card mb-4 shadow-sm" style="border-color:#34495e;">
        <div class="card-header" style="background-color:#34495e;color:white;"><strong>Add Product</strong></div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" class="row g-3">

                <div class="col-md-3">
                    <label>Category</label>
                    <select id="categorySelect" name="category" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['CategoryID'] ?>"><?= htmlspecialchars($c['CategoryName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>SubCategory</label>
                    <select id="subcatSelect" name="subcategory" class="form-select" required>
                        <option value="">Select SubCategory</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Product Name</label>
                    <input type="text" name="product_name" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label>Price</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label>Stock</label>
                    <input type="number" name="stock" class="form-control" required>
                </div>

                <div class="col-md-12">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>

                <div class="col-md-6">
                    <label>Image</label>
                    <input type="file" name="product_image" class="form-control">
                </div>

                <div class="col-md-12 form-check mt-2">
                    <input type="checkbox" name="is_active" class="form-check-input" checked>
                    <label class="form-check-label">Active</label>
                </div>

                <div class="col-md-12 text-end">
                    <button type="submit" name="add_product" class="btn" style="background-color:#34495e;color:white;">Add Product</button>
                </div>

            </form>
        </div>
    </div>

    <!-- PRODUCTS TABLE -->
    <div class="card shadow-sm" style="border-color:#34495e;">
        <div class="card-header" style="background-color:#2c3e50;color:#fff;"><strong>All Products</strong></div>
        <div class="card-body">

            <div class="row mb-3 g-3">
                <div class="col-md-3">
                    <label>Category</label>
                    <select id="filterCategory" class="form-select">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['CategoryID'] ?>"><?= htmlspecialchars($c['CategoryName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>SubCategory</label>
                    <select id="filterSubcategory" class="form-select">
                        <option value="">Select SubCategory</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Search Product</label>
                    <input type="text" id="searchProduct" class="form-control" placeholder="Type product name">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead style="background-color:#34495e;color:#fff;">
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category / SubCategory</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsBody"></tbody>
                </table>
            </div>

        </div>
    </div>

</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" enctype="multipart/form-data" class="modal-content">

            <div class="modal-header" style="background:#34495e;color:#fff;">
                <h5>Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body row g-3">

                <input type="hidden" name="edit_id" id="edit_id">

                <div class="col-md-4">
                    <label>Category</label>
                    <select id="edit_category" class="form-select"></select>
                </div>

                <div class="col-md-4">
                    <label>SubCategory</label>
                    <select id="edit_subcategory" name="edit_subcategory" class="form-select"></select>
                </div>

                <div class="col-md-4">
                    <label>Price</label>
                    <input type="number" step="0.01" name="edit_price" id="edit_price" class="form-control">
                </div>

                <div class="col-md-6">
                    <label>Name</label>
                    <input type="text" name="edit_name" id="edit_name" class="form-control">
                </div>

                <div class="col-md-3">
                    <label>Stock</label>
                    <input type="number" name="edit_stock" id="edit_stock" class="form-control">
                </div>

                <div class="col-md-12">
                    <label>Description</label>
                    <textarea name="edit_description" id="edit_description" class="form-control" rows="3"></textarea>
                </div>

                <div class="col-md-3">
                    <label>Image</label>
                    <input type="file" name="edit_image" class="form-control">
                </div>

                <div class="col-12 form-check mt-2">
                    <input type="checkbox" name="edit_status" id="edit_status" class="form-check-input">
                    <label class="form-check-label" for="edit_status">Active</label>
                </div>
            </div>

            <div class="modal-footer" style="background:#34495e;">
                <button type="submit" name="update_product" class="btn" style="background-color:#fff;color:#34495e;">
                    Update Product
                </button>
            </div>

        </form>
    </div>
</div>

<!-- JAVASCRIPT -->
<script>
const categories    = <?= json_encode($categories) ?>;
const subcategories = <?= json_encode($subcategories) ?>;
const allProducts   = <?= json_encode($productList) ?>;

function populateSubcategories(catId, selectId, selectedSub = 0) {
    const select = document.getElementById(selectId);
    select.innerHTML = '<option value="">Select SubCategory</option>';
    subcategories.forEach(sub => {
        if (sub.CategoryID == catId) {
            const sel = (sub.SubCategoryID == selectedSub) ? 'selected' : '';
            select.innerHTML += `<option value="${sub.SubCategoryID}" ${sel}>${sub.SubCategoryName}</option>`;
        }
    });
}

document.getElementById('categorySelect').addEventListener('change', function () {
    populateSubcategories(this.value, 'subcatSelect');
});

document.getElementById('filterCategory').addEventListener('change', function () {
    populateSubcategories(this.value, 'filterSubcategory');
    filterProducts();
});

document.getElementById('filterSubcategory').addEventListener('change', filterProducts);
document.getElementById('searchProduct').addEventListener('input', filterProducts);

function filterProducts() {
    const catId  = document.getElementById('filterCategory').value;
    const subId  = document.getElementById('filterSubcategory').value;
    const search = document.getElementById('searchProduct').value.toLowerCase();
    const tbody = document.getElementById('productsBody');
    tbody.innerHTML = "";

    const filtered = allProducts.filter(p => {
        let catMatch  = (catId === "" || p.CategoryID == catId);
        let subMatch  = (subId === "" || p.SubCategoryID == subId);
        let nameMatch = p.ProductName.toLowerCase().includes(search);
        return (catId !== "" || subId !== "" || search !== "") && catMatch && subMatch && nameMatch;
    });

    filtered.forEach((p, i) => {
        tbody.innerHTML += `
            <tr>
                <td>${i + 1}</td>
                <td>${p.ImageURL ? `<img src="../${p.ImageURL}" width="50">` : ""}</td>
                <td>${p.ProductName}</td>
                <td>${p.CategoryName} / ${p.SubCategoryName}</td>
                <td>${parseFloat(p.Price).toFixed(2)}</td>
                <td>${p.Stock}</td>
                <td>${p.IsActive ? "Active" : "Inactive"}</td>
                <td>
                    <button class="btn btn-sm editBtn"
                        data-id="${p.ProductID}"
                        data-name="${p.ProductName}"
                        data-price="${p.Price}"
                        data-stock="${p.Stock}"
                        data-status="${p.IsActive}"
                        data-cat="${p.CategoryID}"
                        data-subcat="${p.SubCategoryID}"
                        data-description="${p.Description || ''}">
                        Edit
                    </button>
                    <a href="?delete=${p.ProductID}" onclick="return confirm('Delete this product?')" class="btn btn-sm">Delete</a>
                </td>
            </tr>
        `;
    });

    attachEditEvents();
}

function attachEditEvents() {
    document.querySelectorAll('.editBtn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_name').value = this.dataset.name;
            document.getElementById('edit_price').value = this.dataset.price;
            document.getElementById('edit_stock').value = this.dataset.stock;
            document.getElementById('edit_description').value = this.dataset.description;
            document.getElementById('edit_status').checked = (this.dataset.status == 1);

            const catSelect = document.getElementById('edit_category');
            catSelect.innerHTML = '<option value="">Select Category</option>';
            categories.forEach(c => catSelect.innerHTML += `<option value="${c.CategoryID}">${c.CategoryName}</option>`);
            catSelect.value = this.dataset.cat;

            populateSubcategories(this.dataset.cat, 'edit_subcategory', this.dataset.subcat);

            new bootstrap.Modal(document.getElementById('editModal')).show();
        });
    });
}

// Auto-hide alerts
setTimeout(() => {
    const alert = document.querySelector('.alert');
    if (alert) alert.style.display = "none";
}, 3000);

// Initialize products table
filterProducts();
</script>

<?php include_once "includes/footer.php"; ?>