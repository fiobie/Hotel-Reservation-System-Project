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

// Database Query Functions
function getStats() {
    global $conn;
    
    $stats = [
        'new_bookings' => 0,
        'available_rooms' => 0,
        'check_ins' => 0,
        'check_outs' => 0,
        'total_reservations' => 0,
        'average_stay' => 0,
        'occupancy_rate' => 0,
        'rooms_to_clean' => 0,
        'rooms_cleaned' => 0,
        'maintenance_required' => 0
    ];

    // Get new bookings (bookings made in the last 24 hours)
    $query = "SELECT COUNT(*) as count FROM booking WHERE BookingDate >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['new_bookings'] = $row['count'];
    }

    // Get available rooms
    $query = "SELECT COUNT(*) as count FROM room WHERE RoomStatus = 'Available'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['available_rooms'] = $row['count'];
    }

    // Get today's check-ins
    $query = "SELECT COUNT(*) as count FROM booking WHERE DATE(CheckInDate) = CURDATE() AND BookingStatus = 'Confirmed'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['check_ins'] = $row['count'];
    }

    // Get today's check-outs
    $query = "SELECT COUNT(*) as count FROM booking WHERE DATE(CheckOutDate) = CURDATE()";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['check_outs'] = $row['count'];
    }

    // Get total reservations
    $query = "SELECT COUNT(*) as count FROM booking WHERE BookingStatus != 'Cancelled'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total_reservations'] = $row['count'];
    }

    // Calculate average stay
    $query = "SELECT AVG(TIMESTAMPDIFF(DAY, CheckInDate, CheckOutDate)) as avg_stay 
              FROM booking 
              WHERE BookingStatus != 'Cancelled' AND CheckOutDate > CheckInDate";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['average_stay'] = round($row['avg_stay'], 1);
    }

    // Calculate occupancy rate
    $query = "SELECT 
                (SELECT COUNT(*) FROM room WHERE RoomStatus = 'Occupied') as occupied,
                (SELECT COUNT(*) FROM room) as total";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['occupancy_rate'] = $row['total'] > 0 
            ? round(($row['occupied'] / $row['total']) * 100) 
            : 0;
    }

    // Get housekeeping stats
    $query = "SELECT 
                SUM(CASE WHEN RoomStatus = 'Cleaning' THEN 1 ELSE 0 END) as to_clean,
                SUM(CASE WHEN RoomStatus = 'Available' THEN 1 ELSE 0 END) as cleaned,
                SUM(CASE WHEN RoomStatus = 'Maintenance' THEN 1 ELSE 0 END) as maintenance
              FROM room";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['rooms_to_clean'] = $row['to_clean'];
        $stats['rooms_cleaned'] = $row['cleaned'];
        $stats['maintenance_required'] = $row['maintenance'];
    }

    return $stats;
}

function getBookingSchedule($year, $month) {
    global $conn;
    
    $bookings = [];
    
    $query = "SELECT 
                DAY(CheckInDate) as day,
                COUNT(*) as booking_count
              FROM booking
              WHERE YEAR(CheckInDate) = ? 
              AND MONTH(CheckInDate) = ?
              AND BookingStatus != 'Cancelled'
              GROUP BY DAY(CheckInDate)";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $bookings[$row['day']] = $row['booking_count'];
    }
    
    return $bookings;
}

function getRecentBookings($limit = 5) {
    global $conn;
    
    $bookings = [];
    
    $query = "SELECT 
                b.BookingID as id,
                CONCAT(s.FirstName, ' ', s.LastName) as guest_name,
                b.CheckInDate as check_in_date,
                b.CheckOutDate as check_out_date,
                b.RoomNumber as room_id,
                b.BookingStatus as status,
                r.RoomNumber as room_number
              FROM booking b
              LEFT JOIN account s ON b.StudentID = s.ID
              JOIN room r ON b.RoomNumber = r.RoomNumber
              ORDER BY b.BookingDate DESC
              LIMIT ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    return $bookings;
}

