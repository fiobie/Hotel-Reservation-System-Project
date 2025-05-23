<?php
include 'connections.php';

$sql = "SELECT BookingID, RoomNumber, RoomType, BookingStatus, RoomStatus, Notes, CheckInDate, CheckOutDate, BookingDate, Price FROM booking";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Villa Valore Hotel</title>
  <link rel="stylesheet" href="style.css">
  <style>
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      border: 1px solid #999;
      padding: 8px 12px;
      text-align: center;
    }
    th {
      background-color: #f2f2f2;
    }
    .add-btn {
      margin: 20px 0 10px;
      display: inline-block;
      padding: 10px 20px;
      background-color: #4CAF50;
      color: white;
      text-decoration: none;
      border-radius: 4px;
    }
    .add-btn:hover {
      background-color: #45a049;
    }
    .modal-content input,
.modal-content select {
  display: block;
  width: 100%;
  margin-bottom: 10px;
  padding: 8px;
}
.modal-content button {
  padding: 10px 15px;
  margin-right: 10px;
  border: none;
  background-color: #4CAF50;
  color: white;
  border-radius: 5px;
  cursor: pointer;
}
.modal-content button:hover {
  background-color: #45a049;
}

  </style>
</head>
<body>

<div class="sidebar">
  <h4>Villa Valore Hotel</h4>

  <div class="nav-section">
    <a class="nav-link" href="home.php">📊 Dashboard</a>
    <a class="nav-link" href="student.php">👤 Guest</a>
    <a class="nav-link" href="booking.php">📚 Booking</a>
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
    <a class="nav-link" href="payment.php">💳 Payments</a>
    <a class="nav-link" href="#">📈 Statistics</a>
    <a class="nav-link" href="inbox.php">📬 Inbox</a>
  </div>

  <div class="nav-section">
    <a class="nav-link" href="profile.php">🔐 Profile Account</a>
    <a class="nav-link" href="logout.php">🚪 Logout</a>
  </div>
</div>

<div class="main-content">
  <h2>Booking</h2>
  <p>This is where the hotel booking information is listed.</p>
 
  <table>
    <tr>
      <th>Booking ID</th>
      <th>Room Number</th>
      <th>Room Type</th>
      <th>Booking Status</th>
      <th>Room Status</th>
      <th>Notes</th>
      <th>Check-In Date</th>
      <th>Check-Out Date</th>
      <th>Booking Date</th>
      <th>Price</th>
    </tr>

    <?php if ($result->num_rows > 0): ?>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row["BookingID"] ?></td>
          <td><?= $row["RoomNumber"] ?></td>
          <td><?= $row["RoomType"] ?></td>
          <td><?= $row["BookingStatus"] ?></td>
          <td><?= $row["RoomStatus"] ?></td>
          <td><?= $row["Notes"] ?></td>
          <td><?= $row["CheckInDate"] ?></td>
          <td><?= $row["CheckOutDate"] ?></td>
          <td><?= $row["BookingDate"] ?></td>
          <td><?= $row["Price"] ?></td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="5">No records found</td></tr>
    <?php endif; ?>
  </table>
</div>

<script>
  function toggleMenu(id) {
    const submenu = document.getElementById(id);
    submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
  }
</script>

</body>
</html>
