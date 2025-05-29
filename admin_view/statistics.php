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

// --- STATISTICS QUERIES ---
// Cancellation Rate
$totalBookingsQuery = "SELECT COUNT(*) as total FROM booking";
$cancelledBookingsQuery = "SELECT COUNT(*) as cancelled FROM booking WHERE BookingStatus = 'Cancelled'";
$totalBookings = $conn->query($totalBookingsQuery)->fetch_assoc()['total'] ?? 0;
$cancelledBookings = $conn->query($cancelledBookingsQuery)->fetch_assoc()['cancelled'] ?? 0;
$cancellationRate = $totalBookings > 0 ? round(($cancelledBookings / $totalBookings) * 100) : 0;

// Occupancy Rate
$occupiedRoomsQuery = "SELECT COUNT(*) as occupied FROM room WHERE RoomStatus = 'Occupied'";
$totalRoomsQuery = "SELECT COUNT(*) as total FROM room";
$occupiedRooms = $conn->query($occupiedRoomsQuery)->fetch_assoc()['occupied'] ?? 0;
$totalRooms = $conn->query($totalRoomsQuery)->fetch_assoc()['total'] ?? 0;
$occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

// Customer Rating (Placeholder, since no table exists)
$customerRating = 'N/A'; // Set to static value or N/A

// --- SALES & REVENUE PER MONTH ---
$sales = [];
$revenue = [];
$months = [];
for ($m = 1; $m <= 12; $m++) {
    $monthName = date('F', mktime(0, 0, 0, $m, 10));
    $months[] = $monthName;
    // Total Sales: Completed bookings per month
    $salesQuery = "SELECT COUNT(*) as count FROM booking WHERE BookingStatus = 'Completed' AND MONTH(CheckInDate) = $m";
    $sales[] = (int)($conn->query($salesQuery)->fetch_assoc()['count'] ?? 0);
    // Total Revenue: Sum of TotalBill from payment table for Paid payments per month
    $revenueQuery = "SELECT SUM(TotalBill) as total FROM payment WHERE PaymentStatus = 'Paid' AND MONTH(PaymentDate) = $m";
    $revenue[] = (float)($conn->query($revenueQuery)->fetch_assoc()['total'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - Villa Valore Hotel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f5f6fa; display: flex; }
        .sidebar { width: 200px; background: #008000; min-height: 100vh; padding: 0.5rem; color: white; position: fixed; left: 0; top: 0; bottom: 0; }
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
        .main-content { flex: 1; padding: 2rem; margin-left: 200px; overflow-x: hidden; }
        h1 { color: #333; margin-bottom: 2rem; font-size: 2rem; }
        .stats-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); display: flex; flex-direction: column; align-items: center; }
        .stat-title { font-size: 1.1rem; color: #333; margin-bottom: 0.5rem; font-weight: 600; }
        .stat-value { font-size: 2.2rem; font-weight: bold; color: #147219; margin-bottom: 0.2rem; }
        .stat-label { color: #666; font-size: 1rem; }
        .chart-container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-top: 2rem; }
        .print-btn {
            background: #147219;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .print-btn i { margin-right: 0.5rem; }
        .print-btn:hover { background: #0e5a15; }
        @media print {
            body { background: white !important; }
            .sidebar, .print-btn { display: none !important; }
            .main-content { margin: 0 !important; padding: 0.5in !important; box-shadow: none !important; }
            .chart-container { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation (copied from dashboard.php) -->
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
        <button onclick="window.print()" class="print-btn"><i class="fas fa-print"></i> Print</button>
        <h1>Statistics</h1>
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-title">Cancellation Rate</div>
                <div class="stat-value"><?php echo $cancellationRate; ?>%</div>
                <div class="stat-label">of all bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Occupancy Rate</div>
                <div class="stat-value"><?php echo $occupancyRate; ?>%</div>
                <div class="stat-label">of all rooms</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Customer Rating</div>
                <div class="stat-value"><?php echo $customerRating; ?></div>
                <div class="stat-label">(No data yet)</div>
            </div>
        </div>
        <div class="chart-container">
            <h2 style="margin-bottom: 1rem; color: #333;">Total Sales & Revenue (Monthly)</h2>
            <canvas id="salesRevenueChart" height="100"></canvas>
        </div>
    </div>
    <script>
        // Sidebar toggle
        function toggleMenu(menuId) {
            const submenu = document.getElementById(menuId);
            submenu.classList.toggle('active');
        }
        // Chart.js Bar Graph
        const ctx = document.getElementById('salesRevenueChart').getContext('2d');
        const salesData = <?php echo json_encode($sales); ?>;
        const revenueData = <?php echo json_encode($revenue); ?>;
        const months = <?php echo json_encode($months); ?>;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Total Sales',
                        data: salesData,
                        backgroundColor: 'rgba(20, 114, 25, 0.7)',
                        borderColor: 'rgba(20, 114, 25, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Total Revenue',
                        data: revenueData,
                        backgroundColor: 'rgba(0, 128, 0, 0.4)',
                        borderColor: 'rgba(0, 128, 0, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#333' }
                    },
                    x: {
                        ticks: { color: '#333' }
                    }
                },
                plugins: {
                    legend: { labels: { color: '#333' } }
                }
            }
        });
    </script>
</body>
</html> 
