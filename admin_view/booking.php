<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Villa Valore Hotel</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

  <style>
    #addBookingModal {
      display: none;
      position: fixed;
      z-index: 999;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.5);
    }

    #addBookingModal .modal-content {
      background: #fff;
      width: 400px;
      margin: 100px auto;
      padding: 20px;
      border-radius: 10px;
    }

    #addBookingModal label {
      display: block;
      margin-top: 10px;
    }

    #addBookingModal input,
    #addBookingModal select,
    #addBookingModal textarea {
      width: 100%;
      padding: 8px;
      margin-top: 5px;
    }

    #addBookingModal button {
      padding: 8px 12px;
      margin-top: 15px;
    }

    .add-btn {
      margin: 20px 0 10px;
      display: inline-block;
      padding: 10px 20px;
      background-color: #4CAF50;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      border: none;
      cursor: pointer;
    }

    .add-btn:hover {
      background-color: #45a049;
    }
  </style>
</head>
<body>

<?php
include 'connections.php';

if (isset($_POST['addBooking'])) {
  $BookingID = $_POST['BookingID'];
  $RoomNumber = $_POST['RoomNumber'];
  $RoomType = $_POST['RoomType'];
  $BookingStatus = $_POST['BookingStatus'];
  $RoomStatus = $_POST['RoomStatus'];
  $Notes = $_POST['Notes'];
  $CheckInDate = $_POST['CheckInDate'];
  $CheckOutDate = $_POST['CheckOutDate'];
  $BookingDate = $_POST['BookingDate'];
  $Price = $_POST['Price'];

  $stmt = $conn->prepare("INSERT INTO booking (BookingID, RoomNumber, RoomType, BookingStatus, RoomStatus, Notes, CheckInDate, CheckOutDate, BookingDate, Price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("isssssssss", $BookingID, $RoomNumber, $RoomType, $BookingStatus, $RoomStatus, $Notes, $CheckInDate, $CheckOutDate, $BookingDate, $Price);

  if ($stmt->execute()) {
    echo "<script>alert('Booking added successfully!'); window.location.reload();</script>";
  } else {
    echo "<script>alert('Error adding booking: " . $stmt->error . "');</script>";
  }

  $stmt->close();
}
?>

<div class="sidebar">
  <h4>Villa Valore Hotel</h4>

  <div class="nav-section">
    <a class="nav-link" href="home.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <a class="nav-link" href="student.php"><i class="fas fa-user"></i> Guest</a>
    <a class="nav-link" href="booking.php"><i class="fas fa-book"></i> Booking</a>
  </div>

  <div class="nav-section">
    <div style="color: #aaa; font-size: 0.9em; margin: 10px 0 5px;">MANAGEMENT</div>
    <div class="nav-link toggle-btn" onclick="toggleMenu('management')"><i class="fas fa-cog"></i> Manage</div>
    <div class="submenu" id="management" style="display: none;">
      <a class="nav-link" href="room.php"><i class="fas fa-door-open"></i> Room</a>
      <a class="nav-link" href="menu_service.php"><i class="fas fa-utensils"></i> Menu & Service</a>
      <a class="nav-link" href="account.php"><i class="fas fa-user"></i> Account</a>
      <a class="nav-link" href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
    </div>
  </div>

  <div class="nav-section">
    <a class="nav-link" href="payment.php"><i class="fas fa-credit-card"></i> Payments</a>
    <a class="nav-link" href="statistics.php"><i class="fas fa-chart-line"></i> Statistics</a>
    <a class="nav-link" href="inbox.php"><i class="fas fa-inbox"></i> Inbox</a>
  </div>

  <div class="nav-section">
    <a class="nav-link" href="profile.php"><i class="fas fa-user-lock"></i> Profile Account</a>
    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</div>

<div class="main-content">
  <h2>Booking</h2>
  <p>This is where the hotel booking information is listed.</p>

  <button class="add-btn" onclick="document.getElementById('addBookingModal').style.display='block'">+ Add New Booking</button>

  <?php
  $sql = "SELECT BookingID, RoomNumber, RoomType, BookingStatus, RoomStatus, Notes, CheckInDate, CheckOutDate, BookingDate, Price FROM booking";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
      echo "<table id='bookingTable' class='display nowrap' style='width:100%'>
              <thead> 
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
                    <th>Actions</th>
                </tr>
              </thead>
              <tbody>";
      while($row = $result->fetch_assoc()) {
          echo "<tr>
                  <td>" . htmlspecialchars($row["BookingID"]) . "</td>
                  <td>" . htmlspecialchars($row["RoomNumber"]) . "</td>
                  <td>" . htmlspecialchars($row["RoomType"]) . "</td>
                  <td>" . htmlspecialchars($row["BookingStatus"]) . "</td>
                  <td>" . htmlspecialchars($row["RoomStatus"]) . "</td>
                  <td>" . htmlspecialchars($row["Notes"]) . "</td>
                  <td>" . htmlspecialchars($row["CheckInDate"]) . "</td>
                  <td>" . htmlspecialchars($row["CheckOutDate"]) . "</td>
                  <td>" . htmlspecialchars($row["BookingDate"]) . "</td>
                  <td>" . htmlspecialchars($row["Price"]) . "</td>
                  <td>
                    <button class='edit-btn'>Edit</button>
                    <button class='view-btn'>View</button>
                    <button class='delete-btn'>Delete</button>
                  </td>
                </tr>";
      }
      echo "</tbody></table>";
  } else {
      echo "No bookings found.";
  }

  $conn->close();
  ?>
</div>

<!-- Modal Add Booking Form -->
<div id="addBookingModal">
  <div class="modal-content">
    <h3>Add New Booking</h3>
    <form method="POST" action="">
      <label>Booking ID</label>
      <input type="text" name="BookingID" required>

      <label>Room Number</label>
      <input type="text" name="RoomNumber" required>

      <label>Room Type</label>
      <input type="text" name="RoomType" required>

      <label>Booking Status</label>
      <select name="BookingStatus" required>
        <option value="Pending">Pending</option>
        <option value="Confirmed">Confirmed</option>
        <option value="Cancelled">Cancelled</option>
      </select>

      <label>Room Status</label>
      <select name="RoomStatus" required>
        <option value="Available">Available</option>
        <option value="Occupied">Occupied</option>
        <option value="Maintenance">Maintenance</option>
      </select>

      <label>Notes</label>
      <textarea name="Notes" required></textarea>

      <label>Check-In Date</label>
      <input type="date" name="CheckInDate" required>

      <label>Check-Out Date</label>
      <input type="date" name="CheckOutDate" required>

      <label>Booking Date</label>
      <input type="date" name="BookingDate" required>

      <label>Price</label>
      <input type="text" name="Price" required>

      <button type="submit" name="addBooking">Save</button>
      <button type="button" onclick="document.getElementById('addBookingModal').style.display='none'">Cancel</button>
    </form>
  </div>
</div>

<!-- jQuery + DataTables + Buttons JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.flash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
  function toggleMenu(id) {
    const submenu = document.getElementById(id);
    const toggle = submenu.previousElementSibling;
    submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
    toggle.classList.toggle('expanded');
  }

  // Activate DataTables
  $(document).ready(function() {
    $('#bookingTable').DataTable({
      dom: 'Bfrtip',
      buttons: [
        'copy', 'csv', 'excel', 'pdf', 'print', 'colvis'
      ]
    });
  });
</script>

</body>
</html>
