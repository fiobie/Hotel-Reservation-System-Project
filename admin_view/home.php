<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Villa Valore Hotel</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="sidebar">
  <h4>Villa Valore Hotel</h4>

  <div class="nav-section">
    <a class="nav-link" href="home.php">📊 Dashboard</a>
    <a class="nav-link" href="#">👤 Customers</a>
    <a class="nav-link" href="#">📚 Booking</a>
  </div>  


  <div class="nav-section">
    <div style="color: #aaa; font-size: 0.9em; margin: 10px 0 5px;">MANAGEMENT</div>
    <div class="nav-link toggle-btn" onclick="toggleMenu('management')">⚙️ Manage</div>
    <div class="submenu" id="management">
      <a class="nav-link" href="room.php">🚪 Room</a>
      <a class="nav-link" href="menu_service.php">🧾 Menu & Service</a>
      <a class="nav-link" href="account.php">👤 Account</a>
      <a class="nav-link" href="inventory.php">📦 Inventory</a>
    </div>
  </div>

  <div class="nav-section">
    <a class="nav-link" href="#">💳 Payments</a>
    <a class="nav-link" href="#">📈 Statistics</a>
    <a class="nav-link" href="inbox.php">📬 Inbox</a>
  </div>

  <div class="nav-section">
    <a class="nav-link" href="#">🔐 Profile Account</a>
    <a class="nav-link" href="logout.php">🚪 Logout</a>
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
