<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Villa Valore Hotel</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="logout.php">Log Out</a>
    <?php else: ?>
      <a href="login.php">Log In</a>
    <?php endif; ?>
  </nav>
</header>

<!-- Header -->
<div class="header-img">
  <div class="hotel-info">
    <h2>Hotel Villa Valore</h2>
    <div class="info-line"><span class="icon" data-icon="fa-location-dot"></span> CvSU Avenue Brgy. Biga 1, Silang, Cavite 4118</div>
    <div class="info-line"><span class="icon" data-icon="fa-phone"></span> (046) 888-9900</div>
    <div class="info-line"><span class="icon" data-icon="fa-link"></span> <a href="https://cvsu-silang.edu.ph/" target="_blank">cvsu-silang.edu.ph</a></div>
  </div>
</div>
<script>
  // Replace .icon[data-icon] with FontAwesome icons
  document.querySelectorAll('.icon[data-icon]').forEach(function(el) {
    const icon = el.getAttribute('data-icon');
    el.innerHTML = `<i class="fa-solid ${icon}" aria-hidden="true"></i>`;
  });
</script>

<!-- Booking Section -->
<style>
  .booking-panel {
    display: flex;
    gap: 24px;
    justify-content: space-between;
    align-items: stretch; /* Changed from flex-end to stretch for equal height */
    flex-wrap: wrap;
  }
  .booking-box, .cart-box {
    flex: 1 1 0;
    min-width: 180px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    padding: 18px 16px 16px 16px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    height: 100%; /* Ensures all boxes stretch equally */
    box-sizing: border-box;
  }
  .booking-box label, .cart-box p {
    margin-bottom: 8px;
    font-weight: 500;
  }
  .booking-box select, .booking-box input[type="date"] {
    margin-bottom: 8px;
    padding: 6px 8px;
    border-radius: 4px;
    border: 1px solid #ccc;
    font-size: 1em;
  }
  .cart-box {
    justify-content: center;
    align-items: flex-start;
  }
  @media (max-width: 900px) {
    .booking-panel {
      flex-direction: column;
      gap: 16px;
    }
    .booking-box, .cart-box {
      min-width: 0;
      width: 100%;
    }
  }
</style>
<div class="booking-panel">
  <!-- Guests -->
  <div class="booking-box">
    <label><i class="fa-solid fa-user-group"></i> Guests</label>
    <div style="display: flex; align-items: center; gap: 12px;">
      <div>
        <span>Adults</span><br>
        <button type="button" id="adult-minus" style="width:28px; height:28px; border-radius:50%; border:1px solid #ccc; background:#fff; color:#333; font-size:18px; display:inline-flex; align-items:center; justify-content:center;">
          <i class="fa-solid fa-minus"></i>
        </button>
        <span id="adult-count" style="margin:0 6px;">1</span>
        <button type="button" id="adult-plus" style="width:28px; height:28px; border-radius:50%; border:1.5px solid #27ae60; background:#eafaf1; color:#27ae60; font-size:18px; display:inline-flex; align-items:center; justify-content:center; box-shadow:0 1px 3px rgba(39,174,96,0.08);">
          <i class="fa-solid fa-plus"></i>
        </button>
      </div>
      <div>
        <span>Children</span><br>
        <button type="button" id="child-minus" style="width:28px; height:28px; border-radius:50%; border:1px solid #ccc; background:#fff; color:#333; font-size:18px; display:inline-flex; align-items:center; justify-content:center;">
          <i class="fa-solid fa-minus"></i>
        </button>
        <span id="child-count" style="margin:0 6px;">0</span>
        <button type="button" id="child-plus" style="width:28px; height:28px; border-radius:50%; border:1.5px solid #27ae60; background:#eafaf1; color:#27ae60; font-size:18px; display:inline-flex; align-items:center; justify-content:center; box-shadow:0 1px 3px rgba(39,174,96,0.08);">
          <i class="fa-solid fa-plus"></i>
        </button>
      </div>
    </div>
  </div>
  <script>
    // Guest counter logic
    const adultCount = document.getElementById('adult-count');
    const childCount = document.getElementById('child-count');
    document.getElementById('adult-minus').onclick = function() {
      let val = parseInt(adultCount.innerText);
      if (val > 1) { adultCount.innerText = val - 1; updateCart(); }
    };
    document.getElementById('adult-plus').onclick = function() {
      let val = parseInt(adultCount.innerText);
      if (val < 6) { adultCount.innerText = val + 1; updateCart(); }
    };
    document.getElementById('child-minus').onclick = function() {
      let val = parseInt(childCount.innerText);
      if (val > 0) { childCount.innerText = val - 1; updateCart(); }
    };
    document.getElementById('child-plus').onclick = function() {
      let val = parseInt(childCount.innerText);
      if (val < 3) { childCount.innerText = val + 1; updateCart(); }
    };
    // Patch updateCart to use new counters
    function updateCart() {
      let totalGuests = parseInt(adultCount.innerText) + parseInt(childCount.innerText);
      document.getElementById('cart-items').innerText = `${totalGuests} guest(s)`;
      document.getElementById('cart-total').innerText = totalGuests * 1000;
    }
    // Initialize cart
    updateCart();
  </script>

  <!-- Check-in -->
  <div class="booking-box">
    <label><i class="fa-solid fa-calendar-days"></i> Check-in</label>
    <input type="date" id="checkin" />
  </div>

  <!-- Check-out -->
  <div class="booking-box">
    <label><i class="fa-solid fa-calendar-check"></i> Check-out</label>
    <input type="date" id="checkout" />
  </div>

  <!-- Cart -->
  <div class="cart-box">
    <p>Your Cart: <strong id="cart-items">0 items</strong></p>
    <p>Total: ₱<strong id="cart-total">0</strong></p>
  </div>
