<?php include 'connections.php';

// Handle AJAX update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['BookingID']) && !isset($_POST['deleteBooking'])) {
  $bookingid = intval($_POST['BookingID']);
  $bookingdate = $conn->real_escape_string($_POST['BookingDate']);
  $checkin = $conn->real_escape_string($_POST['CheckInDate']);
  $checkout = $conn->real_escape_string($_POST['CheckOutDate']);
  $room = intval($_POST['RoomNumber']);
  $type = $conn->real_escape_string($_POST['RoomType']);
  $bookingstatus = $conn->real_escape_string($_POST['BookingStatus']);
  $roomstatus = $conn->real_escape_string($_POST['RoomStatus']);
  $notes = $conn->real_escape_string($_POST['Notes']);
  $price = $conn->real_escape_string($_POST['Price']);

  $sql = "UPDATE booking SET 
    BookingDate='$bookingdate',
    CheckInDate='$checkin',
    CheckOutDate='$checkout',
    RoomNumber=$room,
    RoomType='$type',
    BookingStatus='$bookingstatus',
    RoomStatus='$roomstatus',
    Notes='$notes',
    Price='$price'
    WHERE BookingID=$bookingid";
  $success = $conn->query($sql);

  header('Content-Type: application/json');
  echo json_encode(['success' => $success]);
  exit;
}

// Handle delete booking AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteBooking']) && isset($_POST['BookingID'])) {
  $bookingid = intval($_POST['BookingID']);
  $sql = "DELETE FROM booking WHERE BookingID=$bookingid";
  $success = $conn->query($sql);
  header('Content-Type: application/json');
  echo json_encode(['success' => $success]);
  exit;
}