// Handle AJAX Calendar Updates
if (isset($_GET['ajax_calendar'])) {
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
    
    $bookingSchedule = getBookingSchedule($year, $month);
    $firstDay = mktime(0, 0, 0, $month, 1, $year);
    $daysInMonth = date('t', $firstDay);
    $startDay = date('N', $firstDay);
    $currentDate = date('j');
    $currentMonth = date('n');
    $currentYear = date('Y');
    
    $calendarHtml = '';
    
    for ($i = 1; $i < $startDay; $i++) {
        $calendarHtml .= '<div class="calendar-day empty"></div>';
    }
    
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $classes = ['calendar-day'];
        if ($day == $currentDate && $month == $currentMonth && $year == $currentYear) {
            $classes[] = 'current-day';
        }
        if (isset($bookingSchedule[$day]) && $bookingSchedule[$day] > 0) {
            $classes[] = 'has-bookings';
        }
        
        $calendarHtml .= '<div class="' . implode(' ', $classes) . '">';
        $calendarHtml .= (int)$day;
        if (isset($bookingSchedule[$day])) {
            $calendarHtml .= '<span class="booking-count">' . $bookingSchedule[$day] . '</span>';
        }
        $calendarHtml .= '</div>';
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'calendarHtml' => $calendarHtml,
        'monthDisplay' => date('F Y', $firstDay)
    ]);
    exit;
}

