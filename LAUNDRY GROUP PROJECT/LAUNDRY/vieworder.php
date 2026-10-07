<?php
session_start();

if (!isset($_SESSION['CUSTID'])) {
    header("Location: login_user.html");
    exit;
}

$custname = htmlspecialchars($_SESSION['CUST_NAME']);
$email    = htmlspecialchars($_SESSION['CUST_EMAIL']);

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

$CUSTID = $_SESSION['CUSTID'];

// Check if we want to show only pending orders
$showPendingOnly = isset($_GET['pending']) && $_GET['pending'] == '1';

$sql = "
    SELECT
        ORDERID,
        TO_CHAR(ORDER_DATE, 'YYYY-MM-DD') AS ORDER_DATE,
        STATUS,
        TOTAL_PRICE,
        TO_CHAR(PICKUP_DATE, 'YYYY-MM-DD') AS PICKUP_DATE,
        TO_CHAR(DELIVERY_DATE, 'YYYY-MM-DD') AS DELIVERY_DATE
    FROM
        LAUNDRY_ORDER
    WHERE
        CUSTID = :CUSTID
";

if ($showPendingOnly) {
    $sql .= " AND STATUS = 'PENDING'";
}

$sql .= " ORDER BY ORDER_DATE DESC";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ':CUSTID', $CUSTID);
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
    <title>My Orders - Kerei Laundry</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body {
            margin: 0;
            background: linear-gradient(135deg, #c471ed, #f64f59);
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: start;
            padding: 40px;
        }
        .container {
            background: #fff;
            max-width: 1200px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            padding: 40px;
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95);}
            to { opacity: 1; transform: scale(1);}
        }
        h2 {
            text-align: center;
            color: #c471ed;
            margin-bottom: 30px;
        }
        p {
            text-align: center;
            color: #555;
            font-size: 16px;
        }
        .btn-group {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 20px;
            background: linear-gradient(to right, #c471ed, #f64f59);
            color: #fff;
            text-decoration: none;
            border-radius: 50px;
            font-size: 14px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .btn.disabled {
            background: linear-gradient(to right, #d3d3d3, #a9a9a9);
            color: #555;
            cursor: not-allowed;
            pointer-events: none;
        }
        .btn.filter {
            background: linear-gradient(to right, #b6fbff, #83a4d4);
            color: #333;
        }
        .btn.pay {
            background: linear-gradient(to right, #ff9a9e, #fad0c4);
            color: #333;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        th {
            background: #c471ed;
            color: #fff;
            padding: 12px;
            text-align: center;
            font-weight: bold;
        }
        td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .status-label {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            color: #fff;
            font-weight: bold;
            font-size: 13px;
            min-width: 100px;
            text-align: center;
        }
        .status-PENDING {
            background: linear-gradient(to right, #ffecd2, #fcb69f);
            color: #333;
        }
        .status-PAID {
            background: linear-gradient(to right, #a1c4fd, #c2e9fb);
            color: #333;
        }
        .status-COMPLETED {
            background: linear-gradient(to right, #d4fc79, #96e6a1);
            color: #333;
        }
        .status-FAILED {
            background: linear-gradient(to right, #ff9a9e, #fecfef);
            color: #333;
        }
        .actions {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>🧺 My Laundry Orders</h2>

    <p>Welcome, <strong><?= $custname ?></strong> (<?= $email ?>)</p>

    <div class="btn-group">
        <?php if ($showPendingOnly): ?>
            <a href="orders_homepage.php" class="btn filter">
                <i class="fas fa-list"></i> View All Orders
            </a>
        <?php else: ?>
            <a href="orders_homepage.php?pending=1" class="btn filter">
                <i class="fas fa-hourglass-half"></i> Place New Order
            </a>
        <?php endif; ?>
        <a href="user_dashboard.php" class="btn filter">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if (empty($orders)): ?>
        <p style="margin-top: 30px; font-weight: bold;">You have no orders<?= $showPendingOnly ? ' pending' : '' ?>.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Order Date</th>
                    <th>Status</th>
                    <th>Total Price (RM)</th>
                    <th>Pickup Date</th>
                    <th>Delivery Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['ORDERID']) ?></td>
                    <td><?= htmlspecialchars($order['ORDER_DATE']) ?></td>
                    <td>
                        <span class="status-label status-<?= htmlspecialchars(strtoupper($order['STATUS'])) ?>">
                            <?= htmlspecialchars($order['STATUS']) ?>
                        </span>
                    </td>
                    <td><?= number_format($order['TOTAL_PRICE'], 2) ?></td>
                    <td><?= htmlspecialchars($order['PICKUP_DATE']) ?></td>
                    <td><?= htmlspecialchars($order['DELIVERY_DATE']) ?></td>
                    <td class="actions">
                        <?php if ($order['STATUS'] === 'PENDING'): ?>
                            <a href="payment.php?orderid=<?= urlencode($order['ORDERID']) ?>" class="btn pay">
                                <i class="fas fa-credit-card"></i> Pay Now
                            </a>
                        <?php else: ?>
                            <span class="btn disabled">Paid</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
