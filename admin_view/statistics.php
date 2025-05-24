<?php
include 'connections.php';

// Dummy data (replace with real SQL queries from your DB)
$monthlyBookings = [5, 8, 12, 9, 15, 10, 13]; // Jan–Jul
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'];

$occupied = 30;
$available = 20;

$totalRevenue = 50000;
$totalCost = 20000;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Statistics - Villa Valore</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            padding: 40px;
        }
        h1 { color: #2e7d32; text-align: center; margin-bottom: 30px; }
        .chart-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        .stat-cards {
            display: flex;
            justify-content: center;
            gap: 40px;
        }
        .card {
            background: white;
            padding: 20px 40px;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
            text-align: center;
        }
        .card h2 {
            margin-bottom: 10px;
            color: #388e3c;
        }
        canvas {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
        }
    </style>
</head>
<body>

<h1>📊 Hotel Statistics Dashboard</h1>

<div class="chart-container">
    <canvas id="barChart"></canvas>
    <canvas id="lineChart"></canvas>
</div>

<div class="chart-container">
    <canvas id="pieChart"></canvas>
</div>

<div class="stat-cards">
    <div class="card">
        <h2>Revenue</h2>
        <p>₱<?= number_format($totalRevenue, 2) ?></p>
    </div>
    <div class="card">
        <h2>Cost</h2>
        <p>₱<?= number_format($totalCost, 2) ?></p>
    </div>
</div>

<script>
    const months = <?= json_encode($months) ?>;
    const bookings = <?= json_encode($monthlyBookings) ?>;

    // Bar Chart
    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Monthly Bookings',
                data: bookings,
                backgroundColor: '#66bb6a'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: { display: true, text: 'Monthly Bookings' }
            }
        }
    });

    // Line Chart
    new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Booking Trend',
                data: bookings,
                borderColor: '#388e3c',
                backgroundColor: 'rgba(56, 142, 60, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: { display: true, text: 'Booking Trends Over Time' }
            }
        }
    });

    // Pie Chart
    new Chart(document.getElementById('pieChart'), {
        type: 'pie',
        data: {
            labels: ['Occupied Rooms', 'Available Rooms'],
            datasets: [{
                data: [<?= $occupied ?>, <?= $available ?>],
                backgroundColor: ['#43a047', '#c8e6c9']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: { display: true, text: 'Room Occupancy Distribution' }
            }
        }
    });
</script>

</body>
</html>
