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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ReservationID']) && !isset($_POST['createReservation'])) {
    $id = intval($_POST['ReservationID']);
    $guest = $conn->real_escape_string($_POST['GuestName']);
    $checkin = $conn->real_escape_string($_POST['PCheckInDate']);
    $checkout = $conn->real_escape_string($_POST['PCheckOutDate']);
    $room = intval($_POST['RoomNumber']);
    $type = $conn->real_escape_string($_POST['RoomType']);
    $status = $conn->real_escape_string($_POST['Status']);

    // Validate dates
    if (strtotime($checkin) >= strtotime($checkout)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Check-out date must be after check-in date']);
        exit;
    }

    $sql = "UPDATE reservation SET 
        GuestName='$guest',
        PCheckInDate='$checkin',
        PCheckOutDate='$checkout',
        RoomNumber=$room,
        RoomType='$type',
        Status='$status'
        WHERE ReservationID=$id";
    $success = $conn->query($sql);

    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $success ? 'Reservation updated successfully' : 'Failed to update reservation']);
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
    
    // Validate dates
    if (strtotime($checkin) >= strtotime($checkout)) {
        $error = "Check-out date must be after check-in date";
    } else {
        // Check if room is available for the selected dates
        $checkSql = "SELECT COUNT(*) as count FROM reservation 
                     WHERE RoomNumber = $room 
                     AND Status != 'Cancelled'
                     AND ((PCheckInDate <= '$checkin' AND PCheckOutDate > '$checkin') 
                          OR (PCheckInDate < '$checkout' AND PCheckOutDate >= '$checkout')
                          OR (PCheckInDate >= '$checkin' AND PCheckOutDate <= '$checkout'))";
        $checkResult = $conn->query($checkSql);
        $checkRow = $checkResult->fetch_assoc();
        
        if ($checkRow['count'] > 0) {
            $error = "Room $room is not available for the selected dates";
        } else {
            $sql = "INSERT INTO reservation (GuestName, PCheckInDate, PCheckOutDate, RoomNumber, RoomType, Status) 
                    VALUES ('$guest', '$checkin', '$checkout', $room, '$type', '$status')";
            if ($conn->query($sql)) {
                header('Location: reservation.php?success=1');
    exit;
            } else {
                $error = "Failed to create reservation: " . $conn->error;
            }
        }
    }
}
// Fetch reservations with search and filter
$whereClause = "WHERE 1=1";
$params = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $whereClause .= " AND (GuestName LIKE '%$search%' OR ReservationID LIKE '%$search%' OR RoomNumber LIKE '%$search%')";
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $whereClause .= " AND Status = '$status'";
}

if (isset($_GET['room_type']) && !empty($_GET['room_type'])) {
    $roomType = $conn->real_escape_string($_GET['room_type']);
    $whereClause .= " AND RoomType = '$roomType'";
}

if (isset($_GET['checkin_date']) && !empty($_GET['checkin_date'])) {
    $checkinDate = $conn->real_escape_string($_GET['checkin_date']);
    $whereClause .= " AND DATE(PCheckInDate) = '$checkinDate'";
}

$resQuery = "SELECT * FROM reservation $whereClause ORDER BY ReservationID DESC";
$resResult = $conn->query($resQuery);

