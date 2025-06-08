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

// Get Inventory Stats by Category
function getInventoryStats() {
    global $conn;
    $categories = [
        'Toiletries' => [
            'keywords' => ['toothbrush', 'toothpaste', 'soap', 'shampoo', 'towel', 'toiletries'],
            'stock' => 0
        ],
        'Amenities' => [
            'keywords' => ['pillow', 'blanket', 'slippers', 'robe', 'amenities'],
            'stock' => 0
        ],
        'Food' => [
            'keywords' => ['food', 'snack', 'water', 'juice', 'meal', 'bread', 'fruit'],
            'stock' => 0
        ]
    ];
    $query = "SELECT ItemName, SUM(CurrentStocks) as stock FROM inventory GROUP BY ItemName";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $item = strtolower($row['ItemName']);
            foreach ($categories as $cat => &$catData) {
                foreach ($catData['keywords'] as $kw) {
                    if (strpos($item, $kw) !== false) {
                        $catData['stock'] += (int)$row['stock'];
                        break;
                    }
                }
            }
        }
    }
    return [
        'Toiletries' => $categories['Toiletries']['stock'],
        'Amenities' => $categories['Amenities']['stock'],
        'Food' => $categories['Food']['stock']
    ];
}

$inventoryStats = getInventoryStats();

// --- Dashboard Stats Queries ---
// New Booking: count of bookings with BookingStatus = 'Pending'
$newBooking = $conn->query("SELECT COUNT(*) as count FROM booking WHERE BookingStatus = 'Pending'")->fetch_assoc()['count'] ?? 0;
// Available Room: count of rooms with RoomStatus = 'Available'
$availableRoom = $conn->query("SELECT COUNT(*) as count FROM room WHERE RoomStatus = 'Available'")->fetch_assoc()['count'] ?? 0;
// Check In: count of bookings with today's CheckInDate
$checkIn = $conn->query("SELECT COUNT(*) as count FROM booking WHERE DATE(CheckInDate) = CURDATE() AND BookingStatus = 'Confirmed'")->fetch_assoc()['count'] ?? 0;
// Check Out: count of bookings with today's CheckOutDate
$checkOut = $conn->query("SELECT COUNT(*) as count FROM booking WHERE DATE(CheckOutDate) = CURDATE() AND BookingStatus = 'Confirmed'")->fetch_assoc()['count'] ?? 0;
// Reservation: count of all reservations (not cancelled)
$reservation = $conn->query("SELECT COUNT(*) as count FROM booking WHERE BookingStatus != 'Cancelled'")->fetch_assoc()['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
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
        .dashboard { max-width: 1400px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 2rem; font-size: 2rem; }
        .inventory-section { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 2rem; margin-top: 2rem; }
        .inventory-section h2 { font-size: 1.3rem; color: #222; margin-bottom: 1.5rem; font-weight: 700; }
        .inventory-table { width: 100%; border-collapse: collapse; }
        .inventory-table th, .inventory-table td { padding: 1rem; text-align: left; border-bottom: 1px solid #f0f2f5; font-size: 1.1rem; }
        .inventory-table th { color: #666; font-weight: 600; background: #f8f9fa; }
        .inventory-table td { color: #222; font-weight: 600; }
        .inventory-icon { font-size: 2rem; margin-right: 1rem; vertical-align: middle; }
        .category-row { display: flex; align-items: center; }
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
            .dashboard { padding: 0.5rem; }
            .inventory-section { padding: 1rem; }
            .inventory-table th, .inventory-table td { padding: 0.5rem; font-size: 0.9rem; }
            h1 { font-size: 1.2rem; }
        }
        /* Responsive table: stack rows on mobile */
        @media (max-width: 500px) {
            .inventory-table, .inventory-table thead, .inventory-table tbody, .inventory-table th, .inventory-table td, .inventory-table tr {
                display: block;
                width: 100%;
            }
            .inventory-table thead { display: none; }
            .inventory-table tr { margin-bottom: 1rem; border-bottom: 2px solid #f0f2f5; }
            .inventory-table td {
                padding-left: 40%;
                position: relative;
                font-size: 1rem;
                border: none;
                border-bottom: 1px solid #f0f2f5;
            }
            .inventory-table td:before {
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);
                font-weight: bold;
                color: #666;
                content: attr(data-label);
                font-size: 0.95rem;
            }
            .category-row { justify-content: flex-start; }
        }
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #fafbfa;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            padding: 2rem 2.5rem;
            min-width: 180px;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1 1 180px;
            max-width: 220px;
        }
        .stat-icon {
            font-size: 2.2rem;
            color: #b0b0b0;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            font-size: 1.1rem;
            color: #444;
            margin-bottom: 0.2rem;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #222;
        }
        .inventory-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            padding: 2rem 2.5rem;
            margin-top: 1.5rem;
            max-width: 420px;
        }
        .inventory-card h2 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1.2rem;
        }
        .inventory-table-modern {
            width: 100%;
        }
        .inventory-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        .inventory-row {
            display: flex;
            align-items: center;
            margin-bottom: 1.1rem;
            gap: 1.2rem;
        }
        .inventory-row span {
            font-size: 1.1rem;
        }
        .inventory-icon {
            font-size: 2rem;
            margin-right: 0.7rem;
            color: #b0b0b0;
        }
        .inventory-value {
            font-weight: bold;
            font-size: 1.3rem;
            margin-left: auto;
        }
        @media (max-width: 900px) {
            .stats-container { flex-direction: column; gap: 1rem; }
            .stat-card { max-width: 100%; min-width: 0; }
            .inventory-card { max-width: 100%; }
        }
    </style>
</head>
<body>
    <button class="hamburger" id="sidebarToggle" aria-label="Open sidebar">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <!-- Sidebar Navigation (copied from dashboard.php) -->
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
    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard">
            <h1 style="margin-bottom: 2rem;">Staff Dashboard</h1>
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon"><i class="far fa-calendar-alt"></i></div>
                    <div class="stat-label">New Booking</div>
                    <div class="stat-value"><?php echo $newBooking; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-bed"></i></div>
                    <div class="stat-label">Available Room</div>
                    <div class="stat-value"><?php echo $availableRoom; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-door-open"></i></div>
                    <div class="stat-label">Check In</div>
                    <div class="stat-value"><?php echo $checkIn; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-door-closed"></i></div>
                    <div class="stat-label">Check Out</div>
                    <div class="stat-value"><?php echo $checkOut; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="far fa-calendar-check"></i></div>
                    <div class="stat-label">Reservation</div>
                    <div class="stat-value"><?php echo $reservation; ?></div>
                </div>
            </div>
            <div class="inventory-card">
                <h2>Inventory</h2>
                <div class="inventory-table-modern">
                    <div class="inventory-header">
                        <span>Category</span>
                        <span>Available Stock</span>
                    </div>
                    <div class="inventory-row">
                        <span class="inventory-icon"><i class="fas fa-suitcase"></i></span>
                        <span>Toiletries</span>
                        <span class="inventory-value"><?php echo $inventoryStats['Toiletries']; ?></span>
                    </div>
                    <div class="inventory-row">
                        <span class="inventory-icon"><i class="fas fa-bed"></i></span>
                        <span>Amenities</span>
                        <span class="inventory-value"><?php echo $inventoryStats['Amenities']; ?></span>
                    </div>
                    <div class="inventory-row">
                        <span class="inventory-icon"><i class="fas fa-utensils"></i></span>
                        <span>Food</span>
                        <span class="inventory-value"><?php echo $inventoryStats['Food']; ?></span>
                    </div>
                </div>
            </div>
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
    </script>
</body>
</html> 
