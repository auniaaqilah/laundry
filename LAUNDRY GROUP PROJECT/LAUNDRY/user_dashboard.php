<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['CUSTID'])) {
    header("Location: login_user.html");
    exit;
}

// Get customer info from session
$custname = htmlspecialchars($_SESSION['CUST_NAME']);
$email = htmlspecialchars($_SESSION['CUST_EMAIL']);
$CUSTID = $_SESSION['CUSTID'];

// Oracle DB connection
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

// Get total orders
$sqlTotal = "SELECT COUNT(*) AS TOTAL FROM LAUNDRY_ORDER WHERE CUSTID = :CUSTID";
$stidTotal = oci_parse($conn, $sqlTotal);
oci_bind_by_name($stidTotal, ":CUSTID", $CUSTID);
oci_execute($stidTotal);
$rowTotal = oci_fetch_assoc($stidTotal);
$totalOrders = $rowTotal ? intval($rowTotal['TOTAL']) : 0;

// Get pending orders
$sqlPending = "SELECT COUNT(*) AS PENDING FROM LAUNDRY_ORDER WHERE CUSTID = :CUSTID AND STATUS = 'PENDING'";
$stidPending = oci_parse($conn, $sqlPending);
oci_bind_by_name($stidPending, ":CUSTID", $CUSTID);
oci_execute($stidPending);
$rowPending = oci_fetch_assoc($stidPending);
$pendingOrders = $rowPending ? intval($rowPending['PENDING']) : 0;

// Get total spending for loyalty points (completed orders only)
$sqlPoints = "SELECT NVL(SUM(TOTAL_PRICE), 0) AS TOTAL_SPENT FROM LAUNDRY_ORDER WHERE CUSTID = :CUSTID AND STATUS = 'COMPLETED'";
$stidPoints = oci_parse($conn, $sqlPoints);
oci_bind_by_name($stidPoints, ":CUSTID", $CUSTID);
oci_execute($stidPoints);
$rowPoints = oci_fetch_assoc($stidPoints);
$totalSpent = $rowPoints ? floatval($rowPoints['TOTAL_SPENT']) : 0;
$loyaltyPoints = floor($totalSpent / 10);

// Get last order date
$sqlLastDate = "SELECT MAX(ORDER_DATE) AS LAST_DATE FROM LAUNDRY_ORDER WHERE CUSTID = :CUSTID";
$stidLastDate = oci_parse($conn, $sqlLastDate);
oci_bind_by_name($stidLastDate, ":CUSTID", $CUSTID);
oci_execute($stidLastDate);
$rowLast = oci_fetch_assoc($stidLastDate);
$lastOrderDate = $rowLast['LAST_DATE'] ? date('Y-m-d', strtotime($rowLast['LAST_DATE'])) : '-';

// Get 3 most recent orders
$sqlRecent = "
    SELECT ORDERID, TO_CHAR(ORDER_DATE, 'YYYY-MM-DD') AS ORDER_DATE, STATUS
    FROM LAUNDRY_ORDER
    WHERE CUSTID = :CUSTID
    ORDER BY ORDER_DATE DESC FETCH FIRST 3 ROWS ONLY
";
$stidRecent = oci_parse($conn, $sqlRecent);
oci_bind_by_name($stidRecent, ":CUSTID", $CUSTID);
oci_execute($stidRecent);
$recentOrders = [];
while ($row = oci_fetch_assoc($stidRecent)) {
    $recentOrders[] = $row;
}

oci_free_statement($stidTotal);
oci_free_statement($stidPending);
oci_free_statement($stidPoints);
oci_free_statement($stidLastDate);
oci_free_statement($stidRecent);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>User Dashboard - Kerei Laundry</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f1f1f1;
      margin: 0;
      padding: 0;
    }
    .container {
      width: 90%;
      max-width: 800px;
      margin: 30px auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.2);
    }
    .banner {
      background: linear-gradient(to right, #007bff, #4caf50);
      color: #fff;
      padding: 25px;
      border-radius: 10px;
      text-align: center;
      margin-bottom: 30px;
    }
    .profile-card {
      background: #f9f9f9;
      border: 1px solid #ddd;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      margin-bottom: 30px;
    }
    .profile-card i {
      font-size: 60px;
      color: #007bff;
      margin-bottom: 10px;
    }
    .profile-card h3 {
      margin: 10px 0 5px 0;
      color: #007bff;
    }
    .profile-card p {
      margin: 0;
      color: #555;
    }
    .stats {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-bottom: 30px;
      justify-content: center;
    }
    .stat {
      flex: 1 1 150px;
      background: #007bff;
      color: #fff;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
    }
    .stat h3 {
      margin: 0;
      font-size: 2em;
    }
    .stat p {
      margin: 5px 0 0;
    }
    .btn {
      display: block;
      width: 100%;
      margin: 10px 0;
      padding: 14px;
      background: linear-gradient(to right, #007bff, #4caf50);
      color: #fff;
      text-decoration: none;
      border-radius: 5px;
      font-size: 16px;
      font-weight: bold;
      transition: background 0.3s ease;
      text-align: center;
    }
    .btn i {
      margin-right: 8px;
    }
    .btn:hover {
      background: linear-gradient(to right, #0056b3, #3e8e41);
    }
    .btn.logout {
      background: #dc3545;
    }
    .btn.logout:hover {
      background: #b02a37;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    table th, table td {
      border: 1px solid #ddd;
      padding: 10px;
    }
    table th {
      background: #007bff;
      color: #fff;
      text-align: left;
    }
    table tr:nth-child(even) {
      background: #f9f9f9;
    }
    @media (max-width: 600px) {
      .stats {
        flex-direction: column;
      }
      .btn {
        font-size: 14px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="banner">
      <h1><i class="fas fa-tshirt"></i> Kerei Laundry Dashboard</h1>
      <p>Clean Clothes, Happy Life!</p>
    </div>

    <div class="profile-card">
      <i class="fas fa-user-circle"></i>
      <h3><?= $custname ?></h3>
      <p><?= $email ?></p>
    </div>

    <div class="stats">
      <div class="stat">
        <h3><?= $totalOrders ?></h3>
        <p>Total Orders</p>
      </div>
      <div class="stat">
        <h3><?= $pendingOrders ?></h3>
        <p>Pending Orders</p>
      </div>
      <div class="stat">
        <h3><?= $loyaltyPoints ?></h3>
        <p>Loyalty Points</p>
      </div>
      <div class="stat">
        <h3><?= $lastOrderDate ?></h3>
        <p>Last Order Date</p>
      </div>
    </div>

    <a class="btn" href="services_homepage.php"><i class="fas fa-tshirt"></i> View Laundry Services</a>
    <a class="btn" href="orders_homepage.php"><i class="fas fa-plus-circle"></i> Place New Order</a>
    <a class="btn" href="vieworder.php"><i class="fas fa-history"></i> My Laundry Orders</a>
    <a class="btn logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>

    <h3 style="margin-top: 40px;">Recent Orders</h3>
    <table>
      <tr>
        <th>Order ID</th>
        <th>Date</th>
        <th>Status</th>
      </tr>
      <?php if (empty($recentOrders)) : ?>
      <tr>
        <td colspan="3" style="text-align:center;">No recent orders found.</td>
      </tr>
      <?php else: ?>
      <?php foreach ($recentOrders as $order): ?>
      <tr>
        <td><?= htmlspecialchars($order['ORDERID']) ?></td>
        <td><?= htmlspecialchars($order['ORDER_DATE']) ?></td>
        <td><?= htmlspecialchars($order['STATUS']) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </table>
  </div>
</body>
</html>
