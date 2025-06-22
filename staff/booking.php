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
// CALENDAR LOGIC
// ============================================================================
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$dayOfWeek = date('w', $firstDay);

// ============================================================================
// FETCH BOOKING DATA
// ============================================================================
$bookings = [];
$firstDateOfMonth = "$year-$month-01";
$lastDateOfMonth = "$year-$month-$daysInMonth";

$sql = "SELECT 
            b.*, 
            s.FirstName, s.LastName, s.Gender, s.PhoneNumber, s.Address, s.Email, s.Nationality, s.BirthDate, s.StudentID as StudentIDNum
        FROM booking b
        LEFT JOIN student s ON b.StudentID = s.StudentID
        WHERE 
            (b.CheckInDate <= '$lastDateOfMonth' AND b.CheckOutDate >= '$firstDateOfMonth') 
        AND b.BookingStatus NOT IN ('Cancelled')";

$result = $conn->query($sql);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

function getBookingsForDate($date, $bookings) {
    $bookingsOnDate = [];
    foreach ($bookings as $booking) {
        $checkIn = new DateTime($booking['CheckInDate']);
        $checkOut = new DateTime($booking['CheckOutDate']);
        $current = new DateTime($date);
        
        // Check if the date is within the booking range (inclusive of check-in, exclusive of check-out)
        if ($current >= $checkIn && $current < $checkOut) {
            $bookingsOnDate[] = $booking;
        }
    }
    return $bookingsOnDate;
}

