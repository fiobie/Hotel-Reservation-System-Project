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
    $query = "SELECT COUNT(*) as count FROM reservations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['new_bookings'] = $row['count'];
    }

    // Get available rooms
    $query = "SELECT COUNT(*) as count FROM rooms WHERE status = 'available'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['available_rooms'] = $row['count'];
    }

    // Get today's check-ins
    $query = "SELECT COUNT(*) as count FROM reservations WHERE check_in_date = CURDATE() AND status = 'confirmed'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['check_ins'] = $row['count'];
    }

    // Get today's check-outs
    $query = "SELECT COUNT(*) as count FROM reservations WHERE check_out_date = CURDATE()";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['check_outs'] = $row['count'];
    }

    // Get total reservations
    $query = "SELECT COUNT(*) as count FROM reservations WHERE status != 'cancelled'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total_reservations'] = $row['count'];
    }

    // Calculate average stay
    $query = "SELECT AVG(DATEDIFF(check_out_date, check_in_date)) as avg_stay 
              FROM reservations 
              WHERE status != 'cancelled' AND check_out_date > check_in_date";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['average_stay'] = round($row['avg_stay'], 1);
    }

    // Calculate occupancy rate
    $query = "SELECT 
                (SELECT COUNT(*) FROM rooms WHERE status = 'occupied') as occupied,
                (SELECT COUNT(*) FROM rooms) as total";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['occupancy_rate'] = $row['total'] > 0 
            ? round(($row['occupied'] / $row['total']) * 100) 
            : 0;
    }

    // Get housekeeping stats
    $query = "SELECT 
                SUM(CASE WHEN cleaning_status = 'pending' THEN 1 ELSE 0 END) as to_clean,
                SUM(CASE WHEN cleaning_status = 'cleaned' THEN 1 ELSE 0 END) as cleaned,
                SUM(CASE WHEN maintenance_status = 'required' THEN 1 ELSE 0 END) as maintenance
              FROM rooms";
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
                DAY(check_in_date) as day,
                COUNT(*) as booking_count
              FROM reservations
              WHERE YEAR(check_in_date) = ? 
              AND MONTH(check_in_date) = ?
              AND status != 'cancelled'
              GROUP BY DAY(check_in_date)";
              
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
                r.id,
                r.guest_name,
                r.check_in_date,
                r.check_out_date,
                r.room_id,
                r.status,
                rm.room_number
              FROM reservations r
              JOIN rooms rm ON r.room_id = rm.id
              ORDER BY r.created_at DESC
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
        $calendarHtml .= $day;
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
    <style>
        /* Custom styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f5f6fa;
            padding: 2rem;
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

        h2 {
            color: #1a237e;
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f0f2f5;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 0.25rem;
        }

        .right-section {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .stats-section, .housekeeping {
            background: white;
            padding: 0.75rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .stats-content, .housekeeping-content {
            display: grid;
            gap: 0.25rem;
        }

        .stat-item, .housekeeping-item {
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 6px;
            transition: all 0.2s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stat-item:hover, .housekeeping-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            background: white;
        }

        .calendar-day {
            aspect-ratio: 1;
            padding: 0.4rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            cursor: pointer;
            position: relative;
            font-size: 0.85rem;
            color: #333;
            transition: all 0.2s ease;
            min-width: 28px;
            min-height: 28px;
        }

        .calendar-day:not(.empty):hover {
            background-color: #f0f2f5;
        }

        .calendar-day.empty {
            cursor: default;
        }

        .calendar-day.has-bookings {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .calendar-day.current-day {
            background-color: #147219;
            color: white;
        }

        .booking-count {
            font-size: 0.7rem;
            margin-top: 0.2rem;
            opacity: 0.8;
        }

        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-weight: 500;
            color: #666;
            margin-bottom: 0.75rem;
            padding: 0.4rem;
            border-bottom: 1px solid #eee;
            font-size: 0.8rem;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.4rem;
            padding: 0.5rem;
            max-width: 100%;
        }

        .calendar-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .nav-btn {
            border: none;
            background: #f0f2f5;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #666;
            transition: all 0.2s ease;
        }

        .nav-btn:hover {
            background: #e4e6e9;
            color: #333;
        }

        .booking-list {
            margin-top: 1rem;
            background: white;
            padding: 1.25rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            font-weight: 500;
            color: #666;
            background-color: #f8f9fa;
        }

        td {
            color: #333;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .housekeeping-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .housekeeping-item .icon {
            color: #1a237e;
            opacity: 0.2;
            font-size: 1.5rem;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .calendar-day {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h1>Villa Valore Hotel</h1>
        
        <div class="content-grid">
            <!-- Calendar Section -->
            <div class="booking-schedule">
                <h2>Recent Booking Schedule</h2>
                <div class="calendar-nav">
                    <button class="nav-btn prev-month">&lt;</button>
                    <span class="current-month"><?php echo date('F Y'); ?></span>
                    <button class="nav-btn next-month">&gt;</button>
                </div>
                <div class="calendar-header">
                    <div>Mon</div>
                    <div>Tue</div>
                    <div>Wed</div>
                    <div>Thu</div>
                    <div>Fri</div>
                    <div>Sat</div>
                    <div>Sun</div>
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
                        echo $day;
                        if (isset($bookingSchedule[$day])) {
                            echo '<span class="booking-count">' . $bookingSchedule[$day] . '</span>';
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Right Section -->
            <div class="right-section">
                <!-- Reservation Stats -->
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

                <!-- Housekeeping -->
                <div class="housekeeping">
                    <h2>Housekeeping</h2>
                    <div class="housekeeping-content">
                        <div class="housekeeping-item">
                            <div>
                                <h4>Rooms to Clean</h4>
                                <p><?php echo $stats['rooms_to_clean']; ?></p>
                            </div>
                            <span class="icon">🧹</span>
                        </div>
                        <div class="housekeeping-item">
                            <div>
                                <h4>Rooms Cleaned</h4>
                                <p><?php echo $stats['rooms_cleaned']; ?></p>
                            </div>
                            <span class="icon">✓</span>
                        </div>
                        <div class="housekeeping-item">
                            <div>
                                <h4>Maintenance Required</h4>
                                <p><?php echo $stats['maintenance_required']; ?></p>
                            </div>
                            <span class="icon">🔧</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings Table -->
        <div class="booking-list">
            <h2>Recent Bookings</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Guest Name</th>
                            <th>Room</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $booking): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($booking['status'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
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
