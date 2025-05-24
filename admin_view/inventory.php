<?php
include 'connections.php';

$sql = "SELECT ItemID, ItemName, DateReceived, DateExpiry, Quantity, Price, Total, CurrentStocks, RqStocks, Status FROM inventory";
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
  <h2>Inventory</h2>
  <p>This is where the hotel inventory is listed.</p>

  <!-- Modal Add Item Form -->
<div id="addItemModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
  <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 50%; border-radius: 10px;">
    <h3>Add New Inventory Item</h3>
    <form method="POST" action="">
      <input type="hidden" name="add_item" value="1">

      <label>Item Name</label>
      <input type="text" name="ItemName" required>

      <label>Date Received</label>
      <input type="datetime-local" name="DateReceived" required>

      <label>Date Expiry</label>
      <input type="datetime-local" name="DateExpiry" required>

      <label>Quantity</label>
      <input type="number" name="Quantity" required>

      <label>Price</label>
      <input type="number" name="Price" required>

      <label>Current Stocks</label>
      <input type="number" name="CurrentStocks" required>

      <label>Request Stocks</label>
      <input type="number" name="RqStocks" required>

      <label>Status</label>
      <select name="Status" required>
        <option value=""></option>
        <option value="Approved">Approved</option>
        <option value="Pending">Pending</option>
      </select>

      <br><br>
      <button type="submit" name="submitItem">Save</button>
      <button type="button" onclick="document.getElementById('addItemModal').style.display='none'">Cancel</button>
    </form>
  </div>
</div>
<button class="add-btn" onclick="document.getElementById('addItemModal').style.display='block'">+ Add Item</button>
  <br>
  <table>
    <tr>
      <th>Item ID</th>
      <th>Item Name</th>
      <th>Date Received</th>
      <th>Date Expiry</th>
      <th>Quantity</th>
      <th>Price</th>
      <th>Total</th>
      <th>Current Stocks</th>
      <th>Request Stocks</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>

    <?php if ($result->num_rows > 0): ?>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row["ItemID"] ?></td>
          <td><?= $row["ItemName"] ?></td>
          <td><?= $row["DateReceived"] ?></td>
          <td><?= $row["DateExpiry"] ?></td>
          <td><?= $row["Quantity"] ?></td>
          <td><?= $row["Price"] ?></td>
          <td><?= $row["Total"] ?></td>
          <td><?= $row["CurrentStocks"] ?></td>
          <td><?= $row["RqStocks"] ?></td>
          <td><?= $row["Status"] ?></td>
          <td>
            <button class="edit-btn">Edit</button>
            <button class="view-btn">View</button>
            <button class="delete-btn">Delete</button>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="10">No records found</td></tr>
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
