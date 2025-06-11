<!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Villa Valore Hotel</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

      <!-- DataTables CSS -->
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
      <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
      <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f5f6fa;
            display: flex;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 200px;
            background: #008000;
            min-height: 100vh;
            padding: 0.5rem;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
        }

        .sidebar-title {
            color: white;
            font-size: 1.4rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            padding: 1rem;
        }

        .nav-section {
            margin-bottom: 1rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            transition: background-color 0.2s;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
            opacity: 0.9;
        }

        .management-label {
            color: #90EE90;
            font-size: 0.8em;
            margin: 1rem 0 0.5rem 1rem;
        }

        .toggle-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
        }

        .toggle-btn::after {
            content: '▼';
            font-size: 0.7rem;
            margin-left: 0.5rem;
        }

        .submenu {
            margin-left: 1.5rem;
            display: none;
        }

        .submenu.active {
            display: block;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 200px;
            overflow-x: hidden;
        }

        .dashboard {
            max-width: 1400px;
            margin: 0 auto;
        }

        h1 {
            color: #333;
            margin-bottom: 2rem;
            font-size: 2rem;
        }

        /* Add Booking */
        #addRoomModal {
          display: none;
          position: fixed;
          z-index: 999;
          top: 0; left: 0; right: 0; bottom: 0;
          background: rgba(0,0,0,0.5);
        }

        #addRoomModal .modal-content {
          background: #fff;
          width: 400px;
          margin: 100px auto;
          padding: 20px;
          border-radius: 10px;
        }

        #addRoomModal label {
          display: block;
          margin-top: 10px;
        }

        #addRoomModal input,
        #addRoomModal select {
          width: 100%;
          padding: 8px;
          margin-top: 5px;
        }

        #addRoomModal button {
          padding: 8px 12px;
          margin-top: 15px;
        }
         /* Add Button */
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
        .table-scroll {
        width: 100%;
        overflow-x: auto;
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
        echo "<script>alert('Booking added successfully!'); window.location.href=window.location.href;</script>";
      } else {
        echo "<script>alert('Error adding booking: " . $stmt->error . "');</script>";
      }

      $stmt->close();
    }
    ?>

  <!-- Sidebar Navigation -->
    <div class="sidebar">
        <h4 class="sidebar-title">Villa Valore Hotel</h4>
        
        <div class="nav-section">
            <a class="nav-link" href="index.php"><i class="fas fa-th-large"></i>Dashboard</a>
            <a class="nav-link" href="student.php"><i class="fas fa-user"></i>Guest</a>
            <a class="nav-link" href="booking.php"><i class="fas fa-book"></i>Booking</a>
        </div>

        <div class="nav-section">
            <div class="management-label">MANAGEMENT</div>
            <div class="nav-link toggle-btn" onclick="toggleMenu('management')">
                <div><i class="fas fa-cog"></i>Manage</div>
            </div>
            <div class="submenu" id="management">
                <a class="nav-link" href="room.php"><i class="fas fa-door-open"></i>Room</a>
                <a class="nav-link" href="menu_service.php"><i class="fas fa-utensils"></i>Menu & Service</a>
                <a class="nav-link" href="account.php"><i class="fas fa-user"></i>Account</a>
                <a class="nav-link" href="inventory.php"><i class="fas fa-box"></i>Inventory</a>
            </div>
        </div>

        <div class="nav-section">
            <a class="nav-link" href="payment.php"><i class="fas fa-credit-card"></i>Payments</a>
            <a class="nav-link" href="statistics.php"><i class="fas fa-chart-line"></i>Statistics</a>
            <a class="nav-link" href="inbox.php"><i class="fas fa-inbox"></i>Inbox</a>
        </div>

        <div class="nav-section">
            <a class="nav-link" href="profile.php"><i class="fas fa-user-lock"></i>Profile Account</a>
            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <h1>Bookings</h1>

      <button class="add-btn" onclick="document.getElementById('addRoomModal').style.display='block'">+ Add Booking</button>

      <div class="table-scroll">
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
                        <th>Check In Date</th>
                        <th>Check Out Date</th>
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
          echo "No payments found.";
      }

      $conn->close();
      ?>
      </div>
      </div>

    <!-- Modal Add Room Form -->
    <div id="addRoomModal">
      <div class="modal-content">
        <h3>Add New Payment</h3>
        <form method="POST" action="">
          <label>Booking ID</label>
          <input type="text" name="BookingID" required>

          <label>Room Number</label>
          <input type="text" name="RoomNumber" required>

          <label>Room Type</label>
          <select name="RoomType" required>
            <option value="Standard">Standard</option>
            <option value="Deluxe">Deluxe</option>
            <option value="Suite">Suite</option>
          </select>

          <label>Booking Status</label>
          <select name="BookingStatus" required>
            <option value="Pending">Pending</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Cancelled">Cancelled</option>
            <option value="Completed">Completed</option>
          </select>

          <label>Room Status</label>
          <select name="RoomStatus" required>
            <option value="Available">Available</option>
            <option value="Booked">Booked</option>
            <option value="Reserved">Reserved</option>
            <option value="Occupied">Occupied</option>
            <option value="Maintenance">Maintenance</option>
          </select>

          <label>Notes</label>
          <textarea name="Notes" required></textarea>

          <label>Check In Date</label>
          <input type="date" name="CheckInDate" required>

          <label>Check Out Date</label>
          <input type="date" name="CheckOutDate" required>

          <label>Booking Date</label>
          <input type="date" name="BookingDate" required>

          <label>Price</label>
          <input type="number" name="Price" required>

          <button type="submit" name="addBooking">Save</button>
          <button type="button" onclick="document.getElementById('addRoomModal').style.display='none'">Cancel</button>
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