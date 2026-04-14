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

include "../includes/db_con.php";
include "includes/header.php";

// ---------- NOTIFICATIONS ----------
$alert_msg = "";
if (isset($_GET['success'])) $alert_msg = "Subcategory added successfully!";
if (isset($_GET['updated'])) $alert_msg = "Subcategory updated successfully!";
if (isset($_GET['deleted'])) $alert_msg = "Subcategory deleted successfully!";

// ---------- ADD SUBCATEGORY ----------
if (isset($_POST['add_subcategory'])) {

    $subcategory_name = mysqli_real_escape_string($connection, $_POST['subcategory_name']);
    $category_id = intval($_POST['category_id']);

    $imagePath = "";
    if (isset($_FILES['image']) && $_FILES['image']['name'] != "") {
        $targetDir = __DIR__ . "/../assets/images/subcategories/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $imageName = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", $_FILES['image']['name']);
        $targetFile = $targetDir . $imageName;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            $imagePath = "assets/images/subcategories/" . $imageName;
        }
    }

    $insert = "INSERT INTO SubCategory (SubCategoryName, CategoryID, ImageURL)
               VALUES ('$subcategory_name', $category_id, '$imagePath')";
    mysqli_query($connection, $insert);
    header("Location: subcategories.php?success=1");
    exit();
}

// ---------- EDIT SUBCATEGORY ----------
if (isset($_POST['edit_subcategory'])) {
    $id = intval($_POST['subcategory_id']);
    $subcategory_name = mysqli_real_escape_string($connection, $_POST['subcategory_name']);
    $category_id = intval($_POST['category_id']);

    $updateQuery = "UPDATE SubCategory SET SubCategoryName='$subcategory_name', CategoryID=$category_id";

    if (isset($_FILES['image']) && $_FILES['image']['name'] != "") {
        $oldImgQuery = mysqli_query($connection, "SELECT ImageURL FROM SubCategory WHERE SubCategoryID=$id");
        $oldImgRow = mysqli_fetch_assoc($oldImgQuery);
        if ($oldImgRow['ImageURL'] && file_exists(__DIR__ . "/../" . $oldImgRow['ImageURL'])) {
            unlink(__DIR__ . "/../" . $oldImgRow['ImageURL']);
        }

        $targetDir = __DIR__ . "/../assets/images/subcategories/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $imageName = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", $_FILES['image']['name']);
        $targetFile = $targetDir . $imageName;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            $imagePath = "assets/images/subcategories/" . $imageName;
            $updateQuery .= ", ImageURL='$imagePath'";
        }
    }

    $updateQuery .= " WHERE SubCategoryID=$id";
    mysqli_query($connection, $updateQuery);
    header("Location: subcategories.php?updated=1");
    exit();
}

// ---------- DELETE SUBCATEGORY ----------
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $imgQuery = mysqli_query($connection, "SELECT ImageURL FROM SubCategory WHERE SubCategoryID=$id");
    $imgRow = mysqli_fetch_assoc($imgQuery);

    if ($imgRow && $imgRow['ImageURL'] && file_exists(__DIR__ . "/../" . $imgRow['ImageURL'])) {
        unlink(__DIR__ . "/../" . $imgRow['ImageURL']);
    }

    mysqli_query($connection, "DELETE FROM SubCategory WHERE SubCategoryID=$id");
    header("Location: subcategories.php?deleted=1");
    exit();
}

// ---------- FETCH CATEGORIES & SUBCATEGORIES ----------
$categories = [];
$subcategoriesList = [];

$resCat = mysqli_query($connection, "SELECT * FROM Category ORDER BY CategoryName ASC");
while ($row = mysqli_fetch_assoc($resCat)) $categories[] = $row;

