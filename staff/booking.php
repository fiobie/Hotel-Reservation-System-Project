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

// 1. Auto-update room status to 'Available' for finished stays
$today = date('Y-m-d');
$autoUpdateSql = "UPDATE room r
    JOIN booking b ON r.RoomNumber = b.RoomNumber
    SET r.RoomStatus = 'Available'
    WHERE b.CheckOutDate < '$today' AND (b.BookingStatus = 'Confirmed' OR b.BookingStatus = 'Completed') AND r.RoomStatus != 'Available'";
$conn->query($autoUpdateSql);

$sql = "SELECT 
            b.*, 
            s.FirstName AS FirstName, 
            s.LastName AS LastName, 
            s.Gender AS Gender, 
            s.PhoneNumber AS PhoneNumber, 
            s.Address AS Address, 
            s.Email AS Email, 
            s.Nationality AS Nationality, 
            s.BirthDate AS BirthDate, 
            s.StudentID AS StudentIDNum
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

// Fetch reservation data and merge with bookings for calendar display
$reservationSql = "SELECT * FROM reservation WHERE Status != 'Cancelled'";
$reservationResult = $conn->query($reservationSql);
if ($reservationResult) {
    while ($row = $reservationResult->fetch_assoc()) {
        // Map reservation fields to booking-like structure
        $bookings[] = [
            'BookingID' => 'R' . $row['ReservationID'], // Prefix to avoid collision
            'StudentID' => null,
            'RoomNumber' => $row['RoomNumber'],
            'RoomType' => $row['RoomType'],
            'CheckInDate' => $row['PCheckInDate'],
            'CheckOutDate' => $row['PCheckOutDate'],
            'BookingDate' => null,
            'BookingStatus' => $row['Status'],
            'Notes' => '',
            'RoomStatus' => 'Reserved', // Always yellow for reservations
            'FirstName' => $row['GuestName'],
            'LastName' => '',
            'Gender' => '',
            'PhoneNumber' => '',
            'Address' => '',
            'Email' => '',
            'Nationality' => '',
            'BirthDate' => '',
            'StudentIDNum' => '',
        ];
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
// AJAX HANDLER: Return available rooms by type for dropdown (for modal)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['roomType'])) {
    header('Content-Type: application/json');
    $roomType = $conn->real_escape_string($_GET['roomType']);
    $rooms = [];
    $sql = "SELECT RoomNumber FROM room WHERE RoomType = '$roomType' AND RoomStatus = 'Available' ORDER BY RoomNumber";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
    }
    echo json_encode($rooms);
    exit;
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
    $roomNumber = $_POST['roomNumber'] ?? '';

    // Validate required fields
    if (empty($roomNumber) || empty($checkIn) || empty($checkOut)) {
        echo json_encode(['success' => false, 'message' => 'Please select a room and provide check-in/check-out dates.']);
        exit;
    }

    // Check if the selected room is available for the given dates
    $roomAvailableSql = "SELECT RoomNumber FROM room WHERE RoomNumber = ? AND RoomType = ? AND RoomStatus = 'Available'";
    $stmt = $conn->prepare($roomAvailableSql);
    $stmt->bind_param('is', $roomNumber, $roomType);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result || $result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Selected room is not available.']);
        exit;
    }

    // Check for overlapping bookings for this room
    $overlapSql = "SELECT 1 FROM booking WHERE RoomNumber = ? AND BookingStatus NOT IN ('Cancelled', 'Completed') AND ((CheckInDate < ? AND CheckOutDate > ?) OR (CheckInDate < ? AND CheckOutDate > ?) OR (CheckInDate >= ? AND CheckOutDate <= ?))";
    $stmt_overlap = $conn->prepare($overlapSql);
    $stmt_overlap->bind_param('issssss', $roomNumber, $checkOut, $checkIn, $checkIn, $checkOut, $checkIn, $checkOut);
    $stmt_overlap->execute();
    $overlapResult = $stmt_overlap->get_result();
    if ($overlapResult && $overlapResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Selected room is already booked for the chosen dates.']);
        exit;
    }

    // Create a new student record for the guest
    $studentSql = "INSERT INTO student (FirstName, LastName, Gender, PhoneNumber, Address, Email, Nationality, BirthDate, StudentID) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_student = $conn->prepare($studentSql);
    if (!$stmt_student) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare guest record statement: ' . $conn->error]);
        exit;
    }
    $stmt_student->bind_param("sssssssss", $firstName, $lastName, $gender, $phone, $address, $email, $nationality, $birthdate, $studentIdNum);
    
    if ($stmt_student->execute()) {
        $studentId = $conn->insert_id;

        $roomStatus = (strtolower($bookingStatus) === 'pending' || strtolower($bookingStatus) === 'reserved') ? 'Reserved' : 'Booked';
        $bookingSql = "INSERT INTO booking (StudentID, RoomNumber, RoomType, CheckInDate, CheckOutDate, BookingDate, BookingStatus, Notes, RoomStatus) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_booking = $conn->prepare($bookingSql);
        if (!$stmt_booking) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare booking statement: ' . $conn->error]);
            exit;
        }
        $stmt_booking->bind_param("iisssssss", $studentId, $roomNumber, $roomType, $checkIn, $checkOut, $bookingDate, $bookingStatus, $specialRequest, $roomStatus);
        
        if ($stmt_booking->execute()) {
            // Update room status
            $updateRoomSql = "UPDATE room SET RoomStatus = ? WHERE RoomNumber = ?";
            $stmt_update = $conn->prepare($updateRoomSql);
            $stmt_update->bind_param("si", $roomStatus, $roomNumber);
            $stmt_update->execute();
            echo json_encode(['success' => true, 'message' => 'Booking created successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create booking: ' . $stmt_booking->error]);
        }
    } else {
         echo json_encode(['success' => false, 'message' => 'Failed to create guest record: ' . $stmt_student->error]);
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

    if ($bookingId > 0 && $studentId > 0 && !empty($checkIn) && !empty($checkOut) && !empty($roomNumber) && is_numeric($roomNumber)) {
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

// Add AJAX handler for early checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'early_checkout') {
    $bookingId = $_POST['bookingId'] ?? 0;
    $roomNumber = $_POST['roomNumber'] ?? '';
    if ($bookingId > 0 && !empty($roomNumber)) {
        $today = date('Y-m-d');
        $sql = "UPDATE booking SET CheckOutDate = ?, BookingStatus = 'Completed' WHERE BookingID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $today, $bookingId);
        $success = $stmt->execute();
        if ($success) {
            $sql2 = "UPDATE room SET RoomStatus = 'Available' WHERE RoomNumber = ?";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param('i', $roomNumber);
            $stmt2->execute();
            echo json_encode(['success' => true, 'message' => 'Guest checked out early. Room is now available.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to process early checkout.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid booking or room.']);
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
        .control-btn { padding: 0.7rem 2rem; border-radius: 24px; border: 2px solid #4bb174; font-size: 1.1rem; font-weight: 600; background: #fff; color: #4bb174; cursor: pointer; transition: background 0.2s, color 0.2s, box-shadow 0.2s, border 0.2s; box-shadow: none; }
        .control-btn:hover { background: #e6f4ea; color: #357a4b; border-color: #357a4b; }
        .walk-in-btn { background: #4bb174; color: #fff; border: 2px solid #4bb174; }
        .walk-in-btn:hover { background: #357a4b; color: #fff; border-color: #357a4b; }

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

        /* Redesigned Modal Header and Modal for Pleasant Look */
        .modal-content {
            background: #fff;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.12), 0 1.5px 8px 0 rgba(0,0,0,0.08);
            border-radius: 1.5rem;
            padding: 2.5rem;
            min-width: 340px;
            max-width: 900px;
            width: 98vw;
            margin: 2rem auto;
            border: none;
            position: relative;
            transition: box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            max-height: 95vh;
            overflow: hidden;
        }
        .modal-content form {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 1.1rem;
            flex: 1 1 auto;
            min-height: 0;
            max-height: 70vh;
            overflow-y: auto;
            overflow-x: hidden;
            padding-bottom: 2.5rem;
            padding-right: 1rem;
            scrollbar-width: thin;
            scrollbar-color: #b6e7c9 #f3f3f3;
        }
        .modal-content form::-webkit-scrollbar {
            width: 10px;
            background: #f3f3f3;
            border-radius: 8px;
        }
        .modal-content form::-webkit-scrollbar-thumb {
            background: #b6e7c9;
            border-radius: 8px;
            min-height: 40px;
        }
        .modal-content form::-webkit-scrollbar-thumb:hover {
            background: #4bb174;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 1.2rem;
            width: 100%;
        }
        .form-group label {
            margin-bottom: 0.3rem;
            font-weight: 600;
            color: #2d4a36;
            font-size: 0.98rem;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            border-radius: 0.9rem;
            border: 1.5px solid #e0e7ef;
            background: #f8fafc;
            font-size: 1.08rem;
            padding: 0.7rem 1.1rem;
            margin-top: 0.1rem;
            margin-bottom: 0.1rem;
            box-shadow: 0 1.5px 6px 0 rgba(80, 180, 255, 0.03);
            transition: border 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border: 1.5px solid #4f8cff;
            box-shadow: 0 2px 12px 0 rgba(80, 180, 255, 0.10);
            outline: none;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1.2rem;
            margin-top: 2rem;
            background: transparent;
            position: static;
            bottom: auto;
            z-index: auto;
            padding-bottom: 0;
        }
        @media (max-width: 700px) {
            .modal-content {
                max-width: 98vw;
                padding: 1rem 0.5rem 1.5rem 0.5rem;
                border-radius: 1.2rem;
            }
        }
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 0 !important;
            padding: 2rem 2.5rem 1.2rem 2.5rem;
            border-radius: 1.5rem 1.5rem 0 0;
            background: #f8fafc;
            border-left: 8px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(60,60,90,0.04);
            min-height: 64px;
            z-index: 10;
        }
        .modal-header h2, .modal-header h4 {
            font-size: 1.6rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
            letter-spacing: 0.01em;
        }
        .modal-header.status-booked {
            border-left: 8px solid #dc3545;
            background: #ffdde0;
        }
        .modal-header.status-reserved {
            border-left: 8px solid #ffc107;
            background: #fff7d6;
        }
        .modal-header.status-maintenance {
            border-left: 8px solid #6c757d;
            background: #e6e6e6;
        }
        .close-btn {
            font-size: 1.5rem;
            color: #888;
            background: #fff;
            border: 1.5px solid #e0e7ef;
            border-radius: 50%;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 1px 4px rgba(30,41,59,0.08);
            transition: background 0.2s, color 0.2s, border 0.2s, transform 0.18s;
            margin-left: 1rem;
        }
        .close-btn:hover {
            background: #f8fafc;
            color: #dc3545;
            border-color: #dc3545;
            transform: scale(1.08);
        }
        .guest-info-table th, .guest-info-table td {
            background: #f8fafc;
        }
        /* Redesigned legend */
        .booking-legend {
            display: flex;
            gap: 1.2rem;
            align-items: center;
            margin-bottom: 1.2rem;
            font-size: 1rem;
            background: #f8fafc;
            border-radius: 1rem;
            padding: 0.5rem 1.2rem;
            box-shadow: 0 1px 4px rgba(60,60,90,0.04);
            width: fit-content;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .legend-dot {
            width: 1.1rem;
            height: 1.1rem;
            border-radius: 50%;
            display: inline-block;
            border: 2px solid #fff;
            box-shadow: 0 1px 4px rgba(60,60,90,0.08);
            vertical-align: middle;
        }
        .legend-booked { background: #dc3545; }
        .legend-reserved { background: #ffc107; }
        .legend-maintenance { background: #6c757d; }
        /* Smooth transition for header color changes */
        .modal-header { transition: border-color 0.3s, background 0.3s; }

        /* NEW FORM STYLES */
        .form-step { display: none; }
        .form-step.active { display: block; }

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
            background: #fff;
            border: 1.5px solid #b6e7c9;
            border-radius: 12px;
            box-shadow: 0 2px 12px 0 rgba(60, 60, 90, 0.07);
            padding: 1.1rem 1.2rem;
            color: #3a4a3a;
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
            color: #7a8a7a;
            font-size: 1.08rem;
            letter-spacing: 0.2px;
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

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        .modal[style*="display: flex"] {
            display: flex !important;
        }
        .modal-content {
            padding-top: 2.5rem;
        }
        .modal-content form {
            overflow-y: auto;
            max-height: 70vh;
        }
        .modal-content h4 {
            margin-top: 1.2rem;
        }
        .booking-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem 1.5rem;
            max-width: 900px;
        }
        .form-group {
            margin-bottom: 0.7rem;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.5rem 0.8rem;
            font-size: 0.98rem;
        }
        @media (max-width: 700px) {
            .booking-details-grid {
                grid-template-columns: 1fr;
            }
        }
        .enhanced-calendar-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
        }
        .calendar-picker {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: 1.5rem;
        }
        .calendar-picker select,
        .calendar-picker input[type="number"] {
            padding: 0.4rem 0.7rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            background: #fff;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            transition: border 0.2s;
        }
        .calendar-picker select:focus,
        .calendar-picker input[type="number"]:focus {
            border-color: #008000;
            outline: none;
        }
        .calendar-go-btn {
            padding: 0.4rem 1.1rem;
            background: #008000;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }
        .calendar-go-btn:hover {
            background: #006400;
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
            <div class="booking-legend">
                <span class="legend-item"><span class="legend-dot legend-booked"></span>Booked</span>
                <span class="legend-item"><span class="legend-dot legend-reserved"></span>Reserved</span>
                <span class="legend-item"><span class="legend-dot legend-maintenance"></span>Maintenance</span>
            </div>
            <div class="calendar-header">
                <h1>Booking Schedule</h1>
                <div class="calendar-nav enhanced-calendar-nav">
                    <a href="?month=<?php echo $month == 1 ? 12 : $month - 1; ?>&year=<?php echo $month == 1 ? $year - 1 : $year; ?>" class="calendar-nav-btn"><i class="fas fa-chevron-left"></i></a>
                    <span class="calendar-month-year"><?php echo date('F Y', $firstDay); ?></span>
                    <a href="?month=<?php echo $month == 12 ? 1 : $month + 1; ?>&year=<?php echo $month == 12 ? $year + 1 : $year; ?>" class="calendar-nav-btn"><i class="fas fa-chevron-right"></i></a>
                    <div class="calendar-picker">
                        <select id="monthSelect">
                            <?php
                            for ($m = 1; $m <= 12; $m++) {
                                $selected = $m == $month ? 'selected' : '';
                                echo "<option value=\"$m\" $selected>" . date('F', mktime(0,0,0,$m,1)) . "</option>";
                            }
                            ?>
                        </select>
                        <input type="number" id="yearInput" value="<?php echo $year; ?>" min="2000" max="2100">
                        <button id="goToDateBtn" class="calendar-go-btn">Go</button>
                    </div>
                </div>
            </div>

            <div class="controls-bar">
                <button class="control-btn" id="filterBtn">Search & Filter</button>
                <button class="control-btn walk-in-btn" id="walkInBtn">Walk-in booking</button>
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

    <!-- Move modals here, as direct children of body -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <form id="bookingForm">
                <!-- Step 1: Booking Details -->
                <div id="form-step-1" class="form-step active">
                    <div class="modal-header">
                        <h2>Booking & Guest Details</h2>
                        <span class="close-btn">&times;</span>
                    </div>
                    <h4>Booking Details</h4>
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
                            <input type="date" id="bookingDate" name="bookingDate" value="<?php echo date('Y-m-d'); ?>" required readonly style="background:#f3f3f3; cursor:not-allowed;">
                        </div>
                        <div class="form-group">
                            <label for="bookingStatus">Booking Status</label>
                            <select id="bookingStatus" name="bookingStatus"><option value="Pending">Pending</option><option value="Confirmed">Confirmed</option></select>
                        </div>
                        <div class="form-group">
                            <label for="roomType">Room Type</label>
                            <select id="roomType" name="roomType">
                                <option value="Deluxe">Deluxe</option>
                                <option value="Standard">Standard</option>
                                <option value="Suite">Suite</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="roomNumber">Room Number</label>
                            <select id="roomNumber" name="roomNumber" required>
                                <option value="">Select Room Number</option>
                            </select>
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
                    <div class="modal-footer">
                        <button type="button" class="control-btn" id="cancelBtn">Cancel</button>
                        <button type="button" class="control-btn walk-in-btn" id="nextBtn">Next</button>
                    </div>
                </div>
                <!-- Step 2: Guest Information -->
                <div id="form-step-2" class="form-step">
                    <div class="modal-header">
                        <h2>Booking & Guest Details</h2>
                        <span class="close-btn">&times;</span>
                    </div>
                    <h4>Guest Details</h4>
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
                        <select id="detailsRoomNumber" name="roomNumber" required>
                            <option value="">Select Room</option>
                            <?php
                            $roomRes = $conn->query("SELECT RoomNumber FROM room ORDER BY RoomNumber");
                            while ($room = $roomRes->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($room['RoomNumber']) . '">' . htmlspecialchars($room['RoomNumber']) . '</option>';
                            }
                            ?>
                        </select>
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
                    <button type="button" class="control-btn" id="earlyCheckoutBtn" style="display:none; background:#f7b731; color:#fff; border:2px solid #f7b731;">Check Out Now</button>
                </div>
            </form>
        </div>
    </div>

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
            const earlyCheckoutBtn = document.getElementById('earlyCheckoutBtn');
            
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
            const roomTypeSelect = document.getElementById('roomType');
            const roomNumberSelect = document.getElementById('roomNumber');
            const detailsBookingId = document.getElementById('detailsBookingId');
            const detailsRoomNumber = document.getElementById('detailsRoomNumber');

            // ============================================================================
            // MODAL VISIBILITY LOGIC
            // ============================================================================
            if (walkInBtn) {
                walkInBtn.onclick = () => {
                    if (bookingModal) {
                        bookingModal.style.display = 'flex';
                        if (formStep1) formStep1.classList.add('active');
                        if (formStep2) formStep2.classList.remove('active');
                    }
                }
            }

            if(filterBtn) {
                filterBtn.onclick = () => {
                    if (filterModal) filterModal.style.display = 'flex';
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
                        document.getElementById('detailsBookingId').value = bar.dataset.bookingId;
                        document.getElementById('detailsStudentId').value = bar.dataset.studentId;
                        // Repopulate and set room dropdown
                        var roomDropdown = document.getElementById('detailsRoomNumber');
                        if (roomDropdown) {
                            populateRoomDropdown(roomDropdown, bar.dataset.roomNumber);
                        }
                        document.getElementById('detailsCheckIn').value = bar.dataset.checkIn;
                        document.getElementById('detailsCheckOut').value = bar.dataset.checkOut;
                        document.getElementById('detailsBookingStatus').value = bar.dataset.bookingStatus;
                        document.getElementById('detailsRoomStatus').value = bar.dataset.status.charAt(0).toUpperCase() + bar.dataset.status.slice(1);
                        document.getElementById('detailsNotes').value = bar.dataset.notes;
                        document.getElementById('detailsFirstName').value = bar.dataset.firstName;
                        document.getElementById('detailsLastName').value = bar.dataset.lastName;
                        document.getElementById('detailsEmail').value = bar.dataset.email;
                        document.getElementById('detailsPhone').value = bar.dataset.phone;
                        // Color the modal header
                        const header = detailsModal.querySelector('.modal-header');
                        header.classList.remove('status-booked', 'status-reserved', 'status-maintenance');
                        if (bar.dataset.status === 'booked') header.classList.add('status-booked');
                        else if (bar.dataset.status === 'reserved') header.classList.add('status-reserved');
                        else if (bar.dataset.status === 'maintenance') header.classList.add('status-maintenance');
                        detailsModal.style.display = 'flex';
                    }
                });
            });

            // ============================================================================
            // ROOM NUMBER DROPDOWN LOGIC
            // ============================================================================
            if(roomTypeSelect && roomNumberSelect) {
                roomTypeSelect.addEventListener('change', function() {
                    const selectedType = this.value;
                    fetch('booking.php?roomType=' + encodeURIComponent(selectedType))
                        .then(response => response.json())
                        .then(data => {
                            roomNumberSelect.innerHTML = '<option value="">Select Room Number</option>';
                            data.forEach(room => {
                                roomNumberSelect.innerHTML += `<option value="${room.RoomNumber}">${room.RoomNumber}</option>`;
                            });
                        });
                });
            }

            // ============================================================================
            // SHOW/HIDE EARLY CHECKOUT BUTTON
            // ============================================================================
            document.querySelectorAll('.booking-bar').forEach(bar => {
                bar.addEventListener('click', () => {
                    if (earlyCheckoutBtn) {
                        const status = bar.dataset.bookingStatus;
                        const checkOut = bar.dataset.checkOut;
                        const today = new Date().toISOString().slice(0, 10);
                        if ((status === 'Confirmed' || status === 'Pending') && checkOut >= today) {
                            earlyCheckoutBtn.style.display = 'inline-block';
                        } else {
                            earlyCheckoutBtn.style.display = 'none';
                        }
                    }
                });
            });

            if (earlyCheckoutBtn) {
                earlyCheckoutBtn.onclick = function() {
                    if (confirm('Are you sure you want to check out this guest now?')) {
                        fetch('booking.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'action=early_checkout&bookingId=' + encodeURIComponent(detailsBookingId.value) + '&roomNumber=' + encodeURIComponent(detailsRoomNumber.value)
                        })
                        .then(response => response.json())
                        .then(result => {
                            alert(result.message);
                            if (result.success) location.reload();
                        });
                    }
                };
            }

            // ============================================================================
            // ADD A JS ARRAY OF ALL ROOMS FOR ROBUST DROPDOWN POPULATION
            // ============================================================================
            const allRooms = <?php
            $roomArr = [];
            $roomRes = $conn->query("SELECT RoomNumber FROM room ORDER BY RoomNumber");
            while ($room = $roomRes->fetch_assoc()) {
                $roomArr[] = $room['RoomNumber'];
            }
            echo json_encode($roomArr);
            ?>;

            // ============================================================================
            // ADD A JS HELPER FUNCTION TO REPOPULATE THE ROOM DROPDOWN
            // ============================================================================
            function populateRoomDropdown(dropdown, selectedRoom) {
                dropdown.innerHTML = '<option value="">Select Room</option>';
                allRooms.forEach(room => {
                    dropdown.innerHTML += `<option value="${room}">${room}</option>`;
                });
                if (selectedRoom) dropdown.value = selectedRoom;
            }

            // ============================================================================
            // DATE PICKER LOGIC
            // ============================================================================
            var goBtn = document.getElementById('goToDateBtn');
            if(goBtn) {
                goBtn.addEventListener('click', function() {
                    var month = document.getElementById('monthSelect').value;
                    var year = document.getElementById('yearInput').value;
                    window.location.href = '?month=' + month + '&year=' + year;
                });
            }
        });
    </script>
</body>
</html> 
