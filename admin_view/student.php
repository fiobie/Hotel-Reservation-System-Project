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
        body {
          margin: 0;
          font-family: Arial, sans-serif;
        }
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
        .sidebar h4 {
          margin-bottom: 30px;
          font-size: 1.5em;
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
        .submenu {
            margin-left: 1.5rem;
            display: none;
        }

        .submenu.active {
            display: block;
        }
        .toggle-btn {
          cursor: pointer;
        }
        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 200px; /* Match new sidebar width */
            overflow-x: hidden;
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
          background-color: #008000;
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

        /* Custom DataTable Styling */
      #studentTable.dataTable {
      border-collapse: separate;
      border-spacing: 0;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      }

#studentTable thead {
  background-color:#008000; 
  color: white;
}

#studentTable thead th {
  padding: 12px;
  font-weight: 600;
  text-align: left;
}

#studentTable tbody tr:nth-child(even) {
  background-color: #f2f2f2;
}

#studentTable tbody tr:nth-child(odd) {
  background-color: #ffffff;
}

#studentTable tbody td {
  padding: 10px;
  vertical-align: middle;
}

#studentTable tbody tr:hover {
  background-color: #e0f5e0;
}

/* Action buttons */
#studentTable .edit-btn,
#studentTable .view-btn {
  background-color: #008000;
  color: white;
  border: none;
  padding: 5px 10px;
  margin-right: 5px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.85rem;
}

#studentTable .edit-btn:hover,
#studentTable .view-btn:hover {
  background-color: #006400;
}


        
    }

      </style>
    </head>
    <body>

<?php
    include 'connections.php';

    if (isset($_POST['addStudent'])) {
      $StudentID = $_POST['StudentID'];
      $FirstName = $_POST['FirstName'];
      $LastName = $_POST['LastName'];
      $Gender = $_POST['Gender'];
      $PhoneNumber = $_POST['PhoneNumber'];
      $Address = $_POST['Address'];
      $Email = $_POST['Email'];
      $Nationality = $_POST['Nationality'];
      $Birthdate = $_POST['Birthdate'];
    

      $stmt = $conn->prepare("INSERT INTO student (StudentID, FirstName, LastName, Gender, PhoneNumber, Address, Email, Nationality, Birthdate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("issssssss", $StudentID, $FirstName, $LastName, $Gender, $PhoneNumber, $Address, $Email, $Nationality, $Birthdate);

      if ($stmt->execute()) {
        echo "<script>alert('Student added successfully!'); window.location.href=window.location.href;</script>";
      } else {
        echo "<script>alert('Error adding student: " . $stmt->error . "');</script>";
      }

      $stmt->close();
    }
    ?>

  <div class="sidebar">
      <h4>Villa Valore Hotel</h4>

        <div class="nav-section">
          <a class="nav-link" href="index.php"><i class="fas fa-th-large"></i> Dashboard</a>
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
      <h2>Guest</h2>
      <p>This is for the guests.</p>

      <button class="add-btn" onclick="document.getElementById('addRoomModal').style.display='block'">+ Add Guest</button>

      <div class="table-scroll">
      <?php
      $sql = "SELECT StudentID, FirstName, LastName, Gender, PhoneNumber, Address, Email, Nationality, Birthdate FROM student";
      $result = $conn->query($sql);

      if ($result->num_rows > 0) {
          echo "<table id='studentTable' class='display nowrap' style='width:100%'>
                  <thead> 
                    <tr>
                        <th>Student ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Gender</th>
                        <th>Phone Number</th>
                        <th>Address</th>
                        <th>Email</th>
                        <th>Nationality</th>
                        <th>Birthdate</th>
                        <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>";
          while($row = $result->fetch_assoc()) {
              echo "<tr>
                      <td>" . htmlspecialchars($row["StudentID"]) . "</td>
                      <td>" . htmlspecialchars($row["FirstName"]) . "</td>
                      <td>" . htmlspecialchars($row["LastName"]) . "</td>
                      <td>" . htmlspecialchars($row["Gender"]) . "</td>
                      <td>" . htmlspecialchars($row["PhoneNumber"]) . "</td>
                      <td>" . htmlspecialchars($row["Address"]) . "</td>
                      <td>" . htmlspecialchars($row["Email"]) . "</td>
                      <td>" . htmlspecialchars($row["Nationality"]) . "</td>
                      <td>" . htmlspecialchars($row["Birthdate"]) . "</td>
                      <td>
                      <button class='edit-btn'>Edit</button></a>
                      <button class='view-btn'>View</button></a>
                      <button class='view-btn'>Delete</button></a>
                      </td>
                    </tr>"; 
          }
          echo "</tbody></table>";
      } else {
          echo "No payments found.";
      }

      $conn->close();

      /* View Data 
      if(isset($_GET['action']) && $_GET['action'] == 'view'){
        $id = $_GET["StudentID"];
        $sql = "SELECT * FROM student WHERE StudentID = '$id'"; 
        $result = $conn->query($sql);

        if($result -> num_rows > 0 ){
          while($row = $result->fetch_assoc()){
            echo "<h2>View User</h2>";
            echo "ID: " . $row["id"] . "<br>"; 
            echo "Name: " . $row["name"]
          }
        }

      */
      ?>
      </div>
      </div>

    <!-- Modal Add Room Form -->
    <div id="addRoomModal">
      <div class="modal-content">
        <h3>Add New Student</h3>
        <form method="POST" action="">
          <label>Student ID</label>
          <input type="text" name="StudentID" required>

          <label>First Name</label>
          <input type="text" name="FirstName" required>

          <label>Last Name</label>
          <input type="text" name="LastName" required>

          <label>Gender</label>
          <select name="Gender" required>
            <option value="Female">Female</option>
            <option value="Male">Male</option>
            <option value="Prefer not to say">Prefer not to say</option>
          </select>

          <label>Phone Number</label>
          <input type="text" name="PhoneNumber" required>

          <label>Address</label>
          <input type="text" name="Address" required>

          <label>Email</label>
          <input type="email" name="Email" required>

          <label>Nationality</label>
          <input type="text" name="Nationality" required>

          <label>Birthdate</label>
          <input type="date" name="Birthdate" required>

          <button type="submit" name="addStudent">Save</button>
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
        $('#studentTable').DataTable({
          dom: 'Bfrtip',
          buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print', 'colvis'
          ]
        });
      });
    </script>

        </body>
            </html>