$resSub = mysqli_query($connection, "
    SELECT s.*, c.CategoryName 
    FROM SubCategory s
    LEFT JOIN Category c ON c.CategoryID = s.CategoryID
    ORDER BY s.SubCategoryID DESC
");
while ($row = mysqli_fetch_assoc($resSub)) $subcategoriesList[] = $row;

?>

<div class="main p-4">
    <h2 class="mb-4 fw-bold" style="color:#34495e;">Manage Subcategories</h2>

    <?php if($alert_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $alert_msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ADD SUBCATEGORY FORM -->
    <div class="card mb-4 shadow-sm" style="border-color:#34495e;">
        <div class="card-header" style="background-color:#34495e;color:white;"><strong>Add New Subcategory</strong></div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" class="row g-3">

                <div class="col-md-4">
                    <label>Subcategory Name</label>
                    <input type="text" name="subcategory_name" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label>Select Category</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Choose</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['CategoryID'] ?>"><?= htmlspecialchars($c['CategoryName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label>Subcategory Image</label>
                    <input type="file" name="image" class="form-control">
                </div>

                <div class="col-md-12 text-end">
                    <button class="btn" style="background-color:#34495e;color:white;" name="add_subcategory">Add Subcategory</button>
                </div>

            </form>
        </div>
    </div>

    <!-- SUBCATEGORIES TABLE -->
    <div class="card shadow-sm" style="border-color:#34495e;">
        <div class="card-header" style="background-color:#34495e;color:white;"><strong>All Subcategories</strong></div>
        <div class="card-body p-0">
            <?php if(empty($subcategoriesList)): ?>
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                    <p class="mb-0 mt-2">No subcategories found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:5%;">#</th>
                                <th style="width:35%;">Subcategory</th>
                                <th style="width:35%; padding-left:10px;">Category</th>
                                <th style="width:25%; text-align:right; padding-right:15px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($subcategoriesList as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($row['SubCategoryName']) ?></td>
                                <td style="padding-left:10px;"><?= htmlspecialchars($row['CategoryName']) ?></td>
                                <td style="text-align:right; padding-right:15px;">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button 
                                            class="btn btn-sm btn-outline-warning rounded-pill editBtn"
                                            data-id="<?= $row['SubCategoryID'] ?>"
                                            data-name="<?= htmlspecialchars($row['SubCategoryName'], ENT_QUOTES) ?>"
                                            data-category="<?= $row['CategoryID'] ?>"
                                            data-image="<?= $row['ImageURL'] ?>"
                                        >Edit</button>
                                        <a href="?delete=<?= $row['SubCategoryID'] ?>" onclick="return confirm('Delete this?')" 
                                           class="btn btn-sm btn-outline-danger rounded-pill">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="subcategory_id" id="edit_id">
        <div class="modal-content">
            <div class="modal-header" style="background:#34495e;color:white;">
                <h5 class="modal-title">Edit Subcategory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="mb-3">
                    <label>Subcategory Name</label>
                    <input type="text" name="subcategory_name" id="edit_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Select Category</label>
                    <select name="category_id" id="edit_category" class="form-select" required>
                        <option value="">Choose</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['CategoryID'] ?>"><?= $c['CategoryName'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Current Image</label><br>
                    <img id="current_image" src="" width="100" style="display:none; margin-bottom:10px;">
                    <input type="file" name="image" class="form-control">
                </div>

            </div>
            <div class="modal-footer">
                <button type="submit" name="edit_subcategory" class="btn btn-outline-primary text-primary rounded-0">Save Changes</button>
                <button type="button" class="btn btn-outline-secondary text-secondary rounded-0" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.editBtn').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const name = btn.dataset.name;
        const category = btn.dataset.category;
        const image = btn.dataset.image;

        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_category').value = category;

        const imgElem = document.getElementById('current_image');
        if(image) {
            imgElem.src = '../' + image;
            imgElem.style.display = 'block';
        } else {
            imgElem.style.display = 'none';
        }

        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
</script>

<?php include "includes/footer.php"; ?>