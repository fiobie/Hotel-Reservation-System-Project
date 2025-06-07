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

// Form Submission Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($_POST['StudentID']) ||
        empty($_POST['BookingID']) ||
        empty($_POST['FirstName']) ||
        empty($_POST['LastName']) ||
        empty($_POST['Gender']) ||
        empty($_POST['PhoneNumber']) ||
        empty($_POST['Address']) ||
        empty($_POST['Email']) ||
        empty($_POST['Nationality']) ||
        empty($_POST['Birthdate'])
    ) {
        $confirmation = "<p style='color: red;'>All fields are required.</p>";
    } else {
        $student_id = $conn->real_escape_string($_POST['StudentID']);
        $booking_id = $conn->real_escape_string($_POST['BookingID']);
        $first_name = $conn->real_escape_string($_POST['FirstName']);
        $last_name = $conn->real_escape_string($_POST['LastName']);
        $gender = $conn->real_escape_string($_POST['Gender']);
        $phone_number = $conn->real_escape_string($_POST['PhoneNumber']);
        $address = $conn->real_escape_string($_POST['Address']);
        $email = $conn->real_escape_string($_POST['Email']);
        $nationality = $conn->real_escape_string($_POST['Nationality']);
        $birthdate = $_POST['Birthdate'];

        $sql = "INSERT INTO student (StudentID, BookingID, FirstName, LastName, Gender, PhoneNumber, Address, Email, Nationality, Birthdate)
                VALUES ('$student_id', '$booking_id', '$first_name', '$last_name', '$gender', '$phone_number', '$address', '$email', '$nationality', '$birthdate')";

        if ($conn->query($sql)) {
            $conn->close();
            header("Location: paymentdetails.php?BookingID=$booking_id");
            exit();
        } else {
            $confirmation = "<p style='color: red;'>Error: " . $conn->error . "</p>";
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
  <link rel="stylesheet" href="style.css">
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
  
  <!-- Optional: Debug -->
  <!-- <p>Debug Booking ID: <?php echo htmlspecialchars($generated_booking_id); ?></p> -->

  <form method="POST">
    <div class="form-group">
      <label for="BookingID">Booking ID:</label>
      <input type="text" id="BookingID_display" value="<?php echo $generated_booking_id; ?>" readonly class="readonly-field" />
      <input type="hidden" id="BookingID" name="BookingID" value="<?php echo $generated_booking_id; ?>" />
    </div>

    <div class="form-group">
      <label for="StudentID">Student ID:</label>
      <input type="text" id="StudentID" name="StudentID" required />
    </div>

    <div class="form-group">
      <label for="FirstName">First Name:</label>
      <input type="text" id="FirstName" name="FirstName" required />
    </div>

    <div class="form-group">
      <label for="LastName">Last Name:</label>
      <input type="text" id="LastName" name="LastName" required />
    </div>

    <div class="form-group">
      <label for="Gender">Gender:</label>
      <select id="Gender" name="Gender" required>
        <option value="">Select Gender</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Other">Other</option>
      </select>
    </div>

    <div class="form-group">
      <label for="PhoneNumber">Phone Number:</label>
      <input type="text" id="PhoneNumber" name="PhoneNumber" required />
    </div>

    <div class="form-group">
      <label for="Address">Address:</label>
      <input type="text" id="Address" name="Address" required />
    </div>

    <div class="form-group">
      <label for="Email">Email:</label>
      <input type="email" id="Email" name="Email" required />
    </div>

    <div class="form-group">
      <label for="Nationality">Nationality:</label>
      <input type="text" id="Nationality" name="Nationality" required />
    </div>

    <div class="form-group">
      <label for="Birthdate">Birthdate:</label>
      <input type="date" id="Birthdate" name="Birthdate" required />
    </div>

    <button type="submit" class="btn">Submit</button>
    <button type="button" class="btn" onclick="window.history.back();">Back</button>
  </form>

  <?php echo $confirmation; ?>
</div>

</body>
</html>
