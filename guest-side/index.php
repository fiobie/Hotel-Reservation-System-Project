<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /> 
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GUEST VIEW</title>

  <!-- Google Fonts: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

  <!-- Font Awesome for Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Poppins", sans-serif;
      font-weight: bold;
    }

    :root {
      --white-color: #fff;
      --yellow-color: #f3d400;
      --black-color: #000000;
      --orange-color: #ff8200;
      --green-color: #008000;

      --font-size-s: 0.9rem;
      --font-size-n: 1rem;
      --font-size-m: 1.12rem;
      --font-size-l: 1.5rem;
      --font-size-xl: 2rem;
      --font-size-xxl: 2.3rem;

      --border-radius-s: 8px;
      --border-radius-m: 30px;
      --border-radius-circle: 50%;

      --site-max-width: 1300px;
    }

    ul {
      list-style: none;
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    button {
      cursor: pointer;
      border: none;
      background: none;
      color: inherit;
    }

    img {
      width: 100%;
    }

    .section-content {
      margin: 0 auto;
      padding: 0 20px;
      max-width: var(--site-max-width);
    }

    /* TOP CONTACT BAR */
    .top-bar {
      background-color: var(--green-color);
      color: var(--white-color);
      font-size: 15px;
      padding: 10px 20px;
    }

    .top-bar .top-bar-content {
      display: flex;
      justify-content: flex-start;
      align-items: center;
      gap: 30px;
      max-width: var(--site-max-width);
      margin: auto;
      flex-wrap: wrap;
    }

    .top-bar .contact-left {
      display: flex;
      gap: 30px;
      align-items: center;
      flex-wrap: wrap;
    }

    .top-bar i {
      margin-right: 8px;
    }

    /* HEADER SECTION - all fonts green */
    header,
    header * {
      color: var(--green-color);
    }

    header a {
      color: var(--green-color);
    }

    header {
      background: var(--white-color);
    }

    header .navbar {
      display: flex;
      padding: 20px;
      align-items: center;
      justify-content: space-between;
    }

    .navbar .nav_logo {
      display: flex;
      align-items: center;
      gap: 15px;
      text-decoration: none;
    }

    .navbar .nav_logo img {
      height: 60px;
      width: auto;
    }

    .navbar .logo-texts {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }

    .logo-texts .main-title {
      font-size: 30px;
    }

    .logo-texts .subtitle,
    .logo-texts .motto {
      font-size: 11px;
    }

    .navbar .nav-menu {
      display: flex;
      gap: 10px;
      position: relative;
    }

    .navbar .nav-menu .nav-link {
      padding: 10px 18px;
      font-size: var(--font-size-m);
      border-radius: var(--border-radius-m);
      transition: 0.3s ease;
      position: relative;
    }

    .nav-item.dropdown > .nav-link::after {
      content: " ▼";
      font-size: 0.7em;
      margin-left: 5px;
    }

    .navbar .nav-menu .nav-link:hover {
      color: var(--white-color);
      background: var(--green-color);
    }

    .nav-item.dropdown {
      position: relative;
    }

    .dropdown-menu {
      display: none;
      position: absolute;
      top: 100%;
      left: 0;
      background-color: var(--white-color);
      border-radius: var(--border-radius-s);
      padding: 10px 0;
      min-width: 160px;
      z-index: 999;
      flex-direction: column;
    }

    .dropdown-menu .dropdown-link {
      padding: 10px 20px;
      color: var(--black-color);
      font-size: var(--font-size-m);
    }

    .dropdown-menu .dropdown-link:hover {
      background-color: transparent;
      color: var(--black-color);
    }

    .nav-item.dropdown:hover .dropdown-menu {
      display: flex;
    }

    .bedroom-reservation {
      position: relative;
      background-image: url('https://images.unsplash.com/photo-1501117716987-c8d7138e4e9b?auto=format&fit=crop&w=1350&q=80');
      background-size: cover;
      background-position: center;
      padding: 100px 20px;
      text-align: center;
      color: var(--white-color);
    }

    .bedroom-reservation::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.5);
      z-index: 1;
    }

    .reservation-content {
      position: relative;
      max-width: 800px;
      margin: 0 auto;
      z-index: 2;
    }

    .welcome-message {
      font-size: var(--font-size-xxl);
      margin-bottom: 30px;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.7);
      color: var(--white-color);
    }

    .book-button {
      font-size: var(--font-size-m);
      padding: 12px 30px;
      border: 2px solid var(--white-color);
      color: var(--white-color);
      background-color: transparent;
      border-radius: var(--border-radius-s);
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    .book-button:hover {
      background-color: var(--green-color);
      color: var(--white-color);
    }
  </style>
</head>
<body>
  <!-- TOP CONTACT BAR -->
  <div class="top-bar">
    <div class="top-bar-content">
      <div class="contact-left">
        <div class="address">
          <i class="fas fa-home"></i><span style="font-weight: normal;">Biga 1, Silang, Cavite, 4118</span>
        </div>
        <div class="email">
          <i class="fas fa-envelope"></i><span style="font-weight: normal;">cvsusilang@cvsu.edu.ph</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Header / Navbar -->
  <header>
    <nav class="navbar section-content">
      <!-- Logo and Hotel Info -->
      <a href="#" class="nav_logo">
        <img src="a71061fe-9eee-4a7d-8e02-94c0fdcb959f.png" />
        <div class="logo-texts">
          <span class="main-title">Villa Valore Hotel</span>
          <span class="subtitle">CAVITE STATE UNIVERSITY - SILANG CAMPUS</span>
          <span class="motto">Truth | Excellence | Service</span>
        </div>
      </a>

      <!-- Navigation Links -->
      <ul class="nav-menu">
        <li class="nav-item"><a href="#" class="nav-link">Home</a></li>
        <li class="nav-item"><a href="#" class="nav-link">About</a></li>
        <li class="nav-item dropdown">
          <a href="#" class="nav-link">Accommodations</a>
          <ul class="dropdown-menu">
            <li><a href="#" class="dropdown-link">Standard</a></li>
            <li><a href="#" class="dropdown-link">Deluxe</a></li>
            <li><a href="#" class="dropdown-link">Suite</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a href="#" class="nav-link">Services</a>
          <ul class="dropdown-menu">
            <li><a href="#" class="dropdown-link">Spa</a></li>
            <li><a href="#" class="dropdown-link">Dining</a></li>
            <li><a href="#" class="dropdown-link">Event Hosting</a></li>
          </ul>
        </li>
        <li class="nav-item"><a href="#" class="nav-link">Contact Us</a></
