<?php
include 'connections.php';

// --- FILTER HANDLING ---
$where = [];
$params = [];

// If filter form is submitted (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (
  isset($_GET['AccountID']) || isset($_GET['FirstName']) || isset($_GET['LastName']) ||
  isset($_GET['Email']) || isset($_GET['AccountType'])
)) {
  if (!empty($_GET['AccountID'])) {
    $where[] = "accountID = ?";
    $params[] = $_GET['AccountID'];
  }
  if (!empty($_GET['FirstName'])) {
    $where[] = "FirstName = ?";
    $params[] = $_GET['FirstName'];
  }
  if (!empty($_GET['LastName'])) {
    $where[] = "LastName = ?";
    $params[] = $_GET['LastName'];
  }
  if (!empty($_GET['Email'])) {
    $where[] = "Email = ?";
    $params[] = $_GET['Email'];
  }
  if (!empty($_GET['AccountType'])) {
    $where[] = "AccountType = ?";
    $params[] = $_GET['AccountType'];
  }
}

// --- AJAX UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accountID']) && isset($_POST['FirstName']) && isset($_POST['LastName'])) {
  $accountid = intval($_POST['accountID']);
  $firstname = $conn->real_escape_string($_POST['FirstName']);
  $lastname = $conn->real_escape_string($_POST['LastName']);
  $email = $conn->real_escape_string($_POST['Email']);
  $password = $conn->real_escape_string($_POST['Password']);
  $phonenumber = $conn->real_escape_string($_POST['PhoneNumber']);
  $accounttype = $conn->real_escape_string($_POST['AccountType']);
  $status = $conn->real_escape_string($_POST['Status']);

  $sql = "UPDATE account SET 
    FirstName='$firstname',
    LastName='$lastname',
    Email='$email',
    Password='$password',
    PhoneNumber='$phonenumber',
    AccountType='$accounttype',
    Status='$status'
    WHERE accountID=$accountid";
  $success = $conn->query($sql);

  header('Content-Type: application/json');
  echo json_encode(['success' => $success]);
  exit;
}

// --- AJAX DELETE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteAccount']) && isset($_POST['AccountID'])) {
  $accountid = intval($_POST['AccountID']);
  $sql = "DELETE FROM account WHERE accountID=$accountid";
  $success = $conn->query($sql);
  header('Content-Type: application/json');
  echo json_encode(['success' => $success]);
  exit;
}

// --- CREATE ACCOUNT (AJAX/POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createAccount'])) {
  $firstname = $conn->real_escape_string($_POST['FirstName']);
  $lastname = $conn->real_escape_string($_POST['LastName']);
  $email = $conn->real_escape_string($_POST['Email']);
  $password = $conn->real_escape_string($_POST['Password']);
  $phonenumber = $conn->real_escape_string($_POST['PhoneNumber']);
  $accounttype = $conn->real_escape_string($_POST['AccountType']);
  $status = $conn->real_escape_string($_POST['Status']);
  $sql = "INSERT INTO account (FirstName, LastName, Email, Password, PhoneNumber, AccountType, Status) VALUES ('$firstname', '$lastname', '$email', '$password', '$phonenumber', '$accounttype', '$status')";
  $success = $conn->query($sql);
  if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
  } else {
    header('Location: account.php');
    exit;
  }
}

