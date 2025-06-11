<?php
include 'connections.php';

if (isset($_POST['register'])) {
    if (!isset($_POST['terms'])) {
        $error = "You must agree to the Terms and Conditions.";
    } else {
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Check if email already exists
        $check = mysqli_query($conn, "SELECT * FROM account WHERE Email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email already registered.";
        } else {
            $query = "INSERT INTO account (Email, Password) VALUES ('$email', '$password')";
            if (mysqli_query($conn, $query)) {
                header("Location: login.php");
                exit;
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | Villa Valore Hotel</title>
    <style>
        /* Same styles as login */
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #ffffff;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background-color: #f0f0f0;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
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
        }

        .terms {
            margin: 10px 0;
            font-size: 14px;
            text-align: left;
        }

        .terms input {
            margin-right: 5px;
        }

        .error {
            color: red;
            margin-top: 10px;
        }

        a {
            text-decoration: none;
            color: #2e7d32;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Register - Villa Valore Hotel</h2>
    <form method="POST" action="register.php">
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Password" required />

        <div class="terms">
            <label><input type="checkbox" name="terms" required>
                I agree to the <a href="terms.html" target="_blank">Terms and Conditions</a>
            </label>
        </div>

        <button type="submit" name="register">Register</button>
        <p><a href="login.php">Already have an account? Login</a></p>

        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
    </form>
</div>
</body>
</html>
