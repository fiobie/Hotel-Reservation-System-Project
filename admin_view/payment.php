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
        .table-scroll {
        width: 100%;
        overflow-x: auto;
    }

      </style>
    </head>
    <body>

<?php
    include 'connections.php';

    if (isset($_POST['addInvoice'])) {
      $PaymentID = $_POST['PaymentID'];
      $Amount = $_POST['Amount'];
      $PaymentStatus = $_POST['PaymentStatus'];
      $PaymentDate = $_POST['PaymentDate'];
      $PaymentMethod = $_POST['PaymentMethod'];
      $Discount = $_POST['Discount'];
      $TotalBill = $_POST['TotalBill'];
      $ReferenceCode = $_POST['ReferenceCode'];

      $stmt = $conn->prepare("INSERT INTO payment (PaymentID, Amount, PaymentStatus, PaymentDate, PaymentMethod, Discount, TotalBill, ReferenceCode) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("issssiss", $PaymentID, $Amount, $PaymentStatus, $PaymentDate, $PaymentMethod, $Discount, $TotalBill, $ReferenceCode);

      if ($stmt->execute()) {
        echo "<script>alert('Invoice added successfully!'); window.location.href=window.location.href;</script>";
      } else {
        echo "<script>alert('Error adding invoice: " . $stmt->error . "');</script>";
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
      <h2>Payments</h2>
      <p>This is for the payments.</p>

      <button class="add-btn" onclick="document.getElementById('addRoomModal').style.display='block'">+ Add Invoice</button>

      <div class="table-scroll">
      <?php
      $sql = "SELECT PaymentID, Amount, PaymentStatus, PaymentDate, PaymentMethod, Discount, TotalBill, ReferenceCode FROM payment";
      $result = $conn->query($sql);

      if ($result->num_rows > 0) {
          echo "<table id='paymentTable' class='display nowrap' style='width:100%'>
                  <thead> 
                    <tr>
                        <th>Payment ID</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Discount</th>
                        <th>Total Bill</th>
                        <th>Reference Code</th>
                        <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>";
          while($row = $result->fetch_assoc()) {
              echo "<tr>
                      <td>" . htmlspecialchars($row["PaymentID"]) . "</td>
                      <td>" . htmlspecialchars($row["Amount"]) . "</td>
                      <td>" . htmlspecialchars($row["PaymentStatus"]) . "</td>
                      <td>" . htmlspecialchars($row["PaymentDate"]) . "</td>
                      <td>" . htmlspecialchars($row["PaymentMethod"]) . "</td>
                      <td>" . htmlspecialchars($row["Discount"]) . "</td>
                      <td>" . htmlspecialchars($row["TotalBill"]) . "</td>
                      <td>" . htmlspecialchars($row["ReferenceCode"]) . "</td>
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
          <label>Payment ID</label>
          <input type="text" name="PaymentID" required>

          <label>Amount</label>
          <input type="text" name="Amount" required>

          <label>Status</label>
          <select name="PaymentStatus" required>
            <option value="Pending">Pending</option>
            <option value="Unpaid">Unpaid</option>
            <option value="Failed">Failed</option>
            <option value="Refunded">Refunded</option>
          </select>

          <label>Date</label>
          <input type="date" name="PaymentDate" required>

          <label>Method</label>
          <select name="PaymentMethod" required>
            <option value="Credit Card">Cash</option>
            <option value="PayPal">Card</option>
            <option value="Bank Transfer">Online</option>
          </select>

          <label>Discount</label>
          <input type="number" name="Discount" required>

          <label>Total Bill</label>
          <input type="number" name="TotalBill" required>

          <label>Reference Code</label>
          <input type="text" name="ReferenceCode" required>

          <button type="submit" name="addPayment">Save</button>
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
        $('#paymentTable').DataTable({
          dom: 'Bfrtip',
          buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print', 'colvis'
          ]
        });
      });
    </script>

        </body>
            </html>