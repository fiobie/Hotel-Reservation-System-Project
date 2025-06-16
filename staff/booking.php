<?php
// ============================================================================
// DATABASE CONNECTION
// ============================================================================
$host = "localhost";
$user = "root";
$password = "";
$dbname = "hotel_reservation_systemdb";
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ============================================================================
// AJAX HANDLERS
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['BookingID'])) {
    handleBookingUpdate($conn);
}

// Handle early checkout/cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    handleBookingAction($conn);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================
function handleBookingAction($conn) {
    $bookingId = intval($_POST['BookingID']);
    $action = $_POST['action'];
    $currentTime = date('Y-m-d H:i:s');
    
    if ($action === 'complete_stay') {
        // Complete the stay early - update checkout time to now
        $sql = "UPDATE booking SET 
                CheckOutDate = '$currentTime',
                BookingStatus = 'Completed',
                RoomStatus = 'Available'
                WHERE BookingID = $bookingId";
    } elseif ($action === 'cancel_stay') {
        // Cancel the stay - mark as cancelled
        $sql = "UPDATE booking SET 
                BookingStatus = 'Cancelled',
                RoomStatus = 'Available'
                WHERE BookingID = $bookingId";
    }
    
    $success = $conn->query($sql);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

function handleBookingUpdate($conn) {
    $bookingId = intval($_POST['BookingID']);
    $checkIn = $conn->real_escape_string($_POST['CheckInDate']);
    $checkOut = $conn->real_escape_string($_POST['CheckOutDate']);
    $roomNumber = intval($_POST['RoomNumber']);
    $roomType = $conn->real_escape_string($_POST['RoomType']);
    $roomStatus = $conn->real_escape_string($_POST['RoomStatus']);
    $bookingStatus = $conn->real_escape_string($_POST['BookingStatus']);
    $notes = $conn->real_escape_string($_POST['Notes']);
    $price = floatval($_POST['Price']);

    $sql = "UPDATE booking SET 
        CheckInDate='$checkIn',
        CheckOutDate='$checkOut',
        RoomNumber=$roomNumber,
        RoomType='$roomType',
        RoomStatus='$roomStatus',
        BookingStatus='$bookingStatus',
        Notes='$notes',
        Price=$price
        WHERE BookingID=$bookingId";
    
    $success = $conn->query($sql);

    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

function getBookingsForMonth($conn, $year, $month) {
    $sql = "SELECT 
                b.BookingID, 
                b.ReservationID, 
                b.StudentID, 
                b.StaffID, 
                b.RoomNumber, 
                b.RoomType, 
                b.BookingStatus, 
                b.RoomStatus, 
                b.Notes, 
                b.CheckInDate, 
                b.CheckOutDate, 
                b.BookingDate, 
                b.Price, 
                CONCAT(s.FirstName, ' ', s.LastName) as GuestName
            FROM booking b 
            LEFT JOIN student s ON b.StudentID = s.StudentID 
            WHERE (YEAR(b.CheckInDate) = $year AND MONTH(b.CheckInDate) = $month) 
               OR (YEAR(b.CheckOutDate) = $year AND MONTH(b.CheckOutDate) = $month)";
    
    $result = $conn->query($sql);
$bookings = [];
    
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $checkIn = date('Y-m-d', strtotime($row['CheckInDate']));
        $checkOut = date('Y-m-d', strtotime($row['CheckOutDate']));
            $guest = $row['GuestName'] ? $row['GuestName'] : 'Guest #' . $row['BookingID'];
            
            // Create date range for this booking
        $period = new DatePeriod(
            new DateTime($checkIn),
            new DateInterval('P1D'),
            (new DateTime($checkOut))->modify('+1 day')
        );
            
        foreach ($period as $date) {
            $d = $date->format('Y-m-d');
            $bookings[$d][] = [
                    'bookingId' => $row['BookingID'],
                'guest' => $guest,
                    'status' => $row['RoomStatus'],
                    'roomNumber' => $row['RoomNumber'],
                    'roomType' => $row['RoomType'],
                    'bookingStatus' => $row['BookingStatus'],
                    'checkIn' => $row['CheckInDate'],
                    'checkOut' => $row['CheckOutDate'],
                    'notes' => $row['Notes'],
                    'price' => $row['Price']
            ];
        }
    }
}
    
    return $bookings;
}

