<?php
session_start();
require_once __DIR__ . '/../includes/db_con.php';
require_once __DIR__ . '/includes/header.php';

$errors = [];
$messages = [];

/* ===================== Handle POST ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['CategoryName']);

    if ($name === '') {
        $errors[] = "Name required.";
    } else {
        $safe = mysqli_real_escape_string($connection, $name);

        /* -------- Add Category -------- */
        if (isset($_POST['add_category'])) {
            $imagePath = "";
            if (isset($_FILES['ImageURL']) && $_FILES['ImageURL']['name'] != "") {
                $targetDir = __DIR__ . "/../assets/images/categories/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

                $imageName = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", $_FILES['ImageURL']['name']);
                $targetFile = $targetDir . $imageName;

                if (move_uploaded_file($_FILES["ImageURL"]["tmp_name"], $targetFile)) {
                    $imagePath = "assets/images/categories/" . $imageName;
                }
            }

            $sql = "INSERT INTO Category (CategoryName, ImageURL) VALUES ('$safe', '$imagePath')";
            mysqli_query($connection, $sql) ? $messages[] = "Added successfully." : $errors[] = mysqli_error($connection);
        }

        /* -------- Update Category -------- */
        if (isset($_POST['update_category'])) {
            $id = (int)$_POST['CategoryID'];
            $updateQuery = "UPDATE Category SET CategoryName='$safe'";

            if (isset($_FILES['ImageURL']) && $_FILES['ImageURL']['name'] != "") {
                // Delete old image
                $oldImgQuery = mysqli_query($connection, "SELECT ImageURL FROM Category WHERE CategoryID=$id");
                $oldImgRow = mysqli_fetch_assoc($oldImgQuery);
                if ($oldImgRow['ImageURL'] && file_exists(__DIR__ . "/../" . $oldImgRow['ImageURL'])) {
                    unlink(__DIR__ . "/../" . $oldImgRow['ImageURL']);
                }

                $targetDir = __DIR__ . "/../assets/images/categories/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

                $imageName = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", $_FILES['ImageURL']['name']);
                $targetFile = $targetDir . $imageName;

                if (move_uploaded_file($_FILES["ImageURL"]["tmp_name"], $targetFile)) {
                    $imagePath = "assets/images/categories/" . $imageName;
                    $updateQuery .= ", ImageURL='$imagePath'";
                }
            }

            $updateQuery .= " WHERE CategoryID=$id";
            mysqli_query($connection, $updateQuery) ? $messages[] = "Updated successfully." : $errors[] = mysqli_error($connection);
        }
    }
}

/* ===================== Delete Category ===================== */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $imgQuery = mysqli_query($connection, "SELECT ImageURL FROM Category WHERE CategoryID=$id");
    $imgRow = mysqli_fetch_assoc($imgQuery);
    if ($imgRow['ImageURL'] && file_exists(__DIR__ . "/../" . $imgRow['ImageURL'])) {
        unlink(__DIR__ . "/../" . $imgRow['ImageURL']);
    }

    $sql = "DELETE FROM Category WHERE CategoryID=$id";
    mysqli_query($connection, $sql) ? $messages[] = "Deleted successfully." : $errors[] = mysqli_error($connection);
}

/* ===================== Fetch Categories ===================== */
$cats = mysqli_query($connection, "SELECT * FROM Category ORDER BY CategoryID DESC");
?>

<style>
/* Buttons for Add, Save, Update */
.btn-modern {
    background-color: #34495e !important;
    color: white !important;
    border-radius: 0;
    padding: 10px 18px;
    font-weight: 600;
    border: none;
    transition: .2s;
}
.btn-modern:hover {
    background-color: #2c3e50 !important;
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    color: white !important;
}

/* Modal close X white */
.modal-header .btn-close {
    filter: invert(1);
}

/* Modal header title white */
.modal-header h5 {
    color: white;
}

/* Main layout fix without sidebar */
.main {
    margin-left: 0;
    width: 100%;
    min-height: calc(100vh - 70px); /* adjust based on footer */
    padding: 1rem 2rem;
}
</style>

<div class="main">

    <div class="d-flex justify-content-between mb-3">
        <h3>Manage Categories</h3>
        <button class="btn-modern" data-bs-toggle="modal" data-bs-target="#addModal">+ Add Category</button>
    </div>

    <!-- Messages -->
    <?php if ($messages): ?>
        <div class="alert alert-success">
            <?php foreach ($messages as $m) echo "<div>$m</div>"; ?>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e) echo "<div>$e</div>"; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Category Name</th>
                        <th width="180">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = mysqli_fetch_assoc($cats)): ?>
                        <tr>
                            <td><?= $c['CategoryID'] ?></td>
                            <td><?= htmlspecialchars($c['CategoryName']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $c['CategoryID'] ?>">Edit</button>
                                <a href="?delete=<?= $c['CategoryID'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addModal">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header brand-bg">
                <h5>Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label>Name</label>
                <input type="text" name="CategoryName" class="form-control" required>
                <label class="mt-3">Upload Image</label>
                <input type="file" name="ImageURL" class="form-control">
            </div>
            <div class="modal-footer">
                <button class="btn-modern" name="add_category">Save</button>
            </div>
        </form>
    </div>
</div>

<?php
// Edit modals
$cats2 = mysqli_query($connection, "SELECT * FROM Category ORDER BY CategoryID DESC");
while ($c = mysqli_fetch_assoc($cats2)): ?>
<div class="modal fade" id="editModal<?= $c['CategoryID'] ?>">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header brand-bg">
                <h5>Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="CategoryID" value="<?= $c['CategoryID'] ?>">
                <label>Name</label>
                <input type="text" name="CategoryName" class="form-control" value="<?= htmlspecialchars($c['CategoryName']) ?>" required>
                <label class="mt-3">Upload Image</label>
                <?php if ($c['ImageURL']): ?>
                    <div class="mb-2">
                        <img src="/OnlineSupermarketDB/<?= $c['ImageURL'] ?>" width="100">
                    </div>
                <?php endif; ?>
                <input type="file" name="ImageURL" class="form-control">
            </div>
            <div class="modal-footer">
                <button class="btn-modern" name="update_category">Update</button>
            </div>
        </form>
    </div>
</div>
<?php endwhile; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>