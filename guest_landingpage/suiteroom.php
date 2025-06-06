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

  <!-- Top Header Bar -->
  <div class="top-bar">
    <div class="logo-container">
      <img src="cvsu-logo.png" alt="CVSU Logo" class="cvsu-logo">
      <div class="university-info">
        <strong>CAVITE STATE UNIVERSITY – SILANG CAMPUS</strong>
        <span>Truth | Excellence | Service</span>
      </div>
    </div>
   <div class="top-icons">
      <i class="fas fa-map-marker-alt icon" title="Location"></i>
      <i class="fas fa-search icon" title="Search"></i>
      <i class="fas fa-envelope icon" title="Email"></i>
    </div>
  </div>

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
      <a href="index.php">Home</a>
      <a href="about.php">About</a>

      <div class="dropdown">
        <a href="#stay">Stay <span class="arrow">▼</span></a>
        <div class="dropdown-content">
          <a href="standardroom.php">Standard Room</a>
          <a href="deluxeroom.php">Deluxe Room</a>
          <a href="suiteroom.php">Suite Room</a>
        </div>
      </div>

      <div class="dropdown">
        <a href="#offers">Offers <span class="arrow">▼</span></a>
        <div class="dropdown-content">
          <a href="#">Weekend Promo</a>
          <a href="#">Summer Lovin' Package</a>
          <a href="#">Holiday Packages</a>
        </div>
      </div>

      <a href="contact.php">Contact</a>
    </nav>

    <button class="book-btn" onclick="window.location.href='booking.php'">BOOK NOW</button>
  </header>

</body>
</html>
