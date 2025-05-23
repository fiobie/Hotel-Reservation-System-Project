<?php
include 'connections.php';

$sql = "SELECT PaymentID, Amount, PaymentStatus, PaymentDate, PaymentMethod, Discount, TotalBill, ReferenceCode FROM payment";
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
  <h2>Payments</h2>
  <p>This is where the hotel payment information is listed.</p>

  <!-- Modal Add Item Form -->
<div id="addItemModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
  <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 50%; border-radius: 10px;">
    <h3>Payment</h3>
    <form method="POST" action="">
      <input type="hidden" name="add_item" value="1">

      <label>Payment ID</label>
      <input type="text" name="aymentID" required>

      <label>Amount</label>
      <input type="text" name="Amount" required>

      <label>Status</label>
        <select name="PaymentStatus" required>
            <option value="Paid">Paid</option>
            <option value="Unpaid">Unpaid</option>
            <option value="Failed">Failed</option>
            <option value="Refunded">Refunded</option>
        </select>

      <label>Date</label>
      <input type="email" name="PaymentDate" required>

      <label>Payment Method</label>
      <select name="PaymentMethod" required>
            <option value="Cash">Cash</option>
            <option value="Card">Card</option>
            <option value="Online">Online</option>
        </select>

      <label>Discount</label>
      <input type="text" name="Discount" required>

      <label>Total Bill</label>
      <input type="text" name="TotalBill" required>

      <label>Reference Code</label>
      <input type="text" name="ReferenceCode" required>

      <br><br>
      <button type="submit" name="submitItem">Save</button>
      <button type="button" onclick="document.getElementById('addItemModal').style.display='none'">Cancel</button>
    </form>
  </div>
</div>
<button class="add-btn" onclick="document.getElementById('addItemModal').style.display='block'">+ Add New Invoice</button>
  <br>
  
  <table>
    <tr>
      <th>Payment ID</th>
      <th>Amount</th>
      <th>Status</th>
      <th>Date</th>
      <th>Payment Method</th>
      <th>Discount</th>
      <th>Total Bill</th>
      <th>Reference Code</th>
    </tr>

    <?php if ($result->num_rows > 0): ?>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row["PaymentID"] ?></td>
          <td><?= $row["Amount"] ?></td>
          <td><?= $row["PaymentStatus"] ?></td>
          <td><?= $row["PaymentDate"] ?></td>
          <td><?= $row["PaymentMethod"] ?></td>
          <td><?= $row["Discount"] ?></td>
          <td><?= $row["TotalBill"] ?></td>
          <td><?= $row["ReferenceCode"] ?></td>
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
