    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Villa Valore Hotel</title>
      <link rel="stylesheet" href="style.css">

      <!-- ✅ DataTables CSS -->
      <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
      <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

      <style>
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
          <a class="nav-link" href="room.php">🚪 Room</a>
          <a class="nav-link" href="#">🧾 Menu & Service</a>
          <a class="nav-link" href="#">👤 Account</a>
          <a class="nav-link" href="inventory.php">📦 Inventory</a>
        </div>
      </div>

      <div class="nav-section">
        <a class="nav-link" href="#">💳 Payments</a>
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