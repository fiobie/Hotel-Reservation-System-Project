<?php
session_start();
include 'connections.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $email = trim($_POST['email']);
    $enteredOtp = trim($_POST['otp']);

    $stmt = $conn->prepare("SELECT otp, activation_code, is_verified FROM guest_accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {
        $row = $res->fetch_assoc();

        if ($row['is_verified']) {
            $error = "Already verified. Please log in.";
        } elseif ($enteredOtp === $row['otp']) {
            $update = $conn->prepare("UPDATE guest_accounts SET is_verified = 1 WHERE email = ?");
            $update->bind_param("s", $email);
            $update->execute();

            $_SESSION['email_verified'] = $email;
            header("Location: guestdetails.php");
            exit;
        } else {
            $error = "Incorrect OTP.";
        }
    } else {
        $error = "Email not found.";
    }
}

// Verification by link
if (isset($_GET['email'], $_GET['code'])) {
    $email = $_GET['email'];
    $code = $_GET['code'];
    $stmt = $conn->prepare("SELECT activation_code, is_verified FROM guest_accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {
        $row = $res->fetch_assoc();
        if ($row['is_verified']) {
            $error = "Already verified. Please log in.";
        } elseif ($code === $row['activation_code']) {
            $update = $conn->prepare("UPDATE guest_accounts SET is_verified = 1 WHERE email = ?");
            $update->bind_param("s", $email);
            $update->execute();

            $_SESSION['email_verified'] = $email;
            header("Location: guestdetails.php");
            exit;
        } else {
            $error = "Invalid verification link.";
        }
    } else {
        $error = "Email not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Verify | Villa Valore</title></head>
<body>
<h2>Email Verification</h2>
<?php if ($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="POST">
    <input type="hidden" name="email" value="<?= htmlspecialchars($_GET['email'] ?? $_SESSION['email_pending'] ?? '') ?>">
    <label>Enter OTP:</label>
    <input type="text" name="otp" maxlength="6" required>
    <button type="submit" name="verify">Verify</button>
</form>
</body>
</html>
