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

// AJAX handler for live data updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_live_data'])) {
    header('Content-Type: application/json');
    
    // Get Inventory Stats by Category
    function getInventoryStats($conn) {
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

    // Get all live data
    $inventoryStats = getInventoryStats($conn);
    $newBooking = $conn->query("SELECT COUNT(*) as count FROM booking WHERE BookingStatus = 'Pending'")->fetch_assoc()['count'] ?? 0;
    $availableRoom = $conn->query("SELECT COUNT(*) as count FROM room WHERE RoomStatus = 'Available'")->fetch_assoc()['count'] ?? 0;
    $checkIn = $conn->query("SELECT COUNT(*) as count FROM booking WHERE DATE(CheckInDate) = CURDATE() AND BookingStatus = 'Confirmed'")->fetch_assoc()['count'] ?? 0;
    $checkOut = $conn->query("SELECT COUNT(*) as count FROM booking WHERE DATE(CheckOutDate) = CURDATE() AND BookingStatus = 'Confirmed'")->fetch_assoc()['count'] ?? 0;
    $reservation = $conn->query("SELECT COUNT(*) as count FROM booking WHERE BookingStatus != 'Cancelled'")->fetch_assoc()['count'] ?? 0;

    // Get recent activity
    $recentBookings = [];
    $recentQuery = "SELECT b.BookingID, b.RoomNumber, b.RoomType, b.BookingStatus, b.CheckInDate, 
                           CONCAT(s.FirstName, ' ', s.LastName) as GuestName
                    FROM booking b 
                    LEFT JOIN student s ON b.StudentID = s.StudentID 
                    WHERE b.BookingDate >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ORDER BY b.BookingDate DESC 
                    LIMIT 5";
    $recentResult = $conn->query($recentQuery);
    if ($recentResult) {
        while ($row = $recentResult->fetch_assoc()) {
            $recentBookings[] = $row;
        }
    }

    // Get low stock alerts
    $lowStockAlerts = [];
    $lowStockQuery = "SELECT ItemName, CurrentStocks, MinimumStocks 
                      FROM inventory 
                      WHERE CurrentStocks <= MinimumStocks 
                      ORDER BY CurrentStocks ASC 
                      LIMIT 5";
    $lowStockResult = $conn->query($lowStockQuery);
    if ($lowStockResult) {
        while ($row = $lowStockResult->fetch_assoc()) {
            $lowStockAlerts[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'stats' => [
                'newBooking' => $newBooking,
                'availableRoom' => $availableRoom,
                'checkIn' => $checkIn,
                'checkOut' => $checkOut,
                'reservation' => $reservation
            ],
            'inventory' => $inventoryStats,
            'recentBookings' => $recentBookings,
            'lowStockAlerts' => $lowStockAlerts,
            'lastUpdate' => date('Y-m-d H:i:s')
        ]
    ]);
    exit;
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
        .sidebar-logo {
            display: block;
            margin: 1.5rem auto;
            width: 80px;
            height: auto;
        }
        .nav-section { margin-bottom: 1rem; }
        .nav-link { display: flex; align-items: center; padding: 0.5rem 1rem; color: white; text-decoration: none; font-size: 0.9rem; margin-bottom: 0.25rem; transition: background-color 0.2s; }
        .nav-link:hover, .nav-link.active { background-color: rgba(255, 255, 255, 0.1); }
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
        .stat-link {
            text-decoration: none;
            color: inherit;
            display: block;
            transition: transform 0.1s;
        }
        .stat-link:hover .stat-card {
            box-shadow: 0 4px 16px rgba(0,128,0,0.10);
            transform: translateY(-2px) scale(1.03);
            cursor: pointer;
        }
        .inventory-link {
            text-decoration: none;
            color: inherit;
            display: block;
            transition: transform 0.1s;
        }
        .inventory-link:hover .inventory-card {
            box-shadow: 0 4px 16px rgba(0,128,0,0.10);
            transform: translateY(-2px) scale(1.03);
            cursor: pointer;
        }

        /* ============================================================================
           LIVE DATA STYLES
           ============================================================================ */
        .live-data-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .live-section {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .live-section:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid #f0f2f5;
        }

        .section-header h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-header h3 i {
            color: #008000;
        }

        .live-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #f8f9fa;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .live-text {
            color: #28a745;
            font-weight: 700;
        }

        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
            100% { opacity: 1; transform: scale(1); }
        }

        .loading-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            padding: 2rem;
            color: #666;
            font-size: 1rem;
        }

        .loading-placeholder i {
            color: #008000;
        }

        /* Recent Bookings Styles */
        .recent-bookings {
            max-height: 300px;
            overflow-y: auto;
        }

        .booking-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            margin-bottom: 0.8rem;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #008000;
            transition: all 0.3s ease;
        }

        .booking-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .booking-info {
            flex: 1;
        }

        .booking-guest {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.3rem;
        }

        .booking-details {
            font-size: 0.9rem;
            color: #666;
        }

        .booking-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        /* Low Stock Alerts Styles */
        .low-stock-alerts {
            max-height: 300px;
            overflow-y: auto;
        }

        .alert-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            margin-bottom: 0.8rem;
            background: #fff5f5;
            border-radius: 12px;
            border-left: 4px solid #dc3545;
            transition: all 0.3s ease;
        }

        .alert-item:hover {
            background: #ffe6e6;
            transform: translateX(5px);
        }

        .alert-info {
            flex: 1;
        }

        .alert-item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.3rem;
        }

        .alert-stock-info {
            font-size: 0.9rem;
            color: #666;
        }

        .alert-stock-count {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #dc3545;
            color: white;
        }

        /* Update Indicator Styles */
        .update-indicator {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-top: 2rem;
            font-size: 0.9rem;
            color: #666;
        }

        .refresh-btn {
            background: #008000;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .refresh-btn:hover {
            background: #006400;
            transform: rotate(180deg);
        }

        .refresh-btn.loading {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Empty State Styles */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .empty-state p {
            font-size: 1rem;
            margin: 0;
        }

        /* Responsive Design for Live Data */
        @media (max-width: 900px) {
            .live-data-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .live-section {
                padding: 1rem;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .booking-item,
            .alert-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .booking-status,
            .alert-stock-count {
                align-self: flex-end;
            }
        }

        /* Animation for data updates */
        .data-update {
            animation: highlight 0.5s ease;
        }

        @keyframes highlight {
            0% { background-color: #fff3cd; }
            100% { background-color: transparent; }
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
        <img src="images/villavalorelogo.png" alt="Villa Valore Logo" class="sidebar-logo">
        <h4 class="sidebar-title">Villa Valore</h4>
        <div class="nav-section">
            <a class="nav-link active" href="staff_dashboard.php"><i class="fas fa-th-large"></i>Dashboard</a>
            <a class="nav-link" href="reservation.php"><i class="fas fa-calendar-check"></i>Reservation</a>
            <a class="nav-link" href="booking.php"><i class="fas fa-book"></i>Booking</a>
            <a class="nav-link" href="room.php"><i class="fas fa-door-open"></i>Room</a>
            <a class="nav-link" href="guest_request.php"><i class="fas fa-comment-dots"></i>Guest Request</a>
            <a class="nav-link" href="staff_inventory.php"><i class="fas fa-box"></i>Inventory</a>
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
                <a href="booking.php" class="stat-link">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="far fa-calendar-alt"></i></div>
                        <div class="stat-label">New Booking</div>
                        <div class="stat-value" id="new-booking"><?php echo $newBooking; ?></div>
                    </div>
                </a>
                <a href="room.php" class="stat-link">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-bed"></i></div>
                        <div class="stat-label">Available Room</div>
                        <div class="stat-value" id="available-room"><?php echo $availableRoom; ?></div>
                    </div>
                </a>
                <a href="booking.php" class="stat-link">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-door-open"></i></div>
                        <div class="stat-label">Check In</div>
                        <div class="stat-value" id="check-in"><?php echo $checkIn; ?></div>
                    </div>
                </a>
                <a href="booking.php" class="stat-link">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-door-closed"></i></div>
                        <div class="stat-label">Check Out</div>
                        <div class="stat-value" id="check-out"><?php echo $checkOut; ?></div>
                    </div>
                </a>
                <a href="reservation.php" class="stat-link">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="far fa-calendar-check"></i></div>
                        <div class="stat-label">Reservation</div>
                        <div class="stat-value" id="reservation"><?php echo $reservation; ?></div>
                    </div>
                </a>
            </div>
            <a href="staff_inventory.php" class="inventory-link">
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
                            <span class="inventory-value" id="toiletries-stock"><?php echo $inventoryStats['Toiletries']; ?></span>
                        </div>
                        <div class="inventory-row">
                            <span class="inventory-icon"><i class="fas fa-bed"></i></span>
                            <span>Amenities</span>
                            <span class="inventory-value" id="amenities-stock"><?php echo $inventoryStats['Amenities']; ?></span>
                        </div>
                        <div class="inventory-row">
                            <span class="inventory-icon"><i class="fas fa-utensils"></i></span>
                            <span>Food</span>
                            <span class="inventory-value" id="food-stock"><?php echo $inventoryStats['Food']; ?></span>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Live Data Sections -->
            <div class="live-data-container">
                <!-- Removed Recent Bookings and Low Stock Alerts sections -->
            </div>

            <!-- Last Update Indicator -->
            <div class="update-indicator" id="update-indicator">
                <span>Last updated: <span id="last-update-time"><?php echo date('Y-m-d H:i:s'); ?></span></span>
                <button class="refresh-btn" id="manual-refresh-btn" title="Refresh data">
                    <i class="fas fa-sync-alt"></i>
                </button>
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

        // LIVE DATA FUNCTIONALITY
        function updateStatValue(elementId, newValue) {
            const element = document.getElementById(elementId);
            if (!element) return;
            element.textContent = newValue;
        }

        function updateLastUpdateTime() {
            const now = new Date();
            const formatted = now.getFullYear() + '-' +
                String(now.getMonth() + 1).padStart(2, '0') + '-' +
                String(now.getDate()).padStart(2, '0') + ' ' +
                String(now.getHours()).padStart(2, '0') + ':' +
                String(now.getMinutes()).padStart(2, '0') + ':' +
                String(now.getSeconds()).padStart(2, '0');
            const lastUpdate = document.getElementById('last-update-time');
            if (lastUpdate) lastUpdate.textContent = formatted;
        }

        function fetchLiveData() {
            const formData = new FormData();
            formData.append('get_live_data', '1');
            fetch('staff_dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const stats = data.data.stats;
                    updateStatValue('new-booking', stats.newBooking);
                    updateStatValue('available-room', stats.availableRoom);
                    updateStatValue('check-in', stats.checkIn);
                    updateStatValue('check-out', stats.checkOut);
                    updateStatValue('reservation', stats.reservation);
                    const inventory = data.data.inventory;
                    updateStatValue('toiletries-stock', inventory.Toiletries);
                    updateStatValue('amenities-stock', inventory.Amenities);
                    updateStatValue('food-stock', inventory.Food);
                    updateLastUpdateTime();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            fetchLiveData(); // Initial fetch
            setInterval(fetchLiveData, 1000); // Fetch every 1 second
            var refreshBtn = document.getElementById('manual-refresh-btn');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function() {
                    fetchLiveData();
                });
            }
        });
    </script>
</body>
</html> 
