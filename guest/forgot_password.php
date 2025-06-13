<?php
if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    // You could check if the email exists in the DB
    // Then send a reset link or show a success message

    $msg = "If this email exists in our records, you will receive a password reset instruction.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password | Villa Valore Hotel</title>
    <link rel="stylesheet" type="text/css" href="styles/forgotpassword.css">
    
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <p>Enter your email to reset your password.</p>
        <form method="POST">
            <input type="email" name="email" placeholder="Your email" required>
            <button type="submit" name="submit">Send Reset Link</button>
            <button type="button" onclick="window.location.href='login.php'">← Back to Login</button>
        </form>
        <?php if (isset($msg)) echo "<div class='message'>$msg</div>"; ?>
    </div>
</body>
</html>
