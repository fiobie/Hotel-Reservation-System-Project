<?php
session_start();
include 'connections.php';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM account WHERE Email='$email' AND Password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $_SESSION['email'] = $email;
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Villa Valore Hotel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #ffffff;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background-color: #f0f0f0;
            padding: 40px 35px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .logo {
            display: block;
            margin: 0 auto 20px auto;
            width: 100px;
            height: auto;
            border-radius: 8px;
        }

        .login-container h1 {
            color: #2e7d32; /* CvSU green */
            margin-bottom: 10px;
        }

        .login-container h2 {
            font-size: 18px;
            color: #333;
            margin-bottom: 25px;
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

        .error {
            margin-top: 15px;
            color: red;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="login-container">
<img src="cvsu-logo.png" alt="CvSU Logo" class="logo">

    <h1>VILLA VALORE HOTEL</h1>
    <h2>LOG IN </h2>
    <form method="POST" action="login.php">
    <input type="email" name="email" placeholder="Email address" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="login">LOG IN</button>
    <a href="forgot_password.php" style="display:block; margin-top:15px; font-size:14px; color:#2e7d32; text-decoration:none;">FORGOT PASSWORD</a>

    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
</form>
</div>

</body>
</html>