// ============================================================================
// CALENDAR CALCULATIONS
// ============================================================================
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$startDay = date('N', $firstDay); // 1 (Mon) - 7 (Sun)

// ============================================================================
// FETCH BOOKING DATA
// ============================================================================
$bookings = getBookingsForMonth($conn, $year, $month);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Schedule</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- ============================================================================
         CSS STYLES
         ============================================================================ -->
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #fafaf9; display: flex; }
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
        .main-content { flex: 1; padding: 2rem; margin-left: 200px; overflow-x: hidden; transition: margin-left 0.3s; max-width: 900px; }
        /* HAMBURGER MENU */
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
        /* ============================================================================
           BASE STYLES
           ============================================================================ */
        body { 
            background: #fafaf9; 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
        }
        
        .main-content { 
            max-width: 900px; 
            margin: 2rem auto; 
            padding: 2rem; 
            background: #fff; 
            border-radius: 18px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); 
        }
        
        h1 { 
            font-size: 2.2rem; 
            margin-bottom: 1.5rem; 
            font-weight: 700; 
        }
        
        /* ============================================================================
           CALENDAR HEADER
           ============================================================================ */
        .calendar-header { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            margin-bottom: 1.5rem; 
        }
        
        .calendar-header h2 { 
            font-size: 1.3rem; 
            font-weight: 600; 
            margin: 0 1rem 0 0; 
        }
        
        .calendar-nav { 
            display: flex; 
            align-items: center; 
            gap: 1.5rem; 
        }
        
        .calendar-nav-btn { 
            background: #eee; 
            border: none; 
            border-radius: 50%; 
            width: 2.2rem; 
            height: 2.2rem; 
            font-size: 1.2rem; 
            cursor: pointer; 
            transition: background 0.2s; 
        }
        
        .calendar-nav-btn:hover { 
            background: #d1d1d1; 
        }
        
        /* ============================================================================
           SEARCH AND FILTER
           ============================================================================ */
        .search-filter-bar { 
            display: flex; 
            align-items: center; 
            gap: 1rem; 
            margin-left: 2.5rem; 
        }
        
        .search-input { 
            padding: 0.7rem 2.5rem 0.7rem 2.5rem; 
            border-radius: 1.2rem; 
            border: none; 
            background: #ededed; 
            font-size: 1rem; 
            width: 200px; 
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
        
        .filter-btn { 
            padding: 0.7rem 1.5rem; 
            border-radius: 1rem; 
            border: 2px solid #222; 
            background: #eaeaff; 
            font-size: 1rem; 
            cursor: pointer; 
            margin-left: 0.5rem; 
            transition: background 0.2s, color 0.2s; 
        }
        
        .filter-btn:hover { 
            background: #a084e8; 
            color: #fff; 
        }
        
        .filter-dropdown { 
            display: none; 
            position: absolute; 
            top: 2.5rem; 
            right: 0; 
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
        
        .filter-dropdown input, 
        .filter-dropdown select { 
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
        
        /* ============================================================================
           CALENDAR TABLE
           ============================================================================ */
        .calendar-table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0.5rem 0.7rem; 
            margin-bottom: 2rem; 
        }
        
        .calendar-table th { 
            font-size: 1.1rem; 
            font-weight: 700; 
            color: #222; 
            text-align: center; 
            padding-bottom: 0.5rem; 
        }
        
        .calendar-table td { 
            text-align: center; 
            vertical-align: top; 
        }
        
        .calendar-day { 
            background: none; 
            border-radius: 1.2rem; 
            min-width: 80px; 
            min-height: 60px; 
            padding: 0.2rem 0.2rem 0.7rem 0.2rem; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: flex-start; 
        }
        
        .calendar-date { 
            font-weight: 600; 
            font-size: 1.1rem; 
            margin-bottom: 0.2rem; 
            color: #222; 
        }
        
        /* ============================================================================
           GUEST BADGES
           ============================================================================ */
        .guest-badge { 
            display: inline-block; 
            margin-top: 0.2rem; 
            padding: 0.2rem 1.1rem; 
            border-radius: 1.2rem; 
            font-size: 1rem; 
            font-weight: 600; 
            background: #ededed; 
            color: #444; 
            margin-bottom: 0.2rem; 
            cursor: pointer; 
            transition: all 0.2s; 
        }
        
        .guest-badge:hover { 
            transform: scale(1.05); 
            box-shadow: 0 2px 8px rgba(0,0,0,0.15); 
        }
        
        /* Status Colors */
        .available { background: #b6f7c1; color: #222; }
        .booked { background: #f7b6b6; color: #222; }
        .reserved { background: #f7f3b6; color: #222; }
        .maintenance { background: #e0e0e0; color: #222; }
        
        /* ============================================================================
           LEGEND
           ============================================================================ */
        .legend { 
            display: flex; 
            gap: 1.5rem; 
            justify-content: center; 
            margin-top: 1.5rem; 
        }
        
        .legend-item { 
            display: flex; 
            align-items: center; 
            gap: 0.6rem; 
            font-size: 1.1rem; 
        }
        
        .legend-badge { 
            width: 2.2rem; 
            height: 1.2rem; 
            border-radius: 0.7rem; 
            display: inline-block; 
        }
        
        .legend-available { background: #b6f7c1; }
        .legend-booked { background: #f7b6b6; }
        .legend-reserved { background: #f7f3b6; }
        .legend-maintenance { background: #e0e0e0; }
        
        /* ============================================================================
           MODAL STYLES
           ============================================================================ */
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background-color: rgba(0,0,0,0.5); 
        }
        
        .modal-content { 
            background-color: #fefefe; 
            margin: 5% auto; 
            padding: 2rem; 
            border-radius: 12px; 
            width: 90%; 
            max-width: 600px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.15); 
        }
        
        .close { 
            color: #aaa; 
            float: right; 
            font-size: 28px; 
            font-weight: bold; 
            cursor: pointer; 
        }
        
        .close:hover { 
            color: #000; 
        }
        
        .modal h2 { 
            margin-bottom: 1.5rem; 
            color: #333; 
        }
        
        /* ============================================================================
           FORM STYLES
           ============================================================================ */
        .form-group { 
            margin-bottom: 1rem; 
        }
        
        .form-group label { 
            display: block; 
            margin-bottom: 0.5rem; 
            font-weight: 600; 
            color: #555; 
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea { 
            width: 100%; 
            padding: 0.7rem; 
            border: 1px solid #ddd; 
            border-radius: 0.5rem; 
            font-size: 1rem; 
        }
        
        .form-group textarea { 
            height: 80px; 
            resize: vertical; 
        }
        
        .form-row { 
            display: flex; 
            gap: 1rem; 
        }
        
        .form-row .form-group { 
            flex: 1; 
        }
        
        .modal-actions { 
            display: flex; 
            justify-content: flex-end; 
            gap: 1rem; 
            margin-top: 2rem; 
        }
        
        .btn { 
            padding: 0.7rem 1.5rem; 
            border: none; 
            border-radius: 0.5rem; 
            cursor: pointer; 
            font-size: 1rem; 
            transition: background 0.2s; 
        }
        
        .btn-primary { 
            background: #007bff; 
            color: white; 
        }
        
        .btn-primary:hover { 
            background: #0056b3; 
        }
        
        .btn-secondary { 
            background: #6c757d; 
            color: white; 
        }
        
        .btn-secondary:hover { 
            background: #545b62; 
        }
        
        .btn-warning { 
            background: #ffc107; 
            color: white; 
        }
        
        .btn-warning:hover { 
            background: #e0a800; 
        }
        
        .btn-danger { 
            background: #dc3545; 
            color: white; 
        }
        
        .btn-danger:hover { 
            background: #c82333; 
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
            <a class="nav-link" href="staff_dashboard.php"><i class="fas fa-th-large"></i>Dashboard</a>
            <a class="nav-link" href="reservation.php"><i class="fas fa-calendar-check"></i>Reservation</a>
            <a class="nav-link" href="booking.php"><i class="fas fa-book"></i>Booking</a>
            <a class="nav-link" href="room.php"><i class="fas fa-door-open"></i>Room</a>
            <a class="nav-link" href="guest_request.php"><i class="fas fa-comment-dots"></i>Guest Request</a>
            <a class="nav-link" href="inventory.php"><i class="fas fa-box"></i>Inventory</a>
        </div>
        <div class="nav-section">
            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i>Log out</a>
        </div>
    </div>
    <div class="main-content">
        
        <!-- ============================================================================
             CALENDAR HEADER
             ============================================================================ -->
        <div class="calendar-header">
            <h1>Booking Schedule</h1>
            
            <!-- Navigation -->
            <div class="calendar-nav" style="gap: 1.5rem;">
                <form method="get" style="display:inline;">
                    <input type="hidden" name="month" value="<?php echo $month == 1 ? 12 : $month - 1; ?>">
                    <input type="hidden" name="year" value="<?php echo $month == 1 ? $year - 1 : $year; ?>">
                    <button type="submit" class="calendar-nav-btn">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                </form>
                
                <h2 style="margin: 0 1.5rem; display: flex; flex-direction: column; align-items: center;">
                    <span><?php echo date('F', $firstDay); ?></span>
                    <span style="font-size: 1.1rem; font-weight: 400; margin-top: -0.3rem; letter-spacing: 1px;">
                        <?php echo $year; ?>
                    </span>
                </h2>
                
                <form method="get" style="display:inline;">
                    <input type="hidden" name="month" value="<?php echo $month == 12 ? 1 : $month + 1; ?>">
                    <input type="hidden" name="year" value="<?php echo $month == 12 ? $year + 1 : $year; ?>">
                    <button type="submit" class="calendar-nav-btn">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </form>
            </div>
            
            <!-- Search and Filter -->
            <div class="search-filter-bar" style="margin-left: 2.5rem;">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Search">
                </div>
                
                <div style="position: relative;">
                    <button class="filter-btn" id="filterBtn">Filter</button>
                    <div class="filter-dropdown" id="filterDropdown">
                        <form id="filterForm">
                            <label>Guest Name 
                                <input type="text" name="GuestName">
                            </label>
                            <label>Room Status
                                <select name="RoomStatus">
                                    <option value="">Any</option>
                                    <option value="Available">Available</option>
                                    <option value="Booked">Booked</option>
                                    <option value="Reserved">Reserved</option>
                                    <option value="Maintenance">Maintenance</option>
                                </select>
                            </label>
                            <div class="filter-actions">
                                <button type="button" id="applyFilterBtn" class="filter-btn">Apply</button>
                                <button type="button" id="clearFilterBtn" class="filter-btn">Clear</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ============================================================================
             CALENDAR TABLE
             ============================================================================ -->
        <table class="calendar-table">
            <thead>
                <tr>
                    <th>MON</th>
                    <th>TUE</th>
                    <th>WED</th>
                    <th>THUR</th>
                    <th>FRI</th>
                    <th>SAT</th>
                    <th>SUN</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Generate calendar grid
                $day = 1;
                $cell = 1;
                $totalCells = ceil(($daysInMonth + $startDay - 1) / 7) * 7;
                
                for ($row = 0; $row < $totalCells / 7; $row++) {
                    echo '<tr>';
                    
                    for ($col = 1; $col <= 7; $col++, $cell++) {
                        $date = null;
                        if ($cell >= $startDay && $day <= $daysInMonth) {
                            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        }
                        
                        echo '<td>';
                        if ($date) {
                            echo '<div class="calendar-day">';
                            echo '<div class="calendar-date">' . $day . '</div>';
                            
                            // Display bookings for this date
                            if (isset($bookings[$date])) {
                                foreach ($bookings[$date] as $b) {
                                    $statusClass = strtolower($b['status']);
                                    echo '<span class="guest-badge ' . $statusClass . '" 
                                            onclick="openEditModal(' . $b['bookingId'] . ', \'' . 
                                            htmlspecialchars($b['guest']) . '\', \'' . 
                                            $b['checkIn'] . '\', \'' . 
                                            $b['checkOut'] . '\', ' . 
                                            $b['roomNumber'] . ', \'' . 
                                            $b['roomType'] . '\', \'' . 
                                            $b['roomStatus'] . '\', \'' . 
                                            $b['bookingStatus'] . '\', \'' . 
                                            htmlspecialchars($b['notes']) . '\', ' . 
                                            $b['price'] . ')">' . 
                                            htmlspecialchars($b['guest']) . 
                                          '</span>';
                                }
                            }
                            
                            echo '</div>';
                            $day++;
                        }
                        echo '</td>';
                    }
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
        
        <!-- ============================================================================
             LEGEND
             ============================================================================ -->
        <div class="legend">
            <div class="legend-item">
                <span class="legend-badge legend-available"></span>Available
            </div>
            <div class="legend-item">
                <span class="legend-badge legend-booked"></span>Booked
            </div>
            <div class="legend-item">
                <span class="legend-badge legend-reserved"></span>Reserved
            </div>
            <div class="legend-item">
                <span class="legend-badge legend-maintenance"></span>Maintenance
            </div>
        </div>
    </div>

    <!-- ============================================================================
         EDIT BOOKING MODAL
         ============================================================================ -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Booking</h2>
            
            <form id="editForm">
                <input type="hidden" id="editBookingID" name="BookingID">
                
                <!-- Guest Name (Read-only) -->
                <div class="form-group">
                    <label for="editGuestName">Guest Name</label>
                    <input type="text" id="editGuestName" readonly>
                </div>
                
                <!-- Check-in/Check-out Dates -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="editCheckInDate">Check-in Date</label>
                        <input type="datetime-local" id="editCheckInDate" name="CheckInDate" required>
                    </div>
                    <div class="form-group">
                        <label for="editCheckOutDate">Check-out Date</label>
                        <input type="datetime-local" id="editCheckOutDate" name="CheckOutDate" required>
                    </div>
                </div>
                
                <!-- Room Information -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="editRoomNumber">Room Number</label>
                        <input type="number" id="editRoomNumber" name="RoomNumber" required>
                    </div>
                    <div class="form-group">
                        <label for="editRoomType">Room Type</label>
                        <select id="editRoomType" name="RoomType" required>
                            <option value="Standard">Standard</option>
                            <option value="Deluxe">Deluxe</option>
                            <option value="Suite">Suite</option>
                        </select>
                    </div>
                </div>
                
                <!-- Status Information -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="editRoomStatus">Room Status</label>
                        <select id="editRoomStatus" name="RoomStatus" required>
                            <option value="Available">Available</option>
                            <option value="Booked">Booked</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editBookingStatus">Booking Status</label>
                        <select id="editBookingStatus" name="BookingStatus" required>
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                </div>
                
                <!-- Price -->
                <div class="form-group">
                    <label for="editPrice">Price</label>
                    <input type="number" id="editPrice" name="Price" step="0.01" required>
                </div>
                
                <!-- Notes -->
                <div class="form-group">
                    <label for="editNotes">Notes</label>
                    <textarea id="editNotes" name="Notes"></textarea>
                </div>
                
                <!-- Action Buttons -->
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="completeStay()">Complete Stay</button>
                    <button type="button" class="btn btn-danger" onclick="cancelStay()">Cancel Stay</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================================
         JAVASCRIPT
         ============================================================================ -->
    <script>
        // ============================================================================
        // DOM ELEMENTS
        // ============================================================================
    const searchInput = document.getElementById('searchInput');
    const filterBtn = document.getElementById('filterBtn');
    const filterDropdown = document.getElementById('filterDropdown');
    const applyFilterBtn = document.getElementById('applyFilterBtn');
    const clearFilterBtn = document.getElementById('clearFilterBtn');
    const filterForm = document.getElementById('filterForm');
    const guestBadges = document.querySelectorAll('.guest-badge');

        // ============================================================================
        // SEARCH AND FILTER FUNCTIONS
        // ============================================================================
        
        // Toggle filter dropdown
    filterBtn.onclick = function() {
        filterDropdown.classList.toggle('active');
    }
        
        // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!filterDropdown.contains(e.target) && e.target !== filterBtn) {
            filterDropdown.classList.remove('active');
        }
    });

        // Check if badge matches filter criteria
    function badgeMatches(badge, filters) {
            if (filters.GuestName && !badge.innerText.toLowerCase().includes(filters.GuestName.toLowerCase())) {
                return false;
            }
            if (filters.RoomStatus && !badge.classList.contains(filters.RoomStatus.toLowerCase())) {
                return false;
            }
        return true;
    }

        // Apply filters
    applyFilterBtn.onclick = function() {
        const formData = new FormData(filterForm);
        const filters = Object.fromEntries(formData.entries());
            
        guestBadges.forEach(badge => {
            badge.style.display = badgeMatches(badge, filters) ? '' : 'none';
        });
            
        filterDropdown.classList.remove('active');
    }
        
        // Clear filters
    clearFilterBtn.onclick = function() {
        filterForm.reset();
            guestBadges.forEach(badge => { 
                badge.style.display = ''; 
            });
    }
        
        // Search functionality
    searchInput.oninput = function() {
        const val = searchInput.value.toLowerCase();
        guestBadges.forEach(badge => {
            badge.style.display = badge.innerText.toLowerCase().includes(val) ? '' : 'none';
        });
    }

        // ============================================================================
        // MODAL FUNCTIONS
        // ============================================================================
        
        // Open edit modal with booking data
        function openEditModal(bookingId, guestName, checkIn, checkOut, roomNumber, roomType, roomStatus, bookingStatus, notes, price) {
            document.getElementById('editBookingID').value = bookingId;
            document.getElementById('editGuestName').value = guestName;
            document.getElementById('editCheckInDate').value = checkIn.replace(' ', 'T');
            document.getElementById('editCheckOutDate').value = checkOut.replace(' ', 'T');
            document.getElementById('editRoomNumber').value = roomNumber;
            document.getElementById('editRoomType').value = roomType;
            document.getElementById('editRoomStatus').value = roomStatus;
            document.getElementById('editBookingStatus').value = bookingStatus;
            document.getElementById('editNotes').value = notes;
            document.getElementById('editPrice').value = price;
            document.getElementById('editModal').style.display = 'block';
        }

        // Close edit modal
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }

        // ============================================================================
        // FORM SUBMISSION
        // ============================================================================
        
        // Handle edit form submission
        document.getElementById('editForm').onsubmit = function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Booking updated successfully!');
                    closeEditModal();
                    location.reload(); // Refresh the page to show updated data
                } else {
                    alert('Error updating booking. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating booking. Please try again.');
            });
        };

        // ============================================================================
        // EARLY CHECKOUT/CANCELLATION FUNCTIONS
        // ============================================================================
        
        // Complete stay early
        function completeStay() {
            const bookingId = document.getElementById('editBookingID').value;
            const guestName = document.getElementById('editGuestName').value;
            
            if (confirm(`Are you sure you want to complete the stay for ${guestName}? This will mark the guest as checked out and make the room available.`)) {
                const formData = new FormData();
                formData.append('BookingID', bookingId);
                formData.append('action', 'complete_stay');
                
                fetch('booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Stay completed successfully! Guest has been checked out early.');
                        closeEditModal();
                        location.reload();
                    } else {
                        alert('Error completing stay. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error completing stay. Please try again.');
                });
            }
        }
        
        // Cancel stay
        function cancelStay() {
            const bookingId = document.getElementById('editBookingID').value;
            const guestName = document.getElementById('editGuestName').value;
            
            if (confirm(`Are you sure you want to cancel the stay for ${guestName}? This will cancel the booking and make the room available.`)) {
                const formData = new FormData();
                formData.append('BookingID', bookingId);
                formData.append('action', 'cancel_stay');
                
                fetch('booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Stay cancelled successfully! Room is now available.');
                        closeEditModal();
                        location.reload();
                    } else {
                        alert('Error cancelling stay. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error cancelling stay. Please try again.');
                });
            }
        }

        // Sidebar toggle menu functionality
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        sidebarToggle.onclick = function() {
            sidebar.classList.toggle('active');
        };
    </script>
</body>
</html> 