// ============================================================================
// AJAX HANDLER FOR WALK-IN BOOKING
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_walkin') {
    // Step 2: Guest Information
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $email = $_POST['email'] ?? '';
    $nationality = $_POST['nationality'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $studentIdNum = $_POST['studentId'] ?? '';
    
    // Step 1: Booking Details
    $checkIn = $_POST['checkInDate'] ?? '';
    $checkOut = $_POST['checkOutDate'] ?? '';
    $bookingDate = $_POST['bookingDate'] ?? '';
    $bookingStatus = $_POST['bookingStatus'] ?? 'Pending';
    $roomType = $_POST['roomType'] ?? '';
    $specialRequest = $_POST['specialRequest'] ?? '';

    // A more robust solution would check for availability over the entire date range.
    $roomSql = "SELECT RoomNumber FROM room WHERE RoomType = '$roomType' AND RoomStatus = 'Available' LIMIT 1";
    $roomResult = $conn->query($roomSql);

    if ($roomResult && $roomResult->num_rows > 0) {
        $room = $roomResult->fetch_assoc();
        $roomNumber = $room['RoomNumber'];

        // Create a new student record for the guest
        $studentSql = "INSERT INTO student (FirstName, LastName, Gender, PhoneNumber, Address, Email, Nationality, BirthDate, StudentID) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_student = $conn->prepare($studentSql);
        $stmt_student->bind_param("sssssssss", $firstName, $lastName, $gender, $phone, $address, $email, $nationality, $birthdate, $studentIdNum);
        
        if ($stmt_student->execute()) {
            $studentId = $conn->insert_id;

            $bookingSql = "INSERT INTO booking (StudentID, RoomNumber, RoomType, CheckInDate, CheckOutDate, BookingDate, BookingStatus, Notes, RoomStatus) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_booking = $conn->prepare($bookingSql);
            $roomStatus = 'Booked'; // When booking, room becomes booked.
            $stmt_booking->bind_param("iisssssss", $studentId, $roomNumber, $roomType, $checkIn, $checkOut, $bookingDate, $bookingStatus, $specialRequest, $roomStatus);
            
            if ($stmt_booking->execute()) {
                // Update room status
                $updateRoomSql = "UPDATE room SET RoomStatus = 'Booked' WHERE RoomNumber = ?";
                $stmt_update = $conn->prepare($updateRoomSql);
                $stmt_update->bind_param("i", $roomNumber);
                $stmt_update->execute();
                echo json_encode(['success' => true, 'message' => 'Booking created successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create booking.']);
            }
        } else {
             echo json_encode(['success' => false, 'message' => 'Failed to create guest record.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => "No available rooms of type '$roomType'."]);
    }
    exit;
}

// ============================================================================
// AJAX HANDLER FOR BOOKING UPDATE
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_booking') {
    // Booking Details
    $bookingId = $_POST['bookingId'] ?? 0;
    $bookingStatus = $_POST['bookingStatus'] ?? '';
    $roomStatus = $_POST['roomStatus'] ?? '';
    $roomNumber = $_POST['roomNumber'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $checkIn = $_POST['checkInDate'] ?? '';
    $checkOut = $_POST['checkOutDate'] ?? '';

    // Guest Details
    $studentId = $_POST['studentId'] ?? 0;
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';

    if ($bookingId > 0 && $studentId > 0 && !empty($checkIn) && !empty($checkOut) && !empty($roomNumber)) {
        if (strtotime($checkIn) >= strtotime($checkOut)) {
            echo json_encode(['success' => false, 'message' => 'Check-out date must be after check-in date.']);
            exit;
        }

        // Use a transaction to ensure both updates succeed or fail together
        $conn->begin_transaction();

        try {
            // 1. Update Booking Table
            $sql_booking = "UPDATE booking SET BookingStatus = ?, RoomStatus = ?, Notes = ?, CheckInDate = ?, CheckOutDate = ?, RoomNumber = ? WHERE BookingID = ?";
            $stmt_booking = $conn->prepare($sql_booking);
            $stmt_booking->bind_param("ssssssi", $bookingStatus, $roomStatus, $notes, $checkIn, $checkOut, $roomNumber, $bookingId);
            $stmt_booking->execute();

            // 2. Update Student Table
            $sql_student = "UPDATE student SET FirstName = ?, LastName = ?, Email = ?, PhoneNumber = ? WHERE StudentID = ?";
            $stmt_student = $conn->prepare($sql_student);
            $stmt_student->bind_param("ssssi", $firstName, $lastName, $email, $phone, $studentId);
            $stmt_student->execute();

            // If both queries are successful, commit the transaction
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Booking and guest details updated successfully!']);

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to update details. Please try again.']);
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input. Please check all fields.']);
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Schedule</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-bg: #f8f9fa;
            --secondary-bg: #ffffff;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --accent-color: #008000;
            --border-color: #dee2e6;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: var(--primary-bg); display: flex; color: var(--text-primary); }
        .sidebar { width: 200px; background: var(--accent-color); min-height: 100vh; padding: 0.5rem; color: white; position: fixed; left: 0; top: 0; bottom: 0; }
        .sidebar-logo { display: block; margin: 1rem auto; width: 70px; height: auto; }
        .sidebar-title { color: white; font-size: 1.2rem; font-weight: 500; margin-bottom: 1.5rem; padding: 1rem 0; text-align: center; }
        .nav-section { margin-bottom: 1rem; }
        .nav-link { display: flex; align-items: center; padding: 0.6rem 1rem; color: white; text-decoration: none; font-size: 0.9rem; margin-bottom: 0.25rem; transition: background-color 0.2s; border-radius: 6px; }
        .nav-link:hover, .nav-link.active { background-color: rgba(255, 255, 255, 0.15); }
        .nav-link i { margin-right: 0.85rem; width: 20px; text-align: center; font-size: 1.1em; }
        
        .main-content { margin-left: 200px; flex-grow: 1; padding: 1.5rem; }
        .calendar-container { max-width: 1200px; margin: 0 auto; }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .calendar-header h1 { font-size: 1.6rem; font-weight: 600; }
        .calendar-nav { display: flex; align-items: center; gap: 0.5rem; }
        .calendar-nav-btn { background: var(--secondary-bg); border: 1px solid var(--border-color); border-radius: 50%; width: 36px; height: 36px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-secondary); transition: all 0.2s; }
        .calendar-nav-btn:hover { background: #e9ecef; color: var(--text-primary); }
        .calendar-month-year { font-size: 1.3rem; font-weight: 500; text-align: center; min-width: 150px; }

        .controls-bar {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .search-wrapper { position: relative; flex-grow: 1; max-width: 300px; }
        .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); }
        .search-input { width: 100%; padding: 0.6rem 0.6rem 0.6rem 2.2rem; border-radius: 20px; border: 1px solid var(--border-color); background: var(--secondary-bg); font-size: 0.9rem; transition: border-color 0.2s, box-shadow 0.2s; }
        .search-input:focus { outline: none; border-color: var(--accent-color); box-shadow: 0 0 0 2px rgba(0,128,0,0.2); }
        .control-btn { padding: 0.6rem 1.2rem; border: 1px solid var(--border-color); background: var(--secondary-bg); border-radius: 20px; font-size: 0.9rem; font-weight: 500; cursor: pointer; transition: all 0.2s; }
        .control-btn:hover { background: #e9ecef; }
        .walk-in-btn { background: #343a40; color: white; border-color: #343a40; }
        .walk-in-btn:hover { background: #23272b; }

        .calendar-legend { display: flex; justify-content: flex-end; align-items: center; gap: 1.5rem; margin-bottom: 1rem; }
        .legend-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; }
        .legend-badge { width: 12px; height: 12px; border-radius: 50%; }
        .legend-available { background: #28a745; }
        .legend-booked { background: #dc3545; }
        .legend-reserved { background: #ffc107; }
        .legend-maintenance { background: #6c757d; }

        .calendar-grid { width: 100%; border-collapse: collapse; }
        .calendar-grid th { text-align: left; padding: 0.8rem; font-weight: 500; color: var(--text-secondary); border-bottom: 2px solid var(--border-color); }
        .calendar-grid td { vertical-align: top; border: 1px solid var(--border-color); height: 90px; padding: 0; }
        .calendar-day { padding: 0.5rem; font-weight: 500; }
        .calendar-day.not-month { color: #ccc; }
        .bookings-container { padding: 0.2rem; }
        .booking-bar {
            padding: 0.2rem 0.5rem;
            margin-bottom: 0.2rem;
            font-size: 0.75rem;
            border-radius: 4px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            font-weight: 500;
            transition: opacity 0.3s ease-in-out;
            cursor: pointer;
        }
        .booking-bar.filtered {
            opacity: 0.2;
            pointer-events: none;
        }
        .booking-bar.status-booked { background-color: #dc3545; color: #ffffff; }
        .booking-bar.status-reserved { background-color: #ffc107; color: #212529; }
        .booking-bar.status-maintenance { background-color: #6c757d; color: #ffffff; }

        /* MODAL STYLES */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 2rem;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        .modal-header h2 { font-size: 1.5rem; }
        .close-btn { color: #aaa; font-size: 1.8rem; font-weight: bold; cursor: pointer; }
        .close-btn:hover, .close-btn:focus { color: black; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 4px; }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding-top: 1rem;
            margin-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        /* NEW FORM STYLES */
        .form-step { display: none; }
        .form-step.active { display: block; }

        .booking-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        #specialRequest {
            width: 100%;
            min-height: 100px;
            padding: 0.6rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }

        .price-section {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .price-section label { font-size: 1.2rem; font-weight: 500; }
        .price-display {
            background: #e9ecef;
            padding: 0.8rem 1.5rem;
            border-radius: 20px;
            font-size: 1.2rem;
            font-weight: 600;
            min-width: 100px;
            text-align: center;
        }

        .guest-info-table { width: 100%; border-collapse: collapse; }
        .guest-info-table th, .guest-info-table td { border: 1px solid var(--border-color); padding: 0.8rem; text-align: left; }
        .guest-info-table th { background-color: #e9ecef; font-weight: 600; width: 150px; }
        .guest-info-table input { width: 100%; border: none; padding: 0.2rem; font-size: 1rem; }
        .guest-info-table input:focus { outline: none; }
        
        #filter-results {
            margin-top: 1.5rem;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0.5rem;
        }
        .result-item {
            padding: 0.5rem;
            border-bottom: 1px solid #f1f1f1;
        }
        .result-item:last-child {
            border-bottom: none;
        }
        .result-item.empty {
            text-align: center;
            color: var(--text-secondary);
        }

        #detailsCheckIn, #detailsCheckOut {
            pointer-events: auto !important;
            background-color: var(--secondary-bg) !important;
        }

        .search-wrapper-modal { position: relative; }
        .search-wrapper-modal .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); }
        #searchInputModal {
            width: 100%;
            padding: 0.6rem 0.6rem 0.6rem 2.2rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <img src="images/villavalorelogo.png" alt="Villa Valore Logo" class="sidebar-logo">
        <h4 class="sidebar-title">Villa Valore</h4>
        <div class="nav-section">
            <a class="nav-link" href="staff_dashboard.php"><i class="fas fa-th-large"></i>Dashboard</a>
            <a class="nav-link" href="reservation.php"><i class="fas fa-calendar-check"></i>Reservation</a>
            <a class="nav-link active" href="booking.php"><i class="fas fa-book"></i>Booking</a>
            <a class="nav-link" href="room.php"><i class="fas fa-door-open"></i>Room</a>
            <a class="nav-link" href="guest_request.php"><i class="fas fa-comment-dots"></i>Guest Request</a>
            <a class="nav-link" href="staff_inventory.php"><i class="fas fa-box"></i>Inventory</a>
        </div>
        <div class="nav-section">
            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i>Log out</a>
        </div>
    </div>

    <div class="main-content">
        <div class="calendar-container">
            <div class="calendar-header">
                <h1>Booking Schedule</h1>
                <div class="calendar-nav">
                    <a href="?month=<?php echo $month == 1 ? 12 : $month - 1; ?>&year=<?php echo $month == 1 ? $year - 1 : $year; ?>" class="calendar-nav-btn"><i class="fas fa-chevron-left"></i></a>
                    <span class="calendar-month-year"><?php echo date('F Y', $firstDay); ?></span>
                    <a href="?month=<?php echo $month == 12 ? 1 : $month + 1; ?>&year=<?php echo $month == 12 ? $year + 1 : $year; ?>" class="calendar-nav-btn"><i class="fas fa-chevron-right"></i></a>
                </div>
            </div>

            <div class="controls-bar">
                <button class="control-btn" id="filterBtn">Search & Filter</button>
                <button class="control-btn walk-in-btn" id="walkInBtn">Walk-in booking</button>
            </div>

            <div class="calendar-legend">
                <div class="legend-item"><span class="legend-badge legend-available"></span> Available</div>
                <div class="legend-item"><span class="legend-badge legend-booked"></span> Booked</div>
                <div class="legend-item"><span class="legend-badge legend-reserved"></span> Reserved</div>
                <div class="legend-item"><span class="legend-badge legend-maintenance"></span> Maintenance</div>
            </div>

            <table class="calendar-grid">
                <thead>
                    <tr>
                        <th>Sunday</th>
                        <th>Monday</th>
                        <th>Tuesday</th>
                        <th>Wednesday</th>
                        <th>Thursday</th>
                        <th>Friday</th>
                        <th>Saturday</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php
                        // Empty cells for days before the first day of the month
                        for ($i = 0; $i < $dayOfWeek; $i++) {
                            echo "<td></td>";
                        }
                        
                        $currentDay = 1;
                        while ($currentDay <= $daysInMonth) {
                            if ($dayOfWeek == 7) {
                                $dayOfWeek = 0;
                                echo "</tr><tr>";
                            }
                            
                            $dateStr = "$year-$month-$currentDay";
                            $dayBookings = getBookingsForDate($dateStr, $bookings);

                            echo "<td><div class='calendar-day'>{$currentDay}</div>";
                            
                            if (!empty($dayBookings)) {
                                echo "<div class='bookings-container'>";
                                foreach($dayBookings as $booking) {
                                    $roomStatus = strtolower($booking['RoomStatus']);
                                    // Default to booked if status is empty or unrecognized
                                    if (empty($roomStatus) || !in_array($roomStatus, ['booked', 'reserved', 'maintenance'])) {
                                        $roomStatus = 'booked';
                                    }
                                    $statusClass = 'status-' . $roomStatus;

                                    $guestName = trim(($booking['FirstName'] ?? '') . ' ' . ($booking['LastName'] ?? ''));
                                    if(empty($guestName)) { $guestName = 'N/A'; }

                                    echo "<div class='booking-bar {$statusClass}' 
                                             data-booking-id='{$booking['BookingID']}'
                                             data-student-id='{$booking['StudentID']}'
                                             data-room-number='{$booking['RoomNumber']}' 
                                             data-guest-name='{$guestName}' 
                                             data-status='" . $roomStatus . "'
                                             data-check-in='" . date('Y-m-d', strtotime($booking['CheckInDate'])) . "'
                                             data-check-out='" . date('Y-m-d', strtotime($booking['CheckOutDate'])) . "'
                                             data-booking-status='{$booking['BookingStatus']}'
                                             data-notes='" . htmlspecialchars($booking['Notes']) . "'
                                             data-first-name='" . htmlspecialchars($booking['FirstName']) . "'
                                             data-last-name='" . htmlspecialchars($booking['LastName']) . "'
                                             data-phone='" . htmlspecialchars($booking['PhoneNumber']) . "'
                                             data-email='" . htmlspecialchars($booking['Email']) . "'>
                                             Room {$booking['RoomNumber']} ({$guestName})
                                          </div>";
                                }
                                echo "</div>";
                            }
                            
                            echo "</td>";
                            
                            $currentDay++;
                            $dayOfWeek++;
                        }
                        
                        // Empty cells for days after the last day of the month
                        while ($dayOfWeek > 0 && $dayOfWeek < 7) {
                            echo "<td></td>";
                            $dayOfWeek++;
                        }
                        ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Walk-in Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <form id="bookingForm">
                <!-- Step 1: Booking Details -->
                <div id="form-step-1" class="form-step active">
                    <div class="modal-header">
                        <h2>Booking Details</h2>
                        <span class="close-btn">&times;</span>
                    </div>
                    <div class="booking-details-grid">
                        <div class="form-group">
                            <label for="checkInDate">Check In</label>
                            <input type="date" id="checkInDate" name="checkInDate" required>
                        </div>
                        <div class="form-group">
                            <label for="checkOutDate">Check Out</label>
                            <input type="date" id="checkOutDate" name="checkOutDate" required>
                        </div>
                        <div class="form-group">
                            <label for="bookingDate">Booking Date</label>
                            <input type="date" id="bookingDate" name="bookingDate" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="bookingStatus">Booking Status</label>
                            <select id="bookingStatus" name="bookingStatus"><option value="Pending">Pending</option><option value="Confirmed">Confirmed</option></select>
                        </div>
                        <div class="form-group">
                            <label for="roomType">Room Type</label>
                            <select id="roomType" name="roomType"><option value="Deluxe">Deluxe</option><option value="Standard">Standard</option><option value="Suite">Suite</option></select>
                        </div>
                        <div class="form-group">
                            <label for="roomStatus">Room Status</label>
                            <select id="roomStatus" name="roomStatus"><option value="Available">Available</option></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="specialRequest">Special Request</label>
                        <textarea id="specialRequest" name="specialRequest"></textarea>
                    </div>
                    <div class="price-section">
                        <label>Total Price:</label>
                        <span class="price-display">$0.00</span>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="control-btn" id="cancelBtn">Cancel</button>
                        <button type="button" class="control-btn walk-in-btn" id="nextBtn">Next</button>
                    </div>
                </div>

                <!-- Step 2: Guest Information -->
                <div id="form-step-2" class="form-step">
                    <div class="modal-header">
                        <h2>Hotel Guest Information</h2>
                        <span class="close-btn">&times;</span>
                    </div>
                    <table class="guest-info-table">
                        <tr><th>First Name</th><td><input type="text" name="firstName" required></td></tr>
                        <tr><th>Last Name</th><td><input type="text" name="lastName" required></td></tr>
                        <tr><th>Gender</th><td><input type="text" name="gender"></td></tr>
                        <tr><th>Phone Number</th><td><input type="tel" name="phone"></td></tr>
                        <tr><th>Address</th><td><input type="text" name="address"></td></tr>
                        <tr><th>Email</th><td><input type="email" name="email"></td></tr>
                        <tr><th>Nationality</th><td><input type="text" name="nationality"></td></tr>
                        <tr><th>Birthdate</th><td><input type="date" name="birthdate"></td></tr>
                        <tr><th>Student ID</th><td><input type="text" name="studentId"></td></tr>
                    </table>
                    <div class="modal-footer">
                        <button type="button" class="control-btn" id="backBtn">Back</button>
                        <button type="submit" class="control-btn walk-in-btn">Create Booking</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Booking & Guest Details</h2>
                <span class="close-btn">&times;</span>
            </div>
            <form id="detailsForm">
                <input type="hidden" name="bookingId" id="detailsBookingId">
                <input type="hidden" name="studentId" id="detailsStudentId">
                
                <h4>Booking Details</h4>
                <div class="booking-details-grid">
                    <div class="form-group">
                        <label for="detailsRoomNumber">Room</label>
                        <input type="text" id="detailsRoomNumber" name="roomNumber" required>
                    </div>
                    <div class="form-group">
                        <label for="detailsBookingStatus">Booking Status</label>
                        <select id="detailsBookingStatus" name="bookingStatus">
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="detailsRoomStatus">Room Status</label>
                        <select id="detailsRoomStatus" name="roomStatus">
                            <option value="Booked">Booked</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Available">Available</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="detailsCheckIn">Check In</label>
                        <input type="date" id="detailsCheckIn" name="checkInDate">
                    </div>
                    <div class="form-group">
                        <label for="detailsCheckOut">Check Out</label>
                        <input type="date" id="detailsCheckOut" name="checkOutDate">
                    </div>
                </div>
                
                <hr>
                
                <h4>Guest Details</h4>
                 <div class="booking-details-grid">
                    <div class="form-group">
                        <label for="detailsFirstName">First Name</label>
                        <input type="text" id="detailsFirstName" name="firstName" required>
                    </div>
                    <div class="form-group">
                        <label for="detailsLastName">Last Name</label>
                        <input type="text" id="detailsLastName" name="lastName" required>
                    </div>
                    <div class="form-group">
                        <label for="detailsEmail">Email</label>
                        <input type="email" id="detailsEmail" name="email">
                    </div>
                    <div class="form-group">
                        <label for="detailsPhone">Phone</label>
                        <input type="tel" id="detailsPhone" name="phone">
                    </div>
                </div>

                <div class="form-group">
                    <label for="detailsNotes">Notes</label>
                    <textarea id="detailsNotes" name="notes"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="control-btn" id="detailsCancelBtn">Close</button>
                    <button type="submit" class="control-btn walk-in-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Filter Modal -->
    <div id="filterModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Search & Filter Bookings</h2>
                <span class="close-btn">&times;</span>
            </div>
            <form id="filterForm">
                 <div class="form-group">
                    <label for="searchInputModal">Search by Guest, Room #, or Status</label>
                    <div class="search-wrapper-modal">
                         <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInputModal" placeholder="e.g., John Doe, 101, booked...">
                    </div>
                </div>
                <div class="form-group">
                    <label for="filterStatus">Filter by Status</label>
                    <select id="filterStatus" name="filterStatus">
                        <option value="">All</option>
                        <option value="booked">Booked</option>
                        <option value="reserved">Reserved</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                 <div id="filter-results-container">
                    <label>Matching Results</label>
                    <div id="filter-results">
                        <div class="result-item empty">Enter search criteria to see results.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="control-btn" id="clearFilterBtn">Clear</button>
                    <button type="submit" class="control-btn walk-in-btn">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ============================================================================
            // MODAL ELEMENTS
            // ============================================================================
            const bookingModal = document.getElementById('bookingModal');
            const filterModal = document.getElementById('filterModal');
            const detailsModal = document.getElementById('detailsModal');
            
            // ============================================================================
            // BUTTON ELEMENTS
            // ============================================================================
            const walkInBtn = document.getElementById('walkInBtn');
            const filterBtn = document.getElementById('filterBtn');
            const closeBtns = document.querySelectorAll('.modal .close-btn');
            const nextBtn = document.getElementById('nextBtn');
            const backBtn = document.getElementById('backBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const detailsCancelBtn = document.getElementById('detailsCancelBtn');
            
            // ============================================================================
            // FORM & FORM ELEMENTS
            // ============================================================================
            const bookingForm = document.getElementById('bookingForm');
            const detailsForm = document.getElementById('detailsForm');
            const filterForm = document.getElementById('filterForm');
            const formStep1 = document.getElementById('form-step-1');
            const formStep2 = document.getElementById('form-step-2');
            const searchInputModal = document.getElementById('searchInputModal');
            const filterStatus = document.getElementById('filterStatus');
            const clearFilterBtn = document.getElementById('clearFilterBtn');

            // ============================================================================
            // MODAL VISIBILITY LOGIC
            // ============================================================================
            if (walkInBtn) {
                walkInBtn.onclick = () => {
                    if (bookingModal) {
                        bookingModal.style.display = 'block';
                        if (formStep1) formStep1.classList.add('active');
                        if (formStep2) formStep2.classList.remove('active');
                    }
                }
            }

            if(filterBtn) {
                filterBtn.onclick = () => {
                    if (filterModal) filterModal.style.display = 'block';
                }
            }

            closeBtns.forEach(btn => {
                btn.onclick = () => {
                    if (bookingModal) bookingModal.style.display = 'none';
                    if (filterModal) filterModal.style.display = 'none';
                    if (detailsModal) detailsModal.style.display = 'none';
                }
            });

            if(cancelBtn) {
                cancelBtn.onclick = () => {
                    if (bookingModal) bookingModal.style.display = 'none';
                }
            }
            
            if(detailsCancelBtn) {
                detailsCancelBtn.addEventListener('click', () => {
                    if(detailsModal) detailsModal.style.display = 'none';
                });
            }

            if(nextBtn) {
                nextBtn.onclick = () => {
                    if (formStep1) formStep1.classList.remove('active');
                    if (formStep2) formStep2.classList.add('active');
                }
            }

            if(backBtn) {
                backBtn.onclick = () => {
                    if (formStep2) formStep2.classList.remove('active');
                    if (formStep1) formStep1.classList.add('active');
                }
            }

            window.onclick = (event) => {
                if (event.target == bookingModal) bookingModal.style.display = 'none';
                if (event.target == filterModal) filterModal.style.display = 'none';
                if (event.target == detailsModal) detailsModal.style.display = 'none';
            }

            // ============================================================================
            // FORM SUBMISSION LOGIC
            // ============================================================================
            if(bookingForm) {
                bookingForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(bookingForm);
                    formData.append('action', 'create_walkin');

                    const response = await fetch('booking.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    alert(result.message);

                    if (result.success) {
                        if (bookingModal) bookingModal.style.display = 'none';
                        location.reload(); 
                    }
                });
            }
            
            if(detailsForm) {
                detailsForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(detailsForm);
                    formData.append('action', 'update_booking');

                    const response = await fetch('booking.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    alert(result.message);

                    if (result.success) {
                        if(detailsModal) detailsModal.style.display = 'none';
                        location.reload();
                    }
                });
            }

            // ============================================================================
            // SEARCH AND FILTER LOGIC
            // ============================================================================
            const allBookings = <?php echo json_encode($bookings); ?>;
            const uniqueBookings = Object.values(allBookings.reduce((acc, booking) => {
                acc[booking.BookingID] = booking;
                return acc;
            }, {}));

            function applySearchAndFilter(updateListOnly = false) {
                const searchTerm = searchInputModal ? searchInputModal.value.toLowerCase() : '';
                const statusFilter = filterStatus ? filterStatus.value.toLowerCase() : '';
                const resultsContainer = document.getElementById('filter-results');
                let resultsHTML = '';
                let matchingBookingIds = new Set();

                uniqueBookings.forEach(booking => {
                    const roomNumber = booking.RoomNumber ? booking.RoomNumber.toLowerCase() : '';
                    const guestName = (booking.FirstName + ' ' + booking.LastName).toLowerCase();
                    let barStatus = booking.RoomStatus ? booking.RoomStatus.toLowerCase() : '';

                    if (!['booked', 'reserved', 'maintenance'].includes(barStatus)) {
                        barStatus = 'booked';
                    }

                    const matchesSearch = searchTerm === '' || roomNumber.includes(searchTerm) || guestName.includes(searchTerm) || barStatus.includes(searchTerm);
                    const matchesFilter = statusFilter === '' || barStatus === statusFilter;

                    if (matchesSearch && matchesFilter) {
                        matchingBookingIds.add(booking.BookingID);
                        resultsHTML += `<div class="result-item"><b>Room ${booking.RoomNumber}</b> - ${booking.FirstName || ''} ${booking.LastName || 'N/A'} (${barStatus})</div>`;
                    }
                });
                
                if (resultsContainer) {
                    if (matchingBookingIds.size > 0) {
                        resultsContainer.innerHTML = resultsHTML;
                    } else {
                        resultsContainer.innerHTML = `<div class="result-item empty">No matching bookings found.</div>`;
                    }
                }
                
                if (!updateListOnly) {
                     document.querySelectorAll('.booking-bar').forEach(bar => {
                        const bookingId = bar.dataset.bookingId;
                        if (matchingBookingIds.has(bookingId)) {
                            bar.classList.remove('filtered');
                        } else {
                            bar.classList.add('filtered');
                        }
                    });
                }
            }

            if(searchInputModal) {
                searchInputModal.addEventListener('keyup', () => applySearchAndFilter(true));
            }
            if(filterStatus) {
                filterStatus.addEventListener('change', () => applySearchAndFilter(true));
            }

            if(filterForm) {
                filterForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    applySearchAndFilter(false); // Apply to calendar
                    if(filterModal) filterModal.style.display = 'none';
                });
            }

            if(clearFilterBtn) {
                clearFilterBtn.addEventListener('click', () => {
                    if(filterStatus) filterStatus.value = '';
                    if(searchInputModal) searchInputModal.value = '';
                    applySearchAndFilter(false); // Apply to calendar
                    if(filterModal) filterModal.style.display = 'none';
                });
            }

            // ============================================================================
            // BOOKING BAR INTERACTIVITY
            // ============================================================================
            document.querySelectorAll('.booking-bar').forEach(bar => {
                bar.addEventListener('click', () => {
                    if (detailsModal) {
                        // Populate Booking Details
                        document.getElementById('detailsBookingId').value = bar.dataset.bookingId;
                        document.getElementById('detailsRoomNumber').value = bar.dataset.roomNumber;
                        document.getElementById('detailsCheckIn').value = bar.dataset.checkIn;
                        document.getElementById('detailsCheckOut').value = bar.dataset.checkOut;
                        document.getElementById('detailsBookingStatus').value = bar.dataset.bookingStatus;
                        document.getElementById('detailsRoomStatus').value = bar.dataset.status.charAt(0).toUpperCase() + bar.dataset.status.slice(1);
                        document.getElementById('detailsNotes').value = bar.dataset.notes;
                        
                        // Populate Guest Details
                        document.getElementById('detailsStudentId').value = bar.dataset.studentId;
                        document.getElementById('detailsFirstName').value = bar.dataset.firstName;
                        document.getElementById('detailsLastName').value = bar.dataset.lastName;
                        document.getElementById('detailsEmail').value = bar.dataset.email;
                        document.getElementById('detailsPhone').value = bar.dataset.phone;

                        detailsModal.style.display = 'block';
                    }
                });
            });
        });
    </script>
</body>
</html> 
