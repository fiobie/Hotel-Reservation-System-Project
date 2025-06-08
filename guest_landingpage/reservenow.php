<?php
include 'connections.php';
$confirmation = "";
$generatedReservationID = "";
$reservationDate = date("Y-m-d");

// Generate Reservation ID on page load if not submitting
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $date_code = date("Ymd");
    $result = $conn->query("SELECT COUNT(*) AS total FROM reservation WHERE DATE(ReservationDate) = CURDATE()");
    $row = $result->fetch_assoc();
    $count_today = $row['total'] + 1;
    $generatedReservationID = "RS-" . $date_code . "-" . str_pad($count_today, 4, '0', STR_PAD_LEFT);
}

// Reservation submission logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['RoomType']) || empty($_POST['CheckInDate']) || empty($_POST['CheckOutDate'])) {
        $confirmation = "<p style='color: red;'>All fields are required.</p>";
    } else {
        $room_type = $conn->real_escape_string($_POST['RoomType']);
        $check_in = $_POST['CheckInDate'];
        $check_out = $_POST['CheckOutDate'];
        $special_request = $conn->real_escape_string($_POST['Notes']);
        $reservation_id = $_POST['reservation_id'];

        // Reservation fee logic
        $fee_map = [
            'standard' => 500,
            'deluxe' => 800,
            'suite' => 1000
        ];
        $reservation_fee = $fee_map[strtolower($room_type)];

        // Insert into reservation table
        $sql = "INSERT INTO reservation (ReservationID, ReservationDate, RoomType, PCheckInDate, PCheckOutDate, Notes, ReservationFee)
                VALUES ('$reservation_id', '$reservationDate', '$room_type', '$check_in', '$check_out', '$special_request', '$reservation_fee')";

        if ($conn->query($sql)) {
            $confirmation = "
                <div class='confirmation'>
                    <h2>Reservation Confirmed!</h2>
                    <p>Thank you for reserving with Villa Valore Hotel!</p>
                    <p>Reservation ID: <strong>$reservation_id</strong></p>
                    <p>Reservation Date: <strong>$reservationDate</strong></p>
                    <p>Room Type: <strong>" . ucfirst($room_type) . "</strong></p>
                    <p>Check-in: <strong>$check_in</strong> | Check-out: <strong>$check_out</strong></p>
                    <p>Reservation Fee: <strong>₱" . number_format($reservation_fee) . "</strong></p>
                    <p>Special Request: <em>$special_request</em></p>
                    <a href='guestdetails.php' class='btn'>Next</a>
                </div>";
        } else {
            $confirmation = "<p style='color: red;'>Error: " . $conn->error . "</p>";
        }

        $conn->close();
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Villa Valore Hotel - Reservation</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

<!-- Main Navigation -->
<header class="main-header">
  <div class="brand">
    <img src="villa-valore-logo.png" alt="Villa Valore Logo" class="villa-logo">
    <div class="brand-text">
      <h1>Villa Valore Hotel</h1>
      <small>BIGA I, SILANG, CAVITE</small>
    </div>
  </div>
  <nav class="nav-links">
    <a href="booking.php">Rooms</a>
    <a href="about.php">About</a>
    <a href="mybookings.php">My Bookings</a>
    <a href="login.php">Log In</a>
  </nav>
</header>

<!-- Reservation Form -->
<div class="container_booking">
  <h2>Reserve a Room</h2>
  <form method="POST">

    <!-- Reservation ID -->
    <div class="form-group">
      <label for="reservation-id">Reservation ID:</label>
      <input type="text" id="reservation-id" name="reservation_id" value="<?php echo $generatedReservationID; ?>" readonly />
    </div>

    <!-- Reservation Date (displayed, not editable) -->
    <div class="form-group">
      <label for="ReservationDate">Reservation Date:</label>
      <input type="text" id="ReservationDate" value="<?php echo $reservationDate; ?>" readonly />
    </div>

    <!-- Room Type -->
    <div class="form-group">
      <label for="RoomType">Room Type:</label>
      <select id="RoomType" name="RoomType" required>
        <option value="">Select a Room Type</option>
        <option value="standard">Standard Room</option>
        <option value="deluxe">Deluxe Room</option>
        <option value="suite">Suite Room</option>
      </select>
    </div>

    <!-- Check-in -->
    <div class="form-group">
      <label for="CheckInDate">Check-in Date:</label>
      <input type="date" id="CheckInDate" name="CheckInDate" required />
    </div>

    <!-- Check-out -->
    <div class="form-group">
      <label for="CheckOutDate">Check-out Date:</label>
      <input type="date" id="CheckOutDate" name="CheckOutDate" required />
    </div>

    <!-- Notes -->
    <div class="form-group">
      <label for="Notes">Special Request / Notes:</label>
      <textarea id="Notes" name="Notes" rows="3"></textarea>
    </div>

    <!-- Reservation Fee -->
    <div class="form-group">
      <label for="ReservationFee">Reservation Fee:</label>
      <input type="text" id="ReservationFee" readonly />
    </div>

    <button type="submit" class="btn">Confirm Reservation</button>
  </form>

  <?php echo $confirmation; ?>
</div>

<script>
document.getElementById("RoomType").addEventListener("change", function () {
  const feeDisplay = document.getElementById("ReservationFee");
  const roomType = this.value;

  const fees = {
    standard: 500,
    deluxe: 800,
    suite: 1000
  };

  if (fees[roomType]) {
    feeDisplay.value = "₱" + fees[roomType].toLocaleString();
  } else {
    feeDisplay.value = "";
  }
});
</script>

</body>
</html>
