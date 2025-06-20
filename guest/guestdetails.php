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
  // Do NOT overwrite existing session values on POST
  if (!isset($_SESSION['StudentID'])) $_SESSION['StudentID'] = $_POST['StudentID'] ?? '';
  if (!isset($_SESSION['FirstName'])) $_SESSION['FirstName'] = $_POST['FirstName'] ?? '';
  if (!isset($_SESSION['LastName'])) $_SESSION['LastName'] = $_POST['LastName'] ?? '';
  if (!isset($_SESSION['Email'])) $_SESSION['Email'] = $_POST['Email'] ?? '';

  $_SESSION['Gender'] = $_POST['Gender'];
  $_SESSION['PhoneNumber'] = $_POST['PhoneNumber'];
  $_SESSION['Address'] = $_POST['Address'];
  $_SESSION['Nationality'] = $_POST['Nationality'];
  $_SESSION['Birthdate'] = $_POST['Birthdate'];

  // Always use session values for these fields
  $student_id = isset($_SESSION['StudentID']) ? $conn->real_escape_string($_SESSION['StudentID']) : '';
  $first_name = isset($_SESSION['FirstName']) ? $conn->real_escape_string($_SESSION['FirstName']) : '';
  $last_name = isset($_SESSION['LastName']) ? $conn->real_escape_string($_SESSION['LastName']) : '';
  $email = isset($_SESSION['Email']) ? $conn->real_escape_string($_SESSION['Email']) : '';

  $booking_id = $conn->real_escape_string($_POST['BookingID']);
  $gender = $conn->real_escape_string($_POST['Gender']);
  $phone_number = $conn->real_escape_string($_POST['PhoneNumber']);
  $address = $conn->real_escape_string($_POST['Address']);
  $nationality = $conn->real_escape_string($_POST['Nationality']);
  $birthdate = $_POST['Birthdate'];

  if (
    empty($student_id) ||
    empty($booking_id) ||
    empty($first_name) ||
    empty($last_name) ||
    empty($gender) ||
    empty($phone_number) ||
    empty($address) ||
    empty($email) ||
    empty($nationality) ||
    empty($birthdate)
  ) {
    $confirmation = "<p style='color: red;'>All fields are required.</p>";
  } else {
    $sql = "INSERT INTO student (StudentID, BookingID, FirstName, LastName, Gender, PhoneNumber, Address, Email, Nationality, Birthdate)
        VALUES ('$student_id', '$booking_id', '$first_name', '$last_name', '$gender', '$phone_number', '$address', '$email', '$nationality', '$birthdate')";

    if ($conn->query($sql)) {
      // Clear session values after successful insert
      unset($_SESSION['StudentID'], $_SESSION['FirstName'], $_SESSION['LastName'], $_SESSION['Gender'], $_SESSION['PhoneNumber'], $_SESSION['Address'], $_SESSION['Email'], $_SESSION['Nationality'], $_SESSION['Birthdate']);
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
      value="<?php echo isset($_SESSION['StudentID']) ? htmlspecialchars($_SESSION['StudentID']) : ''; ?>" />
  </div>

  <div class="form-group">
    <label for="FirstName">First Name:</label>
    <input type="text" id="FirstName" name="FirstName" required 
      value="<?php echo isset($_SESSION['FirstName']) ? htmlspecialchars($_SESSION['FirstName']) : ''; ?>" />
  </div>

  <div class="form-group">
    <label for="LastName">Last Name:</label>
    <input type="text" id="LastName" name="LastName" required 
      value="<?php echo isset($_SESSION['LastName']) ? htmlspecialchars($_SESSION['LastName']) : ''; ?>" />
  </div>

  <div class="form-group">
    <label for="Gender">Gender:</label>
    <select id="Gender" name="Gender" required>
    <option value="">Select Gender</option>
    <option value="Male" <?php if (isset($_SESSION['Gender']) && $_SESSION['Gender'] == 'Male') echo 'selected'; ?>>Male</option>
    <option value="Female" <?php if (isset($_SESSION['Gender']) && $_SESSION['Gender'] == 'Female') echo 'selected'; ?>>Female</option>
    <option value="Prefer not to say" <?php if (isset($_SESSION['Gender']) && $_SESSION['Gender'] == 'Prefer not to say') echo 'selected'; ?>>Prefer not to say</option>
    <option value="Other" <?php if (isset($_SESSION['Gender']) && $_SESSION['Gender'] == 'Other') echo 'selected'; ?>>Other</option>
    </select>
  </div>

  <div class="form-group">
    <label for="PhoneNumber">Phone Number:</label>
    <input type="text" id="PhoneNumber" name="PhoneNumber" required 
      value="<?php echo isset($_SESSION['PhoneNumber']) ? htmlspecialchars($_SESSION['PhoneNumber']) : ''; ?>" />
  </div>

  <div class="form-group">
    <label for="Address">Address:</label>
    <input type="text" id="Address" name="Address" required 
      value="<?php echo isset($_SESSION['Address']) ? htmlspecialchars($_SESSION['Address']) : ''; ?>" />
  </div>

  <div class="form-group">
    <label for="Email">Email:</label>
    <input type="email" id="Email" name="Email" required 
      value="<?php echo isset($_SESSION['Email']) ? htmlspecialchars($_SESSION['Email']) : ''; ?>" />
  </div>

  <div class="form-group">
    <label for="Nationality">Nationality:</label>
    <input type="text" id="Nationality" name="Nationality" required 
      value="<?php echo isset($_SESSION['Nationality']) ? htmlspecialchars($_SESSION['Nationality']) : ''; ?>" />
  </div>

  <div class="form-group">
    <label for="Birthdate">Birthdate:</label>
    <input type="date" id="Birthdate" name="Birthdate" required 
      value="<?php echo isset($_SESSION['Birthdate']) ? htmlspecialchars($_SESSION['Birthdate']) : ''; ?>" />
  </div>

  <button type="submit" class="btn">Submit</button>
  <button type="button" class="btn" onclick="window.location.href='booking.php';">Back</button>

  </form>

  <?php echo $confirmation; ?>
</div>

</body>
</html>
