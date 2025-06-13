<?php
include 'connections.php';
$confirmation = "";
$generatedBookingID = "";
$bookingDate = date("Y-m-d");

// Generate Booking ID on page load if not submitting
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $date_code = date("Ymd");
    $result = $conn->query("SELECT COUNT(*) AS total FROM booking WHERE DATE(BookingDate) = CURDATE()");
    $row = $result->fetch_assoc();
    $count_today = $row['total'] + 1;
    $generatedBookingID = "BK-" . $date_code . "-" . str_pad($count_today, 4, '0', STR_PAD_LEFT);
}

// Redirect if no room is selected
if (!isset($_GET['room']) || empty($_GET['room'])) {
    header("Location: booking.php");
    exit;
}

// Get the selected room type from the URL
$selectedRoomType = htmlspecialchars($_GET['room']);

// Booking submission logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['RoomType']) || empty($_POST['CheckInDate']) || empty($_POST['CheckOutDate'])) {
        $confirmation = "<p style='color: red;'>All fields are required.</p>";
    } else {
        $room_type = $conn->real_escape_string($_POST['RoomType']);
        $check_in = $_POST['CheckInDate'];
        $check_out = $_POST['CheckOutDate'];
        $special_request = $conn->real_escape_string($_POST['Notes']);
        $booking_id = $_POST['booking_id']; // Use the generated one from the form
        $bookingDate = $_POST['BookingDate']; // Get from hidden field

        // Dummy price logic
        $price_map = [
            'standard' => 2000,
            'deluxe' => 3000,
            'suite' => 5000
        ];
        $price = $price_map[strtolower($room_type)];

        // Insert INTO Database
        $sql = "INSERT INTO booking (BookingID, BookingDate, RoomType, CheckInDate, CheckOutDate, Notes, Price)
                VALUES ('$booking_id', '$bookingDate', '$room_type', '$check_in', '$check_out', '$special_request', '$price')";

        if ($conn->query($sql)) {
            $confirmation = "
                <div class='confirmation'>
                    <h2>Booking Confirmed!</h2>
                    <p>Thank you for booking!</p>
                    <p>Your Booking ID is: <strong>$booking_id</strong></p>
                    <p>Room Type: <strong>" . ucfirst($room_type) . "</strong></p>
                    <p>Check-in: <strong>$check_in</strong> | Check-out: <strong>$check_out</strong></p>
                    <p>Booking Date: <strong>$bookingDate</strong></p>
                    <p>Total Price: <strong>₱$price</strong></p>
                    <p>Special Request: <em>$special_request</em></p>
                    <a href='guestdetails.php?BookingID=$booking_id' class='btn'>Next</a>
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
  <title>Villa Valore Hotel</title>
  <link rel="stylesheet" href="styles/booknow.css">
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

<!-- Booking Form -->
<div class="container_booking">
  <h2>Book a Room</h2> <br>
  <p>Fill out the form below to book your room.</p><br>
  <form method="POST"> 

    <!-- Booking ID -->
    <div class="form-group">
        <label for="booking-id">Booking ID:</label>
        <input type="text" id="booking-id" name="booking_id" value="<?php echo $generatedBookingID; ?>" readonly />
    </div>

    <!-- Booking Date (hidden for backend) -->
    <input type="hidden" name="BookingDate" value="<?php echo $bookingDate; ?>" />

    <!-- Booking Date (visible for user) -->
    <div class="form-group">
      <label for="BookingDate">Booking Date:</label>
      <input type="text" id="BookingDate" value="<?php echo $bookingDate; ?>" readonly />
    </div>

    <!-- Room Type (read-only and auto-filled from URL) -->
<div class="form-group">
  <label for="RoomType">Room Type:</label>
  <input type="text" id="RoomType" name="RoomType" value="<?php echo htmlspecialchars($selectedRoomType); ?>" readonly required />
</div>

    <!-- Check-in Date -->
    <div class="form-group">
      <label for="CheckInDate">Check-in Date:</label>
      <input type="date" id="CheckInDate" name="CheckInDate" required />
    </div>

    <!-- Check-out Date -->
    <div class="form-group">
      <label for="CheckOutDate">Check-out Date:</label>
      <input type="date" id="CheckOutDate" name="CheckOutDate" required />
    </div>

    <!-- Notes -->
    <div class="form-group">
      <label for="Notes">Special Request / Notes:</label>
      <textarea id="Notes" name="Notes" rows="3"></textarea>
    </div>

    <!-- Price (just for UI) -->
    <div class="form-group">
      <label for="Price">Estimated Price:</label>
      <input type="text" id="Price" value="<?php echo isset($price) ? '₱' . $price : ''; ?>" readonly />
    </div>

    <!-- Book Now Button -->
    <button type="submit" class="btn">Book Now</button>
  </form>

  <?php echo $confirmation; ?>
</div>

<script>
function generateBookingID() {
  const now = new Date();
  const ymd = now.toISOString().slice(0,10).replace(/-/g, '');
  const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
  return `BK-${ymd}-${random}`;
}

function maybeGenerateID() {
  const room = document.getElementById("RoomType").value;
  const inDate = document.getElementById("CheckInDate").value;
  const outDate = document.getElementById("CheckOutDate").value;
  const bookingInput = document.getElementById("booking-id");

  if (room && inDate && outDate && !bookingInput.value) {
    bookingInput.value = generateBookingID();
  }
}

document.getElementById("RoomType").addEventListener("change", maybeGenerateID);
document.getElementById("CheckInDate").addEventListener("input", maybeGenerateID);
document.getElementById("CheckOutDate").addEventListener("input", maybeGenerateID);
</script>

</body>
</html>
