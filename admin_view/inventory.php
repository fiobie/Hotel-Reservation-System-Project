<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Villa Valore Hotel</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="sidebar">
  <h4>Admin</h4>

  <div class="nav-section">
    <a class="nav-link" href="home.php">📊 Dashboard</a>
    <a class="nav-link" href="#">👤 Customers</a>
  </div>

  <div class="nav-section">
    <div class="nav-link toggle-btn" onclick="toggleMenu('booking')">📚 Booking</div>
    <div class="submenu" id="booking">
      <a class="nav-link" href="#">📅 Booked</a>
      <a class="nav-link" href="#">⏳ Reserved</a>
    </div>
  </div>

  <div class="nav-section">
    <div style="color: #aaa; font-size: 0.9em; margin: 10px 0 5px;">MANAGEMENT</div>
    <div class="nav-link toggle-btn" onclick="toggleMenu('management')">⚙️ Manage</div>
    <div class="submenu" id="management">
      <a class="nav-link" href="room.php">🚪 Room</a>
      <a class="nav-link" href="#">🧾 Menu & Services</a>
      <a class="nav-link" href="#">🏷️ Discounts</a>
      <a class="nav-link" href="#">⭐ Special Offers</a>
      <a class="nav-link" href="inventory.php">📦 Inventory</a>
    </div>
  </div>

  <div class="nav-section">
    <a class="nav-link" href="#">📈 Statistics</a>
    <a class="nav-link" href="#">📬 Inbox</a>
  </div>

  <div class="nav-section">
    <a class="nav-link" href="#">🔐 Account</a>
    <a class="nav-link" href="#">🚪 Logout</a>
  </div>
</div>

<div class="main-content">
  <h2>Inventory</h2>
  <p>This is where the hotel inventory is listed.</p>
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
