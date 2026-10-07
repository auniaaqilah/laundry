<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to Oracle
$dbuser  = 'LAUNDRY';
$dbpass  = 'GROUP5';
$host    = 'localhost';
$port    = '1521';
$service = 'FREEPDB1';
$dsn     = "//{$host}:{$port}/{$service}";

$conn = oci_connect($dbuser, $dbpass, $dsn);
if (!$conn) {
    $e = oci_error();
    die("❌ Connection failed: " . $e['message']);
}

// ✅ Get total orders today
$sqlOrders = "
    SELECT COUNT(ORDERID) AS TOTAL
    FROM LAUNDRY_ORDER
   
";
$stid = oci_parse($conn, $sqlOrders);
oci_execute($stid);
$row = oci_fetch_assoc($stid);
$totalOrdersToday = $row['TOTAL'] ?? 0;
oci_free_statement($stid);

// ✅ Get new users today
$sqlUsers = "
    SELECT COUNT(CUSTID) AS TOTAL
    FROM CUSTOMER
";
$stid = oci_parse($conn, $sqlUsers);
oci_execute($stid);
$row = oci_fetch_assoc($stid);
$newUsersToday = $row['TOTAL'] ?? 0;
oci_free_statement($stid);

oci_close($conn);

// Check login
$isLoggedIn = isset($_SESSION['username']);
$username = $isLoggedIn ? $_SESSION['username'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Kerei Laundry System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    body {
      background-color: #f2f8ff;
      font-family: 'Segoe UI', sans-serif;
    }
    header {
      background: linear-gradient(to right, #007acc, #4caf50);
      color: white;
      padding: 30px 0;
      text-align: center;
    }
    .logo {
      width: 120px;
      margin-bottom: 15px;
    }
    .stats-card {
      background: #fff;
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      text-align: center;
    }
    .stats-card h3 {
      font-size: 2.5em;
      color: #007acc;
      margin: 0;
    }
    .stats-card p {
      margin: 10px 0 0;
      color: #555;
      font-weight: 500;
    }
    .access-card {
      background: #fff;
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      text-align: center;
      transition: transform 0.3s;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .access-card:hover {
      transform: scale(1.05);
    }
    .btn-group-custom {
      display: flex;
      gap: 10px;
      justify-content: center;
      flex-wrap: wrap;
      margin-top: 20px;
    }
    .btn-group-custom .btn {
      flex: 1 1 40%;
      min-width: 140px;
    }
    footer {
      background: #007acc;
      color: white;
      padding: 20px 0;
      text-align: center;
      margin-top: 50px;
    }
  </style>
</head>
<body>

<header>
  <img src="logo.png" alt="Kerei Laundry Logo" class="logo" />
  <h1>Kerei Laundry Service</h1>
  <p>Clear Clothes, Clear Mind.</p>
</header>

<main class="container my-5">

  <?php if ($isLoggedIn): ?>
    <div class="alert alert-success text-center">
      Welcome back, <strong><?php echo htmlspecialchars($username); ?></strong>!
    </div>
  <?php endif; ?>

  <!-- Stats Section -->
  <div class="row mb-5">
    <div class="col-md-6 mb-4">
      <div class="stats-card">
        <h3><?php echo $totalOrdersToday; ?></h3>
        <p>Total Order</p>
      </div>
    </div>
    <div class="col-md-6 mb-4">
      <div class="stats-card">
        <h3><?php echo $newUsersToday; ?></h3>
        <p>Total User</p>
      </div>
    </div>
  </div>

  <!-- Access Section -->
  <div class="row">
    <div class="col-md-6 mb-4">
      <div class="access-card">
        <h2><i class="fas fa-user-shield"></i> Admin Access</h2>
        <a href="login_admin.php" class="btn btn-primary mt-3">
          <i class="fas fa-lock"></i> Admin Login
        </a>
      </div>
    </div>
    <div class="col-md-6 mb-4">
      <div class="access-card">
        <h2><i class="fas fa-user"></i> User Access</h2>
        <div class="btn-group-custom">
          <a href="login_user.php" class="btn btn-primary">
            <i class="fas fa-sign-in-alt"></i> User Login
          </a>
          <a href="signup_user.php" class="btn btn-success">
            <i class="fas fa-user-plus"></i> Sign Up
          </a>
        </div>
      </div>
    </div>
  </div>

</main>

<footer>
  &copy; 2025 Kerei Laundry Service. All rights reserved.
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
