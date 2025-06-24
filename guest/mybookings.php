<?php
session_start();
include 'connections.php';

$student_id = $_SESSION['student_id'] ?? '';
if (!$student_id) {
    header('Location: login.php');
    exit();
}

// Fetch account info
$student = $conn->query("SELECT * FROM student WHERE StudentID = '$student_id'")->fetch_assoc();

// Fetch booking history
$bookings = $conn->query("SELECT * FROM booking WHERE StudentID = '$student_id' ORDER BY BookingDate DESC");
$all_bookings = [];
$booking_ids = [];
while ($row = $bookings->fetch_assoc()) {
    $all_bookings[] = $row;
    $booking_ids[] = "'" . $conn->real_escape_string($row['BookingID']) . "'";
}
$booking_ids_str = implode(',', $booking_ids);
$payments = [];
if ($booking_ids_str) {
    $result = $conn->query("SELECT * FROM payment WHERE BookingID IN ($booking_ids_str) ORDER BY PaymentDate DESC");
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Villa Valore Hotel</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    .section { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #eee; margin: 2em auto; padding: 2em; max-width: 900px; }
    .section h2 { color: #018000; margin-bottom: 1em; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 2em; }
    th, td { padding: 0.7em 1em; border-bottom: 1px solid #eee; text-align: left; }
    th { background: #f8f8f8; color: #333; }
    .success-msg { background: #e6f8ec; color: #018000; border-left: 6px solid #34a56f; padding: 1em; border-radius: 6px; margin-bottom: 2em; }
  </style>
</head>
<body>

<!-- Header -->
<header class="main-header">
  <div class="brand">
    <img src="villa-valore-logo.png" alt="Villa Valore Logo" class="villa-logo">
    <div class="brand-text">
      <h1>Villa Valore Hotel</h1>
      <small>BIGA I, SILANG, CAVITE</small>
    </div>
  </div>
  <nav class="nav-links">
    <a href="index.php">Home</a>
    <a href="mybookings.php">My Bookings</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<div class="section">
<?php if (isset($_GET['success'])): ?>
  <div class="success-msg">Your booking and payment were successful!</div>
<?php endif; ?>

<h2>Account Information</h2>
<table>
  <tr><th>Student ID</th><td><?php echo htmlspecialchars($student['StudentID']); ?></td></tr>
  <tr><th>Name</th><td><?php echo htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']); ?></td></tr>
  <tr><th>Email</th><td><?php echo htmlspecialchars($student['Email']); ?></td></tr>
  <tr><th>Gender</th><td><?php echo htmlspecialchars($student['Gender']); ?></td></tr>
  <tr><th>Phone Number</th><td><?php echo htmlspecialchars($student['PhoneNumber']); ?></td></tr>
  <tr><th>Address</th><td><?php echo htmlspecialchars($student['Address']); ?></td></tr>
  <tr><th>Nationality</th><td><?php echo htmlspecialchars($student['Nationality']); ?></td></tr>
  <tr><th>Birthdate</th><td><?php echo htmlspecialchars($student['Birthdate']); ?></td></tr>
</table>

<h2>Booking History</h2>
<table>
  <tr>
    <th>Booking ID</th>
    <th>Room Type</th>
    <th>Check-in</th>
    <th>Check-out</th>
    <th>Status</th>
    <th>Price</th>
    <th>Booking Date</th>
  </tr>
  <?php if (count($all_bookings)): foreach ($all_bookings as $b): ?>
  <tr>
    <td><?php echo htmlspecialchars($b['BookingID']); ?></td>
    <td><?php echo htmlspecialchars($b['RoomType']); ?></td>
    <td><?php echo htmlspecialchars($b['CheckInDate']); ?></td>
    <td><?php echo htmlspecialchars($b['CheckOutDate']); ?></td>
    <td><?php echo htmlspecialchars($b['BookingStatus']); ?></td>
    <td>₱<?php echo number_format($b['Price']); ?></td>
    <td><?php echo htmlspecialchars($b['BookingDate']); ?></td>
  </tr>
  <?php endforeach; else: ?>
  <tr><td colspan="7">No bookings found.</td></tr>
  <?php endif; ?>
</table>

<h2>Payment History</h2>
<table>
  <tr>
    <th>Payment ID</th>
    <th>Booking ID</th>
    <th>Amount</th>
    <th>Status</th>
    <th>Method</th>
    <th>Date</th>
    <th>Reference Code</th>
  </tr>
  <?php if (count($payments)): foreach ($payments as $p): ?>
  <tr>
    <td><?php echo htmlspecialchars($p['PaymentID']); ?></td>
    <td><?php echo htmlspecialchars($p['BookingID']); ?></td>
    <td>₱<?php echo number_format($p['Amount']); ?></td>
    <td><?php echo htmlspecialchars($p['PaymentStatus']); ?></td>
    <td><?php echo htmlspecialchars($p['PaymentMethod']); ?></td>
    <td><?php echo htmlspecialchars($p['PaymentDate']); ?></td>
    <td><?php echo htmlspecialchars($p['ReferenceCode']); ?></td>
  </tr>
  <?php endforeach; else: ?>
  <tr><td colspan="7">No payments found.</td></tr>
  <?php endif; ?>
</table>
</div>
</body>
</html>