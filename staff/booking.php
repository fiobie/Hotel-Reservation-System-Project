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

// Handle AJAX request for room availability and pricing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_type']) && $_POST['request_type'] === 'get_room_details') {
    getRoomAvailabilityAndPrice($conn);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================
function handleBookingAction($conn) {
    $bookingId = intval($_POST['BookingID'] ?? 0);
    $action = $_POST['action'] ?? '';
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
    $bookingId = intval($_POST['BookingID'] ?? 0);
    $checkIn = $conn->real_escape_string($_POST['CheckInDate'] ?? '');
    $checkOut = $conn->real_escape_string($_POST['CheckOutDate'] ?? '');
    $roomNumber = intval($_POST['RoomNumber'] ?? 0);
    $roomType = $conn->real_escape_string($_POST['RoomType'] ?? '');
    $roomStatus = $conn->real_escape_string($_POST['RoomStatus'] ?? '');
    $bookingStatus = $conn->real_escape_string($_POST['BookingStatus'] ?? '');
    $notes = $conn->real_escape_string($_POST['Notes'] ?? '');
    $price = floatval($_POST['Price'] ?? 0);

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

function getRoomAvailabilityAndPrice($conn) {
    $checkIn = $_POST['checkInDate'] ?? '';
    $checkOut = $_POST['checkOutDate'] ?? '';
    $roomType = $_POST['roomType'] ?? '';

    $response = ['availableRoom' => null, 'price' => 0, 'message' => '', 'roomStatus' => ''];

    // Validate dates
    if (empty($checkIn) || empty($checkOut) || strtotime($checkIn) >= strtotime($checkOut)) {
        $response['message'] = 'Invalid dates provided.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Find an available room of the specified type within the date range
    $sql = "SELECT RoomNumber, PricePerNight, RoomStatus FROM room 
            WHERE RoomType = ? AND RoomStatus = 'Available' AND RoomNumber NOT IN (
                SELECT RoomNumber FROM booking 
                WHERE (CheckInDate < ? AND CheckOutDate > ?)
            ) LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $roomType, $checkOut, $checkIn);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $room = $result->fetch_assoc();
        $response['availableRoom'] = $room['RoomNumber'];
        $response['roomStatus'] = $room['RoomStatus'];

        // Calculate price based on duration and price per night
        $startDate = new DateTime($checkIn);
        $endDate = new DateTime($checkOut);
        $interval = $startDate->diff($endDate);
        $numberOfNights = $interval->days;

        if ($numberOfNights > 0) {
            $response['price'] = $numberOfNights * $room['PricePerNight'];
            $response['message'] = 'Room found.';
        } else {
            $response['message'] = 'Booking must be at least one night.';
        }

    } else {
        // If no available room found, check what rooms exist and their status
        $sql = "SELECT RoomNumber, RoomStatus FROM room WHERE RoomType = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $roomType);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $room = $result->fetch_assoc();
            $response['roomStatus'] = $room['RoomStatus'];
            $response['message'] = 'No available rooms of this type for the selected dates.';
        } else {
            $response['message'] = 'No rooms of this type found.';
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
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
// FETCH ROOMS AND BOOKINGS FOR GANTT CALENDAR
// ============================================================================
$rooms = [];
$roomResult = $conn->query("SELECT RoomNumber, RoomType, RoomStatus FROM room ORDER BY RoomNumber ASC");
while ($room = $roomResult->fetch_assoc()) {
    $rooms[$room['RoomNumber']] = [
        'RoomType' => $room['RoomType'],
        'RoomStatus' => $room['RoomStatus']
    ];
}

// Fetch all bookings for the month
$bookings = [];
$bookingResult = $conn->query("SELECT b.BookingID, b.RoomNumber, b.RoomType, b.RoomStatus, b.BookingStatus, b.CheckInDate, b.CheckOutDate, CONCAT(s.FirstName, ' ', s.LastName) as GuestName FROM booking b LEFT JOIN student s ON b.StudentID = s.StudentID WHERE (YEAR(b.CheckInDate) = $year AND MONTH(b.CheckInDate) = $month) OR (YEAR(b.CheckOutDate) = $year AND MONTH(b.CheckOutDate) = $month) ORDER BY b.RoomNumber, b.CheckInDate");
while ($row = $bookingResult->fetch_assoc()) {
    $bookings[$row['RoomNumber']][] = $row;
}
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
        .main-content { 
            max-width: 950px; 
            margin: 2.5rem auto; 
            padding: 2.5rem 2.5rem 1.5rem 2.5rem; 
            background: #f7f8f6; 
            border-radius: 24px; 
            box-shadow: none;
            border: 1.5px solid #f0f0f0;
        }
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
        
        h1 { 
            font-size: 2.3rem; 
            margin-bottom: 0; 
            font-weight: 800; 
            letter-spacing: -1px;
        }
        
        /* ============================================================================
           CALENDAR HEADER
           ============================================================================ */
        .calendar-header { 
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 1.2rem;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        
        .calendar-header h2 { 
            font-size: 1.3rem; 
            font-weight: 700; 
            margin: 0 1rem 0 0; 
            letter-spacing: 0.5px;
        }
        
        .calendar-nav { 
            display: flex; 
            align-items: center; 
            gap: 1.2rem; 
        }
        
        .calendar-nav-btn { 
            background: #f0f0f0; 
            border: none; 
            border-radius: 50%; 
            width: 2.2rem; 
            height: 2.2rem; 
            font-size: 1.3rem; 
            cursor: pointer; 
            transition: background 0.2s; 
        }
        
        .calendar-nav-btn:hover { 
            background: #e0e0e0; 
        }
        
        /* =========================================================================
           SEARCH AND FILTER
           ========================================================================= */
        .search-filter-bar {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            margin-left: 2.5rem;
            flex-wrap: wrap;
            padding-right: 0;
        }
        
        .search-input { 
            padding: 0.7rem 2.5rem 0.7rem 2.5rem; 
            border-radius: 2rem; 
            border: none; 
            background: #ededed; 
            font-size: 1rem; 
            width: 200px; 
            outline: none; 
            font-weight: 500;
        }
        
        .search-icon { 
            position: absolute; 
            left: 1rem; 
            top: 50%; 
            transform: translateY(-50%); 
            color: #888; 
            font-size: 1.1rem;
        }
        
        .search-wrapper { 
            position: relative; 
            display: flex; 
            align-items: center; 
        }
        
        .filter-btn { 
            padding: 0.7rem 1.5rem; 
            border-radius: 2rem; 
            border: 2px solid #bdbdbd; 
            background: #f4f2fa; 
            font-size: 1rem; 
            font-weight: 600;
            cursor: pointer; 
            margin-left: 0.5rem; 
            transition: background 0.2s, color 0.2s; 
        }
        
        .filter-btn:hover { 
            background: #e0e0e0; 
            color: #222; 
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

        /* Walk-in Booking Button */
        .walk-in-btn {
            background: #545b62;
            color: #fff;
            padding: 0.7rem 2.2rem;
            border-radius: 2rem;
            border: 2px solid #bdbdbd;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            white-space: nowrap;
            margin-left: 0.7rem;
            margin-right: 0;
            min-width: 160px;
            max-width: 100%;
            box-sizing: border-box;
        }
        .walk-in-btn:hover {
            background: #444;
        }
        @media (max-width: 1200px) {
            .calendar-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1.2rem;
            }
            .search-filter-bar {
                width: 100%;
                justify-content: flex-start;
                padding-right: 0;
            }
        }
        
        /* ============================================================================
           CALENDAR TABLE
           ============================================================================ */
        .calendar-table { 
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 18px;
            margin-bottom: 1.2rem;
            background: none;
            box-shadow: none;
            border: none;
        }
        .calendar-table tr {
            background: none;
        }
        
        .calendar-table th { 
            font-size: 1.25rem;
            font-weight: 700;
            color: #111;
            text-align: center;
            padding: 0 10px 0.7rem 10px;
            border-bottom: none;
            letter-spacing: 1px;
        }
        
        .calendar-table td { 
            text-align: center;
            vertical-align: middle;
            min-width: 54px;
            padding: 0.2rem 8px 0.2rem 8px;
            margin-bottom: 0;
            background: #fff;
            height: 70px;
            position: relative;
        }
        
        .calendar-date {
            font-weight: 600;
            font-size: 1.05rem;
            margin-bottom: 0.2rem;
            margin-top: 0.1rem;
            color: #222;
        }
        .calendar-cell-available, .calendar-cell-booked, .calendar-cell-reserved, .calendar-cell-maintenance {
            border-radius: 1.2rem;
            padding: 0;
            background-clip: padding-box;
        }
        .calendar-cell-available { background: #b6f7c1 !important; }
        .calendar-cell-booked { background: #ff4d4d !important; }
        .calendar-cell-reserved { background: #ffe066 !important; }
        .calendar-cell-maintenance { background: #bdbdbd !important; }

        /* Booking bar inside calendar cell */
        .calendar-bar {
            width: 95%;
            border-radius: 0.7rem;
            font-size: 0.82rem;
            font-weight: 400;
            margin: 0.12rem auto 0.05rem auto;
            padding: 0.13rem 0.7rem 0.13rem 0.7rem;
            color: #222;
            text-align: center;
            background: #fff;
            white-space: normal;
            position: relative;
            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
            border: 2px solid #b6f7c1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: box-shadow 0.2s, transform 0.2s, border-color 0.2s;
        }
        .calendar-bar-booked { border-color: #ff4d4d; color: #ff4d4d; }
        .calendar-bar-reserved { border-color: #ffe066; color: #bfa600; }
        .calendar-bar-maintenance { border-color: #bdbdbd; color: #888; }
        .calendar-bar-available { border-color: #b6f7c1; color: #1a7f3c; }
        .calendar-bar-double { border-color: #b2005a; color: #b2005a; }
        .calendar-bar-label {
            font-size: 0.89rem;
            font-weight: 600;
            margin-bottom: 0.04rem;
            color: #222;
        }
        .calendar-bar-status {
            font-size: 0.78rem;
            font-weight: 400;
            color: inherit;
            margin-top: 0.01rem;
        }
        .calendar-bar:hover {
            box-shadow: 0 6px 18px rgba(0,0,0,0.16);
            transform: translateY(-2px) scale(1.04);
            z-index: 2;
        }

        /* Remove double border between adjacent colored cells */
        .calendar-table { border-collapse: separate; border-spacing: 0 18px; }
        .calendar-table tr { background: none; }

        /* Legend improvements */
        .legend {
            display: flex;
            gap: 1.2rem;
            justify-content: center;
            align-items: center;
            margin-bottom: 0.7rem;
            margin-top: 1.2rem;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 1.05rem;
            font-weight: 500;
        }
        .legend-badge {
            width: 1.3rem;
            height: 1.3rem;
            border-radius: 1.3rem;
            display: inline-block;
            margin-right: 0.2rem;
            border: 1.5px solid #e0e0e0;
        }
        .legend-available { background: #b6f7c1; }
        .legend-booked { background: #ff4d4d; }
        .legend-reserved { background: #ffe066; }
        .legend-maintenance { background: #bdbdbd; }

        /* Controls grouping */
        .calendar-header {
            flex-wrap: wrap;
            gap: 1.2rem;
            align-items: flex-end;
        }
        .search-filter-bar {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            margin-left: 0;
            flex-wrap: wrap;
            padding-right: 0;
            background: #fafaf9;
            border-radius: 2rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.03);
            padding: 0.5rem 1.2rem;
        }
        .search-input {
            padding: 0.7rem 2.5rem 0.7rem 2.5rem;
            border-radius: 2rem;
            border: none;
            background: #ededed;
            font-size: 1rem;
            width: 200px;
            outline: none;
            font-weight: 500;
        }
        .filter-btn {
            padding: 0.7rem 1.5rem;
            border-radius: 2rem;
            border: 2px solid #bdbdbd;
            background: #f4f2fa;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-left: 0.5rem;
            transition: background 0.2s, color 0.2s;
        }
        .walk-in-btn {
            background: #545b62;
            color: #fff;
            padding: 0.7rem 2.2rem;
            border-radius: 2rem;
            border: 2px solid #bdbdbd;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            white-space: nowrap;
            margin-left: 0.7rem;
            margin-right: 0;
            min-width: 160px;
            max-width: 100%;
            box-sizing: border-box;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .walk-in-btn:hover {
            background: #444;
        }
        /* Responsive tweaks */
        @media (max-width: 900px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .sidebar { left: -220px; box-shadow: none; }
            .sidebar.active { left: 0; box-shadow: 2px 0 8px rgba(0,0,0,0.08); }
            .hamburger { display: flex; }
            .calendar-header { flex-direction: column; align-items: flex-start; gap: 1.2rem; }
            .search-filter-bar { width: 100%; justify-content: flex-start; padding-right: 0; }
            .calendar-table td { height: 54px; font-size: 0.9rem; }
        }
        
        /* ============================================================================
           CALENDAR TABLE
           ============================================================================ */
        .calendar-table { 
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0.7rem;
            margin-bottom: 1.2rem;
            background: none;
            box-shadow: none;
            border: none;
        }
        .calendar-table tr {
            background: none;
        }
        
        .calendar-table th { 
            font-size: 1.25rem;
            font-weight: 700;
            color: #111;
            text-align: center;
            padding: 0 10px 0.7rem 10px;
            border-bottom: none;
            letter-spacing: 1px;
        }
        
        .calendar-table td { 
            text-align: center;
            vertical-align: top;
            min-width: 54px;
            padding: 0.2rem 6px 0.2rem 6px;
            margin-bottom: 0;
            background: none;
        }
        
        .calendar-date {
            font-weight: 700;
            font-size: 1.1rem;
            color: #111;
            margin-bottom: 0.2rem;
            margin-top: 0.1rem;
            letter-spacing: 0.5px;
        }
        
        .calendar-badge {
            display: inline-block;
            background: #dddddd;
            color: #222;
            font-weight: 700;
            font-size: 1.05rem;
            border-radius: 2rem;
            padding: 0.3rem 1.2rem;
            margin-top: 0.2rem;
            margin-bottom: 0.2rem;
            min-width: 70px;
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
        .booked { background: #ff4d4d; color: #fff; } /* Red for booked */
        .reserved { background: #ffe066; color: #222; } /* Yellow for reserved */
        .maintenance { background: #bdbdbd; color: #222; } /* Gray for maintenance */
        
        /* ============================================================================
           LEGEND
           ============================================================================ */
        .legend { 
            display: flex; 
            gap: 1.2rem; 
            justify-content: center; 
            align-items: center;
            margin-bottom: 0.5rem;
            margin-top: 1.5rem;
        }
        
        .legend-item { 
            display: flex; 
            align-items: center; 
            gap: 0.5rem; 
            font-size: 1.1rem; 
        }
        
        .legend-badge { 
            width: 2.5rem; 
            height: 2rem; 
            border-radius: 2rem; 
            display: inline-block; 
        }
        
        .legend-available { background: #7be881; color: #222; }
        .legend-booked { background: #f47c7c; color: #fff; }
        .legend-reserved { background: #ffe066; color: #222; }
        .legend-maintenance { background: #bdbdbd; color: #222; }
        .legend-cleaning { background: #ffc285; color: #222; }
        
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
            justify-content: center; /* Center horizontally */
            align-items: center;   /* Center vertically */
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 950px; /* Reverted to 950px to accommodate four inputs comfortably */
            max-height: 90vh;
            overflow-y: auto;
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
            flex-wrap: wrap; /* Allow items to wrap to the next line */
            gap: 1.2rem;
        }
        
        .form-row .form-group {
            flex: 1 1 auto; /* Allow items to grow and shrink, and wrap */
            min-width: 220px; /* Increased minimum width for form groups */
        }

        /* Removed min-width: 0 override for #walkInDateRow .form-group to ensure proper sizing */
        /* #walkInDateRow .form-group {
            flex: 1 1 0%;
            min-width: 0;
        } */
        
        .total-price-section {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }
        .total-price-section span {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }
        .total-price-input {
            background-color: #ededed;
            border-radius: 1.2rem;
            padding: 0.7rem 1.2rem;
            width: 120px; /* Adjusted width again to be more compact */
            border: none;
            font-size: 1.1rem;
            font-weight: bold;
            text-align: right;
            outline: none;
        }

        .modal-footer-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        .next-btn {
            background: #d1d1d1;
            color: #222;
            padding: 0.8rem 2rem;
            border-radius: 1.2rem;
            font-weight: 600;
            margin-left: auto;
            margin-top: 2rem;
        }
        .next-btn:hover {
            background: #a0a0a0;
        }

        .input-with-icon i {
            position: absolute;
            left: 12px; /* Adjusted left position */
            color: #888;
            z-index: 1; /* Ensure icon is above input text */
        }
        .input-with-icon input {
            padding: 0.7rem 1rem 0.7rem 40px; /* Increased left padding for text */
            box-sizing: border-box; /* Ensure padding is included in the width */
        }
        .custom-select {
            width: 100%;
            padding: 0.7rem;
            border: 1px solid #ddd;
            border-radius: 1.2rem;
            font-size: 0.95rem; /* Slightly reduced font-size */
            background-color: #ededed;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23888'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            cursor: pointer;
        }
        .custom-select:focus {
            outline: none;
            border-color: #008000;
        }
        
        .date-input {
            background-color: #ededed;
            border-radius: 1.2rem;
            width: 100%;
            border: none;
            font-size: 0.95rem; /* Slightly reduced font-size */
            outline: none;
            min-width: 120px;
        }
        
        .special-request-textarea {
            width: 100%;
            height: 100px; /* Adjust height as needed */
            border: none;
            border-radius: 12px;
            background-color: #ededed;
            padding: 1rem;
            font-size: 1rem;
            resize: none; /* Disable resizing */
            outline: none;
        }

        /* Enhanced Modal Styles for Walk-in Booking */
        #walkInBookingModal .modal-content {
            padding: 2.5rem 4.5rem; /* More generous horizontal padding */
            max-width: 950px; /* Increased max-width for more space */
            box-shadow: 0 8px 30px rgba(0,0,0,0.1); /* Stronger, softer shadow */
        }

        #walkInBookingModal .modal-content h2 {
            font-size: 2rem; /* Larger, more impactful title */
            font-weight: 800; /* Extra bold */
            color: #1a1a1a; /* Darker text for prominence */
            margin-bottom: 2rem; /* More space below main title */
            text-align: left; /* Aligned left as per image */
        }

        #walkInBookingModal .modal-content h3 {
            font-size: 1.3rem; /* Slightly adjusted size for section titles */
            font-weight: 700; /* Bolder */
            color: #333;
            margin-top: 2.5rem; /* More space above section titles */
            margin-bottom: 1.2rem; /* More space below section titles */
            border-bottom: none; /* No border bottom for section titles as per image */
            padding-bottom: 0;
        }

        #walkInBookingModal .form-group label {
            font-size: 0.95rem; /* Slightly larger label for readability */
            color: #555; /* Slightly darker grey for labels */
            margin-bottom: 0.6rem; /* Good spacing between label and input */
            font-weight: 500; /* Medium weight */
        }

        #walkInBookingModal .form-group input,
        #walkInBookingModal .form-group select,
        #walkInBookingModal .form-group textarea {
            padding: 0.75rem 1.1rem; /* Refined padding */
            border-radius: 0.6rem; /* Softened corners */
            background-color: #f7f7f7; /* Very light grey for inputs */
            border: 1px solid #e8e8e8; /* Very subtle border */
            font-size: 1rem; /* Standard font size */
        }

        #walkInBookingModal .form-group input:focus,
        #walkInBookingModal .form-group select:focus,
        #walkInBookingModal .form-group textarea:focus {
            border-color: #4CAF50; /* A pleasant green for focus */
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2); /* Soft green shadow */
            outline: none;
        }

        #walkInBookingModal .date-input,
        #walkInBookingModal .custom-select {
            background-color: #f7f7f7;
            border: 1px solid #e8e8e8;
            border-radius: 0.6rem;
        }

        #walkInBookingModal .special-request-textarea {
            height: 100px; /* Keep the original height, it looks good */
            padding: 1rem;
            background-color: #f7f7f7;
            border: 1px solid #e8e8e8;
            border-radius: 0.6rem;
        }

        #walkInBookingModal .input-with-icon i {
            left: 0.9rem; /* Icon closer to the edge */
            font-size: 1rem; /* Original icon size */
            color: #999; /* Lighter icon color */
        }
        #walkInBookingModal .input-with-icon input {
            padding-left: 2.8rem; /* Adjusted for icon position */
        }
        
        /* Readonly styles for Room Number to look like a display field */
        #walkInAvailableRoomNumber {
            background-color: #e9e9e9; /* Distinct background for readonly */
            color: #444; /* Slightly darker text */
            pointer-events: none; /* Disable interaction */
            opacity: 1; /* Ensure full visibility */
        }

        /* Adjustments for the total price section */
        #walkInBookingModal .total-price-section {
            margin-top: 3rem; /* More vertical separation */
            padding-top: 1.5rem; /* Padding at the top */
            border-top: 1px dashed #e0e0e0; /* A dashed line separator */
        }
        #walkInBookingModal .total-price-section span {
            font-size: 1.2rem; /* As per image */
            font-weight: 600; /* As per image */
            color: #333;
        }
        #walkInBookingModal .total-price-input {
            background-color: #f0f0f0; /* Light grey for total price */
            border-radius: 0.6rem;
            padding: 0.7rem 1rem;
            width: 140px; /* Match image width */
            font-size: 1.05rem; /* Font size as per image */
            font-weight: bold;
            text-align: right;
            border: 1px solid #dcdcdc; /* Subtle border */
        }

        /* Next button styling */
        #walkInBookingModal .modal-footer-actions {
            margin-top: 2.5rem; /* Space before the button */
        }

        #walkInBookingModal .next-btn {
            background: #4CAF50; /* Green from the image */
            color: white;
            padding: 0.8rem 2.2rem; /* Match image padding */
            border-radius: 1.8rem; /* More rounded as per image */
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 2px 6px rgba(0, 128, 0, 0.1); /* Softer shadow */
            border: none;
            cursor: pointer;
            transition: background 0.2s ease-in-out, transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        #walkInBookingModal .next-btn:hover {
            background: #45a049; /* Darker green on hover */
            transform: translateY(-1px); /* More subtle lift */
            box-shadow: 0 4px 10px rgba(0, 128, 0, 0.2); /* Enhanced shadow on hover */
        }

        /* Close button in walk-in modal */
        #walkInBookingModal .close {
            position: absolute;
            top: 1.2rem; /* Adjusted top */
            right: 1.2rem; /* Adjusted right */
            font-size: 1.8rem; /* Match image size */
            color: #b0b0b0; /* Lighter color */
            transition: color 0.2s;
        }
        #walkInBookingModal .close:hover {
            color: #777;
        }

        /* Adjustments for form-row gap */
        #walkInBookingModal .form-row {
            gap: 1.5rem; /* Ensure consistent gap */
            align-items: flex-end; /* Align items at the bottom */
        }
        #walkInBookingModal .form-row .form-group {
            flex: 1 1 calc(25% - 1.125rem); /* Roughly 4 items per row with gap */
            min-width: 180px; /* Ensure a decent minimum width */
        }

        /* Special handling for Booking Status to appear as a dropdown */
        #walkInBookingStatus {
             pointer-events: auto; /* Re-enable interaction for booking status */
             background-color: #f7f7f7; /* Consistent input background */
             color: #333;
             border: 1px solid #e8e8e8;
        }

        /* Make Booking Date input wider for datetime-local */
        #walkInBookingDate,
        .form-group input[type="datetime-local"] {
            display: block;
            min-width: 300px;
            max-width: 400px;
            width: 100%;
            font-size: 1.15rem;
            padding: 0.9rem 1.2rem;
            box-sizing: border-box;
            margin: 0 auto;
        }
        /* Ensure parent .form-group does not restrict width */
        #walkInBookingModal .form-group {
            width: 100%;
            max-width: 420px;
        }

        /* Highlight available calendar cell */
        .calendar-cell-available {
            background: #b6f7c1 !important;
            border-radius: 1.2rem;
        }

        /* ============================================================================
           GANTT-STYLE BOOKING CALENDAR
           ============================================================================ */
        .gantt-calendar-container {
            overflow-x: auto;
            margin: 2rem 0;
        }
        .gantt-calendar-table {
            border-collapse: collapse;
            width: 100%;
            min-width: 900px;
            background: #fff;
        }
        .gantt-calendar-table th, .gantt-calendar-table td {
            border: 1px solid #e0e0e0;
            text-align: center;
            padding: 0.3rem 0.2rem;
            font-size: 0.98rem;
            min-width: 32px;
            position: relative;
        }
        .gantt-calendar-table .room-col, .gantt-calendar-table .room-col-label {
            min-width: 90px;
            background: #f7f7f7;
            font-weight: 600;
        }
        .gantt-bar {
            color: #222;
            font-weight: 600;
            border-radius: 1.2rem;
            font-size: 0.97rem;
            padding: 0.2rem 0.5rem;
            white-space: normal;
            text-align: left;
        }
        .gantt-bar-booked {
            background: #ff4d4d;
            color: #fff;
        }
        .gantt-bar-reserved {
            background: #ffe066;
            color: #222;
        }
        .gantt-bar-maintenance {
            background: #bdbdbd;
            color: #222;
        }
        .gantt-bar-available {
            background: #b6f7c1;
            color: #222;
        }

        /* Booking bar inside calendar cell (calendar view, not Gantt) */
        .calendar-bar-gantt {
            display: block;
            height: 2.1rem;
            margin: 0.2rem 0 0.2rem 0;
            background: #009688;
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            text-align: left;
            padding: 0.2rem 1.2rem;
            border-radius: 0.4rem;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }
        .calendar-bar-normal {
            background: #009688;
        }
        .calendar-bar-double-booking {
            background: #b2002d;
        }
        .calendar-bar-label {
            font-size: 1rem;
            font-weight: 700;
            display: block;
        }

        @media (max-width: 700px) {
            .calendar-table th, .calendar-table td {
                padding-left: 2px;
                padding-right: 2px;
                font-size: 0.85rem;
            }
            .calendar-bar {
                font-size: 0.85rem;
                padding: 0.2rem 0.2rem;
            }
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
                <button class="walk-in-btn" id="walkInBookingBtn">Walk-in booking</button>
            </div>
        </div>
        
        <!-- ============================================================================
             LEGEND (moved above calendar)
             ============================================================================ -->
        <div class="legend" style="display: flex; justify-content: center; align-items: center; gap: 2.5rem; margin-top: 2.5rem;">
            <div class="legend-item" style="display: flex; align-items: center; gap: 0.5rem;">
                <span class="legend-badge legend-available" style="width: 1.2rem; height: 1.2rem; border-radius: 50%; display: inline-block; background: #b6f7c1; border: 1.5px solid #b6f7c1;"></span>
                <span style="font-size: 1.1rem;">Available</span>
            </div>
            <div class="legend-item" style="display: flex; align-items: center; gap: 0.5rem;">
                <span class="legend-badge legend-booked" style="width: 1.2rem; height: 1.2rem; border-radius: 50%; display: inline-block; background: #ff4d4d; border: 1.5px solid #ff4d4d;"></span>
                <span style="font-size: 1.1rem;">Booked</span>
            </div>
            <div class="legend-item" style="display: flex; align-items: center; gap: 0.5rem;">
                <span class="legend-badge legend-reserved" style="width: 1.2rem; height: 1.2rem; border-radius: 50%; display: inline-block; background: #ffe066; border: 1.5px solid #ffe066;"></span>
                <span style="font-size: 1.1rem;">Reserved</span>
            </div>
            <div class="legend-item" style="display: flex; align-items: center; gap: 0.5rem;">
                <span class="legend-badge legend-maintenance" style="width: 1.2rem; height: 1.2rem; border-radius: 50%; display: inline-block; background: #bdbdbd; border: 1.5px solid #bdbdbd;"></span>
                <span style="font-size: 1.1rem;">Maintenance</span>
            </div>
        </div>

        <!-- ============================================================================
             CALENDAR TABLE (Classic Calendar View)
             ============================================================================ -->
        <table class="calendar-table">
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
                <?php
                // Build a 2D array of weeks, each with 7 days (Sun-Sat)
                $weeks = [];
                $week = [];
                $dateCursor = new DateTime("$year-$month-01");
                $firstDayOfWeek = 0; // 0=Sunday
                $lastDate = new DateTime("$year-$month-$daysInMonth");
                $startDayOfWeek = (int)$dateCursor->format('w');
                // Fill initial empty days
                for ($i = 0; $i < $startDayOfWeek; $i++) {
                    $week[] = null;
                }
                while ($dateCursor <= $lastDate) {
                    $week[] = $dateCursor->format('Y-m-d');
                    if (count($week) === 7) {
                        $weeks[] = $week;
                        $week = [];
                    }
                    $dateCursor->modify('+1 day');
                }
                if (count($week) > 0) {
                    while (count($week) < 7) $week[] = null;
                    $weeks[] = $week;
                }
                // Render weeks
                foreach ($weeks as $week) {
                    echo '<tr>';
                    foreach ($week as $date) {
                        echo '<td';
                        if ($date) {
                            // Find all bookings that cover this date
                            $bars = [];
                            $statuses = [];
                            foreach ($bookings as $roomBookings) {
                                foreach ($roomBookings as $b) {
                                    $checkIn = date('Y-m-d', strtotime($b['CheckInDate']));
                                    $checkOut = date('Y-m-d', strtotime($b['CheckOutDate']));
                                    if ($checkIn <= $date && $checkOut >= $date) {
                                        $bars[] = $b;
                                        $statuses[] = strtolower($b['RoomStatus']);
                                    }
                                }
                            }
                            // Determine cell highlight by most important status
                            $cellClass = '';
                            if (in_array('booked', $statuses)) $cellClass = 'calendar-cell-booked';
                            elseif (in_array('reserved', $statuses)) $cellClass = 'calendar-cell-reserved';
                            elseif (in_array('maintenance', $statuses)) $cellClass = 'calendar-cell-maintenance';
                            elseif (in_array('available', $statuses)) $cellClass = 'calendar-cell-available';
                            if ($cellClass) echo ' class="' . $cellClass . '"';
                        }
                        echo '>';
                        if ($date) {
                            $dayNum = (int)date('j', strtotime($date));
                            echo '<div class="calendar-date">' . $dayNum . '</div>';
                            // Render bars (stacked if multiple)
                            foreach ($bars as $bar) {
                                $statusClass = strtolower($bar['RoomStatus']);
                                $barClass = 'calendar-bar calendar-bar-searchable ';
                                if (count($bars) > 1) $barClass .= 'calendar-bar-double ';
                                elseif ($statusClass === 'booked') $barClass .= 'calendar-bar-booked ';
                                elseif ($statusClass === 'reserved') $barClass .= 'calendar-bar-reserved ';
                                elseif ($statusClass === 'maintenance') $barClass .= 'calendar-bar-maintenance ';
                                elseif ($statusClass === 'available') $barClass .= 'calendar-bar-available ';
                                else $barClass .= 'calendar-bar-available ';
                                echo '<div class="' . $barClass . '" '
                                    . 'data-guest-name="' . htmlspecialchars($bar['GuestName']) . '" '
                                    . 'data-booking-id="' . htmlspecialchars($bar['BookingID']) . '" '
                                    . 'data-check-in="' . htmlspecialchars($bar['CheckInDate']) . '" '
                                    . 'data-check-out="' . htmlspecialchars($bar['CheckOutDate']) . '" '
                                    . 'data-room-status="' . htmlspecialchars($bar['RoomStatus']) . '" '
                                    . 'data-room-number="' . htmlspecialchars($bar['RoomNumber']) . '" '
                                    . 'data-room-type="' . htmlspecialchars($bar['RoomType']) . '" '
                                    . 'data-booking-status="' . htmlspecialchars($bar['BookingStatus']) . '" >';
                                echo '<span class="calendar-bar-label">Room ' . $bar['RoomNumber'] . '</span>';
                                echo '<span class="calendar-bar-status">' . ucfirst($statusClass) . '</span>';
                                echo '</div>';
                            }
                        }
                        echo '</td>';
                    }
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
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
         Walk-in Booking Modal
         ============================================================================ -->
    <div id="walkInBookingModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeWalkInBookingModal"><i class="fas fa-times"></i></span>
            <h2>BOOKING DETAILS</h2>
            <div class="modal-body">
                <div class="form-row" id="walkInDateRow">
                    <div class="form-group">
                        <label>Check In</label>
                        <div class="input-with-icon">
                            <i class="fas fa-calendar-alt"></i>
                            <input type="date" class="date-input" id="walkInCheckInDate">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Check Out</label>
                        <div class="input-with-icon">
                            <i class="fas fa-calendar-alt"></i>
                            <input type="date" class="date-input" id="walkInCheckOutDate">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Booking Date</label>
                        <div class="input-with-icon">
                            <i class="fas fa-calendar-alt"></i>
                            <input type="datetime-local" class="date-input" id="walkInBookingDate">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Booking Status</label>
                        <select class="custom-select" id="walkInBookingStatus">
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                </div>

                <h3>Room Details</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Room Type</label>
                        <select class="custom-select" id="walkInRoomType">
                            <option value="Standard">Standard</option>
                            <option value="Deluxe">Deluxe</option>
                            <option value="Suite">Suite</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Room Status</label>
                        <select class="custom-select" id="walkInRoomStatus" readonly>
                            <option value="Available">Available</option>
                            <option value="Booked">Booked</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Cleaning">Cleaning</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Room Number</label>
                        <input type="text" class="date-input" id="walkInAvailableRoomNumber" readonly>
                    </div>
                </div>

                <h3>Special Request</h3>
                <textarea class="special-request-textarea" rows="5" id="walkInSpecialRequest"></textarea>

                <div class="total-price-section">
                    <span>Total Price:</span>
                    <input type="text" class="total-price-input" id="walkInTotalPrice" readonly>
                </div>

                <div class="modal-footer-actions">
                    <button class="btn next-btn" id="walkInNextBtn">Next ></button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================================
         Guest Information Modal (Step 2)
         ============================================================================ -->
    <div id="guestInfoModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <span class="close" id="closeGuestInfoModal"><i class="fas fa-times"></i></span>
            <h2 style="color: #8c8c7b; font-size: 2.5rem; font-weight: 700; margin-bottom: 1.5rem;">Hotel Guest Information</h2>
            <form id="guestInfoForm">
                <table style="width:100%; border: 1px solid #000; border-collapse: collapse; box-shadow: 0 0 0 2px #000;">
                    <thead>
                        <tr style="background: #e5e5e5;">
                            <th colspan="2" style="text-align:left; padding: 0.5rem 0.7rem; font-size: 1.1rem; border: 1px solid #000;">Guest Information</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td style="background: #f5f5f5; padding: 0.6rem 0.7rem; border: 1px solid #000; width: 180px;">First Name</td><td style="border: 1px solid #000;"><input type="text" name="firstName" required style="width: 100%; padding: 0.5rem; border: 1px solid #000;"></td></tr>
                        <tr><td style="background: #f5f5f5; padding: 0.6rem 0.7rem; border: 1px solid #000;">Last Name</td><td style="border: 1px solid #000;"><input type="text" name="lastName" required style="width: 100%; padding: 0.5rem; border: 1px solid #000;"></td></tr>
                        <tr><td style="background: #f5f5f5; padding: 0.6rem 0.7rem; border: 1px solid #000;">Gender</td><td style="border: 1px solid #000;"><input type="text" name="gender" required style="width: 100%; padding: 0.5rem; border: 1px solid #000;"></td></tr>
                        <tr><td style="background: #f5f5f5; padding: 0.6rem 0.7rem; border: 1px solid #000;">Phone Number</td><td style="border: 1px solid #000;"><input type="text" name="phone" required style="width: 100%; padding: 0.5rem; border: 1px solid #000;"></td></tr>
                        <tr><td style="background: #f5f5f5; padding: 0.6rem 0.7rem; border: 1px solid #000;">Address</td><td style="border: 1px solid #000;"><input type="text" name="address" required style="width: 100%; padding: 0.5rem; border: 1px solid #000;"></td></tr>
                        <tr><td style="background: #f5f5f5; padding: 0.6rem 0.7rem; border: 1px solid #000;">Email</td><td style="border: 1px solid #000;"><input type="email" name="email" required style="width: 100%; padding: 0.5rem; border: 1px solid #000;"></td></tr>
                        <tr><td style="background: #f5f5f5; padding: 0.6rem 0.7rem; border: 1px solid #000;">Nationality</td><td style="border: 1px solid #000;"><input type="text" name="nationality" required style="width: 100%; padding: 0.5rem; border: 1px solid #000;"></td></tr>
                        <tr><td style="background: #f5f5f5; padding: 0.6rem 0.7rem; border: 1px solid #000;">Birthdate</td><td style="border: 1px solid #000;"><input type="date" name="birthdate" required style="width: 100%; padding: 0.5rem; border: 1px solid #000;"></td></tr>
                        <tr><td style="background: #f5f5f5; padding: 0.6rem 0.7rem; border: 1px solid #000;">Student ID</td><td style="border: 1px solid #000;"><input type="text" name="studentId" style="width: 100%; padding: 0.5rem; border: 1px solid #000;"></td></tr>
                    </tbody>
                </table>
                <div style="display: flex; justify-content: flex-end; margin-top: 2rem;">
                    <button type="submit" class="btn next-btn" style="background: #c7c6e6; color: #222; font-weight: 600; font-size: 1.1rem; border-radius: 2rem; padding: 0.8rem 2.2rem;">Confirm Booking</button>
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

        // Advanced search and filter for calendar bars
    function filterCalendarBars() {
        const searchVal = searchInput.value.trim().toLowerCase();
        const formData = new FormData(filterForm);
        const filters = Object.fromEntries(formData.entries());
        const bars = document.querySelectorAll('.calendar-bar-searchable');
        bars.forEach(bar => {
            let show = true;
            // Search bar filter
            if (searchVal) {
                const guest = (bar.getAttribute('data-guest-name') || '').toLowerCase();
                const bookingId = (bar.getAttribute('data-booking-id') || '').toLowerCase();
                const checkIn = (bar.getAttribute('data-check-in') || '').toLowerCase();
                const checkOut = (bar.getAttribute('data-check-out') || '').toLowerCase();
                if (!(
                    guest.includes(searchVal) ||
                    bookingId.includes(searchVal) ||
                    checkIn.includes(searchVal) ||
                    checkOut.includes(searchVal)
                )) {
                    show = false;
                }
            }
            // Filter by Guest Name (partial, case-insensitive)
            if (filters.GuestName && filters.GuestName.trim() !== '') {
                const guest = (bar.getAttribute('data-guest-name') || '').toLowerCase();
                if (!guest.includes(filters.GuestName.trim().toLowerCase())) show = false;
            }
            // Filter by Room Status
            if (filters.RoomStatus && filters.RoomStatus !== '') {
                const status = (bar.getAttribute('data-room-status') || bar.className).toLowerCase();
                if (!status.includes(filters.RoomStatus.toLowerCase())) show = false;
            }
            bar.style.display = show ? '' : 'none';
        });
    }

        // Apply filters
    applyFilterBtn.onclick = function() {
        filterCalendarBars();
        filterDropdown.classList.remove('active');
    }
    // Clear filters
    clearFilterBtn.onclick = function() {
        filterForm.reset();
        filterCalendarBars();
    }
    // Search functionality
    searchInput.oninput = function() {
        filterCalendarBars();
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
            const editModal = document.getElementById('editModal');
            const walkInModal = document.getElementById('walkInBookingModal');
            if (event.target == editModal) {
                editModal.style.display = 'none';
            } else if (event.target == walkInModal) {
                walkInModal.style.display = 'none';
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

        // Walk-in Booking Modal functionality
        const walkInBookingBtn = document.getElementById('walkInBookingBtn');
        const walkInBookingModal = document.getElementById('walkInBookingModal');
        const closeWalkInBookingModal = document.getElementById('closeWalkInBookingModal');

        const walkInBookingDateInput = document.getElementById('walkInBookingDate');
        const walkInCheckInDateInput = document.getElementById('walkInCheckInDate');
        const walkInCheckOutDateInput = document.getElementById('walkInCheckOutDate');
        const walkInRoomTypeSelect = document.getElementById('walkInRoomType');
        const walkInTotalPriceInput = document.getElementById('walkInTotalPrice');
        const walkInRoomStatusSelect = document.getElementById('walkInRoomStatus');
        const walkInAvailableRoomNumberInput = document.getElementById('walkInAvailableRoomNumber');
        const walkInBookingStatusSelect = document.getElementById('walkInBookingStatus');

        walkInBookingBtn.onclick = function() {
            walkInBookingModal.style.display = 'flex'; // Use flex for centering
            // Set current date and time for Booking Date
            const now = new Date();
            const tzOffset = now.getTimezoneOffset() * 60000;
            const localISOTime = new Date(now - tzOffset).toISOString().slice(0, 16); // yyyy-MM-ddTHH:mm
            walkInBookingDateInput.value = localISOTime;
            
            // Clear other fields for a fresh booking
            walkInCheckInDateInput.value = '';
            walkInCheckOutDateInput.value = '';
            walkInRoomTypeSelect.value = 'Standard'; // Default to Standard
            walkInTotalPriceInput.value = '';
            walkInRoomStatusSelect.value = 'Available'; // Default to Available initially
            walkInAvailableRoomNumberInput.value = '';
            walkInBookingStatusSelect.value = 'Confirmed'; // Set booking status to Confirmed by default
        }

        closeWalkInBookingModal.onclick = function(event) {
            event.stopPropagation(); // Prevent click from propagating to window
            walkInBookingModal.style.display = 'none';
        }

        // Function to update room status and calculate price
        function updateRoomStatusAndPrice() {
            const checkIn = walkInCheckInDateInput.value;
            const checkOut = walkInCheckOutDateInput.value;
            const roomType = walkInRoomTypeSelect.value;

            if (checkIn && checkOut && roomType) {
                const formData = new FormData();
                formData.append('request_type', 'get_room_details');
                formData.append('checkInDate', checkIn);
                formData.append('checkOutDate', checkOut);
                formData.append('roomType', roomType);

                fetch('booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.availableRoom) {
                        walkInTotalPriceInput.value = data.price.toFixed(2); // Format price to 2 decimal places
                        walkInRoomStatusSelect.value = data.roomStatus; // Set room status from response
                        walkInAvailableRoomNumberInput.value = data.availableRoom;
                        console.log('Available Room:', data.availableRoom);
                    } else {
                        walkInTotalPriceInput.value = '';
                        walkInRoomStatusSelect.value = data.roomStatus || 'Not Available'; // Set room status based on response or 'Not Available'
                        walkInAvailableRoomNumberInput.value = '';
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error fetching room details:', error);
                    walkInTotalPriceInput.value = '';
                    walkInRoomStatusSelect.value = '';
                    walkInAvailableRoomNumberInput.value = '';
                    alert('Error fetching room details. Please try again.');
                });
            } else {
                walkInTotalPriceInput.value = '';
                walkInRoomStatusSelect.value = '';
                walkInAvailableRoomNumberInput.value = '';
            }
        }

        // Event listeners for date changes and room type selection
        walkInCheckInDateInput.onchange = updateRoomStatusAndPrice;
        walkInCheckOutDateInput.onchange = updateRoomStatusAndPrice;
        walkInRoomTypeSelect.onchange = updateRoomStatusAndPrice;

        // Handle the 'Next' button click for walk-in booking
        document.getElementById('walkInNextBtn').onclick = function() {
            // Hide walk-in booking modal and show guest info modal
            walkInBookingModal.style.display = 'none';
            document.getElementById('guestInfoModal').style.display = 'flex';
        };

        // Close guest info modal
        document.getElementById('closeGuestInfoModal').onclick = function(event) {
            event.stopPropagation();
            document.getElementById('guestInfoModal').style.display = 'none';
        };

        // Optionally, handle guest info form submission
        document.getElementById('guestInfoForm').onsubmit = function(e) {
            e.preventDefault();
            // Here you would collect guest info and booking details, then send to server
            alert('Booking confirmed! (Implement server-side logic as needed)');
            document.getElementById('guestInfoModal').style.display = 'none';
            // Optionally reload or update UI
        };
    </script>
</body>
</html> 
