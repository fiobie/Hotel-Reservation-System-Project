<?php
include 'connections.php';
session_start(); // Enable sessions

$confirmation = "";
$generated_booking_id = "";
$estimated_price = "";
$booking_date = date("Y-m-d");

// Fetch Booking ID from GET, SESSION, or latest booking for this student
if (isset($_GET['BookingID'])) {
  $booking_id_param = $conn->real_escape_string($_GET['BookingID']);
  $_SESSION['BookingID'] = $booking_id_param;
} elseif (isset($_SESSION['BookingID'])) {
  $booking_id_param = $conn->real_escape_string($_SESSION['BookingID']);
} elseif (!empty($_SESSION['student_id'])) {
  // Fetch the latest booking for this student
  $student_id = $conn->real_escape_string($_SESSION['student_id']);
  $result = $conn->query("SELECT BookingID FROM booking WHERE StudentID = '$student_id' ORDER BY BookingDate DESC, BookingID DESC LIMIT 1");
  if ($result && $row = $result->fetch_assoc()) {
    $booking_id_param = $row['BookingID'];
    $_SESSION['BookingID'] = $booking_id_param;
  } else {
    $booking_id_param = "";
  }
} else {
  $booking_id_param = "";
}
$generated_booking_id = $booking_id_param;

// Get transferred booking parameters from session
$total_price = $_SESSION['total_price'] ?? '';
$reservation_fee = $_SESSION['reservation_fee'] ?? '';
$duration = $_SESSION['duration'] ?? '';
$adults = $_SESSION['adults'] ?? 1;
$children = $_SESSION['children'] ?? 0;
$checkin_date = $_SESSION['checkin_date'] ?? '';
$checkout_date = $_SESSION['checkout_date'] ?? '';

if (!empty($booking_id_param)) {
  $query = "SELECT BookingID, Price, RoomType, CheckInDate, CheckOutDate, Notes FROM booking WHERE BookingID = '$booking_id_param' LIMIT 1";
  $result = $conn->query($query);
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $generated_booking_id = $row['BookingID'];
    $estimated_price = $row['Price'];
    // Use database values if session values are not available
    if (empty($total_price)) $total_price = $estimated_price;
    if (empty($checkin_date)) $checkin_date = $row['CheckInDate'];
    if (empty($checkout_date)) $checkout_date = $row['CheckOutDate'];
  }
}

// Fetch previous student data for pre-filling
$student_data = [
  'StudentID'   => $_SESSION['student_id']   ?? '',
  'FirstName'   => $_SESSION['first_name']   ?? '',
  'LastName'    => $_SESSION['last_name']    ?? '',
  'Gender'      => '',
  'PhoneNumber' => '',
  'Address'     => '',
  'Email'       => $_SESSION['email']        ?? '',
  'Nationality' => '',
  'Birthdate'   => '',
];
if (!empty($student_data['StudentID'])) {
  $sid = $conn->real_escape_string($student_data['StudentID']);
  $res = $conn->query("SELECT * FROM student WHERE StudentID = '$sid' LIMIT 1");
  if ($res && $row = $res->fetch_assoc()) {
    $student_data['FirstName']   = $row['FirstName'];
    $student_data['LastName']    = $row['LastName'];
    $student_data['Gender']      = $row['Gender'];
    $student_data['PhoneNumber'] = $row['PhoneNumber'];
    $student_data['Address']     = $row['Address'];
    $student_data['Email']       = $row['Email'];
    $student_data['Nationality'] = $row['Nationality'];
    $student_data['Birthdate']   = $row['Birthdate'];
  }
}

$form_values = $student_data;

// Fetch payment-related data for pre-filling
$payment_date = date("Y-m-d");
$generated_payment_id = 'PAY' . uniqid();
$generated_reference_code = 'REF' . strtoupper(bin2hex(random_bytes(4)));

// Fetch latest payment for this booking (optional, for pre-fill)
$latest_payment = [
  'Amount' => $total_price,
  'PaymentMethod' => 'Cash',
  'PaymentStatus' => 'Pending',
];

