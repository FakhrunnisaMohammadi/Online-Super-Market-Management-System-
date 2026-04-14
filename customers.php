<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once "../includes/db_con.php";
include_once "includes/header.php"; // Your new navbar header

// ----------------------
// Handle AJAX toggle status
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_user'])) {
    header('Content-Type: application/json');

    $userId = intval($_POST['toggle_user']);

    $stmt = $connection->prepare("SELECT IsActive FROM Users WHERE UserID=? AND Role='Customer'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $newStatus = $user['IsActive'] ? 0 : 1;
    $stmt = $connection->prepare("UPDATE Users SET IsActive=? WHERE UserID=?");
    $stmt->bind_param("ii", $newStatus, $userId);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        $msg = $newStatus ? 'Customer activated successfully!' : 'Customer deactivated successfully!';
        echo json_encode(['success' => true, 'isActive' => $newStatus, 'message' => $msg]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
    exit;
}

// ----------------------
// Handle Delete Customer
// ----------------------
$notification = '';
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $connection->prepare("DELETE FROM Users WHERE UserID=? AND Role='Customer'");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $notification = "Customer deleted successfully!";
    } else {
        $notification = "Failed to delete customer!";
    }
    $stmt->close();
}

// ----------------------
// Fetch all customers
// ----------------------
$customers_res = $connection->query("SELECT * FROM Users WHERE Role='Customer' ORDER BY Name ASC");
?>

<div class="main p-4">
    <h2 class="mb-4 fw-bold" style="color:#1D3557;">Manage Customers</h2>

    <?php if($notification): ?>
        <div class="alert alert-info alert-dismissible fade show"><?= $notification ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0 table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead style="background:#f1f3f5;">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i=1; while($c=$customers_res->fetch_assoc()): ?>
                        <tr id="user-row-<?= $c['UserID'] ?>">
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($c['Name']) ?></td>
                            <td><?= htmlspecialchars($c['Email']) ?></td>
                            <td><?= htmlspecialchars($c['Phone']) ?></td>
                            <td><?= htmlspecialchars($c['Address']) ?></td>
                            <td>
                                <span class="status-badge" id="status-<?= $c['UserID'] ?>" 
                                      data-active="<?= $c['IsActive'] ?>">
                                    <?= $c['IsActive'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= date("d M, Y", strtotime($c['RegistrationDate'])) ?></td>
                            <td class="d-flex gap-1 flex-wrap">
                                <!-- Activate / Deactivate Button -->
                                <button class="btn-square" 
                                        id="toggle-btn-<?= $c['UserID'] ?>" 
                                        onclick="toggleStatus(<?= $c['UserID'] ?>)"><?= $c['IsActive'] ? 'Active' : 'Inactive' ?></button>

                                <!-- View Button -->
                                <a href="customer_profile.php?id=<?= $c['UserID'] ?>" class="btn-square view-btn">View</a>

                                <!-- Delete Button -->
                                <a href="?delete=<?= $c['UserID'] ?>" class="btn-square delete-btn" onclick="return confirmDelete(event)">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// AJAX toggle for Activate/Deactivate
function toggleStatus(userId) {
    let btn = document.getElementById('toggle-btn-' + userId);
    btn.disabled = true;

    let formData = new FormData();
    formData.append('toggle_user', userId);

    fetch(window.location.href, {method: 'POST', body: formData})
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                let status = document.getElementById('status-' + userId);
                if(data.isActive) {
                    status.dataset.active = 1;
                    status.innerText = 'Active';
                    btn.innerText = 'Active';
                } else {
                    status.dataset.active = 0;
                    status.innerText = 'Inactive';
                    btn.innerText = 'Inactive';
                }
                showNotification(data.message, data.isActive ? 'success' : 'warning');
            } else {
                showNotification(data.message || 'Failed to update status!', 'danger');
            }
            btn.disabled = false;
        })
        .catch(err => {
            console.error(err);
            showNotification('Error occurred!', 'danger');
            btn.disabled = false;
        });
}

function confirmDelete(event) {
    event.preventDefault();
    if(confirm('Are you sure you want to delete this customer?')) {
        window.location.href = event.currentTarget.href;
    }
}

function showNotification(message, type='success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = "alert";
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.main').prepend(alertDiv);

    setTimeout(() => {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(alertDiv);
        bsAlert.close();
    }, 3000);
}
</script>

<style>
/* Buttons */
.btn-square { display: inline-block; padding: 6px 12px; font-size: 0.85rem; border: 1px solid #ccc; background-color: #f8f9fa; color: #495057; text-align: center; cursor: pointer; transition: all 0.2s; min-width: 75px; }
.btn-square:hover { background-color: #0d6efd; color: white; }
.btn-square.view-btn { color: #0d6efd; }
.btn-square.view-btn:hover { background-color: #198754; color: white; }
.btn-square.delete-btn { color: #dc3545; }
.btn-square.delete-btn:hover { background-color: #dc3545; color: white; }

/* Status Badge */
.status-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; border: 1px solid #ccc; background-color: #f8f9fa; color: #495057; text-align: center; min-width: 60px; transition: all 0.2s; }
.status-badge[data-active="1"]:hover { background-color: #198754; color: white; }
.status-badge[data-active="0"]:hover { background-color: #dc3545; color: white; }

/* Responsive */
@media (max-width: 768px) {
    .table td, .table th { font-size: 0.85rem; padding: 0.4rem; }
    .btn-square { font-size: 0.75rem; padding: 4px 8px; min-width: 65px; }
}

/* Main layout fix without sidebar */
.main {
    margin-left: 0;
    width: 100%;
    min-height: calc(100vh - 70px); /* adjust based on footer height */
}
</style>

<?php include_once "includes/footer.php"; ?>