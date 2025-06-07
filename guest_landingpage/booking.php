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

    <button class="book-btn" onclick="window.location.href='booking.php'">BOOK NOW</button>
  </header>

  <!-- Header -->
    <div class="header-img">
      <div class="hotel-info">
        <h2>Hotel Villa Valore</h2>
        <div class="info-line"><i>📍</i> CvSU Avenue Brgy. Biga 1, Silang, Cavite 4118</div>
        <div class="info-line"><i>📞</i> (046) 888-9900</div>
        <div class="info-line"><i>🔗</i> <a href="https://cvsu-silang.edu.ph/" target="_blank">cvsu-silang.edu.ph</a></div>
      </div>
    </div>

  <!-- Booking Section -->
  <div class="booking-panel">
    <!-- Guests -->
    <div class="booking-box">
      <label>👥 Guests</label>
      <select id="adults">
        <option value="1">1 Adult</option>
        <option value="2">2 Adults</option>
        <option value="3">3 Adults</option>
      </select>
      <select id="children">
        <option value="0">0 Children</option>
        <option value="1">1 Child</option>
        <option value="2">2 Children</option>
      </select>
    </div>

    <!-- Check-in -->
    <div class="booking-box">
      <label>📅 Check-in</label>
      <input type="date" id="checkin" />
    </div>

    <!-- Check-out -->
    <div class="booking-box">
      <label>📅 Check-out</label>
      <input type="date" id="checkout" />
    </div>

    <!-- Cart -->
    <div class="cart-box">
      <p>Your Cart: <strong id="cart-items">0 items</strong></p>
      <p>Total: ₱<strong id="cart-total">0</strong></p>
    </div>
  </div> <br><br>

   <div class="container">
    <h2>Select a Room</h2>
    
    <div class="controls">
      <select>
        <option>View By: Rates</option>
      </select>
      <select>
        <option>Sort By: Recommended</option>
      </select>
      <button class="filter-btn">Filters</button>
    </div>

    <!-- Room Card -->
    <div class="room-card">
        
      <!-- Standard Room -->
      <img src="samplebedroom.png" alt="Standard Room" class="room-img"/>
      <div class="room-info">
        <a href="#" class="room-title">Standard Room</a>
        <p>1 King bed &nbsp; • &nbsp; Max Occupancy including children: 5 &nbsp; • &nbsp; 58 sq m</p>
        <p><strong>✔ Guaranteed with Credit Card</strong></p>
        <ul>
          <li>Non-Smoking</li>
          <li>Complimentary Wi-Fi</li>
          <li>Complimentary welcome amenity</li>
        </ul>
      </div>
      <div class="room-price">
        <p class="price">₱8,000</p>
        <p class="per-night">Per Night<br><small>Including taxes and fees</small></p>
        <button class="btn green" onclick="window.location.href='booknow.php'">BOOK NOW</button>
        <button class="btn green" onclick="window.location.href='reservenow.php'">RESERVE NOW</button>
      </div> 
    </div>

      <!-- Deluxe Room -->
<div class="room-card">
  <img src="samplebedroom.png" alt="Deluxe Room" class="room-img"/>
  <div class="room-info">
    <a href="#" class="room-title">Deluxe Room</a>
    <p>1 King bed &nbsp; • &nbsp; Max Occupancy including children: 5 &nbsp; • &nbsp; 58 sq m</p>
    <p><strong>✔ Guaranteed with Credit Card</strong></p>
    <ul>
      <li>Non-Smoking</li>
      <li>Complimentary Wi-Fi</li>
      <li>Complimentary welcome amenity</li>
    </ul>
  </div>
  <div class="room-price">
    <p class="price">₱10,000</p>
    <p class="per-night">Per Night<br><small>Including taxes and fees</small></p>
    <button class="btn green" onclick="window.location.href='booknow.php'">BOOK NOW</button>
    <button class="btn green" onclick="window.location.href='reservenow.php'">RESERVE NOW</button>
  </div>
</div>

<!-- Suite Room -->
<div class="room-card">
  <img src="samplebedroom.png" alt="Suite Room" class="room-img"/>
  <div class="room-info">
    <a href="#" class="room-title">Suite Room</a>
    <p>1 King bed &nbsp; • &nbsp; Max Occupancy including children: 5 &nbsp; • &nbsp; 58 sq m</p>
    <p><strong>✔ Guaranteed with Credit Card</strong></p>
    <ul>
      <li>Non-Smoking</li>
      <li>Complimentary Wi-Fi</li>
      <li>Complimentary welcome amenity</li>
    </ul>
  </div>
  <div class="room-price">
    <p class="price">₱12,000</p>
    <p class="per-night">Per Night<br><small>Including taxes and fees</small></p>
    <button class="btn green" onclick="window.location.href='booknow.php'">BOOK NOW</button>
    <button class="btn green" onclick="window.location.href='reservenow.php'">RESERVE NOW</button>
  </div>
</div>

    <div class="view-more">
      <a href="#">View More Rooms ▾</a>
    </div>
  </div>

  <!-- Optional: Room list will go here -->

  <script>
    // Example logic for updating guest selection display
    const adults = document.getElementById('adults');
    const children = document.getElementById('children');

    adults.addEventListener('change', updateCart);
    children.addEventListener('change', updateCart);

    function updateCart() {
      let totalGuests = parseInt(adults.value) + parseInt(children.value);
      document.getElementById('cart-items').innerText = `${totalGuests} guest(s)`;
      document.getElementById('cart-total').innerText = totalGuests * 1000; // ₱1000 per guest for demo
    }

    // Optionally add check-in/check-out validation
    document.getElementById('checkin').addEventListener('change', () => {
      const checkinDate = new Date(document.getElementById('checkin').value);
      const checkoutInput = document.getElementById('checkout');
      checkoutInput.min = new Date(checkinDate.getTime() + 86400000).toISOString().split('T')[0];
    });
    
    // Filter button functionality
    document.querySelector('.filter-btn').addEventListener('click', () => {
    alert("Filter functionality not implemented yet.");
    });
  </script>
</body>
</html>