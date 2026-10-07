<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Fetch all assignments
$sql = "SELECT * FROM ORDER_ASSIGNMENT ORDER BY ASSIGNED_DATE DESC";
$stid = oci_parse($conn, $sql);
oci_execute($stid);

$assignments = [];
while ($row = oci_fetch_assoc($stid)) {
    $assignments[] = $row;
}
oci_free_statement($stid);

// Fetch all orders
$sql = "SELECT * FROM LAUNDRY_ORDER ORDER BY ORDER_DATE DESC";
$stid = oci_parse($conn, $sql);
oci_execute($stid);

$orders = [];
while ($row = oci_fetch_assoc($stid)) {
    $orders[] = $row;
}
oci_free_statement($stid);

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin - View Assignments & Orders</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #fdf1f5;
      margin: 0;
      padding: 0;
      color: #333;
    }
    .header {
      background: linear-gradient(45deg, #f6d2d8, #f9c5d1);
      color: #fff;
      text-align: center;
      padding: 40px 20px;
      border-radius: 0 0 10px 10px;
    }
    .breadcrumbs {
      max-width: 1200px;
      margin: 20px auto;
      padding: 0 20px;
      font-size: 14px;
    }
    .breadcrumbs a {
      color: #e75480;
      text-decoration: none;
      font-weight: bold;
    }
    .breadcrumbs a:hover {
      text-decoration: underline;
    }
    .container {
      max-width: 1200px;
      margin: auto;
      padding: 0 20px 50px;
    }
    .card {
      background: #fff;
      padding: 25px;
      margin-bottom: 40px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    .card h2 {
      color: #e75480;
      margin-top: 0;
      margin-bottom: 20px;
    }
    .table-container {
      width: 100%;
      overflow-x: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 900px;
    }
    th, td {
      padding: 12px 15px;
      border: 1px solid #f3cdd5;
      text-align: left;
      font-size: 14px;
    }
    th {
      background: linear-gradient(45deg, #f6d2d8, #f9c5d1);
      color: #fff;
    }
    tr:nth-child(even) {
      background-color: #fef3f5;
    }
    tr:hover {
      background-color: #fbe6ec;
    }
    .badge {
      padding: 5px 10px;
      border-radius: 5px;
      color: #fff;
      font-size: 12px;
      font-weight: bold;
      display: inline-block;
    }
    .badge-warning { background: #ffb347; }
    .badge-primary { background: #f78da7; }
    .badge-success { background: #8bc34a; }
    .badge-secondary { background: #b39ddb; }
    .back {
      display: inline-block;
      background: linear-gradient(45deg, #f6d2d8, #f9c5d1);
      color: #fff;
      text-decoration: none;
      padding: 12px 18px;
      border-radius: 6px;
      transition: all 0.3s;
      font-weight: bold;
    }
    .back:hover {
      background: linear-gradient(45deg, #f59cbb, #f78da7);
      transform: scale(1.05);
    }
    p {
      font-size: 14px;
      color: #555;
    }
  </style>
</head>
<body>
<div class="header">
  <h1><i class="fas fa-cogs"></i> Admin Dashboard - Kerei Laundry</h1>
</div>

<div class="breadcrumbs">
  <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a> &raquo; View Assignments & Orders
</div>

<div class="container">

  <div class="card">
    <h2><i class="fas fa-clipboard-list"></i> Order Assignments</h2>
    <?php if (empty($assignments)) : ?>
      <p>No assignments found.</p>
    <?php else : ?>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Assignment ID</th>
              <th>Order ID</th>
              <th>Employee ID</th>
              <th>Task</th>
              <th>Assigned Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($assignments as $a): ?>
              <tr>
                <td><?= htmlspecialchars($a['ASSIGNMENTID']) ?></td>
                <td><?= htmlspecialchars($a['ORDERID']) ?></td>
                <td><?= htmlspecialchars($a['EMPLOYEEID']) ?></td>
                <td><?= htmlspecialchars($a['TASK']) ?></td>
                <td><?= htmlspecialchars($a['ASSIGNED_DATE']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2><i class="fas fa-receipt"></i> Laundry Orders</h2>
    <?php if (empty($orders)) : ?>
      <p>No orders found.</p>
    <?php else : ?>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer ID</th>
              <th>Order Date</th>
              <th>Status</th>
              <th>Total Price (RM)</th>
              <th>Pickup Date</th>
              <th>Delivery Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $o): ?>
              <tr>
                <td><?= htmlspecialchars($o['ORDERID']) ?></td>
                <td><?= htmlspecialchars($o['CUSTID']) ?></td>
                <td><?= htmlspecialchars($o['ORDER_DATE']) ?></td>
                <td>
                  <?php
                  $status = htmlspecialchars($o['STATUS']);
                  $badgeClass = match ($status) {
                      'PENDING' => 'badge badge-warning',
                      'IN_PROGRESS' => 'badge badge-primary',
                      'COMPLETED' => 'badge badge-success',
                      default => 'badge badge-secondary'
                  };
                  ?>
                  <span class="<?= $badgeClass ?>"><?= $status ?></span>
                </td>
                <td><?= number_format($o['TOTAL_PRICE'], 2) ?></td>
                <td><?= htmlspecialchars($o['PICKUP_DATE']) ?></td>
                <td><?= htmlspecialchars($o['DELIVERY_DATE']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <a class="back" href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

</div>
</body>
</html>
