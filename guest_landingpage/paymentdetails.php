<?php include 'connections.php';
$confirmation = "";
$generated_booking_id = "";
$estimated_price = "";
$booking_date = date("Y-m-d");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($_POST['Payment ID']) ||
        empty($_POST['BookingID']) ||
        empty($_POST['Amount']) ||
        empty($_POST['PaymentStatus']) ||
        empty($_POST['PaymentDate']) ||
        empty($_POST['PaymentMethod']) ||
        empty($_POST['Discount']) ||
        empty($_POST['TotalBill']) ||
        empty($_POST['ReferenceCode'])
    ) {
        $confirmation = "<p style='color: red;'>All fields are required.</p>";
    } else {
        $payment_id = $conn->real_escape_string($_POST['PaymentID']);
        $booking_id = $conn->real_escape_string($_POST['BookingID']);
        $amount = $conn->real_escape_string($_POST['Amount']);
        $payment_status = $conn->real_escape_string($_POST['PaymentStatus']);
        $payment_date = $conn->real_escape_string($_POST['PaymentDate']);
        $payment_method = $conn->real_escape_string($_POST['PaymentMethod']);
        $discount = $conn->real_escape_string($_POST['Discount']);
        $total_bill = $conn->real_escape_string($_POST['TotalBill']);
        $reference_code = $conn->real_escape_string($_POST['ReferenceCode']);

        $sql = "INSERT INTO payment (PaymentID, BookingID, Amount, PaymentStatus, PaymentDate, PaymentMethod, Discount, TotalBill, ReferenceCode)
                VALUES ('$payment_id', '$booking_id', '$amount', '$payment_status', '$payment_date', '$payment_method', '$discount', '$total_bill', '$reference_code')";

        if ($conn->query($sql)) {
            $conn->close();
            header("Location: paymentdetails.php?BookingID=$booking_id");
            exit();
        } else {
            $confirmation = "<p style='color: red;'>Error: " . $conn->error . "</p>";
        }
    }
}

// Fetch booking ID and estimated price if booking ID is passed via GET
if (isset($_GET['BookingID'])) {
    $booking_id_param = $conn->real_escape_string($_GET['BookingID']);
    $query = "SELECT BookingID, Price FROM booking WHERE BookingID = '$booking_id_param' LIMIT 1";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $generated_booking_id = $row['BookingID'];
        $estimated_price = $row['Price'];
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
      <a href="signin.php">Sign In</a>
    </nav>
  </header>

<!-- Payment Details Form -->
<div class="container_booking">
  <h2>Payment Details</h2>
  <form method="POST">
    <div class="form-group">
      <label for="BookingID">Booking ID:</label>
      <input type="hidden" id="BookingID" name="BookingID" value="<?php echo $generated_booking_id; ?>" />
    </div>
    <div class="form-group">
      <label for="StudentID">Student ID:</label>
      <input type="hidden" id="StudentID" name="StudentID" value="<?php echo $student_id; ?>" />
    </div>

    <div class="form-group">
      <label for="PaymentID">Payment ID:</label>
      <input type="text" id="PaymentID" name="PaymentID" required />
    </div>

    <div class="form-group">
      <label for="Amount">Amount:</label>
      <input type="text" id="Amount" name="Amount" required />
    </div>

    <div class="form-group">
      <label for="PaymentStatus">Payment Status:</label>
      <input type="text" id="PaymentStatus" name="PaymentStatus" required />
    </div>

    <div class="form-group">
      <label for="PaymentDate">Payment Date:</label>
      <input type="date" id="PaymentDate" name="PaymentDate" required />
    </div>

    <div class="form-group">
      <label for="PaymentMethod">Payment Method:</label>
      <select id="PaymentMethod" name="PaymentMethod" required>
        <option value="">Select Payment Method</option>
        <option value="Cash">Cash</option>
        <option value="Debit Card">Debit Card</option>
        <option value="PayPal">Online</option>
      </select>
    </div>

    <div class="form-group">
      <label for="Discount">Discount:</label>
      <input type="text" id="Discount" name="Discount" required />
    </div>

    <div class="form-group">
      <label for="TotalBill">Total Bill:</label>
      <input type="text" id="TotalBill" name="TotalBill" required />
    </div>

    <div class="form-group">
      <label for="ReferenceCode">Reference Code:</label>
      <input type="text" id="ReferenceCode" name="ReferenceCode" required />
    </div>

    <button type="submit" class="btn">Submit</button>
    <button type="button" class="btn" onclick="window.history.back();">Back</button>
  </form>

  <?php echo $confirmation; ?>
</div>

</body>
</html>
