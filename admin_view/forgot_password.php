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
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #ffffff;
        }

        .container {
            background-color: #f0f0f0;
            padding: 40px 35px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #2e7d32;
            color: white;
            border: none;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background-color: #256428;
        }

        .message {
            color: green;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
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
