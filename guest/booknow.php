<?php
include 'connections.php';
$confirmation = "";
$generatedBookingID = "";
$bookingDate = date("Y-m-d");

// ✅ Check for pre-selected room type via GET
$room_type_from_url = '';
$valid_rooms = ['standard', 'deluxe', 'suite'];

if (isset($_GET['room']) && in_array(strtolower($_GET['room']), $valid_rooms)) {
    $room_type_from_url = strtolower($_GET['room']);
}

// Generate Booking ID on page load if not submitting
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $date_code = date("Ymd");
    $result = $conn->query("SELECT COUNT(*) AS total FROM booking WHERE DATE(BookingDate) = CURDATE()");
    $row = $result->fetch_assoc();
    $count_today = $row['total'] + 1;
    $generatedBookingID = "BK-" . $date_code . "-" . str_pad($count_today, 4, '0', STR_PAD_LEFT);
}

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
                    <h2>Booked Confirmed!</h2>
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    /* Book Now Styles */

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: Arial, sans-serif;
  margin: 0;
  background: #f8f8f8;
}

/* Header Bar */
.top-bar {
  background-color: #018000;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 5px 20px;
  font-size: 14px;
}

.logo-container {
  display: flex;
  align-items: center;
}

.cvsu-logo {
  height: 40px;
  margin-right: 10px;
}

.university-info strong {
  display: block;
}

.top-icons {
  display: flex;
  gap: 15px;
}

.icon {
  font-size: 18px;
  cursor: pointer;
}

.main-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 30px;
  background-color: white;
  border-bottom: 1px solid #ccc;
  flex-wrap: wrap;
}

.brand {
  display: flex;
  align-items: center;
}

.villa-logo {
  height: 60px;
  margin-right: 15px;
}

.brand-text h1 {
  color: #018000;
  font-size: 24px;
  font-weight: bold;
}

.brand-text small {
  color: #666;
  font-size: 12px;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 25px;
  position: relative;
}

    .nav-links a {
      text-decoration: none;
      color: #018000;
      font-weight: bold;
      font-size: 16px;
      position: relative;
    }
    
    .container_booking {
      max-width: 550px;
      margin: 30px auto;
      background: white;
      padding: 25px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      border-radius: 8px;
    }

.form-group {
  margin-bottom: 15px;
}

label {
  font-weight: bold;
  display: block;
  margin-bottom: 5px;
}

input, select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.btn {
  background-color: #0b3d2e;
  color: white;
  padding: 12px 25px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  display: inline-block;
  margin-top: 10px;
  text-decoration: none;
}

.btn:hover {
  background-color: #096649;
}

.confirmation {
  margin-top: 25px;
  background-color: #e6f8ec;
  padding: 20px;
  border-left: 6px solid #34a56f;
  border-radius: 6px;
}

.readonly-field {
  background-color: #f2f2f2;
  border: 1px solid #ccc;
  color: #333;
  cursor: not-allowed;
}

  </style>
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

    <!-- Room Type (select dropdown) -->
<div class="form-group">
  <label for="RoomType">Room Type:</label>
  <select id="RoomType" name="RoomType" required>
    <option value="">-- Select Room Type --</option>
    <option value="standard" <?php if (isset($_POST['RoomType']) && $_POST['RoomType'] == 'standard') echo 'selected'; ?>>Standard</option>
    <option value="deluxe" <?php if (isset($_POST['RoomType']) && $_POST['RoomType'] == 'deluxe') echo 'selected'; ?>>Deluxe</option>
    <option value="suite" <?php if (isset($_POST['RoomType']) && $_POST['RoomType'] == 'suite') echo 'selected'; ?>>Suite</option>
  </select>
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
  return BK-${ymd}-${random};
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