// Handle create booking POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createBooking'])) {
  $bookingdate = $conn->real_escape_string($_POST['BookingDate']);
  $checkin = $conn->real_escape_string($_POST['CheckInDate']);
  $checkout = $conn->real_escape_string($_POST['CheckOutDate']);
  $room = intval($_POST['RoomNumber']);
  $type = $conn->real_escape_string($_POST['RoomType']);
  $bookingstatus = $conn->real_escape_string($_POST['BookingStatus']);
  $roomstatus = $conn->real_escape_string($_POST['RoomStatus']);
  $notes = $conn->real_escape_string($_POST['Notes']);
  $price = $conn->real_escape_string($_POST['Price']);
  $sql = "INSERT INTO booking (BookingDate, CheckInDate, CheckOutDate, RoomNumber, RoomType, BookingStatus, RoomStatus, Notes, Price) VALUES ('$bookingdate', '$checkin', '$checkout', $room, '$type', '$bookingstatus', '$roomstatus', '$notes', '$price')";
  $conn->query($sql);
  header('Location: booking.php');
  exit;
}
// Fetch bookings
$resQuery = "SELECT * FROM booking";
$resResult = $conn->query($resQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Booking</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
    body { background-color: #f5f6fa; display: flex; }
    .sidebar { width: 200px; background: #008000; min-height: 100vh; padding: 0.5rem; color: white; position: fixed; left: 0; top: 0; bottom: 0; transition: left 0.3s, box-shadow 0.3s; z-index: 1000; }
    .sidebar-title { color: white; font-size: 1.4rem; font-weight: 500; margin-bottom: 1.5rem; padding: 1rem; }
    .nav-section { margin-bottom: 1rem; }
    .nav-link { display: flex; align-items: center; padding: 0.5rem 1rem; color: white; text-decoration: none; font-size: 0.9rem; margin-bottom: 0.25rem; transition: background-color 0.2s; }
    .nav-link:hover { background-color: rgba(255, 255, 255, 0.1); }
    .nav-link i { margin-right: 0.75rem; width: 20px; text-align: center; opacity: 0.9; }
    .management-label { color: #90EE90; font-size: 0.8em; margin: 1rem 0 0.5rem 1rem; }
    .toggle-btn { display: flex; align-items: center; justify-content: space-between; cursor: pointer; }
    .toggle-btn::after { content: '▼'; font-size: 0.7rem; margin-left: 0.5rem; }
    .submenu { margin-left: 1.5rem; display: none; }
    .submenu.active { display: block; }
    .main-content { flex: 1; padding: 2rem; margin-left: 200px; overflow-x: hidden; transition: margin-left 0.3s; }
    .reservation-section { max-width: 1200px; margin: 2rem auto; background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 2rem; }
    h1 { font-size: 2rem; margin-bottom: 1.5rem; color: #333; }
    .reservation-table { width: 100%; border-collapse: collapse; }
    .reservation-table th, .reservation-table td { padding: 1rem; border-bottom: 1px solid #f0f2f5; text-align: left; }
    .reservation-table th { background: #f8f9fa; color: #666; font-weight: 600; }
    .reservation-table td { color: #222; font-weight: 500; }
    .action-group {
      display: flex;
      flex-direction: row;
      gap: 0.3rem;
      align-items: center;
      justify-content: flex-start;
    }
    .action-link {
      color: #fff;
      background: #008000;
      border: none;
      border-radius: 0.5rem;
      padding: 0.4rem 0.7rem;
      cursor: pointer;
      text-decoration: none;
      font-size: 1.1rem;
      display: inline-block;
      transition: background 0.2s;
      min-width: 36px;
      text-align: center;
    }
    .action-link:hover { background: #005c00; }
    .delete-btn { background: #e74c3c !important; }
    .delete-btn:hover { background: #c0392b !important; }
    /* Modal styles */
    .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background: rgba(0,0,0,0.3); }
    .modal-content { background: #fff; margin: 5% auto; padding: 2rem; border-radius: 10px; width: 400px; position: relative; }
    .close { position: absolute; right: 1rem; top: 1rem; font-size: 1.5rem; color: #888; cursor: pointer; }
    .modal-content h2 { margin-bottom: 1rem; }
    .modal-content label { font-weight: 600; }
    .modal-content p { margin-bottom: 0.5rem; }
    /* Hamburger menu styles */
    .hamburger {
        display: none;
        position: fixed;
        top: 1rem;
        left: 1rem;
        z-index: 1100;
        width: 36px;
        height: 36px;
        background: #008000;
        border: none;
        border-radius: 6px;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .hamburger span {
        display: block;
        width: 22px;
        height: 3px;
        background: #fff;
        margin: 4px 0;
        border-radius: 2px;
        transition: 0.3s;
    }
    @media (max-width: 900px) {
        .main-content { margin-left: 0; padding: 1rem; }
        .sidebar { left: -220px; box-shadow: none; }
        .sidebar.active { left: 0; box-shadow: 2px 0 8px rgba(0,0,0,0.08); }
        .hamburger { display: flex; }
    }
    @media (max-width: 600px) {
        .reservation-section { padding: 1rem; }
        .reservation-table th, .reservation-table td { padding: 0.5rem; font-size: 0.9rem; }
        h1 { font-size: 1.2rem; }
    }
    @media (max-width: 500px) {
        .reservation-table, .reservation-table thead, .reservation-table tbody, .reservation-table th, .reservation-table td, .reservation-table tr {
            display: block;
            width: 100%;
        }
        .reservation-table thead { display: none; }
        .reservation-table tr { margin-bottom: 1rem; border-bottom: 2px solid #f0f2f5; }
        .reservation-table td {
            padding-left: 40%;
            position: relative;
            font-size: 1rem;
            border: none;
            border-bottom: 1px solid #f0f2f5;
        }
        .reservation-table td:before {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-weight: bold;
            color: #666;
            content: attr(data-label);
            font-size: 0.95rem;
        }
        .action-group {
          flex-direction: row;
          gap: 0.2rem;
        }
    }
    .search-filter-bar {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .search-input {
        padding: 0.7rem 2.5rem 0.7rem 2.5rem;
        border-radius: 1.2rem;
        border: none;
        background: #ededed;
        font-size: 1rem;
        width: 260px;
        outline: none;
    }
    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #888;
    }
    .search-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    .filter-btn, .create-btn {
        padding: 0.7rem 1.5rem;
        border-radius: 1rem;
        border: 2px solid #222;
        background: #f5f6fa;
        font-size: 1rem;
        cursor: pointer;
        margin-left: 0.5rem;
        transition: background 0.2s, color 0.2s;
    }
    .filter-btn:hover, .create-btn:hover {
        background: #222;
        color: #fff;
    }
    .filter-dropdown {
        display: none;
        position: absolute;
        top: 2.5rem;
        left: 0;
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 0.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        z-index: 10;
        min-width: 220px;
        padding: 1rem;
    }
    .filter-dropdown.active {
        display: block;
    }
    .filter-dropdown label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    .filter-dropdown input, .filter-dropdown select {
        width: 100%;
        margin-bottom: 1rem;
        padding: 0.4rem 0.7rem;
        border-radius: 0.5rem;
        border: 1px solid #ccc;
    }
    .filter-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    .modal-content form input, .modal-content form select {
        width: 100%;
        margin-bottom: 1rem;
        padding: 0.5rem 0.7rem;
        border-radius: 0.5rem;
        border: 1px solid #ccc;
    }
    .modal-content form button[type="submit"] {
        width: 100%;
        padding: 0.7rem;
        border-radius: 0.7rem;
        border: none;
        background: #008000;
        color: #fff;
        font-size: 1.1rem;
        font-weight: bold;
        cursor: pointer;
    }
    .modal-content form button[type="submit"]:hover {
        background: #005c00;
    }
    /* Delete Modal */
    #deleteModal .modal-content {
      width: 350px;
      text-align: center;
    }
    #deleteModal .modal-content button {
      width: 45%;
      margin: 0 2%;
      padding: 0.7rem 0;
      border-radius: 0.7rem;
      border: none;
      font-size: 1.1rem;
      font-weight: bold;
      cursor: pointer;
    }
    #deleteModal .modal-content .confirm-delete {
      background: #e74c3c;
      color: #fff;
    }
    #deleteModal .modal-content .confirm-delete:hover {
      background: #c0392b;
    }
    #deleteModal .modal-content .cancel-delete {
      background: #008000;
      color: #fff;
    }
    #deleteModal .modal-content .cancel-delete:hover {
      background: #005c00;
    }
    .filter-btn, .create-btn {
            padding: 0.7rem 1.5rem;
            border-radius: 1rem;
            border: 2px solid #222;
            background: #f5f6fa;
            font-size: 1rem;
            cursor: pointer;
            margin-left: 0.5rem;
            transition: background 0.2s, color 0.2s;
        }
        .filter-btn:hover, .create-btn:hover {
            background: #222;
            color: #fff;
        }
        .filter-dropdown {
            display: none;
            position: absolute;
            top: 2.5rem;
            left: 0;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            z-index: 10;
            min-width: 220px;
            padding: 1rem;
        }
        .filter-dropdown.active {
            display: block;
        }
        .filter-dropdown label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .filter-dropdown input, .filter-dropdown select {
            width: 100%;
            margin-bottom: 1rem;
            padding: 0.4rem 0.7rem;
            border-radius: 0.5rem;
            border: 1px solid #ccc;
        }
        .filter-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
  </style>
</head>
<body>
  <div class="sidebar">
        <h4 class="sidebar-title">Villa Valore Hotel</h4>
    <button class="hamburger" id="sidebarToggle" aria-label="Open sidebar">
        <span></span>
        <span></span>
        <span></span>
    </button>
        <div class="nav-section">
            <a class="nav-link" href="index.php"><i class="fas fa-th-large"></i>Dashboard</a>
            <a class="nav-link" href="student.php"><i class="fas fa-user"></i>Guest</a>
            <a class="nav-link" href="booking.php"><i class="fas fa-book"></i>Booking</a>
            <a class="nav-link" href="reservation.php"><i class="fas fa-calendar-check"></i>Reservation</a>
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
  <div class="main-content">
    <div class="reservation-section">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <h1 style="margin-bottom: 0; border-bottom: 4px solidrgb(255, 255, 255); display: inline-block; padding-bottom: 0.2rem;">Booking</h1>
        <div class="search-filter-bar">
          <div class="search-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" class="search-input" placeholder="Search">
          </div>
          <div style="position: relative;">
                        <button class="filter-btn" id="filterBtn">Filter</button>
                        <div class="filter-dropdown" id="filterDropdown">
                            <form id="filterForm">
                                <label>Booking ID <input type="text" name="BookingID"></label>
                                <label>Booking Date <input type="date" name="PBookingDate"></label>
                                <label>Check-in Date <input type="date" name="PCheckInDate"></label>
                                <label>Check-out Date <input type="date" name="PCheckOutDate"></label>
                                <label>Room Number <input type="text" name="RoomNumber"></label>
                                <label>Room Type
                                    <select name="RoomType">
                                        <option value="">Any</option>
                                        <option value="Standard">Standard</option>
                                        <option value="Deluxe">Deluxe</option>
                                        <option value="Suite">Suite</option>
                                    </select>
                                </label>
                                <label>Status
                                    <select name="Status">
                                        <option value="">Any</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Confirmed">Confirmed</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                </label>
                                <label>Room Status
                                    <select name="RoomStatus">
                                        <option value="">Any</option>
                                        <option value="Available">Available</option>
                                        <option value="Occupied">Occupied</option>
                                        <option value="Maintenance">Maintenance</option>
                                    </select>
                                <div class="filter-actions">
                                    <button type="button" id="applyFilterBtn" class="filter-btn">Apply</button>
                                    <button type="button" id="clearFilterBtn" class="filter-btn">Clear</button>
                                </div>
                            </form>
                        </div>
                    </div>

          <button class="create-btn" id="createBtn">Create Booking</button>
        </div>
      </div>
      <table class="reservation-table">
        <thead>
          <tr>
            <th>Booking ID</th>
            <th>Booking Date</th>
            <th>Check-in Date</th>
            <th>Check-out Date</th>
            <th>Room Number</th>
            <th>Room Type</th>
            <th>Booking Status</th>
            <th>Room Status</th>
            <th>Notes</th>
            <th>Price</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($resResult && $resResult->num_rows > 0): ?>
          <?php while($row = $resResult->fetch_assoc()): ?>
          <tr data-id="<?php echo $row['BookingID']; ?>">
            <td><b><?php echo $row['BookingID']; ?></b></td>
            <td><b><?php echo htmlspecialchars($row['BookingDate']); ?></b></td>
            <td><b><?php echo htmlspecialchars($row['CheckInDate']); ?></b></td>
            <td><b><?php echo htmlspecialchars($row['CheckOutDate']); ?></b></td>
            <td><?php echo $row['RoomNumber']; ?></td>
            <td><b><?php echo $row['RoomType']; ?></b></td>
            <td><b><?php echo $row['BookingStatus']; ?></b></td>
            <td><b><?php echo $row['RoomStatus']; ?></b></td>
            <td><?php echo htmlspecialchars($row['Notes']); ?></td>
            <td><?php echo htmlspecialchars($row['Price']); ?></td>
            <td>
              <div class="action-group">
                <button type="button" class="action-link edit-btn"
                    data-id="<?php echo $row['BookingID']; ?>"
                    data-bookingdate="<?php echo htmlspecialchars($row['BookingDate']); ?>"
                    data-checkin="<?php echo $row['CheckInDate']; ?>"
                    data-checkout="<?php echo $row['CheckOutDate']; ?>"
                    data-room="<?php echo $row['RoomNumber']; ?>"
                    data-type="<?php echo $row['RoomType']; ?>"
                    data-bookingstatus="<?php echo $row['BookingStatus']; ?>"
                    data-roomstatus="<?php echo $row['RoomStatus']; ?>"
                    data-notes="<?php echo htmlspecialchars($row['Notes']); ?>"
                    data-price="<?php echo htmlspecialchars($row['Price']); ?>"
                ><i class="fas fa-edit"></i></button>
                <button type="button" class="action-link view-btn"
                    data-id="<?php echo $row['BookingID']; ?>"
                    data-bookingdate="<?php echo htmlspecialchars($row['BookingDate']); ?>"
                    data-checkin="<?php echo $row['CheckInDate']; ?>"
                    data-checkout="<?php echo $row['CheckOutDate']; ?>"
                    data-room="<?php echo $row['RoomNumber']; ?>"
                    data-type="<?php echo $row['RoomType']; ?>"
                    data-bookingstatus="<?php echo $row['BookingStatus']; ?>"
                    data-roomstatus="<?php echo $row['RoomStatus']; ?>"
                    data-notes="<?php echo htmlspecialchars($row['Notes']); ?>"
                    data-price="<?php echo htmlspecialchars($row['Price']); ?>"
                ><i class="fas fa-eye"></i></button>
                <button type="button" class="action-link delete-btn"
                    data-id="<?php echo $row['BookingID']; ?>"
                ><i class="fas fa-trash"></i></button>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="11">No bookings found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <!-- Edit Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeEditModal">&times;</span>
      <h2>Edit Booking</h2>
      <form id="editForm">
        <input type="hidden" name="BookingID" id="editBookingID">
        <p><label>Booking Date:</label><br><input type="date" name="BookingDate" id="editBookingDate" required></p>
        <p><label>Check-in Date:</label><br><input type="date" name="CheckInDate" id="editCheckIn" required></p>
        <p><label>Check-out Date:</label><br><input type="date" name="CheckOutDate" id="editCheckOut" required></p>
        <p><label>Room Number:</label><br><input type="number" name="RoomNumber" id="editRoomNumber" required></p>
        <p><label>Room Type:</label><br>
          <select name="RoomType" id="editRoomType" required>
            <option value="Standard">Standard</option>
            <option value="Deluxe">Deluxe</option>
            <option value="Suite">Suite</option>
          </select>
        </p>
        <p><label>Booking Status:</label><br>
          <select name="BookingStatus" id="editBookingStatus" required>
            <option value="Pending">Pending</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </p>
        <p><label>Room Status:</label><br>
          <select name="RoomStatus" id="editRoomStatus" required>
            <option value="Available">Available</option>
            <option value="Occupied">Occupied</option>
            <option value="Maintenance">Maintenance</option>
          </select>
        </p>
        <p><label>Notes:</label><br><input type="text" name="Notes" id="editNotes"></p>
        <p><label>Price:</label><br><input type="number" step="0.01" name="Price" id="editPrice" required></p>
        <button type="submit" style="margin-top:1rem;">Save</button>
      </form>
    </div>
  </div>
  <!-- View Modal -->
  <div id="viewModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeViewModal">&times;</span>
      <h2>View Booking</h2>
      <div id="viewDetails">
        <!-- Details will be filled by JS -->
      </div>
    </div>
  </div>
  <!-- Create Booking Modal -->
  <div id="createModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeCreateModal">&times;</span>
      <h2>Create Booking</h2>
      <form id="createForm" method="POST">
        <input type="hidden" name="createBooking" value="1">
        <p><label>Booking Date:</label><br><input type="date" name="BookingDate" required></p>
        <p><label>Check-in Date:</label><br><input type="date" name="CheckInDate" required></p>
        <p><label>Check-out Date:</label><br><input type="date" name="CheckOutDate" required></p>
        <p><label>Room Number:</label><br><input type="number" name="RoomNumber" required></p>
        <p><label>Room Type:</label><br>
          <select name="RoomType" required>
            <option value="Standard">Standard</option>
            <option value="Deluxe">Deluxe</option>
            <option value="Suite">Suite</option>
          </select>
        </p>
        <p><label>Booking Status:</label><br>
          <select name="BookingStatus" required>
            <option value="Pending">Pending</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </p>
        <p><label>Room Status:</label><br>
          <select name="RoomStatus" required>
            <option value="Available">Available</option>
            <option value="Occupied">Occupied</option>
            <option value="Maintenance">Maintenance</option>
          </select>
        </p>
        <p><label>Notes:</label><br><input type="text" name="Notes"></p>
        <p><label>Price:</label><br><input type="number" step="0.01" name="Price" required></p>
        <button type="submit">Create</button>
      </form>
    </div>
  </div>
  <!-- Delete Modal -->
  <div id="deleteModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeDeleteModal">&times;</span>
      <h2>Delete Booking</h2>
      <p>Are you sure you want to delete this booking?</p>
      <div style="margin-top:1.5rem;">
        <button class="confirm-delete">Delete</button>
        <button class="cancel-delete">Cancel</button>
      </div>
    </div>
  </div>
  <script>
  // Edit Modal
  const editModal = document.getElementById('editModal');
  const closeEditModal = document.getElementById('closeEditModal');
  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.onclick = function() {
      editModal.style.display = 'block';
      document.getElementById('editBookingID').value = this.dataset.id;
      document.getElementById('editBookingDate').value = this.dataset.bookingdate;
      document.getElementById('editCheckIn').value = this.dataset.checkin;
      document.getElementById('editCheckOut').value = this.dataset.checkout;
      document.getElementById('editRoomNumber').value = this.dataset.room;
      document.getElementById('editRoomType').value = this.dataset.type;
      document.getElementById('editBookingStatus').value = this.dataset.bookingstatus;
      document.getElementById('editRoomStatus').value = this.dataset.roomstatus;
      document.getElementById('editNotes').value = this.dataset.notes;
      document.getElementById('editPrice').value = this.dataset.price;
    }
  });
  closeEditModal.onclick = function() { editModal.style.display = 'none'; }
  // Save Edit
  const editForm = document.getElementById('editForm');
  editForm.onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(editForm);
    fetch('booking.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        location.reload();
      } else {
        alert('Update failed.');
      }
    });
  }
  // View Modal
  const viewModal = document.getElementById('viewModal');
  const closeViewModal = document.getElementById('closeViewModal');
  document.querySelectorAll('.view-btn').forEach(btn => {
    btn.onclick = function() {
      viewModal.style.display = 'block';
      document.getElementById('viewDetails').innerHTML = `
        <p><label>Booking ID:</label> <span>${this.dataset.id}</span></p>
        <p><label>Booking Date:</label> <span>${this.dataset.bookingdate}</span></p>
        <p><label>Check-in Date:</label> <span>${this.dataset.checkin}</span></p>
        <p><label>Check-out Date:</label> <span>${this.dataset.checkout}</span></p>
        <p><label>Room Number:</label> <span>${this.dataset.room}</span></p>
        <p><label>Room Type:</label> <span>${this.dataset.type}</span></p>
        <p><label>Booking Status:</label> <span>${this.dataset.bookingstatus}</span></p>
        <p><label>Room Status:</label> <span>${this.dataset.roomstatus}</span></p>
        <p><label>Notes:</label> <span>${this.dataset.notes}</span></p>
        <p><label>Price:</label> <span>${this.dataset.price}</span></p>
      `;
    }
  });
  closeViewModal.onclick = function() { viewModal.style.display = 'none'; }
  window.onclick = function(event) {
    if (event.target == editModal) editModal.style.display = 'none';
    if (event.target == viewModal) viewModal.style.display = 'none';
    if (event.target == createModal) createModal.style.display = 'none';
    if (event.target == deleteModal) deleteModal.style.display = 'none';
  }
  // Search logic
  const searchInput = document.getElementById('searchInput');
  const tableRows = document.querySelectorAll('.reservation-table tbody tr');
  searchInput.oninput = function() {
    const val = searchInput.value.toLowerCase();
    tableRows.forEach(row => {
      let match = false;
      row.querySelectorAll('td').forEach(cell => {
        if (cell.innerText.toLowerCase().includes(val)) match = true;
      });
      row.style.display = match ? '' : 'none';
    });
  }
  // Create Booking Modal
  const createModal = document.getElementById('createModal');
  const createBtn = document.getElementById('createBtn');
  const closeCreateModal = document.getElementById('closeCreateModal');
  createBtn.onclick = function() { createModal.style.display = 'block'; }
  closeCreateModal.onclick = function() { createModal.style.display = 'none'; }

  // Delete Modal
  const deleteModal = document.getElementById('deleteModal');
  const closeDeleteModal = document.getElementById('closeDeleteModal');
  let deleteBookingId = null;
  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.onclick = function() {
      deleteBookingId = this.dataset.id;
      deleteModal.style.display = 'block';
    }
  });

  // Search and filter logic
    const searchInput = document.getElementById('searchInput');
    const filterBtn = document.getElementById('filterBtn');
    const filterDropdown = document.getElementById('filterDropdown');
    const applyFilterBtn = document.getElementById('applyFilterBtn');
    const clearFilterBtn = document.getElementById('clearFilterBtn');
    const filterForm = document.getElementById('filterForm');
    const tableRows = document.querySelectorAll('.reservation-table tbody tr');

    filterBtn.onclick = function() {
        filterDropdown.classList.toggle('active');
    }
    document.addEventListener('click', function(e) {
        if (!filterDropdown.contains(e.target) && e.target !== filterBtn) {
            filterDropdown.classList.remove('active');
        }
    });

  closeDeleteModal.onclick = function() { deleteModal.style.display = 'none'; }
  document.querySelector('#deleteModal .cancel-delete').onclick = function() {
    deleteModal.style.display = 'none';
    deleteBookingId = null;
  }
  document.querySelector('#deleteModal .confirm-delete').onclick = function() {
    if (!deleteBookingId) return;
    const formData = new FormData();
    formData.append('deleteBooking', 1);
    formData.append('BookingID', deleteBookingId);
    fetch('booking.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        location.reload();
      } else {
        alert('Delete failed.');
      }
    });
  }
  </script>
</body>
</html>