// Get initial data
$stats = getStats();
$currentYear = date('Y');
$currentMonth = date('n');
$bookingSchedule = getBookingSchedule($currentYear, $currentMonth);
$recentBookings = getRecentBookings(5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f5f6fa;
            display: flex;
        }

        /* Sidebar Styles */
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

        .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
            opacity: 0.9;
        }

        .management-label {
            color: #90EE90;
            font-size: 0.8em;
            margin: 1rem 0 0.5rem 1rem;
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

        .submenu {
            margin-left: 1.5rem;
            display: none;
        }

        .submenu.active {
            display: block;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 200px; /* Match new sidebar width */
            overflow-x: hidden;
        }

        .dashboard {
            max-width: 1400px;
            margin: 0 auto;
        }

        h1 {
            color: #333;
            margin-bottom: 2rem;
            font-size: 2rem;
        }

        /* Stats Cards Container */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            background: #f0f2f5;
            padding: 0.8rem;
            border-radius: 8px;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-info h3 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.3rem;
        }

        .stat-info p {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* Calendar Styles */
        .booking-schedule {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            background: white;
        }

        .calendar-day.current-day {
            background-color: #147219;
            color: white;
        }

        .calendar-day.has-bookings {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .calendar-day.empty {
            background: transparent;
            cursor: default;
        }

        /* Recent Bookings Table */
        .booking-list {
            margin-top: 0;
            padding-top: 1.5rem;
            border-top: 1px solid #dee2e6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
            background: #f8f9fa;
        }

        th {
            color: #666;
            font-weight: 500;
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* Right Section */
        .right-section > div {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .stats-content, .housekeeping-content {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .stat-item, .housekeeping-item {
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .stat-item h4, .housekeeping-item h4 {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .stat-item p, .housekeeping-item p {
            color: #333;
            font-size: 1.25rem;
            font-weight: 600;
        }

        h2 {
            color: #333;
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
        }

        .calendar-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .month-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-btn {
            border: none;
            background: #f0f2f5;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <h4 class="sidebar-title">Villa Valore Hotel</h4>
        
        <div class="nav-section">
            <a class="nav-link" href="home.php"><i class="fas fa-th-large"></i>Dashboard</a>
            <a class="nav-link" href="student.php"><i class="fas fa-user"></i>Guest</a>
            <a class="nav-link" href="booking.php"><i class="fas fa-book"></i>Booking</a>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard">
            <h1>Villa Valore Hotel</h1>
            
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <h3>New Booking</h3>
                        <p><?php echo $stats['new_bookings']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🛏️</div>
                    <div class="stat-info">
                        <h3>Available Room</h3>
                        <p><?php echo $stats['available_rooms']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📥</div>
                    <div class="stat-info">
                        <h3>Check In</h3>
                        <p><?php echo $stats['check_ins']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📤</div>
                    <div class="stat-info">
                        <h3>Check Out</h3>
                        <p><?php echo $stats['check_outs']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="content-grid">
                <!-- Calendar Section -->
                <div class="left-section">
                    <div class="booking-schedule">
                        <div class="calendar-nav">
                            <h2>Recent Booking Schedule</h2>
                            <div class="month-nav">
                                <button class="nav-btn prev-month">&lt;</button>
                                <span class="current-month"><?php echo date('F Y'); ?></span>
                                <button class="nav-btn next-month">&gt;</button>
                            </div>
                        </div>
                        <div class="calendar-header">
                            <div>MON</div>
                            <div>TUE</div>
                            <div>WED</div>
                            <div>THU</div>
                            <div>FRI</div>
                            <div>SAT</div>
                            <div>SUN</div>
                        </div>
                        <div class="calendar-grid">
                            <?php
                            $firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
                            $daysInMonth = date('t', $firstDay);
                            $startDay = date('N', $firstDay);
                            $currentDate = date('j');
                            
                            for ($i = 1; $i < $startDay; $i++) {
                                echo '<div class="calendar-day empty"></div>';
                            }
                            
                            for ($day = 1; $day <= $daysInMonth; $day++) {
                                $classes = ['calendar-day'];
                                if ($day == $currentDate && $currentMonth == date('n')) {
                                    $classes[] = 'current-day';
                                }
                                if (isset($bookingSchedule[$day]) && $bookingSchedule[$day] > 0) {
                                    $classes[] = 'has-bookings';
                                }
                                
                                echo '<div class="' . implode(' ', $classes) . '">';
                                echo (int)$day;
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Recent Bookings Table -->
                    <div class="booking-list">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Room</th>
                                    <th>No.</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings as $booking): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['room_type'] ?? 'Standard'); ?></td>
                                    <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                                    <td><?php echo date('m/d/y', strtotime($booking['check_in_date'])); ?></td>
                                    <td><?php echo date('m/d/y', strtotime($booking['check_out_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Section -->
                <div class="right-section">
                    <div class="stats-section">
                        <h2>Reservation Stats</h2>
                        <div class="stats-content">
                            <div class="stat-item">
                                <h4>Total Reservations</h4>
                                <p><?php echo $stats['total_reservations']; ?></p>
                            </div>
                            <div class="stat-item">
                                <h4>Average Stay</h4>
                                <p><?php echo $stats['average_stay']; ?> days</p>
                            </div>
                            <div class="stat-item">
                                <h4>Occupancy Rate</h4>
                                <p><?php echo $stats['occupancy_rate']; ?>%</p>
                            </div>
                        </div>
                    </div>

                    <div class="housekeeping">
                        <h2>Housekeeping</h2>
                        <div class="housekeeping-content">
                            <div class="housekeeping-item">
                                <h4>Rooms to Clean</h4>
                                <p><?php echo $stats['rooms_to_clean']; ?></p>
                            </div>
                            <div class="housekeeping-item">
                                <h4>Rooms Cleaned</h4>
                                <p><?php echo $stats['rooms_cleaned']; ?></p>
                            </div>
                            <div class="housekeeping-item">
                                <h4>Maintenance Required</h4>
                                <p><?php echo $stats['maintenance_required']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add toggle menu functionality
        function toggleMenu(menuId) {
            const submenu = document.getElementById(menuId);
            submenu.classList.toggle('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const prevMonthBtn = document.querySelector('.prev-month');
            const nextMonthBtn = document.querySelector('.next-month');
            const currentMonthSpan = document.querySelector('.current-month');
            
            let currentDate = new Date();
            
            function updateCalendar(year, month) {
                fetch(`?ajax_calendar=1&year=${year}&month=${month}`)
                    .then(response => response.json())
                    .then(data => {
                        const calendarGrid = document.querySelector('.calendar-grid');
                        calendarGrid.innerHTML = data.calendarHtml;
                        currentMonthSpan.textContent = data.monthDisplay;
                    });
            }
            
            prevMonthBtn.addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() - 1);
                updateCalendar(currentDate.getFullYear(), currentDate.getMonth() + 1);
            });
            
            nextMonthBtn.addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() + 1);
                updateCalendar(currentDate.getFullYear(), currentDate.getMonth() + 1);
            });
        });
    </script>
</body>
</html>
