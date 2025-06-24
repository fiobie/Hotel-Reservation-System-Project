<?php
include 'connections.php';

// --- FILTER HANDLING ---
$where = [];
$params = [];
if (
  $_SERVER['REQUEST_METHOD'] === 'GET' && (
    isset($_GET['RoomID']) || isset($_GET['RoomNumber']) || isset($_GET['RoomName']) || isset($_GET['RoomType']) ||
    isset($_GET['RoomPerHour']) || isset($_GET['RoomStatus']) || isset($_GET['Capacity'])
  )
) {
  if (!empty($_GET['RoomID'])) {
    $where[] = "RoomID = '" . $conn->real_escape_string($_GET['RoomID']) . "'";
  }
  if (!empty($_GET['RoomNumber'])) {
    $where[] = "RoomNumber = '" . $conn->real_escape_string($_GET['RoomNumber']) . "'";
  }
  if (!empty($_GET['RoomName'])) {
    $where[] = "RoomName = '" . $conn->real_escape_string($_GET['RoomName']) . "'";
  }
  if (!empty($_GET['RoomType'])) {
    $where[] = "RoomType = '" . $conn->real_escape_string($_GET['RoomType']) . "'";
  }
  if (!empty($_GET['RoomPerHour'])) {
    $where[] = "RoomPerHour = '" . $conn->real_escape_string($_GET['RoomPerHour']) . "'";
  }
  if (!empty($_GET['RoomStatus'])) {
    $where[] = "RoomStatus = '" . $conn->real_escape_string($_GET['RoomStatus']) . "'";
  }
  if (!empty($_GET['Capacity'])) {
    $where[] = "Capacity = '" . $conn->real_escape_string($_GET['Capacity']) . "'";
  }
}

// --- AJAX UPDATE ---
if (
  $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['RoomID']) && isset($_POST['RoomNumber']) && isset($_POST['RoomName']) && !isset($_POST['createRoom']) && !isset($_POST['deleteRoom'])
) {
  $roomid = intval($_POST['RoomID']);
  $roomnumber = $conn->real_escape_string($_POST['RoomNumber']);
  $roomname = $conn->real_escape_string($_POST['RoomName']);
  $roomtype = $conn->real_escape_string($_POST['RoomType']);
  $roomperhour = floatval($_POST['RoomPerHour']);
  $roomstatus = $conn->real_escape_string($_POST['RoomStatus']);
  $capacity = intval($_POST['Capacity']);

  $sql = "UPDATE room SET RoomNumber='$roomnumber', RoomName='$roomname', RoomType='$roomtype', RoomPerHour='$roomperhour', RoomStatus='$roomstatus', Capacity='$capacity' WHERE RoomID=$roomid";
  $success = $conn->query($sql);
  header('Content-Type: application/json');
  echo json_encode(['success' => $success, 'error' => $conn->error, 'sql' => $sql, 'post' => $_POST]);
  exit;
}

// --- AJAX DELETE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteRoom']) && isset($_POST['RoomID'])) {
  $roomid = intval($_POST['RoomID']);
  $sql = "DELETE FROM room WHERE RoomID=$roomid";
  $success = $conn->query($sql);
  header('Content-Type: application/json');
  echo json_encode(['success' => $success, 'error' => $conn->error, 'sql' => $sql]);
  exit;
}

// --- FETCH LATEST ROOM (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['latestRoom'])) {
  $sql = "SELECT * FROM room ORDER BY RoomID DESC LIMIT 1";
  $result = $conn->query($sql);
  $room = $result ? $result->fetch_assoc() : null;
  header('Content-Type: application/json');
  echo json_encode(['room' => $room]);
  exit;
}

// --- CREATE ROOM (AJAX/POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createRoom'])) {
  $roomnumber = $conn->real_escape_string($_POST['RoomNumber']);
  $roomname = $conn->real_escape_string($_POST['RoomName']);
  $roomtype = $conn->real_escape_string($_POST['RoomType']);
  $roomperhour = floatval($_POST['RoomPerHour']);
  $roomstatus = $conn->real_escape_string($_POST['RoomStatus']);
  $capacity = intval($_POST['Capacity']);
  $sql = "INSERT INTO room (RoomNumber, RoomName, RoomType, RoomPerHour, RoomStatus, Capacity) VALUES ('$roomnumber', '$roomname', '$roomtype', '$roomperhour', '$roomstatus', '$capacity')";
  $success = $conn->query($sql);
  header('Content-Type: application/json');
  echo json_encode(['success' => $success, 'error' => $conn->error, 'sql' => $sql]);
  exit;
}

