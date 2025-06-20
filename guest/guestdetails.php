<?php
include 'connections.php';
session_start(); // Enable sessions

$confirmation = "";
$generated_booking_id = "";
$estimated_price = "";
$booking_date = date("Y-m-d");

// Fetch booking ID from GET or SESSION
if (isset($_GET['BookingID'])) {
  $booking_id_param = $conn->real_escape_string($_GET['BookingID']);
  $_SESSION['BookingID'] = $booking_id_param;
} elseif (isset($_SESSION['BookingID'])) {
  $booking_id_param = $conn->real_escape_string($_SESSION['BookingID']);
} else {
  $booking_id_param = "";
}

if (!empty($booking_id_param)) {
  $query = "SELECT BookingID, Price FROM booking WHERE BookingID = '$booking_id_param' LIMIT 1";
  $result = $conn->query($query);
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $generated_booking_id = $row['BookingID'];
    $estimated_price = $row['Price'];
  }
}

// Auto-fill form with session values if available
$form_values = [
  'StudentID'   => $_SESSION['student_id']   ?? $_SESSION['StudentID']   ?? '',
  'FirstName'   => $_SESSION['first_name']   ?? $_SESSION['FirstName']   ?? '',
  'LastName'    => $_SESSION['last_name']    ?? $_SESSION['LastName']    ?? '',
  'Gender'      => $_SESSION['Gender']       ?? '',
  'PhoneNumber' => $_SESSION['PhoneNumber']  ?? '',
  'Address'     => $_SESSION['Address']      ?? '',
  'Email'       => $_SESSION['email']        ?? $_SESSION['Email']       ?? '',
  'Nationality' => $_SESSION['Nationality']  ?? '',
  'Birthdate'   => $_SESSION['Birthdate']    ?? '',
];

// Form Submission Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Update session values from POST
  $_SESSION['StudentID']   = $_POST['StudentID']   ?? $form_values['StudentID'];
  $_SESSION['FirstName']   = $_POST['FirstName']   ?? $form_values['FirstName'];
  $_SESSION['LastName']    = $_POST['LastName']    ?? $form_values['LastName'];
  $_SESSION['Gender']      = $_POST['Gender']      ?? $form_values['Gender'];
  $_SESSION['PhoneNumber'] = $_POST['PhoneNumber'] ?? $form_values['PhoneNumber'];
  $_SESSION['Address']     = $_POST['Address']     ?? $form_values['Address'];
  $_SESSION['Email']       = $_POST['Email']       ?? $form_values['Email'];
  $_SESSION['Nationality'] = $_POST['Nationality'] ?? $form_values['Nationality'];
  $_SESSION['Birthdate']   = $_POST['Birthdate']   ?? $form_values['Birthdate'];

  // Update $form_values for re-rendering the form
  foreach ($form_values as $key => $val) {
    $form_values[$key] = $_SESSION[$key] ?? '';
  }

  if (
    empty($_SESSION['StudentID']) ||
    empty($_POST['BookingID']) ||
    empty($_SESSION['FirstName']) ||
    empty($_SESSION['LastName']) ||
    empty($_SESSION['Gender']) ||
    empty($_SESSION['PhoneNumber']) ||
    empty($_SESSION['Address']) ||
    empty($_SESSION['Email']) ||
    empty($_SESSION['Nationality']) ||
    empty($_SESSION['Birthdate'])
  ) {
    $confirmation = "<p style='color: red;'>All fields are required.</p>";
  } else {
    $student_id   = $conn->real_escape_string($_SESSION['StudentID']);
    $booking_id   = $conn->real_escape_string($_POST['BookingID']);
    $first_name   = $conn->real_escape_string($_SESSION['FirstName']);
    $last_name    = $conn->real_escape_string($_SESSION['LastName']);
    $gender       = $conn->real_escape_string($_SESSION['Gender']);
    $phone_number = $conn->real_escape_string($_SESSION['PhoneNumber']);
    $address      = $conn->real_escape_string($_SESSION['Address']);
    $email        = $conn->real_escape_string($_SESSION['Email']);
    $nationality  = $conn->real_escape_string($_SESSION['Nationality']);
    $birthdate    = $_SESSION['Birthdate'];

    $sql = "INSERT INTO student (StudentID, BookingID, FirstName, LastName, Gender, PhoneNumber, Address, Email, Nationality, Birthdate)
        VALUES ('$student_id', '$booking_id', '$first_name', '$last_name', '$gender', '$phone_number', '$address', '$email', '$nationality', '$birthdate')";

    if ($conn->query($sql)) {
      // Clear session values after successful insert
      unset($_SESSION['StudentID'], $_SESSION['FirstName'], $_SESSION['LastName'], $_SESSION['Gender'], $_SESSION['PhoneNumber'], $_SESSION['Address'], $_SESSION['Email'], $_SESSION['Nationality'], $_SESSION['Birthdate']);

      // Fetch booking details to show confirmation
      $booking_query = "SELECT * FROM booking WHERE BookingID = '$booking_id' LIMIT 1";
      $result_booking = $conn->query($booking_query);

      if ($result_booking && $result_booking->num_rows > 0) {
        $row = $result_booking->fetch_assoc();
        $room_type = $row['RoomType'];
        $check_in = $row['CheckInDate'];
        $check_out = $row['CheckOutDate'];
        $special_request = $row['Notes'];
        $price = $row['Price'];
        $bookingDate = $row['BookingDate'];

        $confirmation = "
          <div class='confirmation'>
            <h2>Booked Confirmed!</h2>
            <p>Thank you for booking!</p>
            <p>Your Booking ID is: <strong>{$booking_id}</strong></p>
            <p>Room Type: <strong>" . ucfirst($room_type) . "</strong></p>
            <p>Check-in: <strong>{$check_in}</strong> | Check-out: <strong>{$check_out}</strong></p>
            <p>Booking Date: <strong>{$bookingDate}</strong></p>
            <p>Total Price: <strong>₱{$price}</strong></p>
            <p>Special Request: <em>{$special_request}</em></p>
            <a href='paymentdetails.php?BookingID={$booking_id}' class='btn'>Next</a>
          </div>";
      } else {
        $confirmation = "<p style='color: red;'>Booking record not found.</p>";
      }

      $conn->close();
    } else {
      $confirmation = "<p style='color: red;'>Error: {$conn->error}</p>";
    }
  }
}
?>  

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Villa Valore Hotel</title>
  <link rel="stylesheet" href="styles/guestinfo.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
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
    <a href="login.php">Sign In</a>
  </nav>
  </header>

