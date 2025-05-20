<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Villa Valore Hotel</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      display: flex;
    }

    .sidebar {
      width: 260px;
      background-color: #008000;
      color: white;
      min-height: 100vh;
      padding: 20px;
    }

    .sidebar h4 {
      margin-bottom: 30px;
      font-size: 1.5em;
    }

    .nav-section {
      margin-bottom: 20px;
    }

    .nav-link {
      display: block;
      color: white;
      text-decoration: none;
      padding: 8px 10px;
      margin: 4px 0;
      border-radius: 4px;
    }

    .nav-link:hover {
      background-color: #34495e;
    }

    .submenu {
      display: none;
      padding-left: 15px;
    }

    .submenu a {
      font-size: 0.95em;
    }

    .toggle-btn {
      cursor: pointer;
    }

    .toggle-btn::after {
      content: " ▼";
      font-size: 0.8em;
    }

    .toggle-btn.expanded::after {
      content: " ▲";
    }

    .main-content {
      flex-grow: 1;
      padding: 30px;
    }
  </style>
</head>
<body>

<div class="sidebar">
  <h4>Admin</h4>

  <div class="nav-section">
    <a class="nav-link" href="#">📊 Dashboard</a>
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
      <a class="nav-link" href="#">🚪 Room</a>
      <a class="nav-link" href="#">🧾 Menu & Services</a>
      <a class="nav-link" href="#">🏷️ Discounts</a>
      <a class="nav-link" href="#">⭐ Special Offers</a>
      <a class="nav-link" href="#">📦 Inventory</a>
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
