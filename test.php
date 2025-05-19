<?php
session_start();

// MySQL Connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "auth_system";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LogIn & SignUp</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f7f7;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: white;
            padding: 2rem;
            width: 400px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h2 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: #111;
        }

        form input {
            width: 100%;
            padding: 12px;
            margin: 0.5rem 0;
            border: none;
            border-radius: 10px;
            background: #eee;
            font-size: 1rem;
        }

        .row {
            display: flex;
            gap: 10px;
        }

        .row input {
            width: 100%;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #111;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 1rem;
        }

        button:hover {
            background: #333;
        }

        .secondary-btn, .guest-link {
            display: block;
            margin-top: 10px;
            text-decoration: none;
            color: #555;
            font-size: 0.9rem;
        }

        .secondary-btn:hover, .guest-link:hover {
            text-decoration: underline;
        }

        .switch {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 1rem 0;
        }

        .switch hr {
            flex: 1;
            height: 1px;
            background: #ccc;
            border: none;
            margin: 0 10px;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #555;
            margin-top: -0.5rem;
        }
    </style>
</head>
<body>

<div class="container">
    <?php if (!isset($_GET['register'])): ?>
        <h2>Log In</h2>
        <form action="" method="post">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <div class="form-options">
                <label><input type="checkbox" name="remember">Remember Me</label>
                <a href="#">Forgot Password?</a>
            </div>
            <button type="submit" name="login">Log in</button>
            <div class="switch"><hr>Or<hr></div>
            <a href="?register=1" class="secondary-btn">Sign up</a>
            <a href="#" class="guest-link">Continue as guest</a>
        </form>

        <?php
        if (isset($_POST['login'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];

            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows > 0) {
                $user = $res->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user'] = $user['username'];
                    echo "<script>alert('Login successful!'); window.location='index.php';</script>";
                } else {
                    echo "<script>alert('Incorrect password.');</script>";
                }
            } else {
                echo "<script>alert('User not found.');</script>";
            }
        }
        ?>
    <?php else: ?>
        <h2>Sign Up</h2>
        <form action="" method="post">
            <div class="row">
                <input type="text" name="first_name" placeholder="First Name" required>
                <input type="text" name="last_name" placeholder="Last Name" required>
            </div>
            <div class="row">
                <input type="text" name="username" placeholder="Username" required>
                <input type="text" name="phone_number" placeholder="Phone Number" required>
            </div>
            <input type="email" name="email" placeholder="Email Address" required>
            <div class="row">
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            </div>
            <button type="submit" name="register">Create Account</button>
            <div class="switch"><hr>Or<hr></div>
            <a href="test.php" class="secondary-btn">Log in</a>
        </form>

        <?php
        if (isset($_POST['register'])) {
            $fname = $_POST['first_name'];
            $lname = $_POST['last_name'];
            $username = $_POST['username'];
            $phone = $_POST['phone_number'];
            $email = $_POST['email'];
            $password = $_POST['password'];
            $confirm = $_POST['confirm_password'];

            if ($password !== $confirm) {
                echo "<script>alert('Passwords do not match!');</script>";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, phone_number, email, password) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $fname, $lname, $username, $phone, $email, $hash);
                if ($stmt->execute()) {
                    echo "<script>alert('Registration successful!'); window.location='test.php';</script>";
                } else {
                    echo "<script>alert('Error: Could not register user.');</script>";
                }
            }
        }
        ?>
    <?php endif; ?>
</div>

</body>
</html>
