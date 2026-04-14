<?php
session_start();
include_once __DIR__ . '/includes/db_con.php';

// Define variables before ANY redirect
$signup_success = "";
$error_message = "";

// Redirect logged-in users BEFORE including header.php
if (isset($_SESSION['UserID'])) {
    header("Location: index.php");
    exit;
}

include_once __DIR__ . '/includes/header.php';


// Handle Registration
if (isset($_POST['signup'])) {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $address = mysqli_real_escape_string($connection, $_POST['address']);

    // STRONG PASSWORD VALIDATION (NEW)
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $special   = preg_match('@[^\w]@', $password);

    if (!$uppercase || !$lowercase || !$number || !$special || strlen($password) < 8) {
        $error_message = "❌ Password must be at least 8 characters, uppercase letter,lowercase letter, one number, a special character";
    }
    elseif ($password !== $confirm_password) {
        $error_message = "❌ Passwords do not match!";
    } 
    else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO Users (Name, Email, PasswordHash, Role, Phone, Address) 
                  VALUES ('$name', '$email', '$hashed_password', 'Customer', '$phone', '$address')";

        if (mysqli_query($connection, $query)) {
            $signup_success = "✅ Registration successful! You can now log in.";
        } else {
            $error_message = "❌ Error: Email may already be registered.";
        }
    }
}
?>

<section class="container py-5">
    <h2 class="text-center mb-4">Register</h2>

    <?php if ($signup_success): ?>
        <div class="alert alert-success text-center"><?= $signup_success ?></div>

        <!-- ✔️ Login link moved here -->
        <div class="text-center mt-2">
            <a href="login.php" style="color:#1d3557; font-weight:bold;">Login here</a>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger text-center"><?= $error_message ?></div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name <span style="color:red">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email <span style="color:red">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password <span style="color:red">*</span></label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password <span style="color:red">*</span></label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Phone <span style="color:red">*</span></label>
                    <input type="text" class="form-control" id="phone" name="phone" required>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address <span style="color:red">*</span></label>
                    <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                </div>

                <button type="submit" name="signup" class="btn w-100" style="background-color:#1d3557; color:#fff;">Register</button>
            </form>

            <!-- ❌ Hide this if registration was successful -->
            <?php if (!$signup_success): ?>
                <div class="text-center mt-3">
                    Already have an account? 
                    <a href="login.php" style="color:#1d3557; font-weight:bold;">Login here</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>