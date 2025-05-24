<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Villa Valore Hotel</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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
  <h2>Welcome to Admin Panel</h2>
  <p>This is your content area.</p>
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