// --- FETCH ROOMS (with filter) ---
if (count($where) > 0) {
  $sql = "SELECT * FROM room WHERE " . implode(' AND ', $where) . " ORDER BY RoomID DESC";
  $resResult = $conn->query($sql);
} else {
  $resQuery = "SELECT * FROM room ORDER BY RoomID DESC";
  $resResult = $conn->query($resQuery);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Room</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
        :root {
            --theme-green: #008000;
            --theme-green-dark: #005c00;
            --theme-green-light: #90ee90;
            --action-edit: #008000;
            --action-view: #00b894;
            --action-delete: #e74c3c;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: #f5f6fa; display: flex; }
        /* Sidebar Styles */
        .sidebar {
            width: 180px;
            background: #008000;
            min-height: 100vh;
            padding: 0.5rem 0;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
            transition: left 0.3s, width 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sidebar-logo {
            width: 90px;
            height: 90px;
            margin: 1.5rem auto 1rem auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .sidebar-logo img {
            width: 90px;
            height: 90px;
            object-fit: contain;
            border-radius: 0;
            border: none;
            background: transparent;
            box-shadow: none;
        }

        .sidebar-title {
            display: block;
            font-size: 1.25rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1.5rem;
            letter-spacing: 1px;
            /* Professional font styling */
            font-family: 'Montserrat', 'Segoe UI', Arial, sans-serif;
            color: #fff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.08);
        }

        .sidebar .nav-section {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding-left: 1rem;
            gap: 0.5rem;
            margin-bottom: 0;
        }

        .sidebar .nav-section:not(:last-child) {
            margin-bottom: 1rem;
        }

        .sidebar .nav-link {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
            padding: 0.35rem 0.6rem;
            color: white;
            text-decoration: none;
            font-size: 0.93rem;
            margin-bottom: 0.15rem;
            border-radius: 5px;
            width: 90%;
            transition: background-color 0.2s;
            height: 36px;
            gap: 0.5rem;
        }

        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.13);
        }

        .sidebar .nav-link i {
            margin: 0;
            width: 22px;
            text-align: center;
            font-size: 1.08rem;
            opacity: 0.95;
        }

        .sidebar .nav-link span {
            font-size: 0.93rem;
            margin-top: 0;
            display: block;
            text-align: left;
            letter-spacing: 0.5px;
        }

        .sidebar .management-label {
            display: none;
        }

        .sidebar .toggle-btn {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            cursor: pointer;
            width: 90%;
            padding: 0 0.6rem;
            height: 36px;
            gap: 0.5rem;
        }

        .sidebar .toggle-btn::after {
            display: none;
        }

        .sidebar .submenu {
            margin-left: 0.3rem;
            display: none;
            width: 100%;
        }

        .sidebar .submenu.active {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .sidebar-nav-center {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            width: 100%;
            align-items: flex-start;
        }
        .main-content { flex: 1; padding: 2rem; margin-left: 200px; overflow-x: hidden; transition: margin-left 0.3s; }
        .reservation-section { max-width: 1200px; margin: 2rem auto; background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 2rem; }
        h1 { font-size: 2rem; margin-bottom: 1.5rem; color: #333; }
        .reservation-table { width: 100%; border-collapse: collapse; }
        .reservation-table th, .reservation-table td { padding: 1rem; border-bottom: 1px solid #f0f2f5; text-align: left; }
        .reservation-table th { background: #f8f9fa; color: #666; font-weight: 600; }
        .reservation-table td { color: #222; font-weight: 500; }
        /* Action Buttons */
        .action-group {
            display: flex;
            gap: 0.3rem;
            justify-content: center;
            align-items: center;
        }
        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            outline: none;
            border-radius: 50%;
            width: 34px;
            height: 34px;
            font-size: 1.05rem;
            color: #008000;
            background: none;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            box-shadow: none;
            padding: 0;
        }
        .action-btn.edit-btn {
            color: var(--action-edit);
        }
        .action-btn.edit-btn:hover {
            background: #e6f5ea;
            color: var(--theme-green-dark);
        }
        .action-btn.view-btn {
            color: var(--action-view);
        }
        .action-btn.view-btn:hover {
            background: #e6f5ea;
            color: #00916e;
        }
        .action-btn.delete-btn {
            color: var(--action-delete);
        }
        .action-btn.delete-btn:hover {
            background: #fbeaea;
            color: #c0392b;
        }
        .action-btn i {
            font-size: 1.1em;
        }
        /* Center the action group in the table cell */
        .reservation-table td:nth-child(7) {
            text-align: center;
            vertical-align: middle;
        }
        /* Modal styles */
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background: rgba(0,0,0,0.3); }
        .modal-content { background: #fff; margin: 5% auto; padding: 2rem; border-radius: 10px; width: 400px; position: relative; }
        .close { position: absolute; right: 1rem; top: 1rem; font-size: 1.5rem; color: #888; cursor: pointer; }
        .modal-content h2 { margin-bottom: 1rem; }
        .modal-content label { font-weight: 600; }
        .modal-content p { margin-bottom: 0.5rem; }
        /* Hamburger menu styles */
        .hamburger {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1100;
            width: 36px;
            height: 36px;
            background: var(--theme-green);
            border: none;
            border-radius: 6px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .hamburger span {
            display: block;
            width: 22px;
            height: 3px;
            background: #fff;
            margin: 4px 0;
            border-radius: 2px;
            transition: 0.3s;
        }
        @media (max-width: 900px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .sidebar { left: -220px; box-shadow: none; }
            .sidebar.active { left: 0; box-shadow: 2px 0 8px rgba(0,0,0,0.08); }
            .hamburger { display: flex; }
        }
        @media (max-width: 600px) {
            .reservation-section { padding: 1rem; }
            .reservation-table th, .reservation-table td { padding: 0.5rem; font-size: 0.9rem; }
            h1 { font-size: 1.2rem; }
        }
        @media (max-width: 500px) {
            .reservation-table, .reservation-table thead, .reservation-table tbody, .reservation-table th, .reservation-table td, .reservation-table tr {
                display: block;
                width: 100%;
            }
            .reservation-table thead { display: none; }
            .reservation-table tr { margin-bottom: 1rem; border-bottom: 2px solid #f0f2f5; }
            .reservation-table td {
                padding-left: 40%;
                position: relative;
                font-size: 1rem;
                border: none;
                border-bottom: 1px solid #f0f2f5;
            }
            .reservation-table td:before {
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);

                font-weight: bold;
                color: #666;
                content: attr(data-label);
                font-size: 0.95rem;
            }
        }
        .search-filter-bar {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .search-input {
            padding: 0.7rem 2.5rem 0.7rem 2.5rem;
            border-radius: 1.2rem;
            border: none;
            background: #ededed;
            font-size: 1rem;
            width: 260px;
            outline: none;
        }
        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }
        .search-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .filter-btn, .create-btn {
            padding: 0.7rem 1.5rem;
            border-radius: 1rem;
            border: 2px solid #222;
            background: #f5f6fa;
            font-size: 1rem;
            cursor: pointer;
            margin-left: 0.5rem;
            transition: background 0.2s, color 0.2s;
        }
        .filter-btn:hover, .create-btn:hover {
            background: #222;
            color: #fff;
        }
        .filter-dropdown {
            display: none;
            position: absolute;
            top: 2.5rem;
            left: 0;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            z-index: 10;
            min-width: 220px;
            padding: 1rem;
        }
        .filter-dropdown.active {
            display: block;
        }
        .filter-dropdown label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .filter-dropdown input, .filter-dropdown select {
            width: 100%;
            margin-bottom: 1rem;
            padding: 0.4rem 0.7rem;
            border-radius: 0.5rem;
            border: 1px solid #ccc;
        }
        .filter-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
        .modal-content form input, .modal-content form select {
            width: 100%;
            margin-bottom: 1rem;
            padding: 0.5rem 0.7rem;
            border-radius: 0.5rem;
            border: 1px solid #ccc;
        }
        .modal-content form button[type="submit"] {
            width: 100%;
            padding: 0.7rem;
            border-radius: 0.7rem;
            border: none;
            background: var(--theme-green);
            color: #fff;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
        }
        .modal-content form button[type="submit"]:hover {
            background: var(--theme-green-dark);
        }
        /* Delete Modal Buttons */
        .confirm-delete {
            background: var(--action-delete);
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            padding: 0.6rem 1.3rem;
            font-size: 1rem;
            font-weight: 600;
            margin-right: 0.7rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .confirm-delete:hover {
            background: #c0392b;
        }
        .cancel-delete {
            background: #f5f6fa;
            color: #222;
            border: 1px solid #ccc;
            border-radius: 0.5rem;
            padding: 0.6rem 1.3rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .cancel-delete:hover {
            background: #ededed;
            color: var(--theme-green);
        }
         /* Download icon button in table cell */
        .download-table-btn {
            background: none;
            border: none;
            color: #008000;
            border-radius: 50%;
            padding: 0.3rem;
            font-size: 1.1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, color 0.2s;
            margin: 0 auto; /* Center horizontally */
        }
        .download-table-btn i {
            font-size: 1.05em;
            color: #008000;
            transition: color 0.2s;
        }
        .download-table-btn:hover, .download-table-btn:focus {
            background: #e6f5ea;
        }
        .download-table-btn:hover i, .download-table-btn:focus i {
            color: #005c00;
        }
        /* Center the download button in the table cell */
        .reservation-table td:last-child {
            text-align: center;
            vertical-align: middle;
        }
        .sidebar-section-label {
            display: block;
            color: #fff;
            font-size: 0.93rem;
            font-weight: 400;
            opacity: 0.85;
            margin: 0.5rem 0 0.1rem 0.1rem;
            padding-left: 0.2rem;
            letter-spacing: 0.5px;
            cursor: default;
            user-select: none;
        }
        .sidebar .nav-section {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding-left: 1rem;
            gap: 0.5rem;
            margin-bottom: 0;
        }
        .top-bar {
            position: fixed;
            left: 180px;
            right: 0;
            top: 0;
            height: 60px;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            z-index: 1001;
            padding: 0 2rem;
            transition: left 0.3s;
        }
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }
        .top-bar-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #333;
            cursor: pointer;
            position: relative;
        }
        .top-bar-account {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #bbb;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
        }
        .top-bar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.7rem;
            color: #147219;
            margin-right: 1rem;
            cursor: pointer;
        }
        @media (max-width: 900px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .sidebar { left: -220px; box-shadow: none; }
            .sidebar.active { left: 0; box-shadow: 2px 0 8px rgba(0,0,0,0.08); }
        }
        /* Responsive Styles */
        @media (max-width: 1200px) {
            .stats-cards {
                grid-template-columns: 1fr 1fr;
            }
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 900px) {
            .main-content {
                padding: 1rem;
            }
        }
        @media (max-width: 700px) {
            .sidebar {
                left: -200px;
                width: 180px;
            }
            .sidebar.active {
                left: 0;
            }
            .top-bar {
                left: 0;
                padding-left: 0.5rem;
            }
            .main-content {
                margin-left: 0;
            }
            .top-bar-toggle {
                display: block;
            }
        }
        @media (max-width: 600px) {
            .main-content {
                padding: 0.5rem;
            }
        }
        .section-toggle {
            background: none;
            border: none;
            color: #e6e6e6;
            font-size: 1.08rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 0.1rem;
            cursor: pointer;
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 0.35rem 0.6rem 0.35rem 0;
            outline: none;
            border-radius: 5px;
            transition: background 0.18s, color 0.18s;
        }
        .section-toggle:focus, .section-toggle:hover {
            color: #fff;
            background: rgba(255,255,255,0.10);
        }
        .section-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.08rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .section-label i {
            font-size: 1.15rem;
            opacity: 0.95;
        }
        .chevron {
            margin-left: auto;
            font-size: 1.1rem;
            transition: transform 0.25s cubic-bezier(.4,2,.6,1), color 0.18s;
        }
        .section-toggle[aria-expanded="false"] .chevron {
            transform: rotate(-90deg);
        }
        .section-links {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            width: 100%;
            transition: max-height 0.2s, opacity 0.2s;
            overflow: hidden;
            opacity: 1;
            max-height: 500px;
            margin-bottom: 0.2rem;
        }
        .section-links.collapsed {
            opacity: 0;
            max-height: 0;
            pointer-events: none;
        }
        .sidebar .nav-section {
            margin-bottom: 0.2rem;
        }
        .sidebar-section-label {
            display: block;
            color: #fff;
            font-size: 0.93rem;
            font-weight: 400;
            opacity: 0.85;
            margin: 0.5rem 0 0.1rem 0.1rem;
            padding-left: 0.2rem;
            letter-spacing: 0.5px;
            cursor: default;
            user-select: none;
        }
    </style>
</head>
<body>
  <div class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <img src="images/villavalorelogo.png" alt="Villa Valore Logo">
        </div>
        <div class="sidebar-title">Villa Valore</div>
        <div class="sidebar-nav-center">
            <div class="nav-section">
                <a class="nav-link" href="index.php"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            </div>
            <div class="nav-section">
                <span class="sidebar-section-label">Management</span>
                <a class="nav-link" href="student.php"><i class="fas fa-user"></i><span>Guest</span></a>
                <a class="nav-link" href="booking.php"><i class="fas fa-book"></i><span>Booking</span></a>
                <a class="nav-link" href="reservation.php"><i class="fas fa-calendar-check"></i><span>Reservation</span></a>
            </div>
            <div class="nav-section">
                <span class="sidebar-section-label">Resources</span>
                <a class="nav-link" href="room.php"><i class="fas fa-door-open"></i><span>Room</span></a>
                <a class="nav-link" href="menu_service.php"><i class="fas fa-utensils"></i><span>Menu</span></a>
                <a class="nav-link" href="inventory.php"><i class="fas fa-box"></i><span>Inventory</span></a>
            </div>
            <div class="nav-section">
                <span class="sidebar-section-label">Administration</span>
                <a class="nav-link" href="account.php"><i class="fas fa-user"></i><span>Account</span></a>
            </div>
            <div class="nav-section">
                <span class="sidebar-section-label">Finance & Analytics</span>
                <a class="nav-link" href="payment.php"><i class="fas fa-credit-card"></i><span>Invoices</span></a>
                <a class="nav-link" href="statistics.php"><i class="fas fa-chart-line"></i><span>Statistics</span></a>
            </div>
        </div>
    </div>
    <div class="top-bar" id="topBar">
        <button class="top-bar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar"><i class="fas fa-bars"></i></button>
        <div class="top-bar-right">
            <div class="top-bar-icon" title="Email"><i class="fas fa-envelope"></i></div>
            <div class="top-bar-icon" title="Notifications"><i class="fas fa-bell"></i></div>
            <div class="top-bar-account" title="Account">PB</div>
        </div>
    </div>
  
  <!-- Main Content -->
  <div class="main-content">
  <div class="reservation-section">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
    <h1 style="margin-bottom: 0; border-bottom: 4px solid rgb(255, 255, 255); display: inline-block; padding-bottom: 0.2rem;">Room</h1>
    <div class="search-filter-bar">
      <div class="search-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="searchInput" class="search-input" placeholder="Search Rooms">
      </div>
      <button class="create-btn" id="createBtn">Add Room</button>
    </div>
    </div>
    <table class="reservation-table">
    <thead>
      <tr>
      <th>Room ID</th>
      <th>Room Number</th>
      <th>Room Name</th>
      <th>Room Type</th>
      <th>Room Per Hour</th>
      <th>Room Status</th>
      <th>Capacity</th>
      <th>Actions</th>
      <th>Download</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($resResult && $resResult->num_rows > 0): ?>
      <?php while($row = $resResult->fetch_assoc()): ?> 
      <tr data-id="<?php echo $row['RoomID']; ?>">
      <td><b><?php echo $row['RoomID']; ?></b></td>
      <td><b><?php echo htmlspecialchars($row['RoomNumber']); ?></b></td>
      <td><b><?php echo htmlspecialchars($row['RoomName']); ?></b></td>
      <td><b><?php echo htmlspecialchars($row['RoomType']); ?></b></td>
      <td><b><?php echo htmlspecialchars($row['RoomPerHour']); ?></b></td>
      <td><?php echo $row['RoomStatus']; ?></td>
      <td><b><?php echo $row['Capacity']; ?></b></td>
      <td>
        <div class="action-group">
        <button type="button" class="action-btn edit-btn"
          data-id="<?php echo $row['RoomID']; ?>"
          data-roomnumber="<?php echo htmlspecialchars($row['RoomNumber']); ?>"
          data-roomname="<?php echo htmlspecialchars($row['RoomName']); ?>"
          data-roomtype="<?php echo htmlspecialchars($row['RoomType']); ?>"
          data-roomperhour="<?php echo htmlspecialchars($row['RoomPerHour']); ?>"
          data-roomstatus="<?php echo htmlspecialchars($row['RoomStatus']); ?>"
          data-capacity="<?php echo htmlspecialchars($row['Capacity']); ?>"

          ><i class="fas fa-edit"></i></button>
          <button type="button" class="action-btn view-btn"
            data-id="<?php echo $row['RoomID']; ?>"
            data-roomnumber="<?php echo htmlspecialchars($row['RoomNumber']); ?>"
            data-roomname="<?php echo htmlspecialchars($row['RoomName']); ?>"
            data-roomtype="<?php echo htmlspecialchars($row['RoomType']); ?>"
            data-roomperhour="<?php echo htmlspecialchars($row['RoomPerHour']); ?>"
            data-roomstatus="<?php echo htmlspecialchars($row['RoomStatus']); ?>"
            data-capacity="<?php echo htmlspecialchars($row['Capacity']); ?>"

          ><i class="fas fa-eye"></i></button>
          <button type="button" class="action-btn delete-btn"
            data-id="<?php echo $row['RoomID']; ?>"
          ><i class="fas fa-trash"></i></button>
                  </div>
                </td>
                <td>
                        <button class="download-table-btn" title="Download Table" onclick="showDownloadModal(event)">
                          <i class="fas fa-download"></i>
                        </button>
                      </td>
                </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="11">No rooms found.</td></tr>
    <?php endif; ?>
    </tbody>
    </table>
  </div>
  </div>
  <!-- Edit Modal -->
  <div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close" id="closeEditModal">&times;</span>
    <h2>Edit Room</h2>
    <form id="editForm">
    <input type="hidden" name="RoomID" id="editRoomID">
    <p><label>Room Number:</label><br><input type="number" name="RoomNumber" id="editRoomNumber" required></p>
    <p><label>Room Name:</label><br><input type="text" name="RoomName" id="editRoomName" required></p>
    <p><label>Room Type:</label><br>
      <select name="RoomType" id="editRoomType" required>
      <option value="Standard">Standard</option>
      <option value="Deluxe">Deluxe</option>
      <option value="Suite">Suite</option>
      </select>
    </p>
    <p><label>Room Per Hour:</label><br><input type="number" name="RoomPerHour" id="editRoomPerHour" required></p>
    <p><label>Room Status:</label><br>
      <select name="RoomStatus" id="editRoomStatus" required>
      <option value="Available">Available</option>
      <option value="Occupied">Occupied</option>
      <option value="Maintenance">Maintenance</option>
      <option value="Cleaning">Cleaning</option>
      </select>
    </p>
    <p><label>Capacity:</label><br><input type="number" step="0.01" name="Capacity" id="editCapacity" required></p>
    <button type="submit" style="margin-top:1rem;">Save</button>
    </form>
  </div>
  </div>
  <!-- View Modal -->
  <div id="viewModal" class="modal">
  <div class="modal-content">
    <span class="close" id="closeViewModal">&times;</span>
    <h2>View Room</h2>
    <div id="viewDetails"></div>
  </div>
  </div>
  <!-- Download Modal -->
  <div id="downloadModal" class="modal">
    <div class="modal-content" style="width: 350px;">
      <span class="close" id="closeDownloadModal">&times;</span>
      <h2>Download Table</h2>
      <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem;">
        <button class="filter-btn" id="copyTableBtn"><i class="fas fa-copy"></i> Copy </button>
        <button class="filter-btn" id="csvTableBtn"><i class="fas fa-file-csv"></i> CSV File</button>
        <button class="filter-btn" id="excelTableBtn"><i class="fas fa-file-excel"></i> Excel File</button>
        <button class="filter-btn" id="pdfTableBtn"><i class="fas fa-file-pdf"></i> PDF File</button>
        <button class="filter-btn" id="printTableBtn"><i class="fas fa-file-pdf"></i> Print File</button>
      </div>
    </div>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script>
    // Download Modal logic
    const downloadModal = document.getElementById('downloadModal');
    const closeDownloadModal = document.getElementById('closeDownloadModal');

    // Show modal from table cell download icon
    function showDownloadModal(e) {
      e.preventDefault();
      downloadModal.style.display = 'block';
    }

    closeDownloadModal.onclick = function() {
      downloadModal.style.display = 'none';
    };
    window.addEventListener('click', function(e) {
      if (e.target == downloadModal) downloadModal.style.display = 'none';
    });

    // Helper: get table data as array (optionally exclude actions/download columns)
    function getTableData(excludeActions = false) {
      const rows = Array.from(document.querySelectorAll('.reservation-table tbody tr'))
        .filter(row => row.style.display !== 'none');
      let headers = Array.from(document.querySelectorAll('.reservation-table thead th'));
      let colCount = headers.length;
      if (excludeActions) {
        // Remove last two columns: Actions and Download
        headers = headers.slice(0, -2);
        colCount = headers.length;
      } else {
        // Remove only Download column
        headers = headers.slice(0, -1);
        colCount = headers.length;
      }
      headers = headers.map(th => th.innerText.trim());
      const data = rows.map(row =>
        Array.from(row.querySelectorAll('td')).slice(0, colCount).map(td => td.innerText.trim())
      );
      return { headers, data };
    }

    // Copy Table
    document.getElementById('copyTableBtn').onclick = function() {
      const { headers, data } = getTableData();
      const text = [headers.join('\t'), ...data.map(row => row.join('\t'))].join('\n');
      navigator.clipboard.writeText(text).then(() => {
        alert('Table copied to clipboard!');
        downloadModal.style.display = 'none';
      });
    };

    // Download CSV
    document.getElementById('csvTableBtn').onclick = function() {
      const { headers, data } = getTableData();
      const csv = [headers.join(','), ...data.map(row => row.map(cell => `"${cell.replace(/"/g, '""')}"`).join(','))].join('\r\n');
      const blob = new Blob([csv], {type: 'text/csv'});
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'students.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      downloadModal.style.display = 'none';
    };

    // Download Excel
    document.getElementById('excelTableBtn').onclick = function() {
      const { headers, data } = getTableData();
      const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, "Students");
      XLSX.writeFile(wb, "students.xlsx");
      downloadModal.style.display = 'none';
    };

    // Download PDF
    document.getElementById('pdfTableBtn').onclick = function() {
      const { headers, data } = getTableData();
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      doc.autoTable({
        head: [headers],
        body: data,
        styles: { fontSize: 9 },
        headStyles: { fillColor: [0,128,0] }
      });
      doc.save('students.pdf');
      downloadModal.style.display = 'none';
    };

    // Print Table (exclude actions/download columns)
    document.getElementById('printTableBtn').onclick = function() {
      const { headers, data } = getTableData(true);
      let html = '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%">';
      html += '<thead><tr>' + headers.map(h => `<th>${h}</th>`).join('') + '</tr></thead>';
      html += '<tbody>' + data.map(row => '<tr>' + row.map(cell => `<td>${cell}</td>`).join('') + '</tr>').join('') + '</tbody></table>';
      const win = window.open('', '', 'width=900,height=700');
      win.document.write('<html><head><title>Print Students</title></head><body>' + html + '</body></html>');
      win.document.close();
      win.print();
      downloadModal.style.display = 'none';
    };
  </script>
  <!-- jsPDF autotable plugin -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

  <!-- Create Booking Modal -->
  <div id="createModal" class="modal">
  <div class="modal-content">
    <span class="close" id="closeCreateModal">&times;</span>
    <h2>Add Room</h2>
    <form id="createForm">
    <input type="hidden" name="createRoom" value="1">
    <p><label>Room Number:</label><br><input type="number" name="RoomNumber" required></p>
    <p><label>Room Name:</label><br><input type="text" name="RoomName" required></p>
    <p><label>Room Type:</label><br>
      <select name="RoomType" required>
      <option value="Standard">Standard</option>
      <option value="Deluxe">Deluxe</option>
      <option value="Suite">Suite</option>
      </select>
    </p>
    <p><label>Room Per Hour:</label><br><input type="number" name="RoomPerHour" required></p>
    <p><label>Room Status:</label><br>
      <select name="RoomStatus" required>
      <option value="Available">Available</option>
      <option value="Occupied">Occupied</option>
      <option value="Maintenance">Maintenance</option>
      <option value="Cleaning">Cleaning</option>
      </select>
    </p>
    <p><label>Capacity:</label><br><input type="number" step="0.01" name="Capacity" required></p>
    <button type="submit">Create</button>
    </form>
  </div>
  </div>
  <!-- Delete Modal -->
  <div id="deleteModal" class="modal">
  <div class="modal-content">
    <span class="close" id="closeDeleteModal">&times;</span>
    <h2>Delete Room</h2>
    <p>Are you sure you want to delete this room?</p>
    <div style="margin-top:1.5rem;">
    <button class="confirm-delete">Delete</button>
    <button class="cancel-delete">Cancel</button>
    </div>
  </div>
  </div>
  <script>
  // Sidebar submenu toggle
  function toggleMenu(id) {
    var submenu = document.getElementById(id);
    submenu.classList.toggle('active');
  }

  // --- Modal Logic & Action Buttons for Room Table ---
  // Event delegation for dynamic table rows
  function bindRoomTableActions() {
    // Edit Modal
    document.querySelectorAll('.edit-btn').forEach(btn => {
      btn.onclick = function() {
        editModal.style.display = 'block';
        document.getElementById('editRoomID').value = this.dataset.id;
        document.getElementById('editRoomNumber').value = this.dataset.roomnumber;
        document.getElementById('editRoomName').value = this.dataset.roomname;
        document.getElementById('editRoomType').value = this.dataset.roomtype;
        document.getElementById('editRoomPerHour').value = this.dataset.roomperhour;
        document.getElementById('editRoomStatus').value = this.dataset.roomstatus;
        document.getElementById('editCapacity').value = this.dataset.capacity;
      }
    });
    // View Modal
    document.querySelectorAll('.view-btn').forEach(btn => {
      btn.onclick = function() {
        viewModal.style.display = 'block';
        document.getElementById('viewDetails').innerHTML = `
          <p><label>Room ID:</label> <span>${this.dataset.id}</span></p>
          <p><label>Room Name:</label> <span>${this.dataset.roomname}</span></p>
          <p><label>Room Number:</label> <span>${this.dataset.roomnumber}</span></p>
          <p><label>Room Type:</label> <span>${this.dataset.roomtype}</span></p>
          <p><label>Room Per Hour:</label> <span>${this.dataset.roomperhour}</span></p>
          <p><label>Room Status:</label> <span>${this.dataset.roomstatus}</span></p>
          <p><label>Capacity:</label> <span>${this.dataset.capacity}</span></p>
        `;
      }
    });
    // Delete Modal
    document.querySelectorAll('.delete-btn').forEach(btn => {
      btn.onclick = function() {
        deleteRoomId = this.dataset.id;
        deleteModal.style.display = 'block';
      }
    });
  }
  bindRoomTableActions();

  // Edit Modal
  const editModal = document.getElementById('editModal');
  const closeEditModal = document.getElementById('closeEditModal');
  closeEditModal.onclick = function() { editModal.style.display = 'none'; }
  const editForm = document.getElementById('editForm');
  editForm.onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(editForm);
    fetch('room.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        // Update the table row in-place (like reservation.php)
        const roomId = document.getElementById('editRoomID').value;
        const row = document.querySelector(`tr[data-id='${roomId}']`);
        if (row) {
          row.children[1].innerHTML = `<b>${document.getElementById('editRoomNumber').value}</b>`;
          row.children[2].innerHTML = `<b>${document.getElementById('editRoomName').value}</b>`;
          row.children[3].innerHTML = `<b>${document.getElementById('editRoomType').value}</b>`;
          row.children[4].innerHTML = `<b>${document.getElementById('editRoomPerHour').value}</b>`;
          row.children[5].innerHTML = document.getElementById('editRoomStatus').value;
          row.children[6].innerHTML = `<b>${document.getElementById('editCapacity').value}</b>`;
          // Update the edit button's data-* attributes
          const editBtn = row.querySelector('.edit-btn');
          if (editBtn) {
            editBtn.dataset.roomnumber = document.getElementById('editRoomNumber').value;
            editBtn.dataset.roomname = document.getElementById('editRoomName').value;
            editBtn.dataset.roomtype = document.getElementById('editRoomType').value;
            editBtn.dataset.roomperhour = document.getElementById('editRoomPerHour').value;
            editBtn.dataset.roomstatus = document.getElementById('editRoomStatus').value;
            editBtn.dataset.capacity = document.getElementById('editCapacity').value;
          }
        }
        editModal.style.display = 'none';
      } else {
        alert('Update failed.');
      }
    });
  }
  // View Modal
  const viewModal = document.getElementById('viewModal');
  const closeViewModal = document.getElementById('closeViewModal');
  closeViewModal.onclick = function() { viewModal.style.display = 'none'; }
  // Delete Modal
  const deleteModal = document.getElementById('deleteModal');
  const closeDeleteModal = document.getElementById('closeDeleteModal');
  let deleteRoomId = null;
  document.querySelector('.confirm-delete').onclick = function() {
    if (!deleteRoomId) return;
    const formData = new FormData();
    formData.append('deleteRoom', 1);
    formData.append('RoomID', deleteRoomId);
    fetch('room.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        deleteModal.style.display = 'none';
        setTimeout(() => location.reload(), 200);
      } else {
        alert('Delete failed.');
      }
    });
  }
  document.querySelector('.cancel-delete').onclick = function() { deleteModal.style.display = 'none'; deleteRoomId = null; }
  closeDeleteModal.onclick = function() { deleteModal.style.display = 'none'; }
  // Modal close on outside click
  window.onclick = function(event) {
    if (event.target == editModal) editModal.style.display = 'none';
    if (event.target == viewModal) viewModal.style.display = 'none';
    if (event.target == createModal) createModal.style.display = 'none';
    if (event.target == deleteModal) deleteModal.style.display = 'none';
  }
  // Search logic
  const searchInput = document.getElementById('searchInput');
  const tableRows = document.querySelectorAll('.reservation-table tbody tr');
  searchInput.oninput = function() {
    const val = searchInput.value.toLowerCase();
    tableRows.forEach(row => {
      let match = false;
      row.querySelectorAll('td').forEach(cell => {
        if (cell.innerText.toLowerCase().includes(val)) match = true;
      });
      row.style.display = match ? '' : 'none';
    });
  }
  // Add Room Modal
  const createModal = document.getElementById('createModal');
  const createBtn = document.getElementById('createBtn');
  const closeCreateModal = document.getElementById('closeCreateModal');
  const createForm = document.getElementById('createForm');
  createBtn.onclick = function() { createModal.style.display = 'block'; }
  closeCreateModal.onclick = function() { createModal.style.display = 'none'; }
  createForm.onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(createForm);
    fetch('room.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        // Fetch the latest room and add to the table
        fetch('room.php?latestRoom=1')
          .then(res => res.json())
          .then(json => {
            if (json.room) {
              const tbody = document.querySelector('.reservation-table tbody');
              const row = document.createElement('tr');
              row.setAttribute('data-id', json.room.RoomID);
              row.innerHTML = `
                <td><b>${json.room.RoomID}</b></td>
                <td><b>${json.room.RoomNumber}</b></td>
                <td><b>${json.room.RoomName}</b></td>
                <td><b>${json.room.RoomType}</b></td>
                <td><b>${json.room.RoomPerHour}</b></td>
                <td>${json.room.RoomStatus}</td>
                <td><b>${json.room.Capacity}</b></td>
                <td>
                  <div class="action-group">
                    <button type="button" class="action-btn edit-btn"
                      data-id="${json.room.RoomID}"
                      data-roomnumber="${json.room.RoomNumber}"
                      data-roomname="${json.room.RoomName}"
                      data-roomtype="${json.room.RoomType}"
                      data-roomperhour="${json.room.RoomPerHour}"
                      data-roomstatus="${json.room.RoomStatus}"
                      data-capacity="${json.room.Capacity}"
                    ><i class="fas fa-edit"></i></button>
                    <button type="button" class="action-btn view-btn"
                      data-id="${json.room.RoomID}"
                      data-roomnumber="${json.room.RoomNumber}"
                      data-roomname="${json.room.RoomName}"
                      data-roomtype="${json.room.RoomType}"
                      data-roomperhour="${json.room.RoomPerHour}"
                      data-roomstatus="${json.room.RoomStatus}"
                      data-capacity="${json.room.Capacity}"
                    ><i class="fas fa-eye"></i></button>
                    <button type="button" class="action-btn delete-btn"
                      data-id="${json.room.RoomID}"
                    ><i class="fas fa-trash"></i></button>
                  </div>
                </td>
                <td>
                  <button class="download-table-btn" title="Download Table" onclick="showDownloadModal(event)">
                    <i class="fas fa-download"></i>
                  </button>
                </td>
              `;
              tbody.prepend(row);
              bindRoomTableActions();
            }
            createModal.style.display = 'none';
            createForm.reset();
          });
      } else {
        alert('Create failed.');
      }
    });
  }
  </script>
</body>
</html>
