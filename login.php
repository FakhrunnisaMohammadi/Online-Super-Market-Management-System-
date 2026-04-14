<?php
session_start();
include_once __DIR__ . '/includes/db_con.php';

$error_message = "";

// Handle login
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = $_POST['password'];
    $role = mysqli_real_escape_string($connection, $_POST['role']);

    $query = "SELECT * FROM Users WHERE Email='$email' AND Role='$role' AND IsActive=1 LIMIT 1";
    $result = mysqli_query($connection, $query);

    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['PasswordHash'])) {
            $_SESSION['UserID'] = $user['UserID'];
            $_SESSION['Name'] = $user['Name'];
            $_SESSION['Role'] = $user['Role'];

            if ($role === 'Admin') {
                header("Location: /OnlineSupermarketDB/admin/index.php");
                exit;
            } else {
                header("Location: /OnlineSupermarketDB/index.php");
                exit;
            }
        } else {
            $error_message = "❌ Invalid password.";
        }
    } else {
        $error_message = "❌ No $role account found with this email.";
    }
}

include_once __DIR__ . '/includes/header.php';
?>

<!-- Wrap the main container in .content -->
<div class="content">
  <div class="container py-5" style="min-height:80vh;">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg border-0">
          <div class="card-header text-center" style="background:#1d3557; color:white;">
            <h3>Login</h3>
          </div>
          <div class="card-body">
            <?php if($error_message): ?>
              <div class="alert alert-danger"><?= $error_message ?></div>
            <?php endif; ?>
            <form method="POST" action="">
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
              </div>
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                <div class="text-end mt-1">
                  <a href="/OnlineSupermarketDB/forgot_password.php" style="font-size:14px;">Forgot Password?</a>
                </div>
              </div>
              <div class="mb-3">
                <label for="role" class="form-label">Login as</label>
                <select name="role" id="role" class="form-select" required>
                  <option value="Customer" selected>Customer</option>
                  <option value="Admin">Admin</option>
                </select>
              </div>
              <div class="d-grid">
                <button type="submit" name="login" class="btn btn-primary" style="background:#1d3557; border:none;">Login</button>
              </div>
            </form>
          </div>
          <div class="card-footer text-center">
            <span>Don't have an account? </span>
            <a href="/OnlineSupermarketDB/register.php" class="btn btn-outline-primary btn-sm">Register</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
@media (max-width: 768px) {
    .container.py-5 {
        padding-top: 60px;
        padding-bottom: 60px;
    }
    .card {
        margin: 0 15px;
    }
}
</style>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
