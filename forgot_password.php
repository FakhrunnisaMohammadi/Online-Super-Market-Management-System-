<?php
session_start();
include_once __DIR__ . '/includes/db_con.php';

$message = "";
$step = 1; // Step 1: enter email, Step 2: verify OTP, Step 3: reset password

// Step 1: Email submission
if (isset($_POST['submit_email'])) {
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $query = "SELECT UserID FROM Users WHERE Email='$email' LIMIT 1";
    $result = mysqli_query($connection, $query);

    if (mysqli_num_rows($result) == 1) {
        // Generate OTP
        $otp = rand(100000, 999999);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_otp_time'] = time();
        $step = 2;

        // Send OTP to email
        $subject = "Your Password Reset OTP";
        $messageBody = "Your OTP for resetting your password is: $otp";
        $headers = "From: no-reply@onlinesupermarket.com";
        mail($email, $subject, $messageBody, $headers);

        $message = "OTP sent to your email: $email";
    } else {
        $message = "❌ No account found with this email.";
    }
}

// Step 2: OTP verification
if (isset($_POST['verify_otp'])) {
    $step = 2;
    $userOTP = $_POST['otp'];
    $sessionOTP = $_SESSION['reset_otp'] ?? null;
    $otpTime = $_SESSION['reset_otp_time'] ?? 0;

    if (!$sessionOTP) {
        $message = "❌ No OTP generated. Start over.";
        $step = 1;
    } else if (time() - $otpTime > 300) {
        $message = "❌ OTP expired. Request a new one.";
        $step = 1;
    } else if ($userOTP == $sessionOTP) {
        $step = 3;
    } else {
        $message = "❌ Invalid OTP.";
        $step = 2;
    }
}

// Step 3: Reset password
if (isset($_POST['reset_password'])) {
    $step = 3;
    $newPass = $_POST['password'];
    $confirmPass = $_POST['confirm_password'];

    if ($newPass !== $confirmPass) {
        $message = "❌ Passwords do not match!";
    } else {
        $email = $_SESSION['reset_email'];
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        mysqli_query($connection, "UPDATE Users SET PasswordHash='$hashed' WHERE Email='$email'");

        // Clear session
        unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_otp_time']);

        $message = "✅ Password reset successfully. You can now <a href='login.php'>login</a>.";
        $step = 1; // back to email step after success
    }
}

include_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5" style="min-height:80vh;">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg">
                <div class="card-header text-center" style="background:#1d3557; color:white;">
                    <h3>Forgot Password</h3>
                </div>
                <div class="card-body">

                    <?php if($message): ?>
                        <div class="alert alert-info"><?= $message ?></div>
                    <?php endif; ?>

                    <?php if ($step == 1): ?>
                        <!-- Step 1: Enter Email -->
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Enter your email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="submit_email" class="btn btn-primary" style="background:#1d3557; border:none;">Send OTP</button>
                            </div>
                        </form>

                    <?php elseif ($step == 2): ?>
                        <!-- Step 2: Verify OTP -->
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Enter OTP</label>
                                <input type="text" name="otp" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="verify_otp" class="btn btn-primary" style="background:#1d3557; border:none;">Verify OTP</button>
                            </div>
                        </form>

                    <?php elseif ($step == 3): ?>
                        <!-- Step 3: Reset Password -->
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="reset_password" class="btn btn-success">Reset Password</button>
                            </div>
                        </form>
                    <?php endif; ?>

                </div>
                <div class="card-footer text-center">
                    <a href="login.php">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>