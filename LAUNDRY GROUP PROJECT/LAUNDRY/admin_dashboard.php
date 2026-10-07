<?php
session_start();

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Oracle connection details
$dbuser  = 'LAUNDRY';
$dbpass  = 'GROUP5';
$host    = 'localhost';
$port    = '1521';
$service = 'FREEPDB1';
$dsn     = "//{$host}:{$port}/{$service}";

// Connect to Oracle
$conn = oci_connect($dbuser, $dbpass, $dsn);
if (!$conn) {
    $e = oci_error();
    die("❌ Connection failed: " . htmlentities($e['message']));
}

// --- Total Orders (all customers) ---
$sqlTotal = "SELECT COUNT(*) AS TOTAL FROM LAUNDRY_ORDER";
$stidTotal = oci_parse($conn, $sqlTotal);
oci_execute($stidTotal);
$rowTotal = oci_fetch_assoc($stidTotal);
$totalOrders = $rowTotal ? intval($rowTotal['TOTAL']) : 0;
oci_free_statement($stidTotal);

// --- Pending Orders (all customers) ---
$sqlPending = "SELECT COUNT(*) AS PENDING FROM LAUNDRY_ORDER WHERE STATUS = 'PENDING'";
$stidPending = oci_parse($conn, $sqlPending);
oci_execute($stidPending);
$rowPending = oci_fetch_assoc($stidPending);
$pendingOrders = $rowPending ? intval($rowPending['PENDING']) : 0;
oci_free_statement($stidPending);

// --- New Orders Today ---
$sqlNewOrders = "
    SELECT COUNT(*) AS TOTAL 
    FROM LAUNDRY_ORDER 
    WHERE TRUNC(ORDER_DATE) = TRUNC(SYSDATE)
";
$stidNewOrders = oci_parse($conn, $sqlNewOrders);
oci_execute($stidNewOrders);
$rowNewOrders = oci_fetch_assoc($stidNewOrders);
$newOrdersToday = $rowNewOrders ? intval($rowNewOrders['TOTAL']) : 0;
oci_free_statement($stidNewOrders);

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard - Kerei Laundry</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: #fff0f5;
      color: #333;
    }

    .wrapper {
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      background: linear-gradient(160deg, #f8c7d8, #f3a6c1);
      color: #fff;
      width: 240px;
      padding: 30px 20px;
      box-sizing: border-box;
    }

    .sidebar h3 {
      margin-bottom: 30px;
      font-size: 20px;
    }

    .sidebar ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .sidebar ul li {
      margin-bottom: 15px;
    }

    .sidebar ul li a {
      color: #fff;
      text-decoration: none;
      display: block;
      padding: 10px 12px;
      border-radius: 6px;
      transition: background 0.3s;
    }

    .sidebar ul li a:hover {
      background: #f78da7;
    }

    .content {
      flex-grow: 1;
      padding: 40px;
      animation: fadeIn 0.5s ease forwards;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .banner {
      background: linear-gradient(90deg, #f8c7d8, #f9a3b4);
      color: #fff;
      padding: 40px;
      border-radius: 10px;
      margin-bottom: 30px;
      text-align: center;
    }

    .banner h1 {
      margin: 0 0 10px;
      font-size: 28px;
    }

    .banner p {
      margin: 0;
      font-size: 16px;
    }

    .stats {
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
      margin-bottom: 20px;
    }

    .stat-card {
      background: #ffffff;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
      flex: 1 1 220px;
      text-align: center;
      transition: transform 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-5px);
    }

    .stat-card h3 {
      margin: 0;
      font-size: 2.5em;
      color: #e75480;
    }

    .stat-card p {
      margin-top: 10px;
      font-weight: 500;
      color: #666;
    }

    .logout {
      background: #dc3545 !important;
    }

    .logout:hover {
      background: #b52b27 !important;
    }

    @media (max-width: 768px) {
      .wrapper {
        flex-direction: column;
      }

      .sidebar {
        width: 100%;
      }

      .content {
        padding: 20px;
      }

      .stats {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
<div class="wrapper">
  <aside class="sidebar">
    <h3><i class="fas fa-cogs"></i> Admin Panel</h3>
    <ul>
      <li><a href="assign_task.php"><i class="fas fa-tasks"></i> Assign Task</a></li>
      <li><a href="view_assignments.php"><i class="fas fa-list"></i> View Assignments</a></li>
      <li><a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </aside>

  <main class="content">
    <div class="banner">
      <h1>Welcome, <?= htmlspecialchars($_SESSION['admin_name']) ?>! 👋</h1>
      <p>Clear Clothes, Clear Mind.</p>
    </div>

    <div class="stats">
      <div class="stat-card">
        <h3><?= htmlspecialchars($totalOrders) ?></h3>
        <p>Total Orders</p>
      </div>
      <div class="stat-card">
        <h3><?= htmlspecialchars($pendingOrders) ?></h3>
        <p>Pending Orders</p>
      </div>
      <div class="stat-card">
        <h3><?= htmlspecialchars($newOrdersToday) ?></h3>
        <p>New Orders Today</p>
      </div>
    </div>

    <p>This is the admin dashboard. Use the sidebar to navigate through admin options.</p>
  </main>
</div>
</body>
</html>
