<?php
// Database Connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "hotel_reservation_systemdb";
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Handle AJAX update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ReservationID'])) {
    $id = intval($_POST['ReservationID']);
    $guest = $conn->real_escape_string($_POST['GuestName']);
    $checkin = $conn->real_escape_string($_POST['PCheckInDate']);
    $checkout = $conn->real_escape_string($_POST['PCheckOutDate']);
    $room = intval($_POST['RoomNumber']);
    $type = $conn->real_escape_string($_POST['RoomType']);
    $status = $conn->real_escape_string($_POST['Status']);
    $studentID = $conn->real_escape_string($_POST['StudentID']);

    $sql = "UPDATE reservations SET 
        GuestName='$guest',
        PCheckInDate='$checkin',
        PCheckOutDate='$checkout',
        RoomNumber=$room,
        RoomType='$type',
        Status='$status',
        StudentID='$studentID'
        WHERE ReservationID=$id";
    $success = $conn->query($sql);

    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}
// Handle create reservation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createReservation'])) {
    $guest = $conn->real_escape_string($_POST['GuestName']);
    $checkin = $conn->real_escape_string($_POST['PCheckInDate']);
    $checkout = $conn->real_escape_string($_POST['PCheckOutDate']);
    $room = intval($_POST['RoomNumber']);
    $type = $conn->real_escape_string($_POST['RoomType']);
    $status = $conn->real_escape_string($_POST['Status']);
    $studentID = $conn->real_escape_string($_POST['StudentID']);
    $sql = "INSERT INTO reservations (GuestName, PCheckInDate, PCheckOutDate, RoomNumber, RoomType, Status, StudentID) VALUES ('$guest', '$checkin', '$checkout', $room, '$type', '$status', '$studentID')";
    $conn->query($sql);
    header('Location: reservation.php');
    exit;
}
// Fetch reservations
$resQuery = "SELECT * FROM reservations"; // Adjust table name/fields as needed
$resResult = $conn->query($resQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation</title>
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
        .action-link { color: #008000; cursor: pointer; margin-right: 1rem; text-decoration: underline; }
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
    </style>
</head>
<body>
    <button class="hamburger" id="sidebarToggle" aria-label="Open sidebar">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <div class="sidebar">
        <h4 class="sidebar-title">Villa Valore Hotel</h4>
        
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
                <h1 style="margin-bottom: 0; border-bottom: 4px solidrgb(255, 255, 255); display: inline-block; padding-bottom: 0.2rem;">Reservation</h1>
                <div class="search-filter-bar">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Search">
                    </div>
                    <div style="position: relative;">
                        <button class="filter-btn" id="filterBtn">Filter</button>
                        <div class="filter-dropdown" id="filterDropdown">
                            <form id="filterForm">
                                <label>Reservation ID <input type="text" name="ReservationID"></label>
                                <label>Student ID <input type="text" name="StudentID"></label>
                                <label>Guest Name <input type="text" name="GuestName"></label>
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
                                <div class="filter-actions">
                                    <button type="button" id="applyFilterBtn" class="filter-btn">Apply</button>
                                    <button type="button" id="clearFilterBtn" class="filter-btn">Clear</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <button class="create-btn" id="createBtn">Create Reservation</button>
                </div>
            </div>
            <table class="reservation-table">
                <thead>
                    <tr>
                        <th>Reservation ID</th>
                        <th>Student ID</th>
                        <th>Guest Name</th>
                        <th>Check-in Date</th>
                        <th>Check-out Date</th>
                        <th>Room Number</th>
                        <th>Room Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($resResult && $resResult->num_rows > 0): ?>
                    <?php while($row = $resResult->fetch_assoc()): ?>
                    <tr data-id="<?php echo $row['ReservationID']; ?>">
                        <td><b><?php echo $row['ReservationID']; ?></b></td>
                        <td><b><?php echo $row['StudentID']; ?></b></td>
                        <td><b><?php echo htmlspecialchars($row['GuestName']); ?></b></td>
                        <td><b><?php echo date('m/d/Y', strtotime($row['PCheckInDate'])); ?></b></td>
                        <td><b><?php echo date('m/d/Y', strtotime($row['PCheckOutDate'])); ?></b></td>
                        <td><?php echo $row['RoomNumber']; ?></td>
                        <td><b><?php echo $row['RoomType']; ?></b></td>
                        <td><b><?php echo $row['Status']; ?></b></td>
                        <td>
                            <span class="action-link edit-btn"
                                  data-id="<?php echo $row['ReservationID']; ?>"
                                  data-student="<?php echo $row['StudentID']; ?>"
                                  data-guest="<?php echo htmlspecialchars($row['GuestName']); ?>"
                                  data-checkin="<?php echo $row['PCheckInDate']; ?>"
                                  data-checkout="<?php echo $row['PCheckOutDate']; ?>"
                                  data-room="<?php echo $row['RoomNumber']; ?>"
                                  data-type="<?php echo $row['RoomType']; ?>"
                                  data-status="<?php echo $row['Status']; ?>"
                            >Edit</span>
                            <span class="action-link view-btn"
                                  data-id="<?php echo $row['ReservationID']; ?>"
                                  data-student="<?php echo $row['StudentID']; ?>"
                                  data-guest="<?php echo htmlspecialchars($row['GuestName']); ?>"
                                  data-checkin="<?php echo $row['PCheckInDate']; ?>"
                                  data-checkout="<?php echo $row['PCheckOutDate']; ?>"
                                  data-room="<?php echo $row['RoomNumber']; ?>"
                                  data-type="<?php echo $row['RoomType']; ?>"
                                  data-status="<?php echo $row['Status']; ?>"
                            >View</span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8">No reservations found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeEditModal">&times;</span>
            <h2>Edit Reservation</h2>
            <form id="editForm">
                <input type="hidden" name="ReservationID" id="editReservationID">
                <input type="hidden" name="StudentID" id="editStudentID">
                <p><label>Guest Name:</label><br><input type="text" name="GuestName" id="editGuestName" required></p>
                <p><label>Check-in Date:</label><br><input type="date" name="PCheckInDate" id="editCheckIn" required></p>
                <p><label>Check-out Date:</label><br><input type="date" name="PCheckOutDate" id="editCheckOut" required></p>
                <p><label>Room Number:</label><br><input type="number" name="RoomNumber" id="editRoomNumber" required></p>
                <p><label>Room Type:</label><br>
                    <select name="RoomType" id="editRoomType" required>
                        <option value="Standard">Standard</option>
                        <option value="Deluxe">Deluxe</option>
                        <option value="Suite">Suite</option>
                    </select>
                </p>
                <p><label>Status:</label><br>
                    <select name="Status" id="editStatus" required>
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </p>
                <button type="submit" style="margin-top:1rem;">Save</button>
            </form>
        </div>
    </div>
    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeViewModal">&times;</span>
            <h2>View Reservation</h2>
            <div id="viewDetails">
                <!-- Details will be filled by JS -->
            </div>
        </div>
    </div>
    <!-- Create Reservation Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeCreateModal">&times;</span>
            <h2>Create Reservation</h2>
            <form id="createForm" method="POST">
                <input type="hidden" name="createReservation" value="1">
                <p><label>Guest Name:</label><br><input type="text" name="GuestName" required></p>
                <p><label>Check-in Date:</label><br><input type="date" name="PCheckInDate" required></p>
                <p><label>Check-out Date:</label><br><input type="date" name="PCheckOutDate" required></p>
                <p><label>Room Number:</label><br><input type="number" name="RoomNumber" required></p>
                <p><label>Room Type:</label><br>
                    <select name="RoomType" required>
                        <option value="Standard">Standard</option>
                        <option value="Deluxe">Deluxe</option>
                        <option value="Suite">Suite</option>
                    </select>
                </p>
                <p><label>Status:</label><br>
                    <select name="Status" required>
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </p>
                <button type="submit">Create</button>
            </form>
        </div>
    </div>
    <script>
    // Sidebar toggle menu functionality
    function toggleMenu(menuId) {
        const submenu = document.getElementById(menuId);
        submenu.classList.toggle('active');
    }
    // Hamburger menu for sidebar
    const sidebar = document.querySelector('.sidebar');
    const hamburger = document.getElementById('sidebarToggle');
    function closeSidebarOnOverlayClick(e) {
        if (window.innerWidth <= 900 && sidebar.classList.contains('active')) {
            if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    }
    hamburger.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
    document.addEventListener('click', closeSidebarOnOverlayClick);
    window.addEventListener('resize', function() {
        if (window.innerWidth > 900) {
            sidebar.classList.remove('active');
        }
    });
    // Edit Modal
    const editModal = document.getElementById('editModal');
    const closeEditModal = document.getElementById('closeEditModal');
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.onclick = function() {
            editModal.style.display = 'block';
            document.getElementById('editReservationID').value = this.dataset.id;
            document.getElementById('editStudentID').value = this.dataset.student;
            document.getElementById('editGuestName').value = this.dataset.guest;
            document.getElementById('editCheckIn').value = this.dataset.checkin.split('T')[0];
            document.getElementById('editCheckOut').value = this.dataset.checkout.split('T')[0];
            document.getElementById('editRoomNumber').value = this.dataset.room;
            document.getElementById('editRoomType').value = this.dataset.type;
            document.getElementById('editStatus').value = this.dataset.status;
        }
    });
    closeEditModal.onclick = function() { editModal.style.display = 'none'; }
    // Save Edit
    const editForm = document.getElementById('editForm');
    editForm.onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(editForm);
        fetch('update_reservation.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update the table row in the UI
                const row = document.querySelector('tr[data-id="' + formData.get('ReservationID') + '"]');
                row.children[1].innerHTML = '<b>' + formData.get('StudentID') + '</b>';
                row.children[2].innerHTML = '<b>' + formData.get('GuestName') + '</b>';
                row.children[3].innerHTML = '<b>' + new Date(formData.get('PCheckInDate')).toLocaleDateString() + '</b>';
                row.children[4].innerHTML = '<b>' + new Date(formData.get('PCheckOutDate')).toLocaleDateString() + '</b>';
                row.children[5].innerHTML = formData.get('RoomNumber');
                row.children[6].innerHTML = '<b>' + formData.get('RoomType') + '</b>';
                row.children[7].innerHTML = '<b>' + formData.get('Status') + '</b>';
                editModal.style.display = 'none';
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
                <p><label>Reservation ID:</label> <span>${this.dataset.id}</span></p>
                <p><label>Student ID:</label> <span>${this.dataset.student}</span></p>
                <p><label>Guest Name:</label> <span>${this.dataset.guest}</span></p>
                <p><label>Check-in Date:</label> <span>${this.dataset.checkin.split('T')[0]}</span></p>
                <p><label>Check-out Date:</label> <span>${this.dataset.checkout.split('T')[0]}</span></p>
                <p><label>Room Number:</label> <span>${this.dataset.room}</span></p>
                <p><label>Room Type:</label> <span>${this.dataset.type}</span></p>
                <p><label>Status:</label> <span>${this.dataset.status}</span></p>
            `;
        }
    });
    closeViewModal.onclick = function() { viewModal.style.display = 'none'; }
    window.onclick = function(event) {
        if (event.target == editModal) editModal.style.display = 'none';
        if (event.target == viewModal) viewModal.style.display = 'none';
    }
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

    function rowMatches(row, filters) {
        const cells = row.querySelectorAll('td');
        // ReservationID, GuestName, Check-in, Check-out, RoomNumber, RoomType, Status
        if (filters.ReservationID && !cells[0].innerText.toLowerCase().includes(filters.ReservationID.toLowerCase())) return false;
        if (filters.StudentID && !cells[1].innerText.toLowerCase().includes(filters.StudentID.toLowerCase())) return false;
        if (filters.GuestName && !cells[1].innerText.toLowerCase().includes(filters.GuestName.toLowerCase())) return false;
        if (filters.PCheckInDate && !cells[2].innerText.includes(filters.PCheckInDate)) return false;
        if (filters.PCheckOutDate && !cells[3].innerText.includes(filters.PCheckOutDate)) return false;
        if (filters.RoomNumber && !cells[4].innerText.toLowerCase().includes(filters.RoomNumber.toLowerCase())) return false;
        if (filters.RoomType && !cells[5].innerText.toLowerCase().includes(filters.RoomType.toLowerCase())) return false;
        if (filters.Status && !cells[6].innerText.toLowerCase().includes(filters.Status.toLowerCase())) return false;
        return true;
    }

    applyFilterBtn.onclick = function() {
        const formData = new FormData(filterForm);
        const filters = Object.fromEntries(formData.entries());
        tableRows.forEach(row => {
            row.style.display = rowMatches(row, filters) ? '' : 'none';
        });
        filterDropdown.classList.remove('active');
    }
    clearFilterBtn.onclick = function() {
        filterForm.reset();
        tableRows.forEach(row => { row.style.display = ''; });
    }
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
    // Create Reservation Modal
    const createModal = document.getElementById('createModal');
    const createBtn = document.getElementById('createBtn');
    const closeCreateModal = document.getElementById('closeCreateModal');
    createBtn.onclick = function() { createModal.style.display = 'block'; }
    closeCreateModal.onclick = function() { createModal.style.display = 'none'; }
    window.onclick = function(event) {
        if (event.target == createModal) createModal.style.display = 'none';
        if (event.target == editModal) editModal.style.display = 'none';
        if (event.target == viewModal) viewModal.style.display = 'none';
    }
    </script>
</body>
</html> 
