      <!DOCTYPE html>
      <html lang="en">
      <head>
        <meta charset="UTF-8">
        <title>Villa Valore Hotel</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

      <style>
        .sidebar {
    width: 260px;
    background-color: #008000;
    color: white;
    min-height: 100vh;
    padding: 20px;
    position:fixed;
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

      if (isset($_POST['addRoom'])) {
        $RoomNumber = $_POST['RoomNumber'];
        $RoomType = $_POST['RoomType'];
        $RoomPerHour = $_POST['RoomPerHour'];
        $RoomStatus = $_POST['RoomStatus'];
        $Capacity = $_POST['Capacity'];

        $stmt = $conn->prepare("INSERT INTO room (RoomNumber, RoomType, RoomPerHour, RoomStatus, Capacity) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisi", $RoomNumber, $RoomType, $RoomPerHour, $RoomStatus, $Capacity);

        if ($stmt->execute()) {
          echo "<script>alert('Room added successfully!'); window.location.href=window.location.href;</script>";
        } else {
          echo "<script>alert('Error adding room: " . $stmt->error . "');</script>";
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
        <h2>Room</h2>
        <p>This is for the rooms.</p>

        <button class="add-btn" onclick="document.getElementById('addRoomModal').style.display='block'">+ Add Room</button>

        <?php
        $sql = "SELECT RoomNumber, RoomType, RoomPerHour, RoomStatus, Capacity FROM room";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<table id='roomsTable' class='display nowrap' style='width:100%'>
                    <thead> 
                      <tr>
                          <th>Room Number</th>
                          <th>Room Type</th>
                          <th>Rate per Hour</th>
                          <th>Status</th>
                          <th>Capacity</th>
                          <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>";
            while($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . htmlspecialchars($row["RoomNumber"]) . "</td>
                        <td>" . htmlspecialchars($row["RoomType"]) . "</td>
                        <td>" . htmlspecialchars($row["RoomPerHour"]) . "</td>
                        <td>" . htmlspecialchars($row["RoomStatus"]) . "</td>
                        <td>" . htmlspecialchars($row["Capacity"]) . "</td>
                        <td>
                          <button class='edit-btn'>Edit</button>
                          <button class='view-btn'>View</button>
                          <button class='delete-btn'>Delete</button>
                        </td>
                      </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "No rooms found.";
        }

        $conn->close();
        ?>
      </div>

      <!-- Modal Add Room Form -->
      <div id="addRoomModal">
        <div class="modal-content">
          <h3>Add New Room</h3>
          <form method="POST" action="">
            <label>Room Number</label>
            <input type="text" name="RoomNumber" required>

            <label>Room Type</label>
            <input type="text" name="RoomType" required>

            <label>Rate per Hour</label>
            <input type="number" name="RoomPerHour" required>

            <label>Status</label>
            <select name="RoomStatus" required>
              <option value="Available">Available</option>
              <option value="Occupied">Occupied</option>
              <option value="Maintenance">Maintenance</option>
            </select>

            <label>Capacity</label>
            <input type="number" name="Capacity" required>

            <button type="submit" name="addRoom">Save</button>
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
          $('#roomsTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
              'copy', 'csv', 'excel', 'pdf', 'print', 'colvis'
            ]
          });
        });
      </script>

          </body>
              </html>