// Get available rooms for create form
$roomsQuery = "SELECT RoomNumber, RoomType FROM room WHERE RoomStatus = 'Available' ORDER BY RoomNumber";
$roomsResult = $conn->query($roomsQuery);
$availableRooms = [];
if ($roomsResult) {
    while ($room = $roomsResult->fetch_assoc()) {
        $availableRooms[] = $room;
    }
}
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
        .sidebar-logo {
            display: block;
            margin: 1.5rem auto;
            width: 80px;
            height: auto;
        }
        .nav-section { margin-bottom: 1rem; }
        .nav-link { display: flex; align-items: center; padding: 0.5rem 1rem; color: white; text-decoration: none; font-size: 0.9rem; margin-bottom: 0.25rem; }
        .nav-link:hover, .nav-link.active { background-color: rgba(255, 255, 255, 0.1); }
        .nav-link i { margin-right: 0.75rem; width: 20px; text-align: center; }
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
        /* Enhanced Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .action-btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 0.4rem;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .edit-btn {
            background: #007bff;
            color: white;
        }
        .edit-btn:hover {
            background: #0056b3;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .view-btn {
            background: #28a745;
            color: white;
        }
        .view-btn:hover {
            background: #1e7e34;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .action-btn i {
            font-size: 0.8rem;
        }
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
        /* Enhanced Modal and Form Styling */
        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.7rem;
            border-radius: 0.5rem;
            border: 1px solid #ccc;
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-1px);
        }
        /* View Details Styling */
        .view-details {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
            min-width: 120px;
        }
        .detail-value {
            color: #212529;
            font-weight: 500;
        }
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
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
        <img src="images/villavalorelogo.png" alt="Villa Valore Logo" class="sidebar-logo">
        <h4 class="sidebar-title">Villa Valore</h4>
        <a class="nav-link" href="staff_dashboard.php"><i class="fas fa-th-large"></i>Dashboard</a>
        <a class="nav-link active" href="reservation.php"><i class="fas fa-calendar-check"></i>Reservation</a>
        <a class="nav-link" href="booking.php"><i class="fas fa-book"></i>Booking</a>
        <a class="nav-link" href="room.php"><i class="fas fa-door-open"></i>Room</a>
        <a class="nav-link" href="guest_request.php"><i class="fas fa-comment-dots"></i>Guest Request</a>
        <a class="nav-link" href="staff_inventory.php"><i class="fas fa-box"></i>Inventory</a>
        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i>Log out</a>
    </div>
    <div class="main-content">
        <div class="reservation-section">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #c3e6cb;">
                    <i class="fas fa-check-circle"></i> Reservation created successfully!
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #f5c6cb;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                <h1 style="margin-bottom: 0; border-bottom: 4px solid #a084e8; display: inline-block; padding-bottom: 0.2rem;">Reservation</h1>
                <div class="search-filter-bar">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Search by guest name, ID, or room number" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button id="clearSearchBtn" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); border:none; background:transparent; color:#888; font-size:1.2rem; cursor:pointer;">&times;</button>
                    </div>
                    <div style="position: relative;">
                        <button class="filter-btn" id="filterBtn">
                            <i class="fas fa-filter"></i> Filter
                            <?php if (isset($_GET['status']) || isset($_GET['room_type']) || isset($_GET['checkin_date'])): ?>
                                <span style="background: #ff4444; color: white; border-radius: 50%; width: 18px; height: 18px; display: inline-block; font-size: 0.7rem; line-height: 18px; margin-left: 5px;">!</span>
                            <?php endif; ?>
                        </button>
                        <div class="filter-dropdown" id="filterDropdown">
                            <form id="filterForm">
                                <label>Status:
                                    <select name="status">
                                        <option value="">Any Status</option>
                                        <option value="Pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Confirmed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="Cancelled" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </label>
                                <label>Room Type:
                                    <select name="room_type">
                                        <option value="">Any Type</option>
                                        <option value="Standard" <?php echo (isset($_GET['room_type']) && $_GET['room_type'] == 'Standard') ? 'selected' : ''; ?>>Standard</option>
                                        <option value="Deluxe" <?php echo (isset($_GET['room_type']) && $_GET['room_type'] == 'Deluxe') ? 'selected' : ''; ?>>Deluxe</option>
                                        <option value="Suite" <?php echo (isset($_GET['room_type']) && $_GET['room_type'] == 'Suite') ? 'selected' : ''; ?>>Suite</option>
                                    </select>
                                </label>
                                <label>Check-in Date:
                                    <input type="date" name="checkin_date" value="<?php echo isset($_GET['checkin_date']) ? htmlspecialchars($_GET['checkin_date']) : ''; ?>">
                                </label>
                                <div class="filter-actions">
                                    <button type="button" id="applyFilterBtn" class="filter-btn">Apply</button>
                                    <button type="button" id="clearFilterBtn" class="filter-btn">Clear</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <button class="create-btn" id="createBtn">
                        <i class="fas fa-plus"></i> Create Reservation
                    </button>
                </div>
            </div>
            <table class="reservation-table">
                <thead>
                    <tr>
                        <th>Reservation ID</th>
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
                        <td><b><?php echo htmlspecialchars($row['GuestName']); ?></b></td>
                        <td><b><?php echo date('m/d/Y', strtotime($row['PCheckInDate'])); ?></b></td>
                        <td><b><?php echo date('m/d/Y', strtotime($row['PCheckOutDate'])); ?></b></td>
                        <td><?php echo $row['RoomNumber']; ?></td>
                        <td><b><?php echo $row['RoomType']; ?></b></td>
                        <td><b><?php echo $row['Status']; ?></b></td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn edit-btn"
                                  data-id="<?php echo $row['ReservationID']; ?>"
                                  data-guest="<?php echo htmlspecialchars($row['GuestName']); ?>"
                                  data-checkin="<?php echo $row['PCheckInDate']; ?>"
                                  data-checkout="<?php echo $row['PCheckOutDate']; ?>"
                                  data-room="<?php echo $row['RoomNumber']; ?>"
                                  data-type="<?php echo $row['RoomType']; ?>"
                                  data-status="<?php echo $row['Status']; ?>"
                                >
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="action-btn view-btn"
                                  data-id="<?php echo $row['ReservationID']; ?>"
                                  data-guest="<?php echo htmlspecialchars($row['GuestName']); ?>"
                                  data-checkin="<?php echo $row['PCheckInDate']; ?>"
                                  data-checkout="<?php echo $row['PCheckOutDate']; ?>"
                                  data-room="<?php echo $row['RoomNumber']; ?>"
                                  data-type="<?php echo $row['RoomType']; ?>"
                                  data-status="<?php echo $row['Status']; ?>"
                                >
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </div>
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
            <h2><i class="fas fa-edit"></i> Edit Reservation</h2>
            <form id="editForm">
                <input type="hidden" name="ReservationID" id="editReservationID">
                
                <div class="form-group">
                    <label for="editGuestName">Guest Name:</label>
                    <input type="text" name="GuestName" id="editGuestName" required>
                </div>
                
                <div class="form-group">
                    <label for="editCheckIn">Check-in Date:</label>
                    <input type="date" name="PCheckInDate" id="editCheckIn" required>
                </div>
                
                <div class="form-group">
                    <label for="editCheckOut">Check-out Date:</label>
                    <input type="date" name="PCheckOutDate" id="editCheckOut" required>
                </div>
                
                <div class="form-group">
                    <label for="editRoomNumber">Room Number:</label>
                    <input type="number" name="RoomNumber" id="editRoomNumber" required min="1">
                </div>
                
                <div class="form-group">
                    <label for="editRoomType">Room Type:</label>
                    <select name="RoomType" id="editRoomType" required>
                        <option value="Standard">Standard</option>
                        <option value="Deluxe">Deluxe</option>
                        <option value="Suite">Suite</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="editStatus">Status:</label>
                    <select name="Status" id="editStatus" required>
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div id="editFormError" style="color: #dc3545; margin-bottom: 1rem; display: none;"></div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeViewModal">&times;</span>
            <h2><i class="fas fa-eye"></i> View Reservation Details</h2>
            <div id="viewDetails" class="view-details">
                <!-- Details will be filled by JavaScript -->
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
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
                <p><label>Check-in Date:</label><br><input type="date" name="PCheckInDate" id="createCheckIn" required min="<?php echo date('Y-m-d'); ?>"></p>
                <p><label>Check-out Date:</label><br><input type="date" name="PCheckOutDate" id="createCheckOut" required min="<?php echo date('Y-m-d'); ?>"></p>
                <p><label>Room Number:</label><br>
                    <select name="RoomNumber" id="createRoomNumber" required>
                        <option value="">Select a room</option>
                        <?php foreach ($availableRooms as $room): ?>
                            <option value="<?php echo $room['RoomNumber']; ?>">
                                Room <?php echo $room['RoomNumber']; ?> (<?php echo $room['RoomType']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p><label>Room Type:</label><br>
                    <select name="RoomType" id="createRoomType" required>
                        <option value="">Select room type</option>
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
                <div id="createFormError" style="color: #dc3545; margin-bottom: 1rem; display: none;"></div>
                <button type="submit">Create Reservation</button>
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
    
    // Enhanced Search Functionality
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length > 0) {
            clearSearchBtn.style.display = 'block';
        } else {
            clearSearchBtn.style.display = 'none';
        }
        
        // Debounce search to avoid too many requests
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
    
    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        clearSearchBtn.style.display = 'none';
        performSearch('');
    });
    
    function performSearch(query) {
        const currentUrl = new URL(window.location);
        if (query) {
            currentUrl.searchParams.set('search', query);
        } else {
            currentUrl.searchParams.delete('search');
        }
        window.location.href = currentUrl.toString();
    }
    
    // Enhanced Filter Functionality
    const filterBtn = document.getElementById('filterBtn');
    const filterDropdown = document.getElementById('filterDropdown');
    const applyFilterBtn = document.getElementById('applyFilterBtn');
    const clearFilterBtn = document.getElementById('clearFilterBtn');
    const filterForm = document.getElementById('filterForm');
    
    filterBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        filterDropdown.classList.toggle('active');
    });
    
    document.addEventListener('click', function(e) {
        if (!filterDropdown.contains(e.target) && e.target !== filterBtn) {
            filterDropdown.classList.remove('active');
        }
    });
    
    applyFilterBtn.addEventListener('click', function() {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams();
        
        for (let [key, value] of formData.entries()) {
            if (value) {
                params.append(key, value);
            }
        }
        
        // Preserve search query if exists
        const searchQuery = new URLSearchParams(window.location.search).get('search');
        if (searchQuery) {
            params.append('search', searchQuery);
        }
        
        window.location.href = window.location.pathname + '?' + params.toString();
    });
    
    clearFilterBtn.addEventListener('click', function() {
        filterForm.reset();
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.delete('status');
        currentUrl.searchParams.delete('room_type');
        currentUrl.searchParams.delete('checkin_date');
        window.location.href = currentUrl.toString();
    });
    
    // Enhanced Create Reservation
    const createModal = document.getElementById('createModal');
    const createBtn = document.getElementById('createBtn');
    const closeCreateModal = document.getElementById('closeCreateModal');
    const createForm = document.getElementById('createForm');
    const createFormError = document.getElementById('createFormError');
    
    createBtn.addEventListener('click', function() {
        createModal.style.display = 'block';
        createForm.reset();
        createFormError.style.display = 'none';
    });
    
    closeCreateModal.addEventListener('click', function() {
        createModal.style.display = 'none';
    });
    
    // Real-time validation for create form
    const createCheckIn = document.getElementById('createCheckIn');
    const createCheckOut = document.getElementById('createCheckOut');
    const createRoomNumber = document.getElementById('createRoomNumber');
    const createRoomType = document.getElementById('createRoomType');
    
    function validateCreateForm() {
        const checkIn = new Date(createCheckIn.value);
        const checkOut = new Date(createCheckOut.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        createFormError.style.display = 'none';
        
        if (checkIn < today) {
            showFormError('Check-in date cannot be in the past');
            return false;
        }
        
        if (checkOut <= checkIn) {
            showFormError('Check-out date must be after check-in date');
            return false;
        }
        
        if (!createRoomNumber.value) {
            showFormError('Please select a room');
            return false;
        }
        
        if (!createRoomType.value) {
            showFormError('Please select a room type');
            return false;
        }
        
        return true;
    }
    
    function showFormError(message) {
        createFormError.textContent = message;
        createFormError.style.display = 'block';
    }
    
    createForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateCreateForm()) {
            return;
        }
        
        // Submit form
        this.submit();
    });
    
    // Auto-update room type when room number is selected
    createRoomNumber.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const roomType = selectedOption.text.match(/\((.*?)\)/)[1];
            createRoomType.value = roomType;
        }
    });
    
    // Enhanced Edit Modal
    const editModal = document.getElementById('editModal');
    const closeEditModal = document.getElementById('closeEditModal');
    const editFormError = document.getElementById('editFormError');
    
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.onclick = function() {
            editModal.style.display = 'block';
            editFormError.style.display = 'none';
            
            // Populate form with current data
            document.getElementById('editReservationID').value = this.dataset.id;
            document.getElementById('editGuestName').value = this.dataset.guest;
            document.getElementById('editCheckIn').value = this.dataset.checkin.split('T')[0];
            document.getElementById('editCheckOut').value = this.dataset.checkout.split('T')[0];
            document.getElementById('editRoomNumber').value = this.dataset.room;
            document.getElementById('editRoomType').value = this.dataset.type;
            document.getElementById('editStatus').value = this.dataset.status;
        }
    });
    
    closeEditModal.onclick = function() { 
        editModal.style.display = 'none';
        editFormError.style.display = 'none';
    }
    
    // Enhanced View Modal
    const viewModal = document.getElementById('viewModal');
    const closeViewModal = document.getElementById('closeViewModal');
    
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.onclick = function() {
            viewModal.style.display = 'block';
            
            const status = this.dataset.status;
            const statusClass = status.toLowerCase();
            
            document.getElementById('viewDetails').innerHTML = `
                <div class="detail-item">
                    <span class="detail-label">Reservation ID:</span>
                    <span class="detail-value">#${this.dataset.id}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Guest Name:</span>
                    <span class="detail-value">${this.dataset.guest}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Check-in Date:</span>
                    <span class="detail-value">${formatDate(this.dataset.checkin)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Check-out Date:</span>
                    <span class="detail-value">${formatDate(this.dataset.checkout)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Room Number:</span>
                    <span class="detail-value">Room ${this.dataset.room}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Room Type:</span>
                    <span class="detail-value">${this.dataset.type}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Status:</span>
                    <span class="status-badge status-${statusClass}">${status}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Duration:</span>
                    <span class="detail-value">${calculateDuration(this.dataset.checkin, this.dataset.checkout)}</span>
                </div>
            `;
        }
    });
    
    closeViewModal.onclick = function() { 
        viewModal.style.display = 'none';
    }
    
    // Helper functions
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    function calculateDuration(checkIn, checkOut) {
        const start = new Date(checkIn);
        const end = new Date(checkOut);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return `${diffDays} day${diffDays !== 1 ? 's' : ''}`;
    }
    
    // Enhanced Edit Form Validation
    function validateEditForm() {
        const checkIn = new Date(document.getElementById('editCheckIn').value);
        const checkOut = new Date(document.getElementById('editCheckOut').value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        editFormError.style.display = 'none';
        
        if (checkIn < today) {
            showEditFormError('Check-in date cannot be in the past');
            return false;
        }
        
        if (checkOut <= checkIn) {
            showEditFormError('Check-out date must be after check-in date');
            return false;
        }
        
        return true;
    }
    
    function showEditFormError(message) {
        editFormError.textContent = message;
        editFormError.style.display = 'block';
    }
    
    // Enhanced Edit Form Submission
    const editForm = document.getElementById('editForm');
    editForm.onsubmit = function(e) {
        e.preventDefault();
        
        if (!validateEditForm()) {
            return;
        }
        
        const formData = new FormData(editForm);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        submitBtn.disabled = true;
        
        fetch('reservation.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update the table row in the UI
                const row = document.querySelector('tr[data-id="' + formData.get('ReservationID') + '"]');
                row.children[1].innerHTML = '<b>' + formData.get('GuestName') + '</b>';
                row.children[2].innerHTML = '<b>' + new Date(formData.get('PCheckInDate')).toLocaleDateString() + '</b>';
                row.children[3].innerHTML = '<b>' + new Date(formData.get('PCheckOutDate')).toLocaleDateString() + '</b>';
                row.children[4].innerHTML = formData.get('RoomNumber');
                row.children[5].innerHTML = '<b>' + formData.get('RoomType') + '</b>';
                row.children[6].innerHTML = '<b>' + formData.get('Status') + '</b>';
                
                // Update the data attributes for future edits
                const editBtn = row.querySelector('.edit-btn');
                const viewBtn = row.querySelector('.view-btn');
                editBtn.dataset.guest = formData.get('GuestName');
                editBtn.dataset.checkin = formData.get('PCheckInDate');
                editBtn.dataset.checkout = formData.get('PCheckOutDate');
                editBtn.dataset.room = formData.get('RoomNumber');
                editBtn.dataset.type = formData.get('RoomType');
                editBtn.dataset.status = formData.get('Status');
                viewBtn.dataset.guest = formData.get('GuestName');
                viewBtn.dataset.checkin = formData.get('PCheckInDate');
                viewBtn.dataset.checkout = formData.get('PCheckOutDate');
                viewBtn.dataset.room = formData.get('RoomNumber');
                viewBtn.dataset.type = formData.get('RoomType');
                viewBtn.dataset.status = formData.get('Status');
                
                editModal.style.display = 'none';
                showNotification(data.message, 'success');
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('An error occurred while updating the reservation', 'error');
        })
        .finally(() => {
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target == editModal) editModal.style.display = 'none';
        if (event.target == viewModal) viewModal.style.display = 'none';
        if (event.target == createModal) createModal.style.display = 'none';
    }
    
    // Notification system
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            color: white;
            font-weight: 500;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        if (type === 'success') {
            notification.style.background = '#28a745';
        } else {
            notification.style.background = '#dc3545';
        }
        
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
    
    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html> 
