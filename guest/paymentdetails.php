<?php 
include 'connections.php';
$confirmation = "";
$generated_booking_id = "";
$estimated_price = "";
$student_id = "";
$payment_date = date("Y-m-d");

// Generate a unique PaymentID
$generated_payment_id = 'PAY' . time(); // e.g., PAY1723059271

// Generate a unique ReferenceCode
$generated_reference_code = 'REF' . strtoupper(bin2hex(random_bytes(4))); // e.g., REF1A2B3C4

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($_POST['PaymentID']) ||
        empty($_POST['BookingID']) ||
        empty($_POST['PaymentDate']) ||
        empty($_POST['Amount']) ||
        empty($_POST['PaymentStatus']) ||
        empty($_POST['PaymentMethod']) ||
        empty($_POST['ReferenceCode']) ||
        empty($_POST['StudentID']) 
    ) {
        $confirmation = "<p style='color: red;'>All fields are required.</p>";
    } else {
        $payment_id = $conn->real_escape_string($_POST['PaymentID']);
        $booking_id = $conn->real_escape_string($_POST['BookingID']);
        $amount = $conn->real_escape_string($_POST['Amount']);
        $payment_status = $conn->real_escape_string($_POST['PaymentStatus']);
        $payment_date = $conn->real_escape_string($_POST['PaymentDate']);
        $payment_method = $conn->real_escape_string($_POST['PaymentMethod']);
        $reference_code = $conn->real_escape_string($_POST['ReferenceCode']);
        $student_id = $conn->real_escape_string($_POST['StudentID']);
    }
        $sql = "INSERT INTO payment (PaymentID, BookingID, Amount, PaymentStatus, PaymentDate, PaymentMethod, ReferenceCode)
                VALUES ('$payment_id', '$booking_id', '$amount', '$payment_status', '$payment_date', '$payment_method', '$reference_code')";

        if ($conn->query($sql)) {
            $conn->close();
            header("Location: paymentdetails.php?BookingID=$booking_id");
            exit();
        } else {
            $confirmation = "<p style='color: red;'>Error: " . $conn->error . "</p>";
        }
    }
// Fetch BookingID, StudentID, and Price if provided via GET
if (isset($_GET['BookingID'])) {
    $booking_id_param = $conn->real_escape_string($_GET['BookingID']);
    $query = "SELECT BookingID, StudentID, Price FROM booking WHERE BookingID = '$booking_id_param' LIMIT 1";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $generated_booking_id = $row['BookingID'];
        $estimated_price = $row['Price'];
        $student_id = $row['StudentID'];
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
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
    <a href="booking.php">Rooms</a>
    <a href="about.php">About</a>
    <a href="mybookings.php">My Bookings</a>
    <a href="login.php">Log In</a>
  </nav>
</header>

<!-- Payment Form -->
<div class="container_booking">
  <h2>Payment Details</h2>
  <form method="POST">
    <input type="hidden" id="BookingID" name="BookingID" value="<?php echo htmlspecialchars($generated_booking_id); ?>" />
    <input type="hidden" id="StudentID" name="StudentID" value="<?php echo htmlspecialchars($student_id); ?>" />

    <div class="form-group">
      <label for="PaymentID">Payment ID:</label>
      <input type="text" id="PaymentID" name="PaymentID" value="<?php echo $generated_payment_id; ?>" readonly />
    </div>

     <!-- Payment Date (hidden for backend) -->
    <input type="hidden" name="PaymentDate" value="<?php echo $payment_date; ?>" />

    <!-- Payment Date (visible for user) -->
    <div class="form-group">
      <label for="PaymentDate">Payment Date:</label>
      <input type="text" id="PaymentDate" value="<?php echo $payment_date; ?>" readonly />
    </div>

  
    <div class="form-group">
      <label for="Amount">Amount:</label>
      <input type="text" id="Amount" name="Amount" value="<?php echo htmlspecialchars($estimated_price); ?>" required />
    </div>

    <div class="form-group">
      <label for="PaymentMethod">Payment Method:</label>
      <select id="PaymentMethod" name="PaymentMethod" required>
        <option value="Cash">Cash</option>
      </select>
    </div>

    <div class="form-group">
      <label for="PaymentStatus">Payment Status:</label>
      <input type="text" id="PaymentStatus" name="PaymentStatus" value="Pending" required />
    </div>

    <div class="form-group">
      <label for="ReferenceCode">Reference Code:</label>
      <input type="text" id="ReferenceCode" name="ReferenceCode" value="<?php echo $generated_reference_code; ?>" readonly />
    </div>

    <button type="submit" class="btn">Submit</button>
    <button type="button" class="btn" onclick="window.history.back();">Back</button>
  </form>

  <?php echo $confirmation; ?>
</div>

</body>
</html>
