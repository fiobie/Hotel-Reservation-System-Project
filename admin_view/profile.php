<?php
include 'connections.php';

// Fetch guest (account) info
$guest_sql = "SELECT * FROM account LIMIT 1";
$guest_result = $conn->query($guest_sql);

// Fetch booking info
$booking_sql = "SELECT * FROM booking LIMIT 1";
$booking_result = $conn->query($booking_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile Information</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
        }
        .sidebar {
            width: 250px;
            background-color: #008000;
            color: #fff;
            padding: 20px;
            height: 100vh;
        }
        .sidebar h4 {
            margin-top: 0;
        }
        .nav-link {
            display: block;
            color: #fff;
            text-decoration: none;
            margin: 10px 0;
            padding: 8px;
            border-radius: 5px;
        }
        .nav-link:hover {
            background-color: #57606f;
        }
        .main-content {
            padding: 40px;
            flex: 1;
            background-color: #f5f6fa;
        }
        .container {
            display: flex;
            gap: 40px;
            flex-wrap: wrap;
        }
        .card {
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 20px;
            width: 45%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        p {
            margin: 8px 0;
        }
        span.label {
            font-weight: bold;
        }

        body {
  margin: 0;
  font-family: 'Segoe UI', sans-serif;
  background: #fff;
  color: #000;
}

.container {
  padding: 30px;
  max-width: 900px;
  margin: auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.small-title {
  margin: 0;
  font-size: 14px;
  color: #555;
}

.section-title {
  margin-top: 30px;
  font-size: 16px;
  color: #333;
}

.update-btn {
  padding: 10px 20px;
  border: none;
  background: #e0e0e0;
  border-radius: 20px;
  cursor: pointer;
}

.account-info {
  display: flex;
  gap: 50px;
  align-items: flex-start;
  margin-top: 20px;
}

.profile {
  text-align: center;
}

.avatar {
  width: 120px;
  height: 120px;
  background: #d3d3d3;
  border-radius: 50%;
  margin: auto;
  background-image: url('https://via.placeholder.com/120');
  background-size: cover;
  background-position: center;
}

.info p {
  margin: 10px 0;
  font-size: 16px;
}

.password-btn {
  margin-top: 30px;
  padding: 10px 20px;
  background: #d3d3d3;
  border: none;
  border-radius: 20px;
  cursor: pointer;
}

    </style>
</head>
<body>
<div class="sidebar">
  <h4>Villa Valore Hotel</h4>

  <div class="nav-section">
    <a class="nav-link" href="home.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <a class="nav-link" href="student.php"><i class="fas fa-user"></i> Guest</a>
    <a class="nav-link" href="booking.php"><i class="fas fa-book"></i> Booking</a>
  </div>

  <div class="nav-section">
    <div style="color: #ffffff; font-size: 0.9em; margin: 10px 0 5px;">MANAGEMENT</div>
    <div class="nav-link toggle-btn" onclick="toggleMenu('management')"><i class="fas fa-cog"></i> Manage</div>
    <div class="submenu" id="management">
      <a class="nav-link" href="room.php"><i class="fas fa-door-open"></i> Room</a>
      <a class="nav-link" href="menu_service.php"><i class="fas fa-utensils"></i> Menu & Service</a>
      <a class="nav-link" href="account.php"><i class="fas fa-user"></i> Account</a>
      <a class="nav-link" href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
    </div>
  </div>

  <div class="nav-section">
    <a class="nav-link" href="payment.php"><i class="fas fa-credit-card"></i> Payments</a>
    <a class="nav-link" href="#"><i class="fas fa-chart-line"></i> Statistics</a>
    <a class="nav-link" href="inbox.php"><i class="fas fa-inbox"></i> Inbox</a>
  </div>

  <div class="nav-section">
    <a class="nav-link" href="profile.php"><i class="fas fa-user-lock"></i> Profile Account</a>
    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</div>

    <div class="main-content">
        <h2>Profile Account</h2>

         <button class="update-btn">+ Update Info</button>

         <h3 class="section-title">Account Information</h3>

    <div class="account-info">
      <div class="profile">
        <div class="avatar"></div>
        <p>Bio</p>
      </div>
      <div class="info">
        <p><strong>First Name</strong></p>
        <p><strong>Last Name</strong></p>
        <p><strong>Email</strong></p>
        <p><strong>Phone Number</strong></p>
        <p><strong>Position</strong></p>
        <p><strong>Status</strong></p>
      </div>
    </div>
        
<button class="password-btn">Change Password</button>
    </div>
    <script>
  function toggleMenu(id) {
    const submenu = document.getElementById(id);
    const toggle = submenu.previousElementSibling;
    submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
    toggle.classList.toggle('expanded');
  }
</script>
</body>
</html>
