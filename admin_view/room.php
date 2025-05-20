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
  <h2>Room</h2>
  <p>This is for the rooms.</p>

  <?php
  include 'connections.php';

  $sql = "SELECT RoomNumber, RoomType, RoomPerHour, RoomStatus, Capacity FROM room";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
      echo "<table border='1' cellpadding='10' cellspacing='0'>
              <tr>
                  <th>Room Number</th>
                  <th>Room Type</th>
                  <th>Rate per Hour</th>
                  <th>Status</th>
                  <th>Capacity</th>
              </tr>";
      while($row = $result->fetch_assoc()) {
          echo "<tr>
                  <td>" . htmlspecialchars($row["RoomNumber"]) . "</td>
                  <td>" . htmlspecialchars($row["RoomType"]) . "</td>
                  <td>" . htmlspecialchars($row["RoomPerHour"]) . "</td>
                  <td>" . htmlspecialchars($row["RoomStatus"]) . "</td>
                  <td>" . htmlspecialchars($row["Capacity"]) . "</td>
                </tr>";
      }
      echo "</table>";
  } else {
      echo "No rooms found.";
  }

  $conn->close();
  ?>
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
