<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Villa Valore Hotel</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <style>
    .hero-section {
      background: url('images/samplebedroom.png') center/cover no-repeat;
      min-height: 350px;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      color: #fff;
      text-align: center;
    }
    .hero-overlay {
      background: rgba(1,128,0,0.7);
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      z-index: 1;
    }
    .hero-content {
      position: relative;
      z-index: 2;
      max-width: 600px;
      margin: 0 auto;
    }
    .hero-content h1 { font-size: 2.8em; margin-bottom: 0.3em; }
    .hero-content p { font-size: 1.3em; margin-bottom: 1.2em; }
    .hero-content .book-btn { font-size: 1.2em; padding: 14px 36px; }
    .rooms-section {
      background: #fff;
      padding: 50px 0 30px 0;
      text-align: center;
    }
    .rooms-list {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 32px;
      margin-top: 30px;
    }
    .room-card {
      background: #f8f8f8;
      border-radius: 12px;
      box-shadow: 0 2px 12px #e0e0e0;
      width: 320px;
      padding: 0 0 24px 0;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      align-items: center;
      transition: box-shadow 0.2s;
    }
    .room-card:hover { box-shadow: 0 6px 24px #c0eac0; }
    .room-img { width: 100%; height: 180px; object-fit: cover; }
    .room-title { font-size: 1.3em; color: #018000; margin: 18px 0 8px 0; }
    .room-desc { color: #444; font-size: 1em; margin-bottom: 12px; }
    .room-features { color: #666; font-size: 0.98em; margin-bottom: 10px; }
    .room-card .btn { margin-top: 10px; }
    .about-section {
      background: #e6f8ec;
      padding: 40px 0 30px 0;
      text-align: center;
    }
    .about-section h2 { color: #018000; margin-bottom: 18px; }
    .about-section p { max-width: 700px; margin: 0 auto 18px auto; color: #333; }
    .about-amenities {
      display: flex; flex-wrap: wrap; justify-content: center; gap: 30px; margin-top: 18px;
    }
    .amenity { background: #fff; border-radius: 8px; padding: 18px 28px; color: #018000; font-weight: 600; box-shadow: 0 1px 6px #e0e0e0; }
    .footer {
      background: #018000;
      color: #fff;
      padding: 32px 0 18px 0;
      text-align: center;
      margin-top: 40px;
    }
    .footer .footer-links { margin-bottom: 10px; }
    .footer .footer-links a { color: #fff; margin: 0 12px; text-decoration: none; font-weight: 500; }
    .footer .footer-links a:hover { text-decoration: underline; }
    .footer .footer-social { margin-top: 10px; }
    .footer .footer-social i { margin: 0 8px; font-size: 1.3em; color: #fff; }
    @media (max-width: 900px) {
      .rooms-list { flex-direction: column; align-items: center; }
      .about-amenities { flex-direction: column; align-items: center; }
    }
  </style>
</head>
<body>
  <!-- Top Header Bar -->
  <div class="top-bar">
    <div class="logo-container">
      <img src="images/cvsu-logo.png" alt="CVSU Logo" class="cvsu-logo">
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
      <a href="contact.php">Contact</a>
    </nav>
    <button class="book-btn" onclick="window.location.href='booking.php'">BOOK NOW</button>
  </header>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="hero-content">
      <h1>Welcome to Villa Valore Hotel</h1>
      <p>Experience comfort, elegance, and exceptional service in the heart of Silang, Cavite. Your perfect stay awaits!</p>
      <button class="book-btn" onclick="window.location.href='booking.php'">Book Your Stay</button>
    </div>
  </section>

  <!-- Room Features Section -->
  <section class="rooms-section" id="rooms">
    <h2>Our Rooms</h2>
    <div class="rooms-list">
      <div class="room-card">
        <img src="images/samplebedroom.png" alt="Standard Room" class="room-img">
        <div class="room-title">Standard Room</div>
        <div class="room-desc">A calm and inviting space designed for relaxation. Perfect for solo travelers or small families.</div>
        <div class="room-features">1 Queen bed • Up to 2 guests • 28 sq m</div>
        <a href="standardroom.php" class="btn green">View Details</a>
      </div>
      <div class="room-card">
        <img src="images/samplebedroom.png" alt="Deluxe Room" class="room-img">
        <div class="room-title">Deluxe Room</div>
        <div class="room-desc">Spacious and welcoming, tailor-made for families. Enjoy quality bonding time in style.</div>
        <div class="room-features">1 King bed, 1 Sofa bed • Up to 4 guests • 38 sq m</div>
        <a href="deluxeroom.php" class="btn green">View Details</a>
      </div>
      <div class="room-card">
        <img src="images/samplebedroom.png" alt="Suite Room" class="room-img">
        <div class="room-title">Suite Room</div>
        <div class="room-desc">Luxury and elegance for your special occasions. The ultimate comfort for up to 6 guests.</div>
        <div class="room-features">2 King beds, 1 Sofa bed • Up to 6 guests • 50 sq m</div>
        <a href="suiteroom.php" class="btn green">View Details</a>
      </div>
    </div>
  </section>

  <!-- About/Hotel Information Section -->
  <section class="about-section" id="about">
    <h2>About Villa Valore Hotel</h2>
    <p>
      Villa Valore Hotel is your home away from home in Silang, Cavite. We offer modern amenities, elegant rooms, and a warm, welcoming atmosphere for business and leisure travelers alike. Our hotel is conveniently located near Cavite State University and major attractions.
    </p>
    <div class="about-amenities">
      <div class="amenity"><i class="fa fa-wifi"></i> Free WiFi</div>
      <div class="amenity"><i class="fa fa-car"></i> Free Parking</div>
      <div class="amenity"><i class="fa fa-utensils"></i> In-house Dining</div>
      <div class="amenity"><i class="fa fa-swimmer"></i> Swimming Pool</div>
      <div class="amenity"><i class="fa fa-concierge-bell"></i> 24/7 Front Desk</div>
      <div class="amenity"><i class="fa fa-shield-alt"></i> Secure & Safe</div>
    </div>
  </section>

  <!-- Contact & Location Section -->
  <section class="about-section" id="contact" style="background:#fff;">
    <h2>Contact & Location</h2>
    <p>
      <b>Address:</b> BIGA I, SILANG, CAVITE<br>
      <b>Email:</b> <a href="mailto:villavalorehotel@gmail.com">villavalorehotel@gmail.com</a><br>
      <b>Phone:</b> 0912-345-6789<br>
      <b>Landmark:</b> Near Cavite State University - Silang Campus
    </p>
    <p>
      <a href="https://goo.gl/maps/..." target="_blank" class="btn green">View on Google Maps</a>
    </p>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-links">
      <a href="index.php">Home</a> |
      <a href="about.php">About</a> |
      <a href="booking.php">Book Now</a> |
      <a href="contact.php">Contact</a>
    </div>
    <div class="footer-social">
      <a href="#"><i class="fab fa-facebook-f"></i></a>
      <a href="#"><i class="fab fa-instagram"></i></a>
      <a href="#"><i class="fab fa-twitter"></i></a>
    </div>
    <div style="margin-top:10px;">&copy; <?php echo date('Y'); ?> Villa Valore Hotel. All rights reserved.</div>
  </footer>
</body>
</html>
