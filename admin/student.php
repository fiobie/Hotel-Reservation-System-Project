<?php
include 'connections.php';

// --- FILTER HANDLING ---
$where = [];
$params = [];

// 
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (
  isset($_GET['StudentID']) && isset($_GET['FirstName']) && isset($_GET['LastName']) &&
  isset($_GET['Gender']) && isset($_GET['PhoneNumber']) && isset($_GET['Address'])  &&
  isset($_GET['Email']) && isset($_GET['Nationality']) && isset($_GET['Birthdate'])
)) 
{
  if (!empty($_GET['StudentID'])) {
    $where[] = "StudentID = ?";
    $params[] = $_GET['StudentID'];
  }
  if (!empty($_GET['FirstName'])) {
    $where[] = "FirstName = ?";
    $params[] = $_GET['FirstName'];
  }
  if (!empty($_GET['LastName'])) {
    $where[] = "LastName = ?";
    $params[] = $_GET['LastName'];
  }
  if (!empty($_GET['Gender'])) {
    $where[] = "Gender = ?";
    $params[] = $_GET['Gender'];
  }
  if (!empty($_GET['PhoneNumber'])) {
    $where[] = "PhoneNumber = ?";
    $params[] = $_GET['PhoneNumber'];
  }
  if (!empty($_GET['Address'])) {
    $where[] = "Address = ?";
    $params[] = $_GET['Address'];
  }
  if (!empty($_GET['Email'])) {
    $where[] = "Email = ?";
    $params[] = $_GET['Email'];
  }
  if (!empty($_GET['Nationality'])) {
    $where[] = "Nationality = ?";
    $params[] = $_GET['Nationality'];
  }
  if (!empty($_GET['Birthdate'])) {
    $where[] = "Birthdate = ?";
    $params[] = $_GET['Birthdate'];
  }
}

// --- AJAX UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['StudentID']) && isset($_POST['FirstName']) && isset($_POST['LastName']) && !isset($_POST['addStudent']) && !isset($_POST['deleteStudent'])) {
  $studentid = intval($_POST['StudentID']);
  $firstname = $conn->real_escape_string($_POST['FirstName']);
  $lastname = $conn->real_escape_string($_POST['LastName']);
  $gender = $conn->real_escape_string($_POST['Gender']);
  $phonenumber = $conn->real_escape_string($_POST['PhoneNumber']);
  $address = $conn->real_escape_string($_POST['Address']);
  $email = $conn->real_escape_string($_POST['Email']);
  $nationality = $conn->real_escape_string($_POST['Nationality']);
  $birthdate = $conn->real_escape_string($_POST['Birthdate']);

  $sql = "UPDATE student SET
    StudentID='$studentid',
    FirstName='$firstname',
    LastName='$lastname',
    Gender='$gender',
    PhoneNumber='$phonenumber',
    Address='$address',
    Email='$email',
    Nationality='$nationality',
    Birthdate='$birthdate'
    WHERE StudentID=$studentid";

  $success = $conn->query($sql);

  header('Content-Type: application/json');
  echo json_encode(['success' => $success]);
  exit;
}

// --- AJAX DELETE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteStudent']) && isset($_POST['StudentID'])) {
  $studentid = intval($_POST['StudentID']);
  $sql = "DELETE FROM student WHERE StudentID=$studentid";
  $success = $conn->query($sql);
  header('Content-Type: application/json');
  echo json_encode(['success' => $success]);
  exit;
}

// --- CREATE STUDENT (AJAX/POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['addStudent']) || isset($_POST['createStudent']))) {
  $studentid = $conn->real_escape_string($_POST['StudentID']);
  $firstname = $conn->real_escape_string($_POST['FirstName']);
  $lastname = $conn->real_escape_string($_POST['LastName']);
  $gender = $conn->real_escape_string($_POST['Gender']);
  $phonenumber = $conn->real_escape_string($_POST['PhoneNumber']);
  $address = $conn->real_escape_string($_POST['Address']);
  $email = $conn->real_escape_string($_POST['Email']);
  $nationality = $conn->real_escape_string($_POST['Nationality']);
  $birthdate = $conn->real_escape_string($_POST['Birthdate']);
  $sql = "INSERT INTO student (StudentID, FirstName, LastName, Gender, PhoneNumber, Address, Email, Nationality, Birthdate) VALUES ('$studentid','$firstname', '$lastname', '$gender', '$phonenumber', '$address', '$email', '$nationality', '$birthdate')";
  $success = $conn->query($sql);
  if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
  } else {
    header('Location: student.php');
    exit;
  }
}