// --- FETCH ACCOUNTS (with filter) ---
if (count($where) > 0) {
  $sql = "SELECT * FROM account WHERE " . implode(' AND ', $where) . " ORDER BY accountID DESC";
  $stmt = $conn->prepare($sql);
  if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  $resResult = $stmt->get_result();
} else {
  $resQuery = "SELECT * FROM account ORDER BY accountID DESC";
  $resResult = $conn->query($resQuery);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Account</title>
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
            gap: 0.5rem;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            outline: none;
            border-radius: 0.5rem;
            padding: 0.5rem 0.9rem;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s, color 0.2s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.07);
            gap: 0.4rem;
        }
        .action-btn.edit-btn {
            background: var(--action-edit);
        }
        .action-btn.edit-btn:hover {
            background: var(--theme-green-dark);
        }
        .action-btn.view-btn {
            background: var(--action-view);
        }
        .action-btn.view-btn:hover {
            background: #00916e;
        }
        .action-btn.delete-btn {
            background: var(--action-delete);
        }
        .action-btn.delete-btn:hover {
            background: #c0392b;
        }
        .action-btn i {
            font-size: 1.1em;
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
    </style>
</head>
<body>
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
  <div class="main-content">
    <div class="reservation-section">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <h1 style="margin-bottom: 0; border-bottom: 4px solid rgb(255, 255, 255); display: inline-block; padding-bottom: 0.2rem;">Account</h1>
        <div class="search-filter-bar">
          <div class="search-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" class="search-input" placeholder="Search">
          </div>
          <div style="position: relative;">
            <button class="filter-btn" id="filterBtn">Filter</button>
            <div class="filter-dropdown" id="filterDropdown">
              <form id="filterForm" method="GET">
                <label>Account ID <input type="text" name="AccountID" value="<?php echo isset($_GET['AccountID']) ? htmlspecialchars($_GET['AccountID']) : ''; ?>"></label>
                <label>First Name <input type="text" name="FirstName" value="<?php echo isset($_GET['FirstName']) ? htmlspecialchars($_GET['FirstName']) : ''; ?>"></label>
                <label>Last Name <input type="text" name="LastName" value="<?php echo isset($_GET['LastName']) ? htmlspecialchars($_GET['LastName']) : ''; ?>"></label>
                <label>Email <input type="text" name="Email" value="<?php echo isset($_GET['Email']) ? htmlspecialchars($_GET['Email']) : ''; ?>"></label>
                <label>Account Type
                  <select name="AccountType">
                    <option value="">Any</option>
                    <option value="Admin" <?php if(isset($_GET['AccountType']) && $_GET['AccountType']=='Admin') echo 'selected'; ?>>Admin</option>
                    <option value="Staff" <?php if(isset($_GET['AccountType']) && $_GET['AccountType']=='Staff') echo 'selected'; ?>>Staff</option>
                  </select>
                </label>
                <div class="filter-actions">
                  <button type="submit" id="applyFilterBtn" class="filter-btn">Apply</button>
                  <button type="button" id="clearFilterBtn" class="filter-btn">Clear</button>
                </div>
              </form>
            </div>
          </div>
          <button class="create-btn" id="createBtn">Create Account</button>
        </div>
      </div>
      <table class="reservation-table">
        <thead>
          <tr>
            <th>Account ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Email</th>
            <th>Account Type</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($resResult && $resResult->num_rows > 0): ?>
          <?php while($row = $resResult->fetch_assoc()): ?>
          <tr data-id="<?php echo $row['accountID']; ?>">
            <td><b><?php echo $row['accountID']; ?></b></td>
            <td><b><?php echo htmlspecialchars($row['FirstName']); ?></b></td>
            <td><b><?php echo htmlspecialchars($row['LastName']); ?></b></td>
            <td><b><?php echo htmlspecialchars($row['Email']); ?></b></td>
            <td><b><?php echo htmlspecialchars($row['AccountType']); ?></b></td>
            <td>
              <div class="action-group">
                <button type="button" class="action-btn edit-btn"
                  data-id="<?php echo $row['accountID']; ?>"
                  data-firstname="<?php echo htmlspecialchars($row['FirstName']); ?>"
                  data-lastname="<?php echo htmlspecialchars($row['LastName']); ?>"
                  data-email="<?php echo htmlspecialchars($row['Email']); ?>"
                  data-password="<?php echo htmlspecialchars($row['Password']); ?>"
                  data-phonenumber="<?php echo htmlspecialchars($row['PhoneNumber']); ?>"
                  data-accounttype="<?php echo htmlspecialchars($row['AccountType']); ?>"
                  data-status="<?php echo htmlspecialchars($row['Status']); ?>"
                ><i class="fas fa-edit"></i></button>
                <button type="button" class="action-btn view-btn"
                  data-id="<?php echo $row['accountID']; ?>"
                  data-firstname="<?php echo htmlspecialchars($row['FirstName']); ?>"
                  data-lastname="<?php echo htmlspecialchars($row['LastName']); ?>"
                  data-email="<?php echo htmlspecialchars($row['Email']); ?>"
                  data-password="<?php echo htmlspecialchars($row['Password']); ?>"
                  data-phonenumber="<?php echo htmlspecialchars($row['PhoneNumber']); ?>"
                  data-accounttype="<?php echo htmlspecialchars($row['AccountType']); ?>"
                  data-status="<?php echo htmlspecialchars($row['Status']); ?>"
                ><i class="fas fa-eye"></i></button>
                <button type="button" class="action-btn delete-btn"
                  data-id="<?php echo $row['accountID']; ?>"
                ><i class="fas fa-trash"></i></button>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="6">No accounts found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <!-- Edit Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeEditModal">&times;</span>
      <h2>Edit Account</h2>
      <form id="editForm">
        <input type="hidden" name="accountID" id="editAccountID">
        <p><label>First Name:</label><br><input type="text" name="FirstName" id="editFirstName" required></p>
        <p><label>Last Name:</label><br><input type="text" name="LastName" id="editLastName" required></p>
        <p><label>Email:</label><br><input type="email" name="Email" id="editEmail" required></p>
        <p><label>Password:</label><br><input type="password" name="Password" id="editPassword" required></p>
        <p><label>Phone Number:</label><br><input type="text" name="PhoneNumber" id="editPhoneNumber" required></p>
        <p><label>Account Type:</label><br>
          <select name="AccountType" id="editAccountType" required>
            <option value="Admin">Admin</option>
            <option value="Staff">Staff</option>
          </select>
        </p>
        <p><label>Status:</label><br>
          <select name="Status" id="editStatus" required>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </p>
        <button type="submit" style="margin-top:1rem;">Save</button>
      </form>
    </div>
  </div>
  <!-- View Modal -->
  <div id="viewModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeViewModal">&times;</span>
      <h2>View Account</h2>
      <div id="viewDetails"></div>
    </div>
  </div>
  <!-- Create Account Modal -->
  <div id="createModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeCreateModal">&times;</span>
      <h2>Create Account</h2>
      <form id="createForm">
        <input type="hidden" name="createAccount" value="1">
        <p><label>First Name:</label><br><input type="text" name="FirstName" required></p>
        <p><label>Last Name:</label><br><input type="text" name="LastName" required></p>
        <p><label>Email:</label><br><input type="email" name="Email" required></p>
        <p><label>Password:</label><br><input type="password" name="Password" required></p>
        <p><label>Phone Number:</label><br><input type="text" name="PhoneNumber" required></p>
        <p><label>Account Type:</label><br>
          <select name="AccountType" required>
            <option value="Admin">Admin</option>
            <option value="Staff">Staff</option>
          </select>
        </p>
        <p><label>Status:</label><br>
          <select name="Status" required>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </p>
        <button type="submit">Create</button>
      </form>
    </div>
  </div>
  <!-- Delete Modal -->
  <div id="deleteModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeDeleteModal">&times;</span>
      <h2>Delete Account</h2>
      <p>Are you sure you want to delete this account?</p>
      <div style="margin-top:1.5rem;">
        <button class="confirm-delete">Delete</button>
        <button class="cancel-delete">Cancel</button>
      </div>
    </div>
  </div>
  <script>
    // Edit Modal
    const editModal = document.getElementById('editModal');
    const closeEditModal = document.getElementById('closeEditModal');
    document.querySelectorAll('.edit-btn').forEach(btn => {
      btn.onclick = function() {
        editModal.style.display = 'block';
        document.getElementById('editAccountID').value = this.dataset.id;
        document.getElementById('editFirstName').value = this.dataset.firstname;
        document.getElementById('editLastName').value = this.dataset.lastname;
        document.getElementById('editEmail').value = this.dataset.email;
        document.getElementById('editPassword').value = this.dataset.password;
        document.getElementById('editPhoneNumber').value = this.dataset.phonenumber;
        document.getElementById('editAccountType').value = this.dataset.accounttype;
        document.getElementById('editStatus').value = this.dataset.status;
      }
    });
    closeEditModal.onclick = function() { editModal.style.display = 'none'; }
    // Save Edit
    const editForm = document.getElementById('editForm');
    editForm.onsubmit = function(e) {
      e.preventDefault();
      const formData = new FormData(editForm);
      fetch('account.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert('Update failed.');
        }
      });
    }
    // View Modal
    const viewModal = document.getElementById('viewModal');
    const closeViewModal = document.getElementById('closeViewModal');
    document.querySelectorAll('.view-btn').forEach(btn => {
      btn.onclick = function() {
        viewModal.style.display = 'block';
        document.getElementById('viewDetails').innerHTML = `
          <p><label>Account ID:</label> <span>${this.dataset.id}</span></p>
          <p><label>First Name:</label> <span>${this.dataset.firstname}</span></p>
          <p><label>Last Name:</label> <span>${this.dataset.lastname}</span></p>
          <p><label>Email:</label> <span>${this.dataset.email}</span></p>
          <p><label>Password:</label> <span>${this.dataset.password}</span></p>
          <p><label>Phone Number:</label> <span>${this.dataset.phonenumber}</span></p>
          <p><label>Account Type:</label> <span>${this.dataset.accounttype}</span></p>
          <p><label>Status:</label> <span>${this.dataset.status}</span></p>
        `;
      }
    });
    closeViewModal.onclick = function() { viewModal.style.display = 'none'; }
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
    // Create Account Modal
    const createModal = document.getElementById('createModal');
    const createBtn = document.getElementById('createBtn');
    const closeCreateModal = document.getElementById('closeCreateModal');
    createBtn.onclick = function() { createModal.style.display = 'block'; }
    closeCreateModal.onclick = function() { createModal.style.display = 'none'; }

    // --- AJAX CREATE ACCOUNT ---
    document.getElementById('createForm').onsubmit = function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      fetch('account.php', {
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
    const deleteModal = document.getElementById('deleteModal');
    const closeDeleteModal = document.getElementById('closeDeleteModal');
    let deleteBookingId = null;
    document.querySelectorAll('.delete-btn').forEach(btn => {
      btn.onclick = function() {
        deleteBookingId = this.dataset.id;
        deleteModal.style.display = 'block';
      }
    });
    closeDeleteModal.onclick = function() { deleteModal.style.display = 'none'; }
    document.querySelector('#deleteModal .cancel-delete').onclick = function() {
      deleteModal.style.display = 'none';
      deleteBookingId = null;
    }
    document.querySelector('#deleteModal .confirm-delete').onclick = function() {
      if (!deleteBookingId) return;
      const formData = new FormData();
      formData.append('deleteAccount', 1);
      formData.append('AccountID', deleteBookingId);
      fetch('account.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert('Delete failed.');
        }
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
      window.location = 'account.php';
    }
  </script>
</body>
</html>