<!-- Guest Details Form -->
<div class="container_booking">
  <h2>Guest Information</h2>

  <form method="POST">
  <input type="hidden" name="BookingID" value="<?php echo htmlspecialchars($generated_booking_id); ?>" />

  <div class="form-group">
    <label for="StudentID">Student ID:</label>
    <input type="text" id="StudentID" name="StudentID" required 
    value="<?php echo htmlspecialchars($form_values['StudentID']); ?>" />
  </div>

  <div class="form-group">
    <label for="FirstName">First Name:</label>
    <input type="text" id="FirstName" name="FirstName" required 
    value="<?php echo htmlspecialchars($form_values['FirstName']); ?>" />
  </div>

  <div class="form-group">
    <label for="LastName">Last Name:</label>
    <input type="text" id="LastName" name="LastName" required 
    value="<?php echo htmlspecialchars($form_values['LastName']); ?>" />
  </div>

  <div class="form-group">
    <label for="Gender">Gender:</label>
    <select id="Gender" name="Gender" required>
    <option value="">Select Gender</option>
    <option value="Male" <?php if ($form_values['Gender'] == 'Male') echo 'selected'; ?>>Male</option>
    <option value="Female" <?php if ($form_values['Gender'] == 'Female') echo 'selected'; ?>>Female</option>
    <option value="Prefer not to say" <?php if ($form_values['Gender'] == 'Prefer not to say') echo 'selected'; ?>>Prefer not to say</option>
    <option value="Other" <?php if ($form_values['Gender'] == 'Other') echo 'selected'; ?>>Other</option>
    </select>
  </div>

  <div class="form-group">
    <label for="PhoneNumber">Phone Number:</label>
    <input type="text" id="PhoneNumber" name="PhoneNumber" required 
    value="<?php echo htmlspecialchars($form_values['PhoneNumber']); ?>" />
  </div>

  <div class="form-group">
    <label for="Address">Address:</label>
    <input type="text" id="Address" name="Address" required 
    value="<?php echo htmlspecialchars($form_values['Address']); ?>" />
  </div>

  <div class="form-group">
    <label for="Email">Email:</label>
    <input type="email" id="Email" name="Email" required 
    value="<?php echo htmlspecialchars($form_values['Email']); ?>" />
  </div>

  <div class="form-group">
    <label for="Nationality">Nationality:</label>
    <input type="text" id="Nationality" name="Nationality" required 
    value="<?php echo htmlspecialchars($form_values['Nationality']); ?>" />
  </div>

  <div class="form-group">
    <label for="Birthdate">Birthdate:</label>
    <input type="date" id="Birthdate" name="Birthdate" required 
    value="<?php echo htmlspecialchars($form_values['Birthdate']); ?>" />
  </div>

  <button type="submit" class="btn">Submit</button>
  <button type="button" class="btn" onclick="window.location.href='booking.php';">Back</button>

  </form>

  <?php echo $confirmation; ?>
</div>

</body>
</html>