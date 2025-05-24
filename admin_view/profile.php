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
        <div class="container">
            <div class="card">
                <h2>Account Information</h2>
                <?php if ($guest_result && $guest_result->num_rows > 0): ?>
                    <?php $guest = $guest_result->fetch_assoc(); ?>
                    <p><span class="label">Name:</span> <?= $guest['FirstName'] . ' ' . $guest['LastName'] ?></p>
                    <p><span class="label">Email:</span> <?= $guest['Email'] ?></p>
                    <p><span class="label">Phone Number:</span> <?= $guest['PhoneNumber'] ?></p>
                    <p><span class="label">Position:</span> <?= $guest['Position'] ?></p>
                    <p><span class="label">Status:</span> <?= $guest['Status'] ?></p>
                <?php else: ?>
                    <p>No guest info found.</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>Booking Info</h2>
                <?php if ($booking_result && $booking_result->num_rows > 0): ?>
                    <?php $booking = $booking_result->fetch_assoc(); ?>
                    <p><span class="label">Booking ID:</span> <?= $booking['BookingID'] ?></p>
                    <p><span class="label">Room Number:</span> <?= $booking['RoomNumber'] ?></p>
                    <p><span class="label">Room Type:</span> <?= $booking['RoomType'] ?></p>
                    <p><span class="label">Status:</span> <?= $booking['BookingStatus'] ?> / <?= $booking['RoomStatus'] ?></p>
                    <p><span class="label">Check-In:</span> <?= $booking['CheckInDate'] ?></p>
                    <p><span class="label">Check-Out:</span> <?= $booking['CheckOutDate'] ?></p>
                    <p><span class="label">Price:</span> ₱<?= $booking['Price'] ?></p>
                    <p><span class="label">Notes:</span> <?= $booking['Notes'] ?></p>
                <?php else: ?>
                    <p>No booking info found.</p>
                <?php endif; ?>
            </div>
        </div>
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
