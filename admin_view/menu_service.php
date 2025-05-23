<?php
include 'connections.php';

$sql = "SELECT menu_serviceID, Name, Type, Description, SellingPrice FROM menu_service";
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
  <h2>Menu & Service</h2>
  <p>This is where the hotel menu and service is listed.</p>

  <!-- Modal Add Item Form -->
<div id="addItemModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
  <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 50%; border-radius: 10px;">
    <h3>Add New Menu Item</h3>
    <form method="POST" action="">
      <input type="hidden" name="add_item" value="1">

      <label>Menu & Service ID</label>
      <input type="text" name="menu_serviceID" required>

      <label>Name</label>
      <input type="text" name="Name" required>

      <label>Type</label>
      <select name="Type" required>
        <option value=""></option>
        <option value="Reception/Front Desk">Reception/Front Desk</option>
        <option value="Housekeeping">Housekeeping</option>
        <option value="Room Service">Room Service</option>
        <option value="Food & Beverage">Food & Beverage</option>
        <option value="Wi-Fi & Technology">Wi-Fi & Technology</option>
        <option value="Spa & Wellness">Spa & Wellness</option>
        <option value="Safety & Security">Safety & Security</option>
      </select>

      <label>Description</label>
      <input type="text" name="Description" required>

      <label>Selling Price</label>
      <input type="number" name="SellingPrice" required>

      <br><br>
      <button type="submit" name="submitItem">Save</button>
      <button type="button" onclick="document.getElementById('addItemModal').style.display='none'">Cancel</button>
    </form>
  </div>
</div>
<button class="add-btn" onclick="document.getElementById('addItemModal').style.display='block'">+ Add Menu & Service</button>
  <br>
  
  <table>
    <tr>
      <th>Menu & Service ID</th>
      <th>Name</th>
      <th>Type</th>
      <th>Description</th>
      <th>Selling Price</th>
    </tr>

    <?php if ($result->num_rows > 0): ?>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row["menu_serviceID"] ?></td>
          <td><?= $row["Name"] ?></td>
          <td><?= $row["Type"] ?></td>
          <td><?= $row["Description"] ?></td>
          <td><?= $row["SellingPrice"] ?></td>
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