// --- FETCH STUDENTS (with filter) ---
if (count($where) > 0) {
  $sql = "SELECT * FROM student WHERE " . implode(' AND ', $where) . " ORDER BY StudentID DESC";
  $stmt = $conn->prepare($sql);
  if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  $resResult = $stmt->get_result();
} else {
  $resQuery = "SELECT * FROM student ORDER BY StudentID DESC";
  $resResult = $conn->query($resQuery);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student</title>
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
        .sidebar { width: 200px; background: var(--theme-green); min-height: 100vh; padding: 0.5rem; color: white; position: fixed; left: 0; top: 0; bottom: 0; transition: left 0.3s, box-shadow 0.3s; z-index: 1000; }
        .sidebar-title { color: white; font-size: 1.4rem; font-weight: 500; margin-bottom: 1.5rem; padding: 1rem; }
        .nav-section { margin-bottom: 1rem; }
        .nav-link { display: flex; align-items: center; padding: 0.5rem 1rem; color: white; text-decoration: none; font-size: 0.9rem; margin-bottom: 0.25rem; transition: background-color 0.2s; }
        .nav-link:hover { background-color: rgba(255, 255, 255, 0.1); }
        .nav-link i { margin-right: 0.75rem; width: 20px; text-align: center; opacity: 0.9; }
        .management-label { color: var(--theme-green-light); font-size: 0.8em; margin: 1rem 0 0.5rem 1rem; }
        .toggle-btn { display: flex; align-items: center; justify-content: space-between; cursor: pointer; }
        .toggle-btn::after { content: '▼'; font-size: 0.7rem; margin-left: 0.5rem; }
        .submenu { margin-left: 1.5rem; display: none; }
        .submenu.active { display: block; }
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            outline: none;
            border-radius: 50%;
            padding: 0.3rem;
            font-size: 1.1rem;
            background: none;
            cursor: pointer;
            transition: background 0.18s, color 0.18s;
            box-shadow: none;
        }
        .action-btn.edit-btn i {
            color: var(--action-edit);
        }
        .action-btn.edit-btn:hover, .action-btn.edit-btn:focus {
            background: #e6f5ea;
        }
        .action-btn.edit-btn:hover i, .action-btn.edit-btn:focus i {
            color: var(--theme-green-dark);
        }
        .action-btn.view-btn i {
            color: var(--action-view);
        }
        .action-btn.view-btn:hover, .action-btn.view-btn:focus {
            background: #e6f5ea;
        }
        .action-btn.view-btn:hover i, .action-btn.view-btn:focus i {
            color: #00916e;
        }
        .action-btn.delete-btn i {
            color: var(--action-delete);
        }
        .action-btn.delete-btn:hover, .action-btn.delete-btn:focus {
            background: #fbeaea;
        }
        .action-btn.delete-btn:hover i, .action-btn.delete-btn:focus i {
            color: #c0392b;
        }
        .action-btn i {
            font-size: 1em;
            margin: 0;
        }
        /* Center the Actions column */
        .reservation-table td:nth-child(6) {
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
    </style>
</head>
<body>
  <div class="sidebar">
    <div class="sidebar">
        <h4 class="sidebar-title">Villa Valore Hotel</h4>
        
        <div class="nav-section">
            <a class="nav-link" href="index.php"><i class="fas fa-th-large"></i>Dashboard</a>
            <a class="nav-link" href="student.php"><i class="fas fa-user"></i>Guest</a>
            <a class="nav-link" href="booking.php"><i class="fas fa-book"></i>Booking</a>
            <a class="nav-link" href="reservation.php"><i class="fas fa-calendar-check"></i>Reservation</a>
        </div>

        <div class="nav-section">
            <div class="management-label">MANAGEMENT</div>
            <div class="nav-link toggle-btn" onclick="toggleMenu('management')">
                <div><i class="fas fa-cog"></i>Manage</div>
            </div>
            <div class="submenu" id="management">
                <a class="nav-link" href="room.php"><i class="fas fa-door-open"></i>Room</a>
                <a class="nav-link" href="menu_service.php"><i class="fas fa-utensils"></i>Menu & Service</a>
                <a class="nav-link" href="account.php"><i class="fas fa-user"></i>Account</a>
                <a class="nav-link" href="inventory.php"><i class="fas fa-box"></i>Inventory</a>
            </div>
        </div>

        <div class="nav-section">
            <a class="nav-link" href="payment.php"><i class="fas fa-credit-card"></i>Payments</a>
            <a class="nav-link" href="statistics.php"><i class="fas fa-chart-line"></i>Statistics</a>
            <a class="nav-link" href="inbox.php"><i class="fas fa-inbox"></i>Inbox</a>
        </div>

        <div class="nav-section">
            <a class="nav-link" href="profile.php"><i class="fas fa-user-lock"></i>Profile Account</a>
            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
        </div>
    </div>
  </div>

<div class="main-content">
    <div class="reservation-section">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <h1 style="margin-bottom: 0; border-bottom: 4px solid rgb(255, 255, 255); display: inline-block; padding-bottom: 0.2rem;">Guest</h1>
        <div class="search-filter-bar">
          <div class="search-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" class="search-input" placeholder="Search">
          </div>
          <div style="position: relative;">
            <button class="filter-btn" id="filterBtn">Filter</button>
            <div class="filter-dropdown" id="filterDropdown">
              <form id="filterForm" method="GET">
                <label>Student ID <input type="text" name="StudentID" value="<?php echo isset($_GET['StudentID']) ? htmlspecialchars($_GET['StudentID']) : ''; ?>"></label>
                <label>First Name <input type="text" name="FirstName" value="<?php echo isset($_GET['FirstName']) ? htmlspecialchars($_GET['FirstName']) : ''; ?>"></label>
                <label>Last Name <input type="text" name="LastName" value="<?php echo isset($_GET['LastName']) ? htmlspecialchars($_GET['LastName']) : ''; ?>"></label>
                <label>Gender
                  <select name="Gender">
                    <option value="">Any</option>
                    <option value="Male" <?php if(isset($_GET['Gender']) && $_GET['Gender']=='Male') echo 'selected'; ?>>Male</option>
                    <option value="Female" <?php if(isset($_GET['Gender']) && $_GET['Gender']=='Female') echo 'selected'; ?>>Female</option>
                    <option value="Prefer not to say" <?php if(isset($_GET['Gender']) && $_GET['Gender']=='Prefer not to say') echo 'selected'; ?>>Prefer not to say</option>
                  </select>
                </label>
                <label>Phone Number <input type="number" name="PhoneNumber" value="<?php echo isset($_GET['PhoneNumber']) ? htmlspecialchars($_GET['PhoneNumber']) : ''; ?>"></label>
                <label>Address <input type="text" name="Address" value="<?php echo isset($_GET['Address']) ? htmlspecialchars($_GET['Address']) : ''; ?>"></label>
                <label>Email <input type="email" name="Email" value="<?php echo isset($_GET['Email']) ? htmlspecialchars($_GET['Email']) : ''; ?>"></label>
                <label>Nationality <input type="text" name="Nationality" value="<?php echo isset($_GET['Nationality']) ? htmlspecialchars($_GET['Nationality']) : ''; ?>"></label>
                <label>Birthdate <input type="date" name="Birthdate" value="<?php echo isset($_GET['Birthdate']) ? htmlspecialchars($_GET['Birthdate']) : ''; ?>"></label>
                <div class="filter-actions">
                  <button type="submit" id="applyFilterBtn" class="filter-btn">Apply</button>
                  <button type="button" id="clearFilterBtn" class="filter-btn">Clear</button>
                </div>
              </form>
            </div>
          </div>
          <button class="create-btn" id="createBtn">Add Student</button>
        </div>
      </div>
      <table class="reservation-table">
        <thead>
          <tr>
            <th>Student ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Phone Number</th>
            <th>Email</th>
            <th>Actions</th>
            <th>Download</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($resResult && $resResult->num_rows > 0): ?>
          <?php while($row = $resResult->fetch_assoc()): ?>
          <tr data-id="<?php echo $row['StudentID']; ?>">
            <td><b><?php echo $row['StudentID']; ?></b></td>
            <td><b><?php echo htmlspecialchars($row['FirstName']); ?></b></td>
            <td><b><?php echo htmlspecialchars($row['LastName']); ?></b></td>
            <td><?php echo $row['PhoneNumber']; ?></td>
            <td><?php echo $row['Email']; ?></td>
            <td>
              <div class="action-group">
                <button type="button" class="action-btn edit-btn"
                  data-id="<?php echo $row['StudentID']; ?>"
                  data-firstname="<?php echo htmlspecialchars($row['FirstName']); ?>"
                  data-lastname="<?php echo htmlspecialchars($row['LastName']); ?>"
                  data-gender="<?php echo htmlspecialchars($row['Gender']); ?>"
                  data-phonenumber="<?php echo htmlspecialchars($row['PhoneNumber']); ?>"
                  data-address="<?php echo htmlspecialchars($row['Address']); ?>"
                  data-email="<?php echo htmlspecialchars($row['Email']); ?>"
                  data-nationality="<?php echo htmlspecialchars($row['Nationality']); ?>"
                  data-birthdate="<?php echo htmlspecialchars($row['Birthdate']); ?>"
                ><i class="fas fa-edit"></i></button>
                <button type="button" class="action-btn view-btn"
                  data-id="<?php echo $row['StudentID']; ?>"
                  data-firstname="<?php echo htmlspecialchars($row['FirstName']); ?>"
                  data-lastname="<?php echo htmlspecialchars($row['LastName']); ?>"
                  data-gender="<?php echo htmlspecialchars($row['Gender']); ?>"
                  data-phonenumber="<?php echo htmlspecialchars($row['PhoneNumber']); ?>"
                  data-address="<?php echo htmlspecialchars($row['Address']); ?>"
                  data-email="<?php echo htmlspecialchars($row['Email']); ?>"
                  data-nationality="<?php echo htmlspecialchars($row['Nationality']); ?>"
                  data-birthdate="<?php echo htmlspecialchars($row['Birthdate']); ?>"
                ><i class="fas fa-eye"></i></button>
                <button type="button" class="action-btn delete-btn"
                  data-id="<?php echo $row['StudentID']; ?>"
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
          <tr><td colspan="7">No students found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <!-- Edit Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeEditModal">&times;</span>
      <h2>Edit Student</h2>
      <form id="editForm">
        <input type="hidden" name="StudentID" id="editStudentID">
        <p><label>Student ID:</label><br><input type="text" name="FirstName" id="editStudentID" required></p>
        <p><label>First Name:</label><br><input type="text" name="FirstName" id="editFirstName" required></p>
        <p><label>Last Name:</label><br><input type="text" name="LastName" id="editLastName" required></p>
        <p><label>Gender:</label><br>
          <select name="Gender" id="editGender" required>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Prefer not to say">Prefer not to say</option>
          </select>
        </p>
        <p><label>Phone Number:</label><br><input type="text" name="PhoneNumber" id="editPhoneNumber" required></p>
        <p><label>Address:</label><br><input type="text" name="Address" id="editAddress" required></p>
        <p><label>Email:</label><br><input type="email" name="Email" id="editEmail" required></p>
        <p><label>Nationality:</label><br><input type="text" name="Nationality" id="editNationality" required></p>
        <p><label>Birthdate:</label><br><input type="date" name="Birthdate" id="editBirthdate" required></p>
        <button type="submit" style="margin-top:1rem;">Save</button>
      </form>
    </div>
  </div>
  <!-- View Modal -->
  <div id="viewModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeViewModal">&times;</span>
      <h2>View Student Info</h2>
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

  <!-- Create Student Modal -->
  <div id="createModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeCreateModal">&times;</span>
      <h2>Add Student</h2>
      <form id="createForm">
        <input type="hidden" name="createStudent" value="1">
        
        <p><label>Student ID: </label><br><input type="text" name="StudentID" required></p>
        <p><label>First Name:</label><br><input type="text" name="FirstName" required></p>
        <p><label>Last Name:</label><br><input type="text" name="LastName" required></p>
        <p><label>Gender:</label><br>
          <select name="Gender" required>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Prefer not to say">Prefer not to say</option>
          </select>
        </p>
        <p><label>Phone Number:</label><br><input type="text" name="PhoneNumber" required></p>
        <p><label>Address:</label><br><input type="text" name="Address" required></p>
        <p><label>Email:</label><br><input type="email" name="Email" required></p>
        <p><label>Nationality:</label><br><input type="text" name="Nationality" required></p>
        <p><label>Birthdate:</label><br><input type="date" name="Birthdate" required></p>
        <button type="submit">Create</button>
      </form>
    </div>
  </div>
  <!-- Delete Modal -->
  <div id="deleteModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeDeleteModal">&times;</span>
      <h2>Delete Student</h2>
      <p>Are you sure you want to delete this student?</p>
      <div style="margin-top:1.5rem;">
        <button class="confirm-delete">Delete</button>
        <button class="cancel-delete">Cancel</button>
      </div>
    </div>
  </div>
  <script>
  // --- Modal Logic ---
  const editModal = document.getElementById('editModal');
  const closeEditModal = document.getElementById('closeEditModal');
  const viewModal = document.getElementById('viewModal');
  const closeViewModal = document.getElementById('closeViewModal');
  const createModal = document.getElementById('createModal');
  const createBtn = document.getElementById('createBtn');
  const closeCreateModal = document.getElementById('closeCreateModal');
  const deleteModal = document.getElementById('deleteModal');
  const closeDeleteModal = document.getElementById('closeDeleteModal');
  let deleteStudentId = null;

  // Edit Modal
  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.onclick = function() {
      editModal.style.display = 'block';
      document.getElementById('editStudentID').value = this.dataset.id;
      document.getElementById('editFirstName').value = this.dataset.firstname;
      document.getElementById('editLastName').value = this.dataset.lastname;
      document.getElementById('editGender').value = this.dataset.gender;
      document.getElementById('editPhoneNumber').value = this.dataset.phonenumber;
      document.getElementById('editAddress').value = this.dataset.address;
      document.getElementById('editEmail').value = this.dataset.email;
      document.getElementById('editNationality').value = this.dataset.nationality;
      document.getElementById('editBirthdate').value = this.dataset.birthdate;
    }
  });
  closeEditModal.onclick = function() { editModal.style.display = 'none'; }

  // Save Edit
  document.getElementById('editForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('student.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        editModal.style.display = 'none';
        location.reload();
      } else {
        alert('Update failed.');
      }
    });
  }

  // View Modal
  document.querySelectorAll('.view-btn').forEach(btn => {
    btn.onclick = function() {
      viewModal.style.display = 'block';
      document.getElementById('viewDetails').innerHTML = `
        <p><label>Student ID:</label> <span>${this.dataset.id}</span></p>
        <p><label>First Name:</label> <span>${this.dataset.firstname}</span></p>
        <p><label>Last Name:</label> <span>${this.dataset.lastname}</span></p>
        <p><label>Gender:</label> <span>${this.dataset.gender}</span></p>
        <p><label>Phone Number:</label> <span>${this.dataset.phonenumber}</span></p>
        <p><label>Address:</label> <span>${this.dataset.address}</span></p>
        <p><label>Email:</label> <span>${this.dataset.email}</span></p>
        <p><label>Nationality:</label> <span>${this.dataset.nationality}</span></p>
        <p><label>Birthdate:</label> <span>${this.dataset.birthdate}</span></p>
      `;
    }
  });
  closeViewModal.onclick = function() { viewModal.style.display = 'none'; }

  // Create Modal
  createBtn.onclick = function() { createModal.style.display = 'block'; }
  closeCreateModal.onclick = function() { createModal.style.display = 'none'; }

  // AJAX Create Student
  document.getElementById('createForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('student.php', {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        createModal.style.display = 'none';
        location.reload();
      } else {
        alert('Create failed.');
      }
    });
  };

  // Delete Modal
  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.onclick = function() {
      deleteStudentId = this.dataset.id;
      deleteModal.style.display = 'block';
    }
  });
  closeDeleteModal.onclick = function() { deleteModal.style.display = 'none'; }
  document.querySelector('#deleteModal .cancel-delete').onclick = function() {
    deleteModal.style.display = 'none';
    deleteStudentId = null;
  }
  document.querySelector('#deleteModal .confirm-delete').onclick = function() {
    if (!deleteStudentId) return;
    const formData = new FormData();
    formData.append('deleteStudent', 1);
    formData.append('StudentID', deleteStudentId);
    fetch('student.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        deleteModal.style.display = 'none';
        location.reload();
      } else {
        alert('Delete failed.');
      }
    });
  }

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

  // --- FILTER LOGIC ---
  const filterBtn = document.getElementById('filterBtn');
  const filterDropdown = document.getElementById('filterDropdown');
  const clearFilterBtn = document.getElementById('clearFilterBtn');
  filterBtn.onclick = function() {
    filterDropdown.classList.toggle('active');
  }
  document.addEventListener('click', function(e) {
    if (!filterDropdown.contains(e.target) && e.target !== filterBtn) {
      filterDropdown.classList.remove('active');
    }
  });
  clearFilterBtn.onclick = function() {
    window.location = 'student.php';
  }
  </script>
</body>
</html>