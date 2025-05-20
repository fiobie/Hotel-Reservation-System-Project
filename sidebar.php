<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hotel Admin Dashboard</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .sidebar {
            width: 240px;
            height: 100vh;
            background-color: #f8f9fa;
            padding-top: 20px;
            position: fixed;
            border-right: 1px solid #ddd;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: block;
            padding: 12px 20px;
            text-decoration: none;
            color: #333;
            font-size: 15px;
        }

        .sidebar a:hover {
            background-color: #ddd;
        }

        .sidebar .submenu {
            margin-left: 20px;
        }

        .main-content {
            margin-left: 240px;
            padding: 20px;
        }

        .icon {
            margin-right: 10px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>AZHOTEL</h2>
    <a href="home.php"><span class="icon">🏠</span>HOME</a>
    <a href="booking.php"><span class="icon">📅</span>BOOKING</a>
    <a href="room_services.php"><span class="icon">🛎️</span>ROOM SERVICES</a>
    <a href="revenue.php"><span class="icon">💰</span>FINANCIAL OVERVIEW</a>
    <a href="system.php"><span class="icon">⚙️</span>HOTEL MANAGEMENT</a>
    <a href="statistics.php"><span class="icon">📊</span>STATISTICS</a>
    <a href="account_info.php"><span class="icon">👤</span>ACCOUNT</a>
    <a href="logout.php"><span class="icon">🚪</span>LOGOUT</a>
</div>

<div class="main-content">
    <h1>Welcome to Baddie Hotel Admin Panel</h1>
    <!-- Your content goes here -->
</div>

</body>
</html>