// Combined Form Submission Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validate Guest Details fields
  $required_guest = [
    'StudentID', 'BookingID', 'FirstName', 'LastName', 'Gender', 'PhoneNumber', 'Address', 'Email', 'Nationality', 'Birthdate'
  ];
  $missing = false;
  foreach ($required_guest as $field) {
    if (empty($_POST[$field])) $missing = true;
  }
  // Validate Payment Details fields
  $required_payment = [
    'PaymentID', 'PaymentDate', 'Amount', 'PaymentMethod', 'PaymentStatus', 'ReferenceCode'
  ];
  foreach ($required_payment as $field) {
    if (empty($_POST[$field])) $missing = true;
  }
  if ($missing) {
    $confirmation = "<p style='color: red;'>All fields are required.</p>";
  } else {
    // DEBUG: Log POST data
    error_log('POST DATA: ' . print_r($_POST, true));

    // --- GUEST DETAILS DB LOGIC ---
    $student_id   = $conn->real_escape_string($_POST['StudentID']);
    $booking_id   = $conn->real_escape_string($_POST['BookingID']);
    $gender       = $conn->real_escape_string($_POST['Gender']);
    $phone_number = $conn->real_escape_string($_POST['PhoneNumber']);
    $address      = $conn->real_escape_string($_POST['Address']);
    $nationality  = $conn->real_escape_string($_POST['Nationality']);
    $birthdate    = $conn->real_escape_string($_POST['Birthdate']);
    $first_name   = $conn->real_escape_string($_POST['FirstName']);
    $last_name    = $conn->real_escape_string($_POST['LastName']);
    $email        = $conn->real_escape_string($_POST['Email']);
    // Check if student exists
    $check = $conn->query("SELECT 1 FROM student WHERE StudentID = '$student_id' LIMIT 1");
    if ($check && $check->num_rows > 0) {
      // Student exists, update only the allowed fields
      $sql = "UPDATE student SET 
        Gender = '$gender',
        PhoneNumber = '$phone_number',
        Address = '$address',
        Nationality = '$nationality',
        Birthdate = '$birthdate'
        WHERE StudentID = '$student_id'";
      $conn->query($sql);
    } else {
      // Student does not exist, insert new
      $sql_insert = "INSERT INTO student (StudentID, FirstName, LastName, Gender, PhoneNumber, Address, Email, Nationality, Birthdate)
        VALUES ('$student_id', '$first_name', '$last_name', '$gender', '$phone_number', '$address', '$email', '$nationality', '$birthdate')";
      $conn->query($sql_insert);
    }
    // --- BOOKING DB LOGIC (insert if not exists) ---
    $room_type = $conn->real_escape_string($_POST['RoomType'] ?? '');
    $check_in = $conn->real_escape_string($_POST['CheckInDate'] ?? '');
    $check_out = $conn->real_escape_string($_POST['CheckOutDate'] ?? '');
    $price = $conn->real_escape_string($_POST['Price'] ?? '');
    $booking_date = date('Y-m-d');
    $check_booking = $conn->query("SELECT * FROM booking WHERE BookingID = '$booking_id' LIMIT 1");
    if (!$check_booking || $check_booking->num_rows == 0) {
      // Insert booking with StudentID
      $sql_booking = "INSERT INTO booking (BookingID, StudentID, RoomType, CheckInDate, CheckOutDate, BookingStatus, Price, BookingDate)
        VALUES ('$booking_id', '$student_id', '$room_type', '$check_in', '$check_out', 'Pending', '$price', '$booking_date')";
      if (!$conn->query($sql_booking)) {
        error_log('Booking insert error: ' . $conn->error);
        echo '<div style="color:red;">Booking insert error: ' . htmlspecialchars($conn->error) . '</div>';
      }
    } else {
      // Booking exists, ensure StudentID is set
      $existing = $check_booking->fetch_assoc();
      if (empty($existing['StudentID'])) {
        $conn->query("UPDATE booking SET StudentID = '$student_id' WHERE BookingID = '$booking_id'");
      }
    }
    // --- PAYMENT DETAILS DB LOGIC ---
    $payment_id = $conn->real_escape_string($_POST['PaymentID']);
    $amount = $conn->real_escape_string($_POST['Amount']);
    $payment_status = $conn->real_escape_string($_POST['PaymentStatus']);
    $payment_date = $conn->real_escape_string($_POST['PaymentDate']);
    $payment_method = $conn->real_escape_string($_POST['PaymentMethod']);
    $reference_code = $conn->real_escape_string($_POST['ReferenceCode']);
    // Insert payment
    $sql_payment = "INSERT INTO payment (PaymentID, BookingID, Amount, PaymentStatus, PaymentDate, PaymentMethod, ReferenceCode)
      VALUES ('$payment_id', '$booking_id', '$amount', '$payment_status', '$payment_date', '$payment_method', '$reference_code')";
    if (!$conn->query($sql_payment)) {
      error_log('Payment insert error: ' . $conn->error);
      echo '<div style="color:red;">Payment insert error: ' . htmlspecialchars($conn->error) . '</div>';
    } else {
      // After payment, update student with BookingID and PaymentID
      $conn->query("UPDATE student SET BookingID = '$booking_id', PaymentID = '$payment_id' WHERE StudentID = '$student_id'");
      // Redirect to mybookings.php with success message
      header('Location: mybookings.php?success=1');
      exit();
    }
  }
}

