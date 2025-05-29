<?php
include 'connections.php';

$sql = "SELECT RequestID, RoomNumber, RequestTimeStamp, RequestStatus, SpecificRequest FROM guest_request";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Villa Valore Hotel</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
     body {
          margin: 0;
          font-family: Arial, sans-serif;
        }
        .sidebar {
          width: 200px;
          background-color: #008000;
          color: white;
          height: 100vh;
          padding: 20px;
          position: fixed;
          top: 0;
          left: 0;
          overflow-y: auto;
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
        .main-content {
          margin-left: 240px; /* Adjusted for sidebar width */
          padding: 20px;
        }
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
    <a class="nav-link" href="home.php">
  <i class="fas fa-th-large"></i> Dashboard
</a>
    <a class="nav-link" href="student.php">
  <i class="fas fa-user"></i> Guest
</a>
    <a class="nav-link" href="booking.php">
  <i class="fas fa-book"></i> Booking
</a>
</div>

  <div class="nav-section">
    <div style="color: #aaa; font-size: 0.9em; margin: 10px 0 5px;">MANAGEMENT</div>
    <div class="nav-link toggle-btn" onclick="toggleMenu('management')"><i class="fas fa-cog"></i> Manage</div>
    <div class="submenu" id="management">
      <a class="nav-link" href="room.php"><i class="fas fa-door-open"></i>  Room</a>
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
  <h2>Guest's Request</h2>
  <p>This is where the guest requests are listed.</p>
  <br>
  
  <table>
    <tr>
      <th>Request ID</th>
      <th>Room Number</th>
      <th>Request Time</th>
      <th>Specific Request</th>
      <th>Status</th>
    </tr>

    <?php if ($result->num_rows > 0): ?>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row["RequestID"] ?></td>
          <td><?= $row["RoomNumber"] ?></td>
          <td><?= $row["RequestTimeStamp"] ?></td>
          <td><?= $row["SpecificRequest"] ?></td>
          <td><?= $row["RequestStatus"] ?></td>
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