</div>
<script>
  // Set minimum check-in date to today
  document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('checkin').min = today;
    document.getElementById('checkout').min = today;
  });

  // Update checkout min date when checkin changes
  document.getElementById('checkin').addEventListener('change', function() {
    const checkinDate = new Date(this.value);
    const minCheckout = new Date(checkinDate.getTime() + 86400000).toISOString().split('T')[0];
    document.getElementById('checkout').min = minCheckout;
    if (document.getElementById('checkout').value < minCheckout) {
      document.getElementById('checkout').value = minCheckout;
    }
  });
</script>
<br><br>

<div class="container">
  <h2>Select a Room</h2>

  <div class="controls">
    <select><option>View By: Rates</option></select>
    <select><option>Sort By: Recommended</option></select>
    <button class="filter-btn">Filters</button>
  </div>

  <!-- Room Cards -->
  <!-- Serenity Standard Room -->
  <div class="room-card">
    <img src="images/samplebedroom.png" alt="Standard Room" class="room-img"/>
    <div class="room-info">
      <a href="#" class="room-title">Serenity Standard Room</a>
      <p>1 King bed &nbsp; • &nbsp; Max Occupancy including children: 5 &nbsp; • &nbsp; 58 sq m</p>
      <p><strong>✔ Guaranteed with Credit Card</strong></p>
      <ul>
        <li>Non-Smoking</li>
        <li>Complimentary Wi-Fi</li>
        <li>Complimentary welcome amenity</li>
      </ul>
    </div>
    <div class="room-price">
      <p class="price">₱<?= number_format(8000) ?></p>
      <p class="per-night">Per Night<br><small>Including taxes and fees</small></p>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=standard'">BOOK NOW</button>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=standard'">RESERVE NOW</button>
    </div>
  </div>

  <!-- Haven Standard Room -->
  <div class="room-card">
    <img src="images/samplebedroom.png" alt="Standard Room" class="room-img"/>
    <div class="room-info">
      <a href="#" class="room-title">Haven Standard Room</a>
      <p>1 King bed &nbsp; • &nbsp; Max Occupancy including children: 5 &nbsp; • &nbsp; 58 sq m</p>
      <p><strong>✔ Guaranteed with Credit Card</strong></p>
      <ul>
        <li>Non-Smoking</li>
        <li>Complimentary Wi-Fi</li>
        <li>Complimentary welcome amenity</li>
      </ul>
    </div>
    <div class="room-price">
      <p class="price">₱<?= number_format(8000) ?></p>
      <p class="per-night">Per Night<br><small>Including taxes and fees</small></p>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=standard'">BOOK NOW</button>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=standard'">RESERVE NOW</button>
    </div>
  </div>

  <!-- Enchanted Chamber Room -->
  <div class="room-card">
    <img src="images/samplebedroom.png" alt="Standard Room" class="room-img"/>
    <div class="room-info">
      <a href="#" class="room-title">Enchanted Chamber Standard Room</a>
      <p>1 King bed &nbsp; • &nbsp; Max Occupancy including children: 5 &nbsp; • &nbsp; 58 sq m</p>
      <p><strong>✔ Guaranteed with Credit Card</strong></p>
      <ul>
        <li>Non-Smoking</li>
        <li>Complimentary Wi-Fi</li>
        <li>Complimentary welcome amenity</li>
      </ul>
    </div>
    <div class="room-price">
      <p class="price">₱<?= number_format(8000) ?></p>
      <p class="per-night">Per Night<br><small>Including taxes and fees</small></p>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=standard'">BOOK NOW</button>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=standard'">RESERVE NOW</button>
    </div>
  </div>

  <!-- Family Retreat Deluxe Room -->
  <div class="room-card">
    <img src="images/samplebedroom.png" alt="Deluxe Room" class="room-img"/>
    <div class="room-info">
      <a href="#" class="room-title">Family Retreat Deluxe Room</a>
      <p>1 King bed &nbsp; • &nbsp; Max Occupancy including children: 5 &nbsp; • &nbsp; 58 sq m</p>
      <p><strong>✔ Guaranteed with Credit Card</strong></p>
      <ul>
        <li>Non-Smoking</li>
        <li>Complimentary Wi-Fi</li>
        <li>Complimentary welcome amenity</li>
      </ul>
    </div>
    <div class="room-price">
      <p class="price">₱<?= number_format(10000) ?></p>
      <p class="per-night">Per Night<br><small>Including taxes and fees</small></p>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=deluxe'">BOOK NOW</button>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=deluxe'">RESERVE NOW</button>
    </div>
  </div>
  
  <!-- Premier Loft Deluxe Room -->
  <div class="room-card">
    <img src="images/samplebedroom.png" alt="Deluxe Room" class="room-img"/>
    <div class="room-info">
      <a href="#" class="room-title">Premier Loft	Deluxe Room</a>
      <p>1 King bed &nbsp; • &nbsp; Max Occupancy including children: 5 &nbsp; • &nbsp; 58 sq m</p>
      <p><strong>✔ Guaranteed with Credit Card</strong></p>
      <ul>
        <li>Non-Smoking</li>
        <li>Complimentary Wi-Fi</li>
        <li>Complimentary welcome amenity</li>
      </ul>
    </div>
    <div class="room-price">
      <p class="price">₱<?= number_format(10000) ?></p>
      <p class="per-night">Per Night<br><small>Including taxes and fees</small></p>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=deluxe'">BOOK NOW</button>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=deluxe'">RESERVE NOW</button>
    </div>
  </div>
  
  <!-- Luxe Escape Room -->
   <div class="room-card">
    <img src="images/samplebedroom.png" alt="Deluxe Room" class="room-img"/>
    <div class="room-info">
      <a href="#" class="room-title">Luxe Escape Room</a>
      <p>1 King bed &nbsp; • &nbsp; Max Occupancy including children: 5 &nbsp; • &nbsp; 58 sq m</p>
      <p><strong>✔ Guaranteed with Credit Card</strong></p>
      <ul>
        <li>Non-Smoking</li>
        <li>Complimentary Wi-Fi</li>
        <li>Complimentary welcome amenity</li>
      </ul>
    </div>
    <div class="room-price">
      <p class="price">₱<?= number_format(10000) ?></p>
      <p class="per-night">Per Night<br><small>Including taxes and fees</small></p>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=deluxe'">BOOK NOW</button>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=deluxe'">RESERVE NOW</button>
    </div>
  </div>

  <!-- Executive Suite Room -->
  <div class="room-card">
    <img src="images/samplebedroom.png" alt="Suite Room" class="room-img"/>
    <div class="room-info">
      <a href="#" class="room-title">Executive Suite Room</a>
      <p>1 King bed &nbsp; • &nbsp; Max Occupancy including children: 5 &nbsp; • &nbsp; 58 sq m</p>
      <p><strong>✔ Guaranteed with Credit Card</strong></p>
      <ul>
        <li>Non-Smoking</li>
        <li>Complimentary Wi-Fi</li>
        <li>Complimentary welcome amenity</li>
      </ul>
    </div>
    <div class="room-price">
      <p class="price">₱<?= number_format(12000) ?></p>
      <p class="per-night">Per Night<br><small>Including taxes and fees</small></p>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=suite'">BOOK NOW</button>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=suite'">RESERVE NOW</button>
    </div>
  </div>

  <!-- Grand Villa Suite Room -->
  <div class="room-card">
    <img src="images/samplebedroom.png" alt="Suite Room" class="room-img"/>
    <div class="room-info">
      <a href="#" class="room-title">Grand Villa Suite Room</a>
      <p>1 King bed &nbsp; • &nbsp; Max Occupancy including children: 5 &nbsp; • &nbsp; 58 sq m</p>
      <p><strong>✔ Guaranteed with Credit Card</strong></p>
      <ul>
        <li>Non-Smoking</li>
        <li>Complimentary Wi-Fi</li>
        <li>Complimentary welcome amenity</li>
      </ul>
    </div>
    <div class="room-price">
      <p class="price">₱<?= number_format(12000) ?></p>
      <p class="per-night">Per Night<br><small>Including taxes and fees</small></p>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=suite'">BOOK NOW</button>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=suite'">RESERVE NOW</button>
    </div>
  </div>

  <!-- Grand Villa Suite Room -->
  <div class="room-card">
    <img src="images/samplebedroom.png" alt="Suite Room" class="room-img"/>
    <div class="room-info">
      <a href="#" class="room-title">Royal Haven Suite Room</a>
      <p>1 King bed &nbsp; • &nbsp; Max Occupancy including children: 5 &nbsp; • &nbsp; 58 sq m</p>
      <p><strong>✔ Guaranteed with Credit Card</strong></p>
      <ul>
        <li>Non-Smoking</li>
        <li>Complimentary Wi-Fi</li>
        <li>Complimentary welcome amenity</li>
      </ul>
    </div>
    <div class="room-price">
      <p class="price">₱<?= number_format(12000) ?></p>
      <p class="per-night">Per Night<br><small>Including taxes and fees</small></p>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=suite'">BOOK NOW</button>
      <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&room=suite'">RESERVE NOW</button>
    </div>
  </div>

  <div class="view-more">
    <a href="#">View More Rooms ▾</a>
  </div>
</div>

<script>
  const adults = document.getElementById('adults');
  const children = document.getElementById('children');

  adults.addEventListener('change', updateCart);
  children.addEventListener('change', updateCart);

  function updateCart() {
    let totalGuests = parseInt(adults.value) + parseInt(children.value);
    document.getElementById('cart-items').innerText = `${totalGuests} guest(s)`;
    document.getElementById('cart-total').innerText = totalGuests * 1000;
  }

  document.getElementById('checkin').addEventListener('change', () => {
    const checkinDate = new Date(document.getElementById('checkin').value);
    const checkoutInput = document.getElementById('checkout');
    checkoutInput.min = new Date(checkinDate.getTime() + 86400000).toISOString().split('T')[0];
  });

  document.querySelector('.filter-btn').addEventListener('click', () => {
    alert("Filter functionality not implemented yet.");
  });
</script>

</body>
</html>