// DEBUG: Show last 5 bookings and payments for troubleshooting
$debug_bookings = $conn->query("SELECT * FROM booking ORDER BY BookingDate DESC, BookingID DESC LIMIT 5");
$debug_payments = $conn->query("SELECT * FROM payment ORDER BY PaymentDate DESC, PaymentID DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Villa Valore Hotel</title>
  <link rel="stylesheet" href="styles/guestinfo.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <style>
    .booking-summary {
      background-color: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
    }
    .booking-summary h3 {
      color: #018000;
      margin-bottom: 10px;
      font-size: 18px;
    }
    .summary-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
      font-size: 14px;
    }
    .summary-label {
      font-weight: 600;
      color: #555;
    }
    .summary-value {
      color: #333;
    }
    .price-highlight {
      font-size: 18px;
      font-weight: bold;
      color: #018000;
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
    <a href="logout.php">Logout</a>
  </nav>
  </header>

<!-- Guest Details Form -->
<div class="container_booking">
  <h2>Guest Information</h2>

  <!-- Booking Summary -->
  <?php if ($total_price || $duration || $checkin_date): ?>
  <div class="booking-summary">
    <h3>Booking Summary</h3>
    <?php if ($duration): ?>
    <div class="summary-row">
      <span class="summary-label">Duration:</span>
      <span class="summary-value"><?php echo htmlspecialchars($duration); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($adults || $children): ?>
    <div class="summary-row">
      <span class="summary-label">Guests:</span>
      <span class="summary-value"><?php echo $adults + $children; ?> (<?php echo $adults; ?> adults, <?php echo $children; ?> children)</span>
    </div>
    <?php endif; ?>
    <?php if ($checkin_date): ?>
    <div class="summary-row">
      <span class="summary-label">Check-in:</span>
      <span class="summary-value"><?php echo htmlspecialchars($checkin_date); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($checkout_date): ?>
    <div class="summary-row">
      <span class="summary-label">Check-out:</span>
      <span class="summary-value"><?php echo htmlspecialchars($checkout_date); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($total_price): ?>
    <div class="summary-row">
      <span class="summary-label">Total Price:</span>
      <span class="summary-value price-highlight">₱<?php echo number_format($total_price); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($reservation_fee): ?>
    <div class="summary-row">
      <span class="summary-label">Reservation Fee:</span>
      <span class="summary-value">₱<?php echo number_format($reservation_fee); ?></span>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <form method="POST">
  <!-- Visible Booking ID field -->
  <div class="form-group">
    <label for="BookingID">Booking ID:</label>
    <input type="text" id="BookingID" name="BookingID" value="<?php echo htmlspecialchars($generated_booking_id); ?>" readonly />
  </div>

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

  <!-- Payment Details Section -->
  <h2 style="margin-top:2em;">Payment Details</h2>

  <div class="form-group">
    <label for="PaymentID">Payment ID:</label>
    <input type="text" id="PaymentID" name="PaymentID" value="<?php echo htmlspecialchars($generated_payment_id); ?>" readonly />
  </div>

  <input type="hidden" name="PaymentDate" value="<?php echo $payment_date; ?>" />
  <div class="form-group">
    <label for="PaymentDate">Payment Date:</label>
    <input type="text" id="PaymentDate" value="<?php echo $payment_date; ?>" readonly />
  </div>

  <div class="form-group">
    <label for="Amount">Amount:</label>
    <input type="text" id="Amount" name="Amount" value="<?php echo htmlspecialchars($total_price); ?>" required />
  </div>

  <div class="form-group">
    <label for="PaymentMethod">Payment Method:</label>
    <select id="PaymentMethod" name="PaymentMethod" required>
      <option value="Cash" selected>Cash</option>
    </select>
  </div>

  <div class="form-group">
    <label for="PaymentStatus">Payment Status:</label>
    <input type="text" id="PaymentStatus" name="PaymentStatus" value="Pending" required />
  </div>

  <div class="form-group">
    <label for="ReferenceCode">Reference Code:</label>
    <input type="text" id="ReferenceCode" name="ReferenceCode" value="<?php echo htmlspecialchars($generated_reference_code); ?>" readonly />
  </div>

  <button type="submit" class="btn">Submit</button>
  <button type="button" class="btn" onclick="window.location.href='booking.php';">Back</button>

  </form>

  <?php echo $confirmation; ?>
</div>

<div style="background:#fffbe6;border:1px solid #ffe58f;padding:1em;margin-top:2em;">
  <h3>Debug: Last 5 Bookings</h3>
  <table border="1" cellpadding="4" style="width:100%;font-size:0.95em;">
    <tr>
      <th>BookingID</th><th>StudentID</th><th>RoomType</th><th>CheckInDate</th><th>CheckOutDate</th><th>Status</th><th>Price</th><th>BookingDate</th>
    </tr>
    <?php while($row = $debug_bookings && $debug_bookings->fetch_assoc()): ?>
    <tr>
      <td><?php echo htmlspecialchars($row['BookingID']); ?></td>
      <td><?php echo htmlspecialchars($row['StudentID']); ?></td>
      <td><?php echo htmlspecialchars($row['RoomType']); ?></td>
      <td><?php echo htmlspecialchars($row['CheckInDate']); ?></td>
      <td><?php echo htmlspecialchars($row['CheckOutDate']); ?></td>
      <td><?php echo htmlspecialchars($row['BookingStatus']); ?></td>
      <td><?php echo htmlspecialchars($row['Price']); ?></td>
      <td><?php echo htmlspecialchars($row['BookingDate']); ?></td>
    </tr>
    <?php endwhile; ?>
  </table>
  <h3>Debug: Last 5 Payments</h3>
  <table border="1" cellpadding="4" style="width:100%;font-size:0.95em;">
    <tr>
      <th>PaymentID</th><th>BookingID</th><th>Amount</th><th>Status</th><th>Method</th><th>Date</th><th>ReferenceCode</th>
    </tr>
    <?php while($row = $debug_payments && $debug_payments->fetch_assoc()): ?>
    <tr>
      <td><?php echo htmlspecialchars($row['PaymentID']); ?></td>
      <td><?php echo htmlspecialchars($row['BookingID']); ?></td>
      <td><?php echo htmlspecialchars($row['Amount']); ?></td>
      <td><?php echo htmlspecialchars($row['PaymentStatus']); ?></td>
      <td><?php echo htmlspecialchars($row['PaymentMethod']); ?></td>
      <td><?php echo htmlspecialchars($row['PaymentDate']); ?></td>
      <td><?php echo htmlspecialchars($row['ReferenceCode']); ?></td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>

</body>
</html>