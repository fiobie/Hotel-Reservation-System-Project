<?php
session_start();
include 'connections.php';

$error = '';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!isset($_POST['terms'])) {
        $error = "You must agree to the Terms and Conditions.";
    } else {
        $student_id = trim($_POST['student_id']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            // Check for duplicate student ID or email
            $stmt = $conn->prepare("SELECT * FROM student WHERE StudentID = ? OR Email = ?");
            $stmt->bind_param("ss", $student_id, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $error = "An account with this Student Number or Email already exists.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO student (StudentID, FirstName, LastName, Email, Password, ConfirmPassword) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $student_id, $first_name, $last_name, $email, $hashed, $confirm_password);
                if ($stmt->execute()) {
                    // Save user info to session after successful registration
                    $_SESSION['student_id'] = $student_id;
                    $_SESSION['first_name'] = $first_name;
                    $_SESSION['last_name'] = $last_name;
                    $_SESSION['email'] = $email;
                    // Optionally, redirect to dashboard or another page
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hotel Sign Up | Villa Valore Hotel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Font Awesome CDN for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* ... (styles unchanged) ... */
        :root {
            --primary-green: #008000;
            --primary-green-dark: #006400;
            --text-dark: #222;
            --text-light: #fff;
            --border-radius: 18px;
            --input-bg: #f7f7f7;
            --input-border: #b2b2b2;
            --input-focus: #006400;
            --overlay-bg: rgba(0,0,0,0.38);
        }
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            min-height: 100vh;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Arial, sans-serif;
            overflow: hidden;
        }
        .main-wrapper {
            display: flex;
            align-items: stretch;
            justify-content: center;
            background: #fff;
            border-radius: var(--border-radius);
            box-shadow: 0 8px 32px rgba(44, 204, 64, 0.10), 0 1.5px 8px rgba(44, 204, 64, 0.06);
            overflow: hidden;
            max-width: 1200px;
            width: 100%;
            margin: 48px 0;
            border: 1.5px solid #e0e0e0;
            animation: fadeInMain 1.2s cubic-bezier(.77,0,.18,1) both;
            min-height: 650px;
        }
        @keyframes fadeInMain {
            from { opacity: 0; transform: translateY(40px);}
            to { opacity: 1; transform: none;}
        }
        .left-portrait {
            position: relative;
            min-width: 420px;
            max-width: 520px;
            width: 45vw;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: flex-start;
            border-right: 1.5px solid #e0e0e0;
            overflow: hidden;
            padding: 0;
            margin: 0;
            background: none;
        }
        .left-portrait-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            z-index: 0;
            display: block;
            margin: 0;
            padding: 0;
        }
        .left-portrait::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--overlay-bg) 60%, rgba(0,0,0,0.18) 100%);
            z-index: 1;
            transition: background 0.4s;
        }
        .left-content {
            position: absolute;
            bottom: 0;
            left: 0;
            z-index: 2;
            width: 100%;
            padding: 0 0 24px 0;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: flex-end;
            box-sizing: border-box;
        }
        .logo {
            width: 110px;
            height: 110px;
            border-radius: 14px;
            object-fit: contain;
            margin-bottom: 12px;
            margin-left: 24px;
            box-shadow: 0 2px 12px rgba(44,204,64,0.08);
            animation: popLogo 1.2s cubic-bezier(.77,0,.18,1) both;
        }
        .hotel-name, .tagline {
            margin-left: 24px;
        }
        .hotel-name {
            color: #fff;
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            margin-bottom: 0;
            margin-top: 0;
            text-shadow: 0 2px 12px rgba(0,0,0,0.13);
        }
        .tagline {
            color: #fff;
            font-size: 1.35rem;
            font-weight: 500;
            margin-top: 10px;
            text-align: left;
            letter-spacing: 0.5px;
            margin-bottom: 0;
            text-shadow: 0 2px 12px rgba(0,0,0,0.13);
            animation: fadeInTagline 1.6s 0.4s cubic-bezier(.77,0,.18,1) both;
        }
        @keyframes popLogo {
            0% { transform: scale(0.7); opacity: 0;}
            80% { transform: scale(1.08);}
            100% { transform: scale(1); opacity: 1;}
        }
        @keyframes fadeInTagline {
            from { opacity: 0; transform: translateY(30px);}
            to { opacity: 1; transform: none;}
        }
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-60px);}
            to { opacity: 1; transform: none;}
        }
        @keyframes fadeInLeftContent {
            from { opacity: 0; transform: translateY(40px);}
            to { opacity: 1; transform: none;}
        }
        .register-container {
            background: #fff;
            padding: 70px 64px 64px 64px;
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
            width: 100%;
            max-width: 540px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            animation: slideInRight 1.2s cubic-bezier(.77,0,.18,1) both;
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(60px);}
            to { opacity: 1; transform: none;}
        }
        .register-container h1 { display: none; }
        .register-container h2 {
            color: #555;
            font-size: 1.2rem;
            margin-bottom: 34px;
            font-weight: 400;
        }
        .form-row {
            display: flex;
            gap: 18px;
            margin-bottom: 0;
        }
        .form-row.single {
            flex-direction: column;
            gap: 0;
        }
        .form-group {
            margin-bottom: 28px;
            text-align: left;
            position: relative;
            flex: 1;
        }
        .form-group label {
            display: block;
            color: var(--text-dark);
            font-size: 1.08rem;
            margin-bottom: 9px;
            font-weight: 500;
        }
        .input-icon-wrapper {
            position: relative;
            width: 100%;
        }
        .input-icon-wrapper input,
        .input-icon-wrapper select {
            width: 100%;
            padding: 15px 14px 15px 44px;
            border: 1.5px solid var(--input-border);
            border-radius: var(--border-radius);
            font-size: 1.13rem;
            background: var(--input-bg);
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
            color: var(--text-dark);
        }
        .input-icon-wrapper input:focus,
        .input-icon-wrapper select:focus {
            border-color: var(--input-focus);
            outline: none;
            box-shadow: 0 0 0 2px #b2f2bb;
        }
        .input-icon-wrapper .input-icon {
            position: absolute;
            top: 50%;
            left: 14px;
            transform: translateY(-50%);
            color: #b2b2b2;
            font-size: 1.15em;
            pointer-events: none;
        }
        .terms {
            font-size: 1.05rem;
            color: var(--text-dark);
            margin-bottom: 28px;
            text-align: left;
        }
        .terms input[type="checkbox"] {
            margin-right: 9px;
            accent-color: var(--primary-green);
        }
        .register-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(90deg, var(--primary-green) 60%, #43a047 100%);
            color: var(--text-light);
            border: none;
            border-radius: var(--border-radius);
            font-size: 1.18rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s, transform 0.13s;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(44, 204, 64, 0.08);
        }
        .register-btn:hover {
            background: linear-gradient(90deg, var(--primary-green-dark) 60%, #388e3c 100%);
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 4px 18px rgba(44,204,64,0.13);
        }
        .links {
            margin-top: 22px;
            font-size: 1.08rem;
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: center;
        }
        .links a {
            color: var(--primary-green-dark);
            text-decoration: underline;
            font-weight: 500;
            padding: 0;
            border-radius: 0;
            background: none;
            border: none;
            width: auto;
            display: inline;
            transition: color 0.18s;
        }
        .links a:hover {
            color: var(--primary-green);
            background: none;
        }
        .error {
            margin-top: 18px;
            color: #d32f2f;
            background: #ffebee;
            border: 1px solid #ffcdd2;
            border-radius: 7px;
            padding: 12px 0;
            font-size: 1.08rem;
            animation: shakeError 0.4s;
        }
        @keyframes shakeError {
            0% { transform: translateX(0);}
            20% { transform: translateX(-8px);}
            40% { transform: translateX(8px);}
            60% { transform: translateX(-6px);}
            80% { transform: translateX(6px);}
            100% { transform: translateX(0);}
        }
        @media (max-width: 1300px) {
            .main-wrapper { max-width: 98vw; }
            .left-portrait {
                min-width: unset;
                max-width: unset;
                width: 100%;
                height: 260px;
                border-right: none;
                border-bottom: 1.5px solid #e0e0e0;
            }
            .left-content { padding: 0 0 12px 0; }
            .logo { width: 80px; height: 80px; margin-left: 12px; }
            .hotel-name, .tagline { margin-left: 12px; }
            .hotel-name { font-size: 1.6rem; }
            .tagline { font-size: 1.1rem; }
            .register-container {
                border-radius: 0 0 var(--border-radius) var(--border-radius);
                padding: 38px 18px 28px 18px;
                max-width: 98vw;
            }
        }
        @media (max-width: 900px) {
            .main-wrapper {
                flex-direction: column;
                max-width: 100vw;
                margin: 0;
                border-radius: 0;
                min-height: unset;
            }
            .left-portrait { height: 160px; }
            .left-content { padding: 0 0 8px 0; }
            .logo { width: 48px; height: 48px; margin-left: 8px; }
            .hotel-name, .tagline { margin-left: 8px; }
            .hotel-name { font-size: 1.1rem; }
            .tagline { font-size: 0.98rem; }
            .register-container { padding: 18px 6px 12px 6px; max-width: 100vw; }
            .form-row { flex-direction: column; gap: 0; }
        }
        @media (max-width: 600px) {
            .main-wrapper {
                flex-direction: column;
                max-width: 100vw;
                margin: 0;
                border-radius: 0;
            }
            .left-portrait { height: 90px; }
            .left-content { padding: 0 0 4px 0; }
            .logo { width: 28px; height: 28px; margin-left: 4px; }
            .hotel-name, .tagline { margin-left: 4px; }
            .hotel-name { font-size: 0.8rem; }
            .tagline { font-size: 0.7rem; }
        }
        .tab-nav {
            display: flex;
            justify-content: center;
            margin-bottom: 32px;
            gap: 0;
        }
        .tab-btn {
            flex: 1;
            padding: 16px 0;
            background: #f7f7f7;
            border: none;
            border-bottom: 3px solid transparent;
            color: #555;
            font-size: 1.18rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s, border-bottom 0.18s, color 0.18s;
            border-radius: 0;
            outline: none;
        }
        .tab-btn.active, .tab-btn:focus {
            background: #fff;
            border-bottom: 3px solid var(--primary-green-dark);
            color: var(--primary-green-dark);
        }
        .tab-btn:not(.active):hover {
            background: #e8f5e9;
            color: var(--primary-green);
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <div class="left-portrait">
            <img src="images/samplebedroom.png" alt="Hotel Room" class="left-portrait-bg" />
            <div class="left-content">
                <img src="images/villavalorelogo.png" alt="Villa Valore Logo" class="logo">
                <div class="hotel-name">Villa Valore Hotel</div>
                <div class="tagline">Where Every Stay Feels Like Coming Home.</div>
            </div>
        </div>
        <div class="register-container">
            <div class="tab-nav">
                <button class="tab-btn" id="signInTab" type="button" onclick="window.location.href='login.php'">Sign In</button>
                <button class="tab-btn active" id="signUpTab" type="button">Sign Up</button>
            </div>
            <h1>Villa Valore Hotel</h1>
            <form method="POST" action="register.php" id="registerForm" autocomplete="off">

                <input type="hidden" name="otp" value="<?= isset($otp) ? htmlspecialchars($otp) : '' ?>">
                <input type="hidden" name="activation_code" value="<?= isset($activation_code) ? htmlspecialchars($activation_code) : '' ?>">

                <div class="form-row single">
                    <div class="form-group">
                        <label for="student_id">Student Number</label>
                        <div class="input-icon-wrapper">
                            <input type="text" id="student_id" placeholder="Enter your student number" name="student_id" required>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <div class="input-icon-wrapper">
                            <input type="text" id="first_name" placeholder="Enter your first name" name="first_name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <div class="input-icon-wrapper">
                            <input type="text" id="last_name" placeholder="Enter your last name" name="last_name" required>
                        </div>
                    </div>
                </div>
                <div class="form-row single">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-icon-wrapper">
                            <input type="email" id="email" placeholder="Enter your email address" name="email" required>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-icon-wrapper">
                            <input type="password" id="password" placeholder="Enter your password" name="password" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-icon-wrapper">
                            <input type="password" id="confirm_password" placeholder="Confirm your password" name="confirm_password" required>
                        </div>
                    </div>
                </div>
                <div class="terms">
                    <label>
                        <input type="checkbox" name="terms" required>
                        I agree to the <a href="terms.html" target="_blank" style="color:var(--primary-green-dark);text-decoration:underline;">Terms and Conditions</a>
                    </label>
                </div>
                <button type="submit" name="register" class="register-btn">SIGN UP</button>
                <?php if (!empty($error)): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <script>
        // Tab navigation logic
        document.getElementById('signInTab').addEventListener('click', function() {
            window.location.href = 'login.php';
        });
        document.getElementById('signUpTab').classList.add('active');

        // Add icons to inputs using JS
        const iconMap = {
            student_id: 'fa-solid fa-id-card',
            first_name: 'fa-solid fa-user',
            last_name: 'fa-solid fa-user',
            email: 'fa-solid fa-envelope',
            password: 'fa-solid fa-lock',
            confirm_password: 'fa-solid fa-lock'
        };
        Object.keys(iconMap).forEach(function(id) {
            const input = document.getElementById(id);
            if (input) {
                const wrapper = input.parentElement;
                if (wrapper && !wrapper.querySelector('.input-icon')) {
                    const span = document.createElement('span');
                    span.className = 'input-icon';
                    const i = document.createElement('i');
                    iconMap[id].split(' ').forEach(cls => i.classList.add(cls));
                    span.appendChild(i);
                    wrapper.insertBefore(span, input);
                }
            }
        });

        // Password match check (client-side)
        document.getElementById("registerForm").addEventListener("submit", function (e) {
            const password = document.getElementById("password");
            const confirm = document.getElementById("confirm_password");
            const existingError = document.getElementById("pass-error");
            if (existingError) existingError.remove();
            if (password.value !== confirm.value) {
                e.preventDefault();
                const error = document.createElement("div");
                error.id = "pass-error";
                error.className = "error";
                error.textContent = "Passwords do not match.";
                this.insertBefore(error, this.querySelector(".terms"));
            }
        });
    </script>
</body